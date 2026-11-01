<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Factories\SubscriptionFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

const REPORT_EXPORT_HEADERS = ['Data', 'Vencimento', 'Descrição', 'Parcela', 'Tipo', 'Situação', 'Forma de pagamento', 'Carteira', 'Categoria', 'Valor'];

function reportExportPro(): User
{
    $user = User::factory()->create(['terms_accepted_at' => now()]);
    SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);

    return $user->fresh();
}

function reportExportRow(User $user, Wallet $wallet, array $attributes): Transaction
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

/**
 * @return array<int, array<int, string|null>>
 */
function reportExportCsvRows(string $csv): array
{
    $body = substr($csv, 3); // strip the UTF-8 BOM
    $lines = array_values(array_filter(explode("\n", $body), fn (string $line): bool => $line !== ''));

    return array_map(fn (string $line): array => str_getcsv($line, ';', '"', '\\'), $lines);
}

beforeEach(function (): void {
    $this->owner = reportExportPro();
    $this->other = reportExportPro();

    $this->ownerWallet = Wallet::factory()->for($this->owner)->create(['type' => 'checking', 'name' => 'Conta do Dono']);
    $this->otherWallet = Wallet::factory()->for($this->other)->create(['type' => 'checking', 'name' => 'Conta Alheia']);

    reportExportRow($this->owner, $this->ownerWallet, ['description' => 'Mercado do Dono', 'amount' => 1234.5]);
    reportExportRow($this->owner, $this->ownerWallet, ['description' => 'Salario do Dono', 'type' => 'income', 'amount' => 5000]);
    reportExportRow($this->other, $this->otherWallet, ['description' => 'Despesa Alheia Secreta', 'amount' => 999]);
});

it('exports CSV with BOM, ";" separator and the 10 pt-BR headers', function (): void {
    $response = $this->actingAs($this->owner)->get(route('reports.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('.csv');

    $csv = $response->streamedContent();
    expect(str_starts_with($csv, "\xEF\xBB\xBF"))->toBeTrue();

    $rows = reportExportCsvRows($csv);
    expect($rows[0])->toBe(REPORT_EXPORT_HEADERS);

    $descriptions = array_column(array_slice($rows, 1), 2);
    expect($descriptions)->toContain('Mercado do Dono', 'Salario do Dono')
        ->not->toContain('Despesa Alheia Secreta');

    $mercado = collect($rows)->first(fn (array $row): bool => $row[2] === 'Mercado do Dono');
    expect($mercado[4])->toBe('Despesa')
        ->and($mercado[7])->toBe('Conta do Dono')
        ->and($mercado[9])->toBe('1.234,50');
});

it('never exports another user rows, even with their wallet id', function (): void {
    foreach ([['wallet_id' => $this->otherWallet->id], ['wallet' => $this->otherWallet->id]] as $query) {
        $csv = $this->actingAs($this->owner)->get(route('reports.export', $query))->streamedContent();

        expect($csv)->not->toContain('Despesa Alheia Secreta')
            ->and($csv)->not->toContain('Mercado do Dono');
    }
});

it('applies the type filter to the CSV', function (): void {
    $csv = $this->actingAs($this->owner)->get(route('reports.export', ['type' => 'income']))->streamedContent();

    expect($csv)->toContain('Salario do Dono')
        ->and($csv)->not->toContain('Mercado do Dono');
});

it('exports XLSX with the same headers and only own rows', function (): void {
    $response = $this->actingAs($this->owner)->get(route('reports.export', ['format' => 'xlsx']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');

    $body = $response->streamedContent();
    expect(str_starts_with($body, 'PK'))->toBeTrue();

    $path = tempnam(sys_get_temp_dir(), 'finestro_xlsx_');
    file_put_contents($path, $body);

    try {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
    } finally {
        @unlink($path);
    }

    expect($rows[0])->toBe(REPORT_EXPORT_HEADERS);

    $descriptions = array_column(array_slice($rows, 1), 2);
    expect($descriptions)->toContain('Mercado do Dono', 'Salario do Dono')
        ->not->toContain('Despesa Alheia Secreta');

    $mercado = collect($rows)->first(fn (array $row): bool => $row[2] === 'Mercado do Dono');
    expect($mercado[9])->toBe('1.234,50');
});

it('escapes user-supplied text a spreadsheet would run as a formula', function (string $raw): void {
    reportExportRow($this->owner, $this->ownerWallet, ['description' => $raw]);

    $csv = $this->actingAs($this->owner)->get(route('reports.export'))->streamedContent();
    $descriptions = array_column(reportExportCsvRows($csv), 2);

    // The exported cell must be the neutralised value, never the raw formula.
    expect($descriptions)->toContain("'".$raw)
        ->and($descriptions)->not->toContain($raw);
})->with([
    'equals' => ['=1+1'],
    'plus' => ['+1+1'],
    'minus' => ['-1+1'],
    'at' => ['@SUM(1+1)'],
]);

it('writes formula-like text as a plain string cell in XLSX, without the CSV guard', function (string $raw): void {
    reportExportRow($this->owner, $this->ownerWallet, ['description' => $raw]);

    $body = $this->actingAs($this->owner)->get(route('reports.export', ['format' => 'xlsx']))->streamedContent();
    $path = tempnam(sys_get_temp_dir(), 'finestro_xlsx_');
    file_put_contents($path, $body);

    try {
        $zip = new ZipArchive;
        $zip->open($path);
        $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $sheet = IOFactory::load($path)->getActiveSheet();
    } finally {
        @unlink($path);
    }

    $cells = [];
    for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
        $cells[] = $sheet->getCell('C'.$row);
    }
    $cell = collect($cells)->first(fn ($cell): bool => $cell->getValue() === $raw);

    // Original text, typed as string, never a formula and never the guard.
    expect($cell)->not->toBeNull()
        ->and($cell->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($cell->isFormula())->toBeFalse()
        ->and(collect($cells)->map->getValue()->all())->not->toContain("'".$raw)
        ->and($sheetXml)->not->toContain('<f>')
        ->and($sheetXml)->not->toContain('<f ');
})->with([
    'equals' => ['=1+1'],
    'plus' => ['+1+1'],
    'minus' => ['-1+1'],
    'at' => ['@SUM(1+1)'],
    'hyperlink' => ['=HYPERLINK("https://evil.test","clique")'],
]);

it('redirects Free users with the plan error', function (string $format): void {
    $free = User::factory()->create(['terms_accepted_at' => now()]);

    $this->actingAs($free)->get(route('reports.export', ['format' => $format]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHas('error', 'Exportação de relatórios disponível apenas no plano Profissional.');
})->with(['csv', 'xlsx']);

it('forbids users whose role lacks reports.export', function (string $format): void {
    $limited = Role::findOrCreate('limited', 'web');
    $limited->syncPermissions(array_values(array_diff(
        RolesAndPermissionsSeeder::PERMISSIONS,
        ['reports.export', 'admin.access']
    )));
    $this->owner->syncRoles([$limited]);

    $this->actingAs($this->owner)->get(route('reports.export', ['format' => $format]))->assertForbidden();
})->with(['csv', 'xlsx']);

it('rejects an unknown export format', function (): void {
    $this->actingAs($this->owner)
        ->from(route('reports.index'))
        ->get(route('reports.export', ['format' => 'pdf']))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors(['format' => 'Formato de exportação inválido.']);
});
