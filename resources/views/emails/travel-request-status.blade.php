<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $headline }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background: #edf2f7; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { text-decoration: none; }
        .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass td { line-height: 100%; }
    </style>
</head>
@php
    $schemes = [
        'blue' => [
            'hero'   => '#05499c',
            'accent' => '#2563eb',
            'soft'   => '#eff6ff',
            'border' => '#bfdbfe',
            'label'  => '#1d4ed8',
            'badge'  => 'Under Review',
            'icon'   => '&#9658;',
        ],
        'green' => [
            'hero'   => '#065f46',
            'accent' => '#059669',
            'soft'   => '#ecfdf5',
            'border' => '#6ee7b7',
            'label'  => '#065f46',
            'badge'  => 'Approved',
            'icon'   => '&#10003;',
        ],
        'amber' => [
            'hero'   => '#78350f',
            'accent' => '#d97706',
            'soft'   => '#fffbeb',
            'border' => '#fcd34d',
            'label'  => '#92400e',
            'badge'  => 'Revision Required',
            'icon'   => '&#8635;',
        ],
        'red' => [
            'hero'   => '#7f1d1d',
            'accent' => '#dc2626',
            'soft'   => '#fef2f2',
            'border' => '#fca5a5',
            'label'  => '#991b1b',
            'badge'  => 'Not Approved',
            'icon'   => '&#10005;',
        ],
        'slate' => [
            'hero'   => '#1e293b',
            'accent' => '#475569',
            'soft'   => '#f8fafc',
            'border' => '#e2e8f0',
            'label'  => '#334155',
            'badge'  => 'Notification',
            'icon'   => '&#8505;',
        ],
    ];
    $s = $schemes[$tone] ?? $schemes['blue'];
@endphp
<body style="margin:0;padding:0;background:#edf2f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" style="padding:36px 16px 48px;">

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;">

                {{-- ── Organisation pill label ──────────────────────────────────────── --}}
                <tr>
                    <td align="center" style="padding-bottom:18px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="background:#ffffff;border:1px solid #dbe3ef;border-radius:100px;padding:6px 18px;">
                                    <span style="color:#64748b;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">National Institute for Medical Research · Tanzania</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ── Main card ────────────────────────────────────────────────────── --}}
                <tr>
                    <td style="background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #dbe3ef;box-shadow:0 24px 60px rgba(15,23,42,.12);">

                        {{-- ── Hero header ──────────────────────────────────────────── --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="background:{{ $s['hero'] }};padding:28px 36px 32px;">

                                    {{-- Top row: logo + status badge --}}
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="vertical-align:middle;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="vertical-align:middle;padding-right:10px;">
                                                            <img src="{{ asset('NIMR.png') }}" width="38" height="38" alt="NIMR"
                                                                 style="display:block;border-radius:8px;background:rgba(255,255,255,.14);padding:4px;">
                                                        </td>
                                                        <td style="vertical-align:middle;">
                                                            <p style="margin:0;color:rgba(255,255,255,.95);font-size:13px;font-weight:700;line-height:1.2;">NIMR</p>
                                                            <p style="margin:0;color:rgba(255,255,255,.55);font-size:10px;letter-spacing:.09em;text-transform:uppercase;line-height:1.2;">Internal Travel Permit</p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="right" style="vertical-align:middle;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);border-radius:100px;padding:5px 14px;">
                                                            <span style="color:#ffffff;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">{{ $s['badge'] }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    {{-- Icon circle --}}
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;margin-bottom:16px;">
                                        <tr>
                                            <td width="54" height="54"
                                                style="width:54px;height:54px;background:rgba(255,255,255,.16);border:2px solid rgba(255,255,255,.30);border-radius:50%;text-align:center;vertical-align:middle;font-size:24px;color:#ffffff;font-weight:900;line-height:54px;">
                                                {!! $s['icon'] !!}
                                            </td>
                                        </tr>
                                    </table>

                                    <h1 style="margin:0 0 10px;color:#ffffff;font-size:27px;line-height:1.2;font-weight:800;letter-spacing:-.02em;">{{ $headline }}</h1>
                                    <p style="margin:0;color:rgba(255,255,255,.70);font-size:14px;line-height:1.75;max-width:480px;">{{ $intro }}</p>
                                </td>
                            </tr>
                            {{-- Accent bar --}}
                            <tr>
                                <td height="4" style="background:{{ $s['accent'] }};font-size:0;line-height:0;">&nbsp;</td>
                            </tr>
                        </table>

                        {{-- ── Body ─────────────────────────────────────────────────── --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="padding:32px 36px 0;">

                                    {{-- Greeting --}}
                                    <p style="margin:0 0 24px;color:#1e293b;font-size:16px;font-weight:600;line-height:1.5;">Hello, {{ $recipient->name }},</p>

                                    {{-- Request number callout --}}
                                    @if (!empty($details['Request number']))
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:20px;">
                                        <tr>
                                            <td style="background:{{ $s['soft'] }};border:1.5px solid {{ $s['border'] }};border-radius:10px;padding:12px 16px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td>
                                                            <p style="margin:0 0 2px;color:{{ $s['label'] }};font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;">Reference Number</p>
                                                            <p style="margin:0;color:#0f172a;font-size:18px;font-weight:800;letter-spacing:.03em;font-family:'Courier New',Courier,monospace;">{{ $details['Request number'] }}</p>
                                                        </td>
                                                        <td align="right" style="vertical-align:middle;">
                                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                                <tr>
                                                                    <td style="background:{{ $s['accent'] }};border-radius:6px;padding:4px 10px;">
                                                                        <span style="color:#ffffff;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;">{{ $travelRequest->statusLabel() }}</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    @endif

                                    {{-- Details table --}}
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                           style="border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                                        {{-- Table header --}}
                                        <tr>
                                            <td colspan="2" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:10px 16px;">
                                                <span style="color:#94a3b8;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;">Trip Details</span>
                                            </td>
                                        </tr>
                                        @foreach ($details as $label => $value)
                                            @if (filled($value) && $label !== 'Request number')
                                            <tr>
                                                <td style="width:36%;background:{{ $loop->even ? '#ffffff' : '#f8fafc' }};border-bottom:1px solid #f1f5f9;padding:11px 16px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;vertical-align:top;">
                                                    {{ $label }}
                                                </td>
                                                <td style="background:{{ $loop->even ? '#ffffff' : '#f8fafc' }};border-bottom:1px solid #f1f5f9;padding:11px 16px;color:#0f172a;font-size:14px;font-weight:600;vertical-align:top;">
                                                    {{ $value }}
                                                </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </table>

                                    {{-- Approver comment --}}
                                    @if ($comment)
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;">
                                        <tr>
                                            <td style="background:{{ $s['soft'] }};border-left:4px solid {{ $s['accent'] }};border-radius:0 12px 12px 0;padding:16px 18px;">
                                                <p style="margin:0 0 6px;color:{{ $s['label'] }};font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;">Approver Comment</p>
                                                <p style="margin:0;color:#334155;font-size:14px;line-height:1.8;font-style:italic;">&ldquo;{{ $comment }}&rdquo;</p>
                                            </td>
                                        </tr>
                                    </table>
                                    @endif

                                </td>
                            </tr>

                            {{-- CTA button --}}
                            <tr>
                                <td style="padding:28px 36px 8px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td align="center">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="background:{{ $s['hero'] }};border-radius:12px;">
                                                            <!--[if mso]><i style="mso-font-width:50%;letter-spacing:14px;" hidden>&nbsp;</i><![endif]-->
                                                            <a href="{{ $actionUrl }}"
                                                               style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;letter-spacing:.01em;border-radius:12px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                                                {{ $actionText }}&nbsp;&nbsp;&rarr;
                                                            </a>
                                                            <!--[if mso]><i style="mso-font-width:50%;letter-spacing:14px;" hidden>&nbsp;</i><![endif]-->
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        @if ($footnote)
                                        <tr>
                                            <td align="center" style="padding-top:14px;">
                                                <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.7;text-align:center;max-width:460px;">{{ $footnote }}</p>
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>

                            {{-- Divider --}}
                            <tr>
                                <td style="padding:16px 36px 0;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td height="1" style="background:#f1f5f9;font-size:0;line-height:0;">&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            {{-- Footer --}}
                            <tr>
                                <td style="padding:20px 36px 28px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="vertical-align:top;padding-right:14px;" width="36">
                                                <img src="{{ asset('NIMR.png') }}" width="32" height="32" alt="NIMR"
                                                     style="display:block;border-radius:6px;opacity:.45;">
                                            </td>
                                            <td style="vertical-align:top;">
                                                <p style="margin:0 0 4px;color:#475569;font-size:12px;font-weight:700;line-height:1.4;">National Institute for Medical Research</p>
                                                <p style="margin:0;color:#94a3b8;font-size:11px;line-height:1.65;">
                                                    This is an automated notification from the NIMR Internal Travel Permit System.
                                                    Do not reply to this email. HR receives copies of all requests for records purposes only —
                                                    approval decisions are made by designated approvers.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>

                {{-- Below-card copyright --}}
                <tr>
                    <td align="center" style="padding-top:22px;">
                        <p style="margin:0;color:#94a3b8;font-size:11px;line-height:1.6;">
                            &copy; {{ date('Y') }} NIMR Tanzania &nbsp;&middot;&nbsp; Internal Travel Permit System
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
