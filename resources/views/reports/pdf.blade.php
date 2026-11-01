@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $period = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' a ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório {{ $period }} — {{ config('app.name') }}</title>
    <style>
        /* Dompdf não suporta flex/grid: layout baseado em tabelas. */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        .muted { color: #6b7280; }
        .green { color: #15803d; }
        .red { color: #b91c1c; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 16px 0 6px; text-transform: uppercase; letter-spacing: .03em; color: #6b7280; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 8px; margin-bottom: 14px; }
        .header td { vertical-align: bottom; }
        .right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .kpis td { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 10px; width: 25%; }
        .kpis small { display: block; color: #6b7280; font-size: 10px; }
        .kpis strong { font-size: 14px; }
        .data th, .data td { padding: 5px 7px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .data th { font-size: 10px; text-transform: uppercase; color: #6b7280; background: #f9fafb; }
        .num { text-align: right; white-space: nowrap; }
        .split td { vertical-align: top; width: 50%; padding-right: 10px; }
        footer { margin-top: 20px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>Relatório financeiro</h1>
                <div class="muted">{{ $period }}{{ $typeFilter ? ' · ' . ($typeFilter === 'income' ? 'Receitas' : 'Despesas') : '' }}{{ $statusFilter ? ' · ' . ($statusFilter === 'paid' ? 'Realizadas' : 'Pendentes') : '' }}</div>
            </td>
            <td class="right">
                <strong>{{ config('app.name') }}</strong>
                <div class="muted">{{ $userName }} · emitido em {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="kpis">
        <tr>
            <td><small>Receitas</small><strong class="green">{{ $brl($totalIncome) }}</strong></td>
            <td><small>Despesas</small><strong class="red">{{ $brl($totalExpenses) }}</strong></td>
            <td><small>Saldo</small><strong class="{{ $balance >= 0 ? 'green' : 'red' }}">{{ $brl($balance) }}</strong></td>
            <td><small>Lançamentos</small><strong>{{ $transactionCount }}</strong></td>
        </tr>
    </table>

    <table class="split">
        <tr>
            <td>
                <h2>Fixas × variáveis</h2>
                <table class="data">
                    <thead><tr><th></th><th class="num">Fixas</th><th class="num">Variáveis</th></tr></thead>
                    <tbody>
                        <tr><td>Receitas</td><td class="num">{{ $brl($fixedVsVariable['income']['fixed']) }}</td><td class="num">{{ $brl($fixedVsVariable['income']['variable']) }}</td></tr>
                        <tr><td>Despesas</td><td class="num">{{ $brl($fixedVsVariable['expense']['fixed']) }}</td><td class="num">{{ $brl($fixedVsVariable['expense']['variable']) }}</td></tr>
                    </tbody>
                </table>
            </td>
            <td>
                <h2>Realizado × pendente</h2>
                <table class="data">
                    <thead><tr><th></th><th class="num">Realizado</th><th class="num">Pendente</th></tr></thead>
                    <tbody>
                        <tr><td>Receitas</td><td class="num">{{ $brl($statusBreakdown['income']['paid']) }}</td><td class="num">{{ $brl($statusBreakdown['income']['pending']) }}</td></tr>
                        <tr><td>Despesas</td><td class="num">{{ $brl($statusBreakdown['expense']['paid']) }}</td><td class="num">{{ $brl($statusBreakdown['expense']['pending']) }}</td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <table class="split">
        <tr>
            <td>
                <h2>Despesas por categoria</h2>
                <table class="data">
                    <tbody>
                        @forelse($expenseByCategory as $name => $total)
                            <tr><td>{{ $name }}</td><td class="num">{{ $brl($total) }}</td><td class="num muted">{{ $totalExpenses > 0 ? number_format($total / $totalExpenses * 100, 1, ',', '.') : 0 }}%</td></tr>
                        @empty
                            <tr><td class="muted">Sem despesas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <h2>Por carteira</h2>
                <table class="data">
                    <thead><tr><th>Carteira</th><th class="num">Entradas</th><th class="num">Saídas</th></tr></thead>
                    <tbody>
                        @forelse($byWallet as $name => $data)
                            <tr><td>{{ $name }}</td><td class="num green">{{ $brl($data['income']) }}</td><td class="num red">{{ $brl($data['expense']) }}</td></tr>
                        @empty
                            <tr><td class="muted" colspan="3">Sem lançamentos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <h2>Extrato</h2>
    <table class="data">
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

    <footer>Gerado pelo {{ config('app.name') }} · Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL</footer>
</body>
</html>
