<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $headline }}</title>
</head>
@php
    $palette = [
        'blue' => ['main' => '#05499c', 'soft' => '#eaf2ff', 'text' => '#123866'],
        'green' => ['main' => '#0f8a4b', 'soft' => '#eaf8f0', 'text' => '#14532d'],
        'amber' => ['main' => '#b7791f', 'soft' => '#fff7e6', 'text' => '#7c4a03'],
        'red' => ['main' => '#b42318', 'soft' => '#fff0ee', 'text' => '#7a271a'],
        'slate' => ['main' => '#334155', 'soft' => '#f1f5f9', 'text' => '#0f172a'],
    ][$tone] ?? ['main' => '#05499c', 'soft' => '#eaf2ff', 'text' => '#123866'];
@endphp
<body style="margin:0;background:#eef2f7;color:#0f172a;font-family:Inter,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f7;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dbe3ef;box-shadow:0 18px 45px rgba(15,23,42,.10);">
                    <tr>
                        <td style="background:{{ $palette['main'] }};padding:26px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ asset('NIMR.png') }}" width="54" height="54" alt="NIMR" style="display:block;border-radius:12px;background:#ffffff;padding:6px;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;color:#dbeafe;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">
                                        Internal Travel Permit
                                    </td>
                                </tr>
                            </table>
                            <h1 style="margin:22px 0 8px;color:#ffffff;font-size:25px;line-height:1.25;font-weight:800;">{{ $headline }}</h1>
                            <p style="margin:0;color:#dbeafe;font-size:14px;line-height:1.7;">{{ $intro }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 30px 8px;">
                            <p style="margin:0 0 18px;color:#334155;font-size:15px;line-height:1.7;">
                                Hello {{ $recipient->name }},
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                                @foreach ($details as $label => $value)
                                    @if (filled($value))
                                    <tr>
                                        <td style="width:42%;background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:12px 14px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">
                                            {{ $label }}
                                        </td>
                                        <td style="border-bottom:1px solid #e2e8f0;padding:12px 14px;color:#0f172a;font-size:14px;font-weight:650;">
                                            {{ $value }}
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            </table>

                            @if ($comment)
                            <div style="margin-top:18px;background:{{ $palette['soft'] }};border-left:4px solid {{ $palette['main'] }};border-radius:12px;padding:14px 16px;">
                                <p style="margin:0 0 6px;color:{{ $palette['text'] }};font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;">Comment</p>
                                <p style="margin:0;color:#1e293b;font-size:14px;line-height:1.7;">{{ $comment }}</p>
                            </div>
                            @endif

                            <div style="padding:24px 0 14px;text-align:center;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:{{ $palette['main'] }};color:#ffffff;text-decoration:none;font-weight:800;font-size:14px;padding:13px 22px;border-radius:12px;">
                                    {{ $actionText }}
                                </a>
                            </div>

                            @if ($footnote)
                            <p style="margin:0 0 18px;color:#64748b;font-size:13px;line-height:1.7;text-align:center;">
                                {{ $footnote }}
                            </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 30px;color:#64748b;font-size:12px;line-height:1.6;">
                            This email was sent by the NIMR Internal Travel Permit System. HR receives copies for records only; approval decisions remain with the assigned approvers.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
