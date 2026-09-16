{{--
    Shared error page. Deliberately self-contained: inline CSS, no @vite, no
    database, no auth() and no route() calls. It is rendered exactly when
    something is already broken (a failed query, a missing build manifest),
    and if this view threw too, Laravel would fall back to its bare default page.

    Expects: $code, $title, $message. Optional: $detail, $retry (bool).
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $title }} · NIMR Travel Permit</title>
    <link rel="icon" type="image/png" href="{{ asset('NIMR.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #05499c;
            --brand-dark: #043a7d;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --surface: #ffffff;
            --page: #f8fafc;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: var(--page);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            color: inherit;
            text-decoration: none;
        }
        .brand img { height: 44px; width: auto; }
        .brand-name { font-size: 14px; font-weight: 700; line-height: 1.2; }
        .brand-sub { font-size: 12px; color: var(--muted); }
        .card {
            width: 100%;
            max-width: 480px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-top: 4px solid var(--brand);
            border-radius: 16px;
            padding: 36px 32px 32px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .06);
            text-align: center;
        }
        .code {
            display: inline-block;
            margin-bottom: 16px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #eff6ff;
            color: var(--brand);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.01em;
            line-height: 1.25;
        }
        p { margin: 0; color: var(--muted); font-size: 15px; line-height: 1.6; }
        .detail {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #334155;
            font-size: 14px;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid transparent;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background-color .15s, border-color .15s;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-secondary { background: #fff; color: #334155; border-color: var(--line); }
        .btn-secondary:hover { background: var(--page); border-color: #cbd5e1; }
        .btn:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }
        footer { margin-top: 24px; font-size: 12px; color: #94a3b8; text-align: center; }
        @media (max-width: 480px) {
            .card { padding: 28px 20px 24px; }
            h1 { font-size: 21px; }
            .actions { flex-direction: column-reverse; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <a class="brand" href="{{ url('/') }}">
        <img src="{{ asset('NIMR.png') }}" alt="NIMR">
        <span>
            <span class="brand-name" style="display:block;">NIMR</span>
            <span class="brand-sub">{{ __('errors.system_name') }}</span>
        </span>
    </a>

    <main class="card" role="main">
        <span class="code">{{ __('errors.code', ['code' => $code]) }}</span>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        @if (!empty($detail))
            <p class="detail">{{ $detail }}</p>
        @endif

        <div class="actions">
            <button type="button" class="btn btn-secondary" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">
                {{ __('errors.go_back') }}
            </button>
            @if (!empty($retry))
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    {{ __('errors.try_again') }}
                </button>
            @else
                <a class="btn btn-primary" href="{{ url('/') }}">{{ __('errors.go_home') }}</a>
            @endif
        </div>
    </main>

    <footer>&copy; {{ date('Y') }} NIMR Tanzania &mdash; {{ __('errors.system_name') }}</footer>
</body>
</html>
