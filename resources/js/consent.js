/**
 * Cookie consent + consent-gated analytics (LGPD / GDPR / CCPA-CPRA / POPIA).
 *
 * - The server renders <script type="application/json" id="finestro-consent-config">
 *   (components/layout/analytics.blade.php) with the tag ids, the policy
 *   version, the consent endpoint and whether the request carried Sec-GPC.
 * - The decision lives in the first-party cookie `finestro_consent`
 *   (JSON {v, a, m, g, t}), written only by POST /consentimento.
 * - No vendor script is requested before consent. Google tags get Consent
 *   Mode v2 defaults (all "denied") first, then a per-category update.
 * - The Meta Pixel loads only with marketing consent; GPC forces it off.
 */

const COOKIE_NAME = 'finestro_consent';
const LEGACY_KEYS = ['cookie_consent', 'cookie_consent_at'];

const loaded = { gtm: false, ga: false, pixel: false };

function readConfig() {
    const el = document.getElementById('finestro-consent-config');
    if (!el) return {};
    try {
        return JSON.parse(el.textContent || '{}') || {};
    } catch {
        return {};
    }
}

function readCookie(name) {
    const entry = document.cookie.split('; ').find((row) => row.startsWith(`${name}=`));
    if (!entry) return null;
    try {
        return JSON.parse(decodeURIComponent(entry.slice(name.length + 1).replace(/\+/g, ' ')));
    } catch {
        return null;
    }
}

/** Stored decision for the current policy version, or null (banner shown). */
function storedConsent(config) {
    const value = readCookie(COOKIE_NAME);
    if (!value || value.v !== config.version || typeof value.t !== 'number') return null;
    return { analytics: value.a === 1, marketing: value.m === 1 };
}

function hasTags(config) {
    return Boolean(config.gtmId || config.gaId || config.pixelId);
}

function gtag() {
    window.dataLayer.push(arguments);
}

/** Consent Mode v2 defaults: everything denied until the visitor decides. */
function initConsentMode() {
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || gtag;
    window.gtag('consent', 'default', {
        ad_storage: 'denied',
        analytics_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500,
    });
}

function injectScript(src) {
    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
}

/** Expire first-party cookies set by a tag when its consent is withdrawn. */
function expireCookies(pattern) {
    const host = window.location.hostname;
    const domains = ['', host, `.${host}`, `.${host.split('.').slice(-2).join('.')}`];
    document.cookie.split('; ').forEach((row) => {
        const name = row.split('=')[0];
        if (!pattern.test(name)) return;
        domains.forEach((domain) => {
            document.cookie = `${name}=; Max-Age=0; path=/${domain ? `; domain=${domain}` : ''}`;
        });
    });
}

function loadMetaPixel(pixelId) {
    /* eslint-disable */
    !function (f, b, e, v, n, t, s) {
        if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) };
        if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
        n.queue = []; t = b.createElement(e); t.async = !0;
        t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */
    window.fbq('init', pixelId);
    window.fbq('track', 'PageView');
}

/** Apply a decision to the tags of this page view. */
export function applyConsent(config, consent) {
    if (!hasTags(config)) return;

    window.gtag('consent', 'update', {
        analytics_storage: consent.analytics ? 'granted' : 'denied',
        ad_storage: consent.marketing ? 'granted' : 'denied',
        ad_user_data: consent.marketing ? 'granted' : 'denied',
        ad_personalization: consent.marketing ? 'granted' : 'denied',
    });

    if (config.gtmId && (consent.analytics || consent.marketing) && !loaded.gtm) {
        loaded.gtm = true;
        window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
        injectScript(`https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(config.gtmId)}`);
    }

    if (config.gaId && consent.analytics && !loaded.ga) {
        loaded.ga = true;
        injectScript(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(config.gaId)}`);
        window.gtag('js', new Date());
        window.gtag('config', config.gaId);
    }

    if (config.pixelId && consent.marketing && !loaded.pixel) {
        loaded.pixel = true;
        loadMetaPixel(config.pixelId);
    } else if (loaded.pixel && window.fbq) {
        window.fbq('consent', consent.marketing ? 'grant' : 'revoke');
    }

    if (!consent.analytics) expireCookies(/^_ga(_.*)?$/);
    if (!consent.marketing) expireCookies(/^_fb[pc]$/);
}

export function bootConsent(Alpine) {
    const config = readConfig();

    // The old binary banner stored its choice in localStorage; drop it.
    LEGACY_KEYS.forEach((key) => {
        try {
            localStorage.removeItem(key);
        } catch {
            /* storage disabled */
        }
    });

    if (hasTags(config)) {
        initConsentMode();
        const stored = storedConsent(config);
        if (stored) applyConsent(config, stored);
    }

    Alpine.data('cookieConsent', () => ({
        show: false,
        customizing: false,
        saving: false,
        analytics: false,
        marketing: false,
        gpc: false,

        init() {
            this.gpc = config.serverGpc === true || navigator.globalPrivacyControl === true;
            const stored = storedConsent(config);
            if (stored) {
                this.analytics = stored.analytics;
                this.marketing = stored.marketing && !this.gpc;
            } else {
                this.show = true;
            }

            window.addEventListener('open-cookie-preferences', () => {
                this.customizing = true;
                this.show = true;
            });
        },

        acceptAll() {
            this.analytics = true;
            this.marketing = !this.gpc;
            return this.save();
        },

        rejectAll() {
            this.analytics = false;
            this.marketing = false;
            return this.save();
        },

        async save() {
            if (this.saving) return;
            if (this.gpc) this.marketing = false;
            this.saving = true;

            const choice = { analytics: this.analytics, marketing: this.marketing };

            try {
                const response = await fetch(config.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ...choice, source: 'banner' }),
                });
                if (response.ok) {
                    const data = await response.json();
                    choice.analytics = data.analytics === true;
                    choice.marketing = data.marketing === true;
                }
            } catch {
                // Network error: the choice applies to this page only; the
                // banner is shown again on the next page.
            }

            this.analytics = choice.analytics;
            this.marketing = choice.marketing;
            this.saving = false;
            this.show = false;
            this.customizing = false;

            applyConsent(config, choice);
            window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: choice }));
        },
    }));
}
