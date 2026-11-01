<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * /api/v1/transactions: list (filters, summary, pagination), create (JSON,
 * installments, multipart receipt), update, pay/unpay, receipt download,
 * delete and pt-BR validation.
 */

const TRANSACTION_API_KEYS = [
    'id', 'type', 'status', 'status_label', 'amount', 'description', 'date', 'due_date', 'paid_at', 'payment_method',
    'payment_method_label', 'is_recurring', 'recurring_frequency', 'installment_group_id', 'installment_number',
    'installment_total', 'is_overdue', 'notes', 'has_receipt', 'receipt_url', 'wallet_id', 'wallet', 'category_id',
    'category', 'created_at', 'updated_at',
];

const TRANSACTION_API_PDF = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";

function transactionApiRow(User $user, Wallet $wallet, array $attributes = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'status' => 'paid',
        'amount' => 100,
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('private');
    $this->user = proUser();
    $this->wallet = Wallet::factory()->for($this->user)->create(['type' => 'checking', 'balance' => 1000]);
    $this->token = apiToken($this->user, ['transactions:read', 'transactions:write']);
});

it('lists own transactions of the month with summary and pagination', function () {
    transactionApiRow($this->user, $this->wallet, ['description' => 'Mercado', 'amount' => 50]);
    transactionApiRow($this->user, $this->wallet, ['description' => 'Padaria', 'amount' => 20]);
    transactionApiRow($this->user, $this->wallet, ['description' => 'Mês passado', 'date' => now()->subMonthNoOverflow()->toDateString()]);
    $foreign = transactionApiRow($other = proUser(), Wallet::factory()->for($other)->create(), ['description' => 'Alheia']);

    $response = $this->withToken($this->token)->getJson('/api/v1/transactions?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.summary.count', 2)
        ->assertJsonPath('meta.summary.expense_paid', 70)
        ->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta' => ['current_page', 'summary']]);

    expect(array_keys($response->json('data.0')))->toEqualCanonicalizing(TRANSACTION_API_KEYS)
        ->and($response->getContent())->not->toContain($foreign->id)
        ->not->toContain('user_id')
        ->not->toContain('receipt_path')
        ->not->toContain('deleted_at')
        ->not->toContain('"media"');

    $this->withToken($this->token)->getJson('/api/v1/transactions?month=all&year='.now()->year.'&type=income')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('validates list filters in pt-BR', function () {
    $this->withToken($this->token)->getJson('/api/v1/transactions?per_page=101&status=foo&month=13')
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.per_page.0', 'O parâmetro per_page deve ser no máximo 100.')
        ->assertJsonPath('errors.status.0', 'Situação inválida.')
        ->assertJsonPath('errors.month.0', 'Mês inválido. Use 1 a 12 ou "all".');
});

it('creates a paid expense (201) and updates the wallet balance', function () {
    $response = $this->withToken($this->token)->postJson('/api/v1/transactions', [
        'wallet_id' => $this->wallet->id,
        'type' => 'expense',
        'amount' => 150.25,
        'description' => 'Farmácia',
        'date' => now()->toDateString(),
        'payment_method' => 'pix',
    ])->assertCreated()
        ->assertJsonPath('data.amount', '150.25')
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.has_receipt', false)
        ->assertJsonPath('data.receipt_url', null)
        ->assertJsonPath('data.wallet.id', $this->wallet->id)
        ->assertJsonMissingPath('meta');

    expect(array_keys($response->json('data')))->toEqualCanonicalizing(TRANSACTION_API_KEYS)
        ->and((float) $this->wallet->fresh()->balance)->toBe(849.75);
});

it('creates installments and reports them in meta', function () {
    $this->withToken($this->token)->postJson('/api/v1/transactions', [
        'wallet_id' => $this->wallet->id,
        'type' => 'expense',
        'amount' => 300,
        'description' => 'Notebook',
        'date' => now()->toDateString(),
        'installments' => 3,
    ])->assertCreated()
        ->assertJsonPath('meta.installments', 3)
        ->assertJsonPath('data.installment_number', 1)
        ->assertJsonPath('data.installment_total', 3);

    expect(Transaction::query()->where('user_id', $this->user->id)->count())->toBe(3);
});

it('rejects another user\'s wallet and returns pt-BR errors on store', function () {
    $foreignWallet = Wallet::factory()->for(proUser())->create();

    $this->withToken($this->token)->postJson('/api/v1/transactions', [
        'wallet_id' => $foreignWallet->id,
        'type' => 'expense',
        'amount' => 10,
        'description' => 'X',
        'date' => now()->toDateString(),
    ])->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.wallet_id.0', 'Carteira não encontrada.');

    $this->withToken($this->token)->postJson('/api/v1/transactions', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.wallet_id.0', 'Selecione uma carteira.')
        ->assertJsonPath('errors.amount.0', 'O valor é obrigatório.')
        ->assertJsonPath('errors.description.0', 'A descrição é obrigatória.');

    expect(Transaction::query()->where('wallet_id', $foreignWallet->id)->exists())->toBeFalse();
});

it('shows and updates with PUT and PATCH', function () {
    $transaction = transactionApiRow($this->user, $this->wallet, ['description' => 'Antes']);

    $this->withToken($this->token)->getJson("/api/v1/transactions/{$transaction->id}")
        ->assertOk()
        ->assertJsonPath('data.description', 'Antes');

    $this->withToken($this->token)->patchJson("/api/v1/transactions/{$transaction->id}", ['description' => 'Depois'])
        ->assertOk()
        ->assertJsonPath('data.description', 'Depois');

    $this->withToken($this->token)->putJson("/api/v1/transactions/{$transaction->id}", ['notes' => 'Obs'])
        ->assertOk()
        ->assertJsonPath('data.notes', 'Obs');

    $this->withToken($this->token)->patchJson("/api/v1/transactions/{$transaction->id}", ['amount' => 0])
        ->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'O valor deve ser maior que zero.');
});

it('pays and reopens a bill adjusting the wallet balance', function () {
    $bill = transactionApiRow($this->user, $this->wallet, ['status' => 'pending', 'amount' => 200]);

    $this->withToken($this->token)->patchJson("/api/v1/transactions/{$bill->id}/pay")
        ->assertOk()
        ->assertJsonPath('data.status', 'paid');
    expect((float) $this->wallet->fresh()->balance)->toBe(800.0);

    $this->withToken($this->token)->patchJson("/api/v1/transactions/{$bill->id}/unpay")
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.paid_at', null);
    expect((float) $this->wallet->fresh()->balance)->toBe(1000.0);

    $this->withToken($this->token)->patchJson("/api/v1/transactions/{$bill->id}/pay", ['paid_at' => now()->addDay()->toDateString()])
        ->assertStatus(422)
        ->assertJsonPath('errors.paid_at.0', 'A data de pagamento não pode ser futura.');
});

it('uploads a receipt (multipart) and downloads it only for the owner', function () {
    $response = $this->withToken($this->token)->post('/api/v1/transactions', [
        'wallet_id' => $this->wallet->id,
        'type' => 'expense',
        'amount' => 80,
        'description' => 'Consulta',
        'date' => now()->toDateString(),
        'receipt' => UploadedFile::fake()->createWithContent('nota.pdf', TRANSACTION_API_PDF),
    ])->assertCreated()
        ->assertJsonPath('data.has_receipt', true);

    $id = $response->json('data.id');
    expect($response->json('data.receipt_url'))->toBe(route('api.v1.transactions.receipt', $id))
        ->and($response->getContent())->not->toContain('receipts/')
        ->not->toContain('receipt_path')
        ->not->toContain('"media"');

    $this->withToken($this->token)->get("/api/v1/transactions/{$id}/receipt")
        ->assertOk()
        ->assertHeader('Content-Disposition');

    // Creating models between requests resolves the cached Sanctum guard,
    // so forget it after the intruder's token exists.
    $intruderToken = apiToken(proUser());
    app('auth')->forgetGuards();
    $this->withToken($intruderToken)->getJson("/api/v1/transactions/{$id}/receipt")
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('answers 404 for the receipt of a transaction without one', function () {
    $transaction = transactionApiRow($this->user, $this->wallet);

    $this->withToken($this->token)->getJson("/api/v1/transactions/{$transaction->id}/receipt")
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found');
});

it('deletes with 204, including a whole installment group', function () {
    $single = transactionApiRow($this->user, $this->wallet);

    $this->withToken($this->token)->deleteJson("/api/v1/transactions/{$single->id}", ['reason' => 'Duplicada'])
        ->assertNoContent();
    $this->assertSoftDeleted('transactions', ['id' => $single->id]);

    $this->withToken($this->token)->postJson('/api/v1/transactions', [
        'wallet_id' => $this->wallet->id, 'type' => 'expense', 'amount' => 90, 'description' => 'TV',
        'date' => now()->toDateString(), 'installments' => 3,
    ])->assertCreated();
    $first = Transaction::query()->where('description', 'like', 'TV%')->orderBy('installment_number')->firstOrFail();

    $this->withToken($this->token)->deleteJson("/api/v1/transactions/{$first->id}", ['scope' => 'installments'])
        ->assertNoContent();

    expect(Transaction::query()->where('installment_group_id', $first->installment_group_id)->count())->toBe(0);

    $this->withToken($this->token)->deleteJson("/api/v1/transactions/{$first->id}", ['scope' => 'foo'])
        ->assertStatus(422)
        ->assertJsonPath('errors.scope.0', 'Escopo inválido. Use single ou installments.');
});
