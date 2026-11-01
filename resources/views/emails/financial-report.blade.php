@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $r = $report;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório financeiro</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#2563eb;padding:24px;color:#ffffff;">
                        <div style="font-size:20px;font-weight:bold;">{{ config('app.name') }}</div>
                        <div style="font-size:14px;opacity:.9;margin-top:4px;">
                            Relatório de {{ \Carbon\Carbon::parse($r['startDate'])->format('d/m/Y') }}
                            a {{ \Carbon\Carbon::parse($r['endDate'])->format('d/m/Y') }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 16px;">Olá, {{ $user->name }}! Aqui está o resumo do período.</p>

                        <table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
                            <tr style="background:#f9fafb;">
                                <td>Receitas</td>
                                <td align="right" style="color:#16a34a;font-weight:bold;">{{ $brl($r['totalIncome']) }}</td>
                            </tr>
                            <tr>
                                <td>Despesas</td>
                                <td align="right" style="color:#dc2626;font-weight:bold;">{{ $brl($r['totalExpenses']) }}</td>
                            </tr>
                            <tr style="background:#f9fafb;">
                                <td><strong>Saldo do período</strong></td>
                                <td align="right" style="font-weight:bold;color:{{ $r['balance'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ $brl($r['balance']) }}</td>
                            </tr>
                            <tr>
                                <td>Despesas fixas / variáveis</td>
                                <td align="right">{{ $brl($r['fixedVsVariable']['expense']['fixed']) }} / {{ $brl($r['fixedVsVariable']['expense']['variable']) }}</td>
                            </tr>
                            <tr style="background:#f9fafb;">
                                <td>Pendente a pagar / a receber</td>
                                <td align="right">{{ $brl($r['statusBreakdown']['expense']['pending']) }} / {{ $brl($r['statusBreakdown']['income']['pending']) }}</td>
                            </tr>
                        </table>

                        @if(count($r['expenseByCategory']))
                            <h3 style="font-size:15px;margin:24px 0 8px;">Maiores gastos por categoria</h3>
                            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
                                @foreach(collect($r['expenseByCategory'])->take(5) as $name => $total)
                                    <tr style="border-bottom:1px solid #e5e7eb;">
                                        <td>{{ $name }}</td>
                                        <td align="right">{{ $brl($total) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        <p style="margin:24px 0 0;font-size:13px;color:#6b7280;">
                            O extrato completo ({{ $r['transactionCount'] }} lançamentos) está no arquivo CSV anexo — abre direto no Excel ou Google Planilhas.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;background:#f9fafb;font-size:12px;color:#9ca3af;">
                        Você recebeu este e-mail porque solicitou o envio do relatório no {{ config('app.name') }}.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
