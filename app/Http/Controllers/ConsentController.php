<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsentRequest;
use App\Interfaces\Services\ConsentServiceInterface;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Single write path of the cookie consent (banner, /cookies and Settings).
 */
class ConsentController extends Controller
{
    public function __construct(
        private readonly ConsentServiceInterface $consent,
    ) {}

    public function store(StoreConsentRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $consent = $this->consent->record(
            $request,
            $request->boolean('analytics'),
            $request->boolean('marketing'),
            (string) $request->validated('source'),
            $user instanceof User ? $user : null,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'analytics' => $consent->analytics,
                'marketing' => $consent->marketing,
                'gpc' => $consent->gpc,
                'version' => $consent->version,
            ]);
        }

        return back()->with('success', 'Preferências de privacidade salvas.');
    }
}
