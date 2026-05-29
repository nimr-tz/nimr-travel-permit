<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Critical Error Alert</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background: #edf2f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        pre { margin: 0; font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.6; white-space: pre-wrap; word-break: break-all; color: #334155; }
    </style>
</head>
<body style="margin:0;padding:0;background:#edf2f7;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" style="padding:36px 16px 48px;">

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;">

                {{-- Pill label --}}
                <tr>
                    <td align="center" style="padding-bottom:18px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="background:#ffffff;border:1px solid #dbe3ef;border-radius:100px;padding:6px 18px;">
                                    <span style="color:#64748b;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">National Institute for Medical Research · System Alert</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Main card --}}
                <tr>
                    <td style="background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #dbe3ef;box-shadow:0 24px 60px rgba(15,23,42,.12);">

                        {{-- Hero --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="background:#7f1d1d;padding:28px 36px 32px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="vertical-align:middle;">
                                                <p style="margin:0;color:rgba(255,255,255,.95);font-size:13px;font-weight:700;line-height:1.2;">NIMR</p>
                                                <p style="margin:0;color:rgba(255,255,255,.55);font-size:10px;letter-spacing:.09em;text-transform:uppercase;line-height:1.2;">Internal Travel Permit</p>
                                            </td>
                                            <td align="right" style="vertical-align:middle;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);border-radius:100px;padding:5px 14px;">
                                                            <span style="color:#ffffff;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">Critical Alert</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;margin-bottom:16px;">
                                        <tr>
                                            <td width="54" height="54"
                                                style="width:54px;height:54px;background:rgba(255,255,255,.16);border:2px solid rgba(255,255,255,.30);border-radius:50%;text-align:center;vertical-align:middle;font-size:24px;color:#ffffff;font-weight:900;line-height:54px;">
                                                &#9888;
                                            </td>
                                        </tr>
                                    </table>

                                    <h1 style="margin:0 0 10px;color:#ffffff;font-size:27px;line-height:1.2;font-weight:800;letter-spacing:-.02em;">Critical Error Detected</h1>
                                    <p style="margin:0;color:rgba(255,255,255,.70);font-size:14px;line-height:1.75;">
                                        {{ $context['app_name'] }} ({{ $context['app_env'] }}) reported a critical error at {{ $context['occurred_at'] }}.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td height="4" style="background:#dc2626;font-size:0;line-height:0;">&nbsp;</td>
                            </tr>
                        </table>

                        {{-- Body --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="padding:32px 36px 0;">

                                    @foreach ([
                                        'Exception'           => $context['exception'],
                                        'Runtime'             => $context['runtime'],
                                        'Request'             => $context['request'],
                                        'User'                => $context['user'],
                                        'Trace (first 20 frames)' => $context['trace'],
                                    ] + (!empty($context['previous']) ? ['Exception Chain' => $context['previous']] : [])
                                    as $sectionLabel => $sectionData)
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:20px;">
                                        <tr>
                                            <td style="padding-bottom:8px;">
                                                <span style="color:#64748b;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;">{{ $sectionLabel }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="background:#0f172a;border-radius:10px;padding:16px 18px;border:1px solid #1e293b;">
                                                <pre style="color:#e2e8f0;">{{ json_encode($sectionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </td>
                                        </tr>
                                    </table>
                                    @endforeach

                                </td>
                            </tr>

                            {{-- Divider --}}
                            <tr>
                                <td style="padding:8px 36px 0;">
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
                                    <p style="margin:0;color:#94a3b8;font-size:11px;line-height:1.65;">
                                        Automated alert from the NIMR Internal Travel Permit System. This error has been logged. Investigate immediately if this is a production environment.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding-top:22px;">
                        <p style="margin:0;color:#94a3b8;font-size:11px;line-height:1.6;">&copy; {{ date('Y') }} NIMR Tanzania &nbsp;&middot;&nbsp; Internal Travel Permit System</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
