<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\DataExportException;
use App\Interfaces\Repositories\CookieConsentRepositoryInterface;
use App\Interfaces\Repositories\DataExportRepositoryInterface;
use App\Interfaces\Services\ConsentServiceInterface;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Models\DataExport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Configurações > Privacidade: data subject rights (export, consent,
 * deletion) in one place.
 */
class PrivacySettingsController extends Controller
{
    public function __construct(
        private readonly DataExportServiceInterface $exportService,
        private readonly DataExportRepositoryInterface $exports,
        private readonly CookieConsentRepositoryInterface $consents,
        private readonly ConsentServiceInterface $consent,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $exports = $this->exports->listFor((string) $user->id)
            ->map(fn (DataExport $export): array => [
                'export' => $export,
                'url' => $export->isDownloadable() ? $this->exportService->downloadUrl($export) : null,
            ]);

        return view('settings.privacy', [
            'user' => $user,
            'exports' => $exports,
            'hasPendingExport' => $exports->contains(fn (array $row): bool => $row['export']->isInProgress()),
            'consentHistory' => $this->consents->historyFor((string) $user->id),
            'currentConsent' => $this->consent->fromRequest($request),
            'gpc' => $this->consent->gpcRequested($request),
            'exportTtlHours' => (int) config('privacy.export_ttl_hours'),
            'dpoEmail' => (string) config('privacy.dpo_email'),
        ]);
    }

    public function export(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->exportService->request($user);
        } catch (DataExportException $e) {
            return redirect()->route('settings.privacy')->with('error', $e->getMessage());
        }

        return redirect()->route('settings.privacy')
            ->with('success', 'Exportação solicitada. Assim que ficar pronta, o link de download aparece nesta página.');
    }

    /**
     * Route model binding is scoped by BelongsToUser: another user's export is a 404.
     */
    public function download(Request $request, DataExport $export): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->exportService->download($export, $user);
    }
}
