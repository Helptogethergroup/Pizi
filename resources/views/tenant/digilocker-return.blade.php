<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Aadhaar check | Pizi</title>
<style>
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #fefcf6; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #0a1a30; }
    .card { max-width: 420px; margin: 24px; padding: 32px 28px; background: #fff; border: 1px solid #e7e2d6; border-radius: 22px; text-align: center; }
    .dot { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #fff; background: {{ $done ? '#10b981' : ($failed ? '#ef4444' : '#f59e0b') }}; }
    h1 { font-size: 22px; margin: 0 0 8px; }
    p { color: #64748b; line-height: 1.5; margin: 0; }
</style>
</head>
<body>
<div class="card">
    <div class="dot">{{ $done ? '✓' : ($failed ? '!' : '…') }}</div>
    @if($done)
        <h1>Aadhaar verified</h1>
        <p>Your Aadhaar check is done. You can close this page and go back to the Pizi app.</p>
    @elseif($failed)
        <h1>The check was not completed</h1>
        <p>Nothing was shared. Go back to the Pizi app and try again.</p>
    @else
        <h1>We are checking</h1>
        <p>This can take a moment. Go back to the Pizi app, it will show the result.</p>
    @endif
</div>
</body>
</html>
