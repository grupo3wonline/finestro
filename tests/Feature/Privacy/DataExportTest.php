<?php

declare(strict_types=1);

use App\Enums\DataExportStatus;
use App\Interfaces\Repositories\PersonalDataRepositoryInterface;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Jobs\GeneratePersonalDataExport;
use App\Models\DataExport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/*
|--------------------------------------------------------------------------
| Personal data export (access / portability): own data only, signed and
| expiring download, purge, activity without personal content
|--------------------------------------------------------------------------
*/

function requestDataExport(User $user): DataExport
{
    test()->actingAs($user)
        ->post(route('settings.privacy.export'))
        ->assertRedirect(route('settings.privacy'))
        ->assertSessionHas('success');

    return DataExport::withoutGlobalScopes()->where('user_id', $user->id)->latest()->firstOrFail();
}

function openDataExportZip(DataExport $export): ZipArchive
{
    $zip = new ZipArchive;
    expect($zip->open(Storage::disk('local')->path((string) $export->path)))->toBeTrue();

    return $zip;
}

beforeEach(function (): void {
    Storage::fake('local');
    Storage::fake('private');

    $this->alice = User::factory()->create(['email' => 'alice@example.com']);
    $this->bob = User::factory()->create(['email' => 'bob.segredo@example.com']);

    $this->aliceWallet = Wallet::factory()->for($this->alice)->create(['name' => 'Carteira da Alice']);
    $this->aliceTransaction = Transaction::factory()->create([
        'user_id' => $this->alice->id,
        'wallet_id' => $this->aliceWallet->id,
        'type' => 'expense',
        'description' => '=1+1',
        'is_recurring' => false,
    ]);
    $this->aliceTransaction->addMediaFromString("%PDF-1.4\n%%EOF")
        ->usingFileName('recibo-alice.pdf')
        ->toMediaCollection(Transaction::RECEIPT_COLLECTION);

    $bobWallet = Wallet::factory()->for($this->bob)->create(['name' => 'Carteira do Bob']);
    Transaction::factory()->create([
        'user_id' => $this->bob->id,
        'wallet_id' => $bobWallet->id,
        'description' => 'Despesa do Bob',
        'is_recurring' => false,
    ]);
});

it('builds a zip with JSON and CSV of only the owner data, including receipt metadata', function (): void {
    $export = requestDataExport($this->alice);

    expect($export->status)->toBe(DataExportStatus::Ready)
        ->and($export->path)->toBe("exports/{$this->alice->id}/{$export->id}.zip")
        ->and($export->size)->toBeGreaterThan(0)
        ->and($export->expires_at->greaterThan(now()->addHours(23)))->toBeTrue();

    $zip = openDataExportZip($export);
    $json = (string) $zip->getFromName('data.json');
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    expect(array_keys($data['sections']))->toBe([
        'account', 'wallets', 'transactions', 'categories', 'goals', 'budgets',
        'subscriptions', 'payments', 'receipts', 'cookie_consents', 'activity',
    ]);

    $receipt = Media::query()->where('model_id', $this->aliceTransaction->id)->sole();

    expect($data['sections']['account'][0]['email'])->toBe('alice@example.com')
        ->and($data['sections']['account'][0])->not->toHaveKeys(['password', 'remember_token'])
        ->and(collect($data['sections']['wallets'])->pluck('name')->all())->toBe(['Carteira da Alice'])
        ->and($data['sections']['receipts'][0])->toMatchArray([
            'transaction_id' => $this->aliceTransaction->id,
            'file_name' => $receipt->file_name,
            'mime_type' => $receipt->mime_type,
        ])
        ->and($json)->not->toContain('bob.segredo@example.com')
        ->and($json)->not->toContain('Carteira do Bob')
        ->and($json)->not->toContain('Despesa do Bob')
        ->and(collect($data['sections']['activity'])->pluck('event'))->toContain('data_export_requested');

    // CSV cells starting with "=" are neutralised against formula injection.
    $csv = (string) $zip->getFromName('transactions.csv');
    expect($csv)->toContain("'=1+1")
        ->and($zip->locateName('wallets.csv'))->not->toBeFalse()
        ->and($zip->locateName('receipts.csv'))->not->toBeFalse();

    $zip->close();
});

it('refuses a new request while an export is being prepared', function (): void {
    DataExport::factory()->for($this->alice)->create();

    $this->actingAs($this->alice)
        ->post(route('settings.privacy.export'))
        ->assertRedirect(route('settings.privacy'))
        ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'em preparação'));

    expect(DataExport::withoutGlobalScopes()->where('user_id', $this->alice->id)->count())->toBe(1);
});

it('does not let a stale Pending export block a new request', function (): void {
    $stale = DataExport::factory()->for($this->alice)->create();
    $stale->forceFill(['created_at' => now()->subMinutes(DataExport::STALE_AFTER_MINUTES + 1)])->save();

    $this->actingAs($this->alice)
        ->get(route('settings.privacy'))
        ->assertOk()
        ->assertSee('Falhou')
        ->assertDontSee('Em preparação')
        ->assertSee('Solicitar exportação');

    $export = requestDataExport($this->alice);

    expect($export->id)->not->toBe($stale->id)
        ->and($export->status)->toBe(DataExportStatus::Ready);
});

it('marks the export Failed when the queued job fails', function (): void {
    $pending = DataExport::factory()->for($this->alice)->create();
    $ready = DataExport::factory()->for($this->alice)->ready()->create();

    (new GeneratePersonalDataExport((string) $pending->id))->failed(new RuntimeException('worker timeout'));
    (new GeneratePersonalDataExport((string) $ready->id))->failed(null);
    (new GeneratePersonalDataExport('00000000-0000-0000-0000-000000000000'))->failed(null);

    expect($pending->fresh()->status)->toBe(DataExportStatus::Failed)
        ->and($ready->fresh()->status)->toBe(DataExportStatus::Ready);
});

it('marks stale Pending exports Failed in privacy:purge-exports and keeps fresh ones', function (): void {
    $fresh = DataExport::factory()->for($this->alice)->create();
    $stale = DataExport::factory()->for($this->bob)->create();
    $stale->forceFill(['created_at' => now()->subMinutes(DataExport::STALE_AFTER_MINUTES + 5)])->save();

    $this->artisan('privacy:purge-exports')
        ->expectsOutputToContain('1 exportação(ões) travada(s) marcada(s) como falha.')
        ->assertSuccessful();

    expect($stale->fresh()->status)->toBe(DataExportStatus::Failed)
        ->and($fresh->fresh()->status)->toBe(DataExportStatus::Pending);
});

it('serves the file only to its owner through the signed link', function (): void {
    $export = requestDataExport($this->alice);
    $url = app(DataExportServiceInterface::class)->downloadUrl($export);

    $this->actingAs($this->bob)->get($url)->assertNotFound();
    $this->actingAs($this->alice)->get(route('settings.privacy.download', $export))->assertForbidden();

    $this->actingAs($this->alice)->get($url)
        ->assertOk()
        ->assertDownload('finestro-meus-dados-'.$export->created_at->format('Y-m-d').'.zip');
});

it('refuses expired links', function (): void {
    $export = requestDataExport($this->alice);
    $url = app(DataExportServiceInterface::class)->downloadUrl($export);

    // The export expired while the link was still signed as valid: 410 Gone.
    $export->forceFill(['expires_at' => now()->subMinute()])->save();
    $this->actingAs($this->alice)->get($url)->assertStatus(410);

    // The signature itself has expired: refused before reaching the controller.
    $this->travelTo(now()->addHours((int) config('privacy.export_ttl_hours') + 1));
    $status = $this->actingAs($this->alice)->get($url)->getStatusCode();
    expect($status)->not->toBe(200)->and($status)->toBeIn([403, 410]);
});

it('purges expired export files', function (): void {
    $export = requestDataExport($this->alice);
    Storage::disk('local')->assertExists((string) $export->path);

    $this->travelTo(now()->addHours((int) config('privacy.export_ttl_hours') + 1));
    $this->artisan('privacy:purge-exports')->assertSuccessful();

    Storage::disk('local')->assertMissing((string) $export->path);
    expect($export->fresh()->status)->toBe(DataExportStatus::Expired)
        ->and($export->fresh()->path)->toBeNull();
});

it('logs export activity with the export id only', function (): void {
    $export = requestDataExport($this->alice);
    $this->get(app(DataExportServiceInterface::class)->downloadUrl($export))->assertOk();

    $activities = Activity::query()
        ->whereIn('event', ['data_export_requested', 'data_export_downloaded'])
        ->get();

    expect($activities->pluck('event')->sort()->values()->all())->toBe(['data_export_downloaded', 'data_export_requested']);

    foreach ($activities as $activity) {
        expect(array_keys($activity->properties->all()))->toEqualCanonicalizing(['export_id', 'ip'])
            ->and($activity->properties['export_id'])->toBe($export->id)
            ->and($activity->causer_id)->toBe($this->alice->id);
    }
});

it('marks the export as failed without logging personal data', function (): void {
    Log::spy();
    $this->app->instance(PersonalDataRepositoryInterface::class, new class implements PersonalDataRepositoryInterface
    {
        public function collect(User $user): array
        {
            throw new RuntimeException('boom '.$user->email);
        }
    });

    $export = requestDataExport($this->alice);

    expect($export->status)->toBe(DataExportStatus::Failed)->and($export->path)->toBeNull();
    Log::shouldHaveReceived('error')->withArgs(
        fn (string $message, array $context): bool => $message === 'Personal data export failed'
            && ! str_contains((string) json_encode($context), 'alice@example.com')
    )->once();
});
