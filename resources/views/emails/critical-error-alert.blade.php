<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Critical Error Alert</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <h1 style="margin-bottom: 0;">Critical Error Alert</h1>
    <p style="margin-top: 4px; color: #475569;">
        {{ $context['app_name'] }} ({{ $context['app_env'] }}) reported a critical error at {{ $context['occurred_at'] }}.
    </p>

    <h2>Exception</h2>
    <pre style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">{{ json_encode($context['exception'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

    <h2>Runtime</h2>
    <pre style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">{{ json_encode($context['runtime'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

    <h2>Request</h2>
    <pre style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">{{ json_encode($context['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

    <h2>User</h2>
    <pre style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">{{ json_encode($context['user'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

    @if(!empty($context['previous']))
        <h2>Exception Chain</h2>
        <pre style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">{{ json_encode($context['previous'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    @endif

    <h2>Trace (first 20 frames)</h2>
    <pre style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">{{ json_encode($context['trace'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
