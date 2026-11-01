<?php

use App\DTOs\TransactionDTO;
use App\Interfaces\Services\ReceiptServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/*
 * Transaction receipts on spatie/laravel-medialibrary: private disk, one file
 * per transaction, owner-only access, legacy receipt_path fallback and the
 * non-destructive receipts:import-media command.
 */

const RECEIPT_PDF = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";

// 1x1 transparent PNG.
const RECEIPT_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

function receiptPdf(string $name = 'nota.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, RECEIPT_PDF);
}

function receiptPng(string $name = 'foto.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode(RECEIPT_PNG_BASE64));
}

/** Media rows of the receipt collection for a transaction, ignoring user scopes. */
function receiptMedia(Transaction $transaction)
{
    return Media::query()
        ->where('model_type', Transaction::class)
        ->where('model_id', $transaction->id)
        ->where('collection_name', 'receipt')
        ->get();
}

function ownedTransaction(User $user, Wallet $wallet, array $attributes = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'status' => 'paid',
        'amount' => 50,
        'description' => 'Mercado',
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('private');

    $this->user = User::factory()->create();
    $this->wallet = Wallet::factory()->for($this->user)->create(['type' => 'checking', 'balance' => 1000]);
    $this->other = User::factory()->create();
});

it('stores a new receipt as private media under the owner folder', function () {
    $this->actingAs($this->user)->post(route('transactions.store'), [
        'wallet_id' => $this->wallet->id,
        'type' => 'expense',
        'amount' => '80.00',
        'description' => 'Farmácia',
        'date' => now()->toDateString(),
        'receipt' => receiptPdf(),
    ])->assertSessionHasNoErrors()->assertRedirect();

    $transaction = Transaction::firstOrFail();
    $media = receiptMedia($transaction);

    expect($media)->toHaveCount(1)
        ->and($media->first()->disk)->toBe('private')
        ->and($media->first()->getPathRelativeToRoot())->toStartWith('receipts/'.$this->user->id.'/media/'.$media->first()->uuid.'/')
        ->and($media->first()->file_name)->not->toContain('nota')
        ->and($transaction->receipt_path)->toBeNull()
        ->and($transaction->hasReceipt())->toBeTrue();

    Storage::disk('private')->assertExists($media->first()->getPathRelativeToRoot());

    $this->get(route('transactions.receipt', $transaction))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename="comprovante-'.now()->format('Y-m-d').'.pdf"');
});

it('keeps the receipt only on the first installment', function () {
    $this->actingAs($this->user)->post(route('transactions.store'), [
        'wallet_id' => $this->wallet->id,
        'type' => 'expense',
        'amount' => '300.00',
        'description' => 'Geladeira',
        'date' => now()->toDateString(),
        'installments' => 3,
        'receipt' => receiptPdf(),
    ])->assertSessionHasNoErrors();

    $installments = Transaction::orderBy('installment_number')->get();

    expect($installments)->toHaveCount(3)
        ->and(receiptMedia($installments[0]))->toHaveCount(1)
        ->and(receiptMedia($installments[1]))->toHaveCount(0)
        ->and(receiptMedia($installments[2]))->toHaveCount(0)
        ->and(Media::count())->toBe(1);
});

it('replaces the receipt on update and keeps exactly one media', function () {
    $transaction = ownedTransaction($this->user, $this->wallet);
    app(ReceiptServiceInterface::class)->attach($transaction, receiptPdf());
    $old = receiptMedia($transaction)->first();

    $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
        'receipt' => receiptPng(),
    ])->assertSessionHasNoErrors()->assertRedirect(route('transactions.show', $transaction));

    $media = receiptMedia($transaction);

    expect($media)->toHaveCount(1)
        ->and($media->first()->uuid)->not->toBe($old->uuid)
        ->and($media->first()->file_name)->toEndWith('.png')
        ->and($media->first()->mime_type)->toBe('image/png');

    Storage::disk('private')->assertExists($media->first()->getPathRelativeToRoot());
    Storage::disk('private')->assertMissing($old->getPathRelativeToRoot());
});

it('removes the receipt when remove_receipt is checked', function () {
    $transaction = ownedTransaction($this->user, $this->wallet);
    app(ReceiptServiceInterface::class)->attach($transaction, receiptPdf());
    $path = receiptMedia($transaction)->first()->getPathRelativeToRoot();

    $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
        'remove_receipt' => '1',
    ])->assertSessionHasNoErrors();

    $fresh = $transaction->fresh();

    expect(receiptMedia($transaction))->toHaveCount(0)
        ->and($fresh->hasReceipt())->toBeFalse();

    Storage::disk('private')->assertMissing($path);
    $this->get(route('transactions.receipt', $transaction))->assertNotFound();
});

it('clears a legacy receipt_path on removal but keeps the legacy file', function () {
    $legacy = 'receipts/'.$this->user->id.'/antigo.pdf';
    Storage::disk('private')->put($legacy, RECEIPT_PDF);
    $transaction = ownedTransaction($this->user, $this->wallet, ['receipt_path' => $legacy]);

    $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
        'remove_receipt' => '1',
    ])->assertSessionHasNoErrors();

    expect($transaction->fresh()->receipt_path)->toBeNull()
        ->and($transaction->fresh()->hasReceipt())->toBeFalse();

    Storage::disk('private')->assertExists($legacy);
});

it('keeps the receipt row and file when a removal is rolled back', function () {
    $transaction = ownedTransaction($this->user, $this->wallet);
    $old = app(ReceiptServiceInterface::class)->attach($transaction, receiptPdf());
    TransactionHistory::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs($this->user);

    expect(fn () => app(TransactionServiceInterface::class)->updateTransaction(
        $transaction->id,
        TransactionDTO::fromArray(['remove_receipt' => '1', 'reason' => 'teste']),
    ))->toThrow(RuntimeException::class, 'boom');

    $media = receiptMedia($transaction);

    expect($media)->toHaveCount(1)
        ->and($media->first()->uuid)->toBe($old->uuid);

    Storage::disk('private')->assertExists($old->getPathRelativeToRoot());
});

it('keeps the old receipt and leaves no new file when a replacement is rolled back', function () {
    $transaction = ownedTransaction($this->user, $this->wallet);
    $old = app(ReceiptServiceInterface::class)->attach($transaction, receiptPdf());
    TransactionHistory::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs($this->user);

    expect(fn () => app(TransactionServiceInterface::class)->updateTransaction(
        $transaction->id,
        TransactionDTO::fromArray(['receipt' => receiptPng(), 'reason' => 'teste']),
    ))->toThrow(RuntimeException::class, 'boom');

    $media = receiptMedia($transaction);

    expect($media)->toHaveCount(1)
        ->and($media->first()->uuid)->toBe($old->uuid)
        ->and(Storage::disk('private')->allFiles('receipts/'.$this->user->id))
        ->toBe([$old->getPathRelativeToRoot()]);
});

it('leaves no transaction, media row or file when a create with receipt is rolled back', function () {
    TransactionHistory::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs($this->user);

    expect(fn () => app(TransactionServiceInterface::class)->createTransaction(TransactionDTO::fromArray([
        'wallet_id' => $this->wallet->id,
        'type' => 'expense',
        'amount' => 80,
        'description' => 'Farmácia',
        'date' => now()->toDateString(),
        'status' => 'paid',
        'receipt' => receiptPdf(),
    ])))->toThrow(RuntimeException::class, 'boom');

    expect(Transaction::withoutGlobalScopes()->count())->toBe(0)
        ->and(Media::count())->toBe(0)
        ->and(Storage::disk('private')->allFiles('receipts/'.$this->user->id))->toBe([])
        ->and(Storage::disk('private')->allFiles())->toBe([]);
});

it('answers 404 to another user and leaves the owner receipt intact', function () {
    $transaction = ownedTransaction($this->user, $this->wallet);
    app(ReceiptServiceInterface::class)->attach($transaction, receiptPdf());
    $media = receiptMedia($transaction)->first();

    $this->actingAs($this->other);

    $this->get(route('transactions.receipt', $transaction))->assertNotFound();
    $this->put(route('transactions.update', $transaction), ['receipt' => receiptPng()])->assertNotFound();
    $this->put(route('transactions.update', $transaction), ['remove_receipt' => '1'])->assertNotFound();

    $after = receiptMedia($transaction);

    expect($after)->toHaveCount(1)
        ->and($after->first()->uuid)->toBe($media->uuid)
        ->and(Media::count())->toBe(1)
        ->and(Storage::disk('private')->allFiles('receipts/'.$this->other->id))->toBe([]);

    Storage::disk('private')->assertExists($media->getPathRelativeToRoot());
});

it('rejects a text file renamed to .pdf and creates no transaction', function () {
    $this->actingAs($this->user)->post(route('transactions.store'), [
        'wallet_id' => $this->wallet->id,
        'type' => 'expense',
        'amount' => '10.00',
        'description' => 'Falso',
        'date' => now()->toDateString(),
        'receipt' => UploadedFile::fake()->createWithContent('nota.pdf', 'apenas texto, não é um PDF'),
    ])->assertSessionHasErrors('receipt');

    expect(Transaction::withoutGlobalScopes()->count())->toBe(0)
        ->and(Media::count())->toBe(0);
});

it('converts a media library content rejection into a receipt validation error', function () {
    $transaction = ownedTransaction($this->user, $this->wallet);

    expect(fn () => app(ReceiptServiceInterface::class)->attach(
        $transaction,
        UploadedFile::fake()->createWithContent('nota.pdf', 'apenas texto'),
    ))->toThrow(ValidationException::class, 'O comprovante deve ser JPG, PNG ou PDF.');

    expect(Media::count())->toBe(0);
});

it('still downloads a legacy receipt_path without media', function () {
    $legacy = 'receipts/'.$this->user->id.'/legado.pdf';
    Storage::disk('private')->put($legacy, RECEIPT_PDF);
    $transaction = ownedTransaction($this->user, $this->wallet, ['receipt_path' => $legacy]);

    $this->actingAs($this->user)->get(route('transactions.receipt', $transaction))->assertOk();
    $this->actingAs($this->other)->get(route('transactions.receipt', $transaction))->assertNotFound();
});

it('imports legacy receipts idempotently without touching the originals', function () {
    $legacy = 'receipts/'.$this->user->id.'/legado.pdf';
    Storage::disk('private')->put($legacy, RECEIPT_PDF);
    $live = ownedTransaction($this->user, $this->wallet, ['receipt_path' => $legacy]);

    $trashedPath = 'receipts/'.$this->user->id.'/excluida.pdf';
    Storage::disk('private')->put($trashedPath, RECEIPT_PDF);
    $trashed = ownedTransaction($this->user, $this->wallet, ['receipt_path' => $trashedPath]);
    $trashed->delete();

    $missing = ownedTransaction($this->user, $this->wallet, ['receipt_path' => 'receipts/'.$this->user->id.'/sumiu.pdf']);

    // Dry run: reports, changes nothing.
    $this->artisan('receipts:import-media', ['--dry-run' => true])
        ->expectsOutputToContain('Arquivo ausente: transação '.$missing->id)
        ->expectsOutputToContain('[simulação] Importados: 2 | Já importados: 0 | Arquivo ausente: 1')
        ->assertSuccessful();
    expect(Media::count())->toBe(0);

    // Real run imports the live and the soft-deleted row.
    $this->artisan('receipts:import-media')
        ->expectsOutputToContain('Importados: 2 | Já importados: 0 | Arquivo ausente: 1 | Com erro: 0')
        ->assertSuccessful();

    expect(receiptMedia($live))->toHaveCount(1)
        ->and(receiptMedia($trashed))->toHaveCount(1)
        ->and(receiptMedia($missing))->toHaveCount(0)
        ->and(receiptMedia($live)->first()->getPathRelativeToRoot())->toStartWith('receipts/'.$this->user->id.'/media/');

    // Second run is a no-op.
    $this->artisan('receipts:import-media')
        ->expectsOutputToContain('Importados: 0 | Já importados: 2 | Arquivo ausente: 1 | Com erro: 0')
        ->assertSuccessful();

    expect(Media::count())->toBe(2)
        ->and(Transaction::withoutGlobalScopes()->find($live->id)->receipt_path)->toBe($legacy)
        ->and(Transaction::withoutGlobalScopes()->find($trashed->id)->receipt_path)->toBe($trashedPath);

    Storage::disk('private')->assertExists($legacy);
    Storage::disk('private')->assertExists($trashedPath);
    Storage::disk('private')->assertExists(receiptMedia($live)->first()->getPathRelativeToRoot());

    // The imported media is what the owner now downloads.
    $this->actingAs($this->user)->get(route('transactions.receipt', $live))->assertOk();
});
