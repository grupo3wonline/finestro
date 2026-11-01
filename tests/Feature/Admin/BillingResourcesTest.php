<?php

declare(strict_types=1);

use App\Exports\AdminPaymentsExport;
use App\Filament\Resources\PaymentHistories\Pages\ListPaymentHistories;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\ExpectationFailedException;

/*
|--------------------------------------------------------------------------
| Subscriptions and payments resources, payments export
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Carbon::setTestNow('2026-03-15 12:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = mfaAdmin();
    $this->actingAs($this->admin, 'admin');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('lists subscriptions with pt-BR labels and truncated Stripe ids only', function (): void {
    $active = Subscription::factory()->create([
        'stripe_subscription_id' => 'sub_ABCDEFGHIJKLMNOP1a2b',
        'stripe_customer_id' => 'cus_SECRETCUSTOMER99',
        'stripe_price_id' => 'price_SECRETPRICE777',
    ]);
    Subscription::factory()->pendingCancellation()->create();
    Subscription::factory()->pastDue()->create();

    $this->get(ListSubscriptions::getUrl())
        ->assertOk()
        ->assertSee($active->user->email)
        ->assertSee('Ativa')
        ->assertSee('Cancelamento agendado')
        ->assertSee('Pagamento pendente')
        ->assertSee('Profissional')
        ->assertSee('sub_…1a2b')
        ->assertDontSee('sub_ABCDEFGHIJKLMNOP1a2b')
        ->assertDontSee('cus_SECRETCUSTOMER99')
        ->assertDontSee('price_SECRETPRICE777');
});

it('filters subscriptions by status', function (): void {
    $active = Subscription::factory()->create();
    $pastDue = Subscription::factory()->pastDue()->create();

    Livewire::test(ListSubscriptions::class)
        ->filterTable('status', 'past_due')
        ->assertCanSeeTableRecords([$pastDue])
        ->assertCanNotSeeTableRecords([$active]);
});

it('lists payments in BRL with status labels and no Stripe ids', function (): void {
    $payment = PaymentHistory::factory()->create([
        'amount' => 1234.56,
        'stripe_invoice_id' => 'in_SECRETINVOICE123',
        'stripe_payment_intent_id' => 'pi_SECRETINTENT456',
        'hosted_invoice_url' => 'https://invoice.stripe.test/in_x',
    ]);
    PaymentHistory::factory()->failed()->create();

    $this->get(ListPaymentHistories::getUrl())
        ->assertOk()
        ->assertSee($payment->user->name)
        ->assertSee('R$ 1.234,56')
        ->assertSee('Pago')
        ->assertSee('Falhou')
        ->assertSee('Ver fatura')
        ->assertDontSee('in_SECRETINVOICE123')
        ->assertDontSee('pi_SECRETINTENT456');
});

it('filters payments by status and month', function (): void {
    $paidNow = PaymentHistory::factory()->create(['paid_at' => now()->subDay()]);
    $paidLastMonth = PaymentHistory::factory()->create(['paid_at' => now()->subMonth()]);
    $failedNow = PaymentHistory::factory()->failed()->create();

    Livewire::test(ListPaymentHistories::class)
        ->filterTable('status', 'failed')
        ->assertCanSeeTableRecords([$failedNow])
        ->assertCanNotSeeTableRecords([$paidNow, $paidLastMonth])
        ->resetTableFilters()
        ->filterTable('month', '2026-02')
        ->assertCanSeeTableRecords([$paidLastMonth])
        ->assertCanNotSeeTableRecords([$paidNow, $failedNow])
        ->filterTable('month', '2026-03')
        ->assertCanSeeTableRecords([$paidNow, $failedNow])
        ->assertCanNotSeeTableRecords([$paidLastMonth]);
});

it('exports the filtered payments as CSV and XLSX', function (string $action, string $extension): void {
    Excel::fake();
    $user = User::factory()->create(['name' => 'Pagadora', 'email' => 'pagadora@example.com']);
    PaymentHistory::factory()->for($user)->create(['amount' => 29.90]);
    PaymentHistory::factory()->for($user)->create(['amount' => 10.00]);
    PaymentHistory::factory()->for($user)->failed()->create();

    Livewire::test(ListPaymentHistories::class)
        ->filterTable('status', 'paid')
        ->callAction($action);

    Excel::assertDownloaded('pagamentos-20260315-120000.'.$extension, function (AdminPaymentsExport $export): bool {
        $rows = $export->query()->get()->map(fn (PaymentHistory $payment): array => $export->map($payment));

        return $rows->count() === 2
            && $export->headings() === ['Data', 'Usuário', 'E-mail', 'Valor', 'Moeda', 'Situação', 'Descrição']
            && $rows->pluck(3)->sort()->values()->all() === ['R$ 10,00', 'R$ 29,90']
            && $rows->every(fn (array $row): bool => $row[1] === 'Pagadora' && $row[2] === 'pagadora@example.com' && $row[5] === 'Pago')
            && ! str_contains((string) json_encode($rows), 'in_')
            && ! str_contains((string) json_encode($rows), 'pi_');
    });
})->with([
    'csv' => ['exportCsv', 'csv'],
    'xlsx' => ['exportXlsx', 'xlsx'],
]);

it('escapes user-supplied text that a spreadsheet would run as a formula', function (): void {
    $user = User::factory()->create([
        'name' => '=HYPERLINK("https://evil.test","clique")',
        'email' => '+cmd@example.com',
    ]);
    $payment = PaymentHistory::factory()->for($user)->create(['amount' => 29.90, 'description' => '@SUM(1+1)']);
    $tab = PaymentHistory::factory()->for(User::factory()->create(['name' => "\t=1+1"]))->create(['description' => "\r=2"]);
    $minus = PaymentHistory::factory()->for(User::factory()->create(['name' => '-2+3']))->create(['description' => 'Plano Profissional']);

    $export = new AdminPaymentsExport(PaymentHistory::query()->with('user'));

    expect($export->map($payment->load('user')))
        ->sequence(
            fn ($cell) => $cell->toBeString(),
            fn ($cell) => $cell->toBe('\'=HYPERLINK("https://evil.test","clique")'),
            fn ($cell) => $cell->toBe("'+cmd@example.com"),
            fn ($cell) => $cell->toBe('R$ 29,90'),
            fn ($cell) => $cell->toBe('BRL'),
            fn ($cell) => $cell->toBe('Pago'),
            fn ($cell) => $cell->toBe("'@SUM(1+1)"),
        )
        ->and($export->map($tab->load('user'))[1])->toBe("'\t=1+1")
        ->and($export->map($tab)[6])->toBe("'\r=2")
        ->and($export->map($minus->load('user'))[1])->toBe("'-2+3")
        ->and($export->map($minus)[6])->toBe('Plano Profissional');
});

it('writes formula-like text as plain string cells in XLSX and keeps the CSV guard', function (): void {
    $payload = [
        'name' => '=HYPERLINK("https://evil.test","clique")',
        'email' => '+cmd@example.com',
        'description' => '@SUM(1+1)',
    ];
    PaymentHistory::factory()->for(User::factory()->create(['name' => $payload['name'], 'email' => $payload['email']]))
        ->create(['amount' => 29.90, 'description' => $payload['description']]);

    $xlsx = Excel::raw(new AdminPaymentsExport(PaymentHistory::query()->with('user'), ExcelWriter::XLSX), ExcelWriter::XLSX);
    $path = tempnam(sys_get_temp_dir(), 'finestro_admin_xlsx_');
    file_put_contents($path, $xlsx);

    try {
        $zip = new ZipArchive;
        $zip->open($path);
        $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $sheet = IOFactory::load($path)->getActiveSheet();
    } finally {
        @unlink($path);
    }

    // Row 2 is the only data row: B = name, C = e-mail, G = description.
    foreach (['B2' => $payload['name'], 'C2' => $payload['email'], 'G2' => $payload['description']] as $coordinate => $raw) {
        $cell = $sheet->getCell($coordinate);
        expect($cell->getValue())->toBe($raw)
            ->and($cell->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($cell->isFormula())->toBeFalse();
    }
    expect($sheetXml)->not->toContain('<f>')
        ->and($sheetXml)->not->toContain('<f ');

    $csv = Excel::raw(new AdminPaymentsExport(PaymentHistory::query()->with('user'), ExcelWriter::CSV), ExcelWriter::CSV);
    $row = str_getcsv(explode("\n", substr($csv, 3))[1], ';', '"', '\\');

    expect($row[1])->toBe("'".$payload['name'])
        ->and($row[2])->toBe("'".$payload['email'])
        ->and($row[6])->toBe("'".$payload['description']);
});

it('builds the header-action export for the chosen writer', function (string $action, string $extension, string $expectedName): void {
    Excel::fake();
    PaymentHistory::factory()->for(User::factory()->create(['name' => '=1+1']))->create();

    Livewire::test(ListPaymentHistories::class)->callAction($action);

    Excel::assertDownloaded('pagamentos-20260315-120000.'.$extension, fn (AdminPaymentsExport $export): bool => $export->map($export->query()->firstOrFail())[1] === $expectedName);
})->with([
    'csv keeps the guard' => ['exportCsv', 'csv', "'=1+1"],
    'xlsx keeps the original text' => ['exportXlsx', 'xlsx', '=1+1'],
]);

it('rejects the export for a plain user', function (): void {
    Excel::fake();
    PaymentHistory::factory()->create();

    $component = Livewire::test(ListPaymentHistories::class);

    $this->actingAs(User::factory()->create(), 'admin');

    $component->call('mountAction', 'exportCsv')->assertForbidden();

    expect(fn () => Excel::assertDownloaded('pagamentos-20260315-120000.csv'))
        ->toThrow(ExpectationFailedException::class);
});
