/**
 * Form submission hardening for Finestro.
 *
 * Prevents the two classic problems with plain HTML forms:
 *   1. Double submission — a fast double-click, or an impatient user clicking
 *      "Salvar" twice, posting the same data twice (duplicate records).
 *   2. Accidental re-submission on reload — combined with the app's
 *      POST-Redirect-GET flow on the server, we make sure the submit state is
 *      cleared when the page is restored from the bfcache (back/forward).
 *
 * Strategy: on the first `submit` we mark the form as busy, disable its submit
 * button(s) and swap the label for a spinner. A second submit while busy is
 * cancelled. Forms are re-enabled if client-side validation fails, or when the
 * page is shown again from cache.
 */

const BUSY = 'data-submitting';

/** Disable a form's submit controls and remember their original markup. */
function lockForm(form) {
    form.setAttribute(BUSY, '1');

    form.querySelectorAll('button[type="submit"], input[type="submit"], [data-submit]').forEach((btn) => {
        btn.dataset.originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        // Preserve width so the button doesn't collapse when the label changes.
        const rect = btn.getBoundingClientRect();
        if (rect.width) btn.style.minWidth = `${Math.ceil(rect.width)}px`;
        if (btn.tagName === 'BUTTON') {
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
        }
    });
}

/** Re-enable a form's submit controls and restore their labels. */
function unlockForm(form) {
    form.removeAttribute(BUSY);

    form.querySelectorAll('button[type="submit"], input[type="submit"], [data-submit]').forEach((btn) => {
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        btn.style.minWidth = '';
        if (btn.dataset.originalHtml !== undefined) {
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
    });
}

/**
 * Branded confirmation for destructive/irreversible submits.
 *
 * Any form with a `data-confirm="message"` attribute asks the user to confirm
 * via SweetAlert2 (never the native confirm()) before it is allowed through.
 * The flag `data-confirmed` is set once the user accepts so the re-dispatched
 * submit passes straight to the normal busy/lock flow.
 */
function bindConfirm(form) {
    if (!form.hasAttribute('data-confirm')) return;

    form.addEventListener(
        'submit',
        (e) => {
            if (form.dataset.confirmed === '1') return; // already confirmed
            e.preventDefault();
            e.stopImmediatePropagation();

            const message = form.getAttribute('data-confirm') || 'Tem certeza?';
            const run = () => {
                form.dataset.confirmed = '1';
                form.requestSubmit?.() ?? form.submit();
            };

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Confirmar',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: 'Sim, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) run();
                });
            } else if (window.confirm(message)) {
                // Fallback only if SweetAlert2 failed to load.
                run();
            }
        },
        true, // capture: run before the double-submit guard below
    );
}

function bindForm(form) {
    bindConfirm(form);

    // Opt-out hook for forms that intentionally allow repeated submits.
    if (form.hasAttribute('data-allow-resubmit')) return;

    form.addEventListener('submit', (e) => {
        // Already in flight? Block the duplicate submission outright.
        if (form.hasAttribute(BUSY)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }

        // Pending confirmation? Let bindConfirm handle the gate first.
        if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== '1') {
            return;
        }

        // An inline `onsubmit="return confirm(...)"` runs BEFORE this listener.
        // If the user cancels, the event is already defaultPrevented — don't
        // lock a form that isn't actually going to submit.
        if (e.defaultPrevented) return;

        // Let native validation run first — don't lock an invalid form,
        // otherwise the user can never fix and resubmit it.
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        lockForm(form);
    });
}

/**
 * Generic debounce helper, also exposed on window for inline handlers.
 * @param {Function} fn
 * @param {number} wait milliseconds
 */
export function debounce(fn, wait = 300) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

/**
 * Search-as-you-type for GET filter forms.
 *
 * An input with `data-debounce-submit="<ms>"` submits its form once the user
 * stops typing (default 600 ms), so one request goes out per pause instead of
 * one per keystroke. It does nothing when:
 *   - the text did not change since the last submit;
 *   - the term has a single character (too noisy; clearing the field is fine);
 *   - the form is already submitting (e.g. the user pressed Enter);
 *   - an IME composition is in progress.
 * After the page reloads with the result, the caret is put back at the end of
 * the field so typing can continue.
 */
function bindAutoSearch(input) {
    const form = input.form;
    if (!form) return;

    const wait = Number(input.dataset.debounceSubmit) || 600;
    const refocusKey = `finestro:refocus:${window.location.pathname}`;
    let last = input.value.trim();

    if (window.sessionStorage.getItem(refocusKey) === input.name) {
        window.sessionStorage.removeItem(refocusKey);
        input.focus();
        const end = input.value.length;
        input.setSelectionRange?.(end, end);
    }

    const submitLater = debounce(() => {
        const value = input.value.trim();
        if (value === last || value.length === 1 || form.hasAttribute(BUSY)) return;

        last = value;
        window.sessionStorage.setItem(refocusKey, input.name);
        form.requestSubmit?.() ?? form.submit();
    }, wait);

    input.addEventListener('input', (event) => {
        if (!event.isComposing) submitLater();
    });
}

export function bootForms() {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form').forEach(bindForm);
        document.querySelectorAll('input[data-debounce-submit]').forEach(bindAutoSearch);
    });

    // Restore forms when the page is served from the bfcache (back/forward).
    // Without this, a locked "Salvar" button would stay disabled after the
    // user navigates back to a previously submitted form.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            document.querySelectorAll(`form[${BUSY}]`).forEach(unlockForm);
        }
    });
}
