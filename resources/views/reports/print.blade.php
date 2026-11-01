@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $period = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' a ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Relatório {{ $period }} — {{ config('app.name') }}</title>
    <style>
        :root { --ink: #111827; --muted: #6b7280; --line: #e5e7eb; --green: #15803d; --red: #b91c1c; --brand: #2563eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif; color: var(--ink); background: #f3f4f6; font-size: 13px; }
        .page { max-width: 210mm; margin: 24px auto; background: #fff; padding: 18mm 16mm; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid var(--brand); padding-bottom: 10px; margin-bottom: 18px; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 14px; margin: 22px 0 8px; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); }
        .muted { color: var(--muted); }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .kpi { border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; }
        .kpi small { display: block; color: var(--muted); font-size: 11px; }
        .kpi strong { font-size: 16px; }
        .green { color: var(--green); } .red { color: var(--red); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { font-size: 11px; text-transform: uppercase; color: var(--muted); background: #f9fafb; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .toolbar { max-width: 210mm; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button, .toolbar a { font: inherit; padding: 8px 14px; border-radius: 8px; border: 1px solid var(--line); background: #fff; cursor: pointer; text-decoration: none; color: var(--ink); }
        .toolbar button.primary { background: var(--brand); color: #fff; border-color: var(--brand); }
        footer { margin-top: 24px; font-size: 11px; color: var(--muted); text-align: center; }
        @media (max-width: 640px) { .kpis { grid-template-columns: repeat(2, 1fr); } .grid2 { grid-template-columns: 1fr; } .page { padding: 16px; margin: 8px; } }
        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #fff; }
            .page { box-shadow: none; margin: 0; padding: 0; max-width: none; }
            .toolbar { display: none; }
            tr { page-break-inside: avoid; }
            h2 { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('reports.index', request()->query()) }}">← Voltar</a>
        <button type="button" class="primary" onclick="window.print()">Imprimir / Salvar como PDF</button>
    </div>

    <main class="page">
        <header>
            <div>
                <h1>Relatório financeiro</h1>
                <div class="muted">{{ $period }}{{ $typeFilter ? ' · ' . ($typeFilter === 'income' ? 'Receitas' : 'Despesas') : '' }}{{ $statusFilter ? ' · ' . ($statusFilter === 'paid' ? 'Realizadas' : 'Pendentes') : '' }}</div>
            </div>
            <div style="text-align:right">
                <strong>{{ config('app.name') }}</strong>
                <div class="muted">{{ auth()->user()->name }} · emitido em {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </header>

        <section class="kpis">
            <div class="kpi"><small>Receitas</small><strong class="green">{{ $brl($totalIncome) }}</strong></div>
            <div class="kpi"><small>Despesas</small><strong class="red">{{ $brl($totalExpenses) }}</strong></div>
            <div class="kpi"><small>Saldo</small><strong class="{{ $balance >= 0 ? 'green' : 'red' }}">{{ $brl($balance) }}</strong></div>
            <div class="kpi"><small>Lançamentos</small><strong>{{ $transactionCount }}</strong></div>
        </section>

        <div class="grid2">
            <section>
                <h2>Fixas × variáveis</h2>
                <table>
                    <thead><tr><th></th><th class="num">Fixas</th><th class="num">Variáveis</th></tr></thead>
                    <tbody>
                        <tr><td>Receitas</td><td class="num">{{ $brl($fixedVsVariable['income']['fixed']) }}</td><td class="num">{{ $brl($fixedVsVariable['income']['variable']) }}</td></tr>
                        <tr><td>Despesas</td><td class="num">{{ $brl($fixedVsVariable['expense']['fixed']) }}</td><td class="num">{{ $brl($fixedVsVariable['expense']['variable']) }}</td></tr>
                    </tbody>
                </table>
            </section>
            <section>
                <h2>Realizado × pendente</h2>
                <table>
                    <thead><tr><th></th><th class="num">Realizado</th><th class="num">Pendente</th></tr></thead>
                    <tbody>
                        <tr><td>Receitas</td><td class="num">{{ $brl($statusBreakdown['income']['paid']) }}</td><td class="num">{{ $brl($statusBreakdown['income']['pending']) }}</td></tr>
                        <tr><td>Despesas</td><td class="num">{{ $brl($statusBreakdown['expense']['paid']) }}</td><td class="num">{{ $brl($statusBreakdown['expense']['pending']) }}</td></tr>
                    </tbody>
                </table>
            </section>
        </div>

        <div class="grid2">
            <section>
                <h2>Despesas por categoria</h2>
                <table>
                    <tbody>
                        @forelse($expenseByCategory as $name => $total)
                            <tr><td>{{ $name }}</td><td class="num">{{ $brl($total) }}</td><td class="num muted">{{ $totalExpenses > 0 ? number_format($total / $totalExpenses * 100, 1, ',', '.') : 0 }}%</td></tr>
                        @empty
                            <tr><td class="muted">Sem despesas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            <section>
                <h2>Por carteira</h2>
                <table>
                    <thead><tr><th>Carteira</th><th class="num">Entradas</th><th class="num">Saídas</th></tr></thead>
                    <tbody>
                        @forelse($byWallet as $name => $data)
                            <tr><td>{{ $name }}</td><td class="num green">{{ $brl($data['income']) }}</td><td class="num red">{{ $brl($data['expense']) }}</td></tr>
                        @empty
                            <tr><td class="muted" colspan="3">Sem lançamentos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        <h2>Extrato</h2>
        <table>
            <thead>
                <tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Carteira</th><th>Situação</th><th class="num">Valor</th></tr>
            </thead>
            <tbody>
                @forelse($transactions->sortBy('date') as $t)
                    <tr>
                        <td>{{ $t->date->format('d/m/Y') }}</td>
                        <td>{{ $t->description }}{{ $t->installment_label ? ' (' . $t->installment_label . ')' : '' }}</td>
                        <td>{{ $t->category->name ?? '—' }}</td>
                        <td>{{ $t->wallet->name ?? '—' }}</td>
                        <td>{{ $t->status_label }}</td>
                        <td class="num {{ $t->isIncome() ? 'green' : 'red' }}">{{ $t->isIncome() ? '+' : '−' }} {{ $brl($t->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Nenhum lançamento no período.</td></tr>
                @endforelse
            </tbody>
        </table>

        <footer>Gerado pelo {{ config('app.name') }} · {{ url('/') }}</footer>
    </main>
</body>
</html>
