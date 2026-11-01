<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convite para carteira compartilhada</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#039855;padding:24px;color:#ffffff;">
                        <div style="font-size:20px;font-weight:bold;">{{ config('app.name') }}</div>
                        <div style="font-size:14px;opacity:.9;margin-top:4px;">Convite para carteira compartilhada</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 16px;">Olá, {{ $inviteeName }}!</p>
                        <p style="margin:0 0 16px;line-height:1.5;">
                            <strong>{{ $inviterName }}</strong> convidou você para acompanhar a carteira
                            <strong>{{ $walletName }}</strong> no {{ config('app.name') }}, com o nível de acesso
                            <strong>{{ $roleLabel }}</strong>.
                        </p>
                        <p style="margin:0 0 24px;line-height:1.5;">
                            @if($needsRegistration)
                                Você ainda não tem uma conta no {{ config('app.name') }}. Crie a sua gratuitamente
                                usando este e-mail e o convite será vinculado automaticamente.
                            @else
                                Para aceitar, acesse sua conta e confirme o convite na página de carteiras.
                            @endif
                        </p>
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="border-radius:10px;background:#039855;">
                                    <a href="{{ $actionUrl }}"
                                       style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;">
                                        {{ $needsRegistration ? 'Criar conta e aceitar' : 'Ver convite' }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:24px 0 0;font-size:13px;color:#6b7280;line-height:1.5;">
                            Se você não reconhece este convite, pode ignorar este e-mail com segurança —
                            nada será compartilhado até que você aceite.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;background:#f9fafb;font-size:12px;color:#9ca3af;">
                        Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
