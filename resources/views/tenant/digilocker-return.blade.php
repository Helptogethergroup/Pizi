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
    .dot { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #fff; background: {{ ($done || (!$failed && !empty($ownerFlow))) ? '#10b981' : ($failed ? '#ef4444' : '#f59e0b') }}; }
    h1 { font-size: 22px; margin: 0 0 8px; }
    p { color: #64748b; line-height: 1.5; margin: 0; }
</style>
</head>
<body>
<div class="card">
    <div class="dot">{{ ($done || (!$failed && !empty($ownerFlow))) ? '✓' : ($failed ? '!' : '…') }}</div>
    @if($done)
        <h1>Aadhaar verified</h1>
        <p>Your Aadhaar check is done. You can close this page and go back to the Pizi app.</p>
        <p style="margin-top:16px"><a href="{{ url('/tenant/dashboard') }}" style="color:#ed4e3d;font-weight:700">Or continue on pizi.in →</a></p>
    @elseif($failed)
        <h1>The check was not completed</h1>
        <p>Nothing was shared. Go back to the Pizi app and try again.</p>
        <p style="margin-top:16px"><a href="{{ url('/tenant/kyc') }}" style="color:#ed4e3d;font-weight:700">Or go to your KYC page on pizi.in →</a></p>
    @elseif(!empty($ownerFlow))
        <h1>Aadhaar shared</h1>
        <p>Done. Go back to the Pizi Owner app: the tenant's details fill in there by themselves.</p>
        <p style="margin-top:18px">
            <a href="intent://open#Intent;package=com.pizi_owner.india;end"
               style="display:inline-block;background:#ed4e3d;color:#fff;font-weight:700;padding:12px 22px;border-radius:12px;text-decoration:none">Open the Pizi Owner app</a>
        </p>
        <p style="margin-top:14px;font-size:13px">If the button does nothing, just close this page and open the Pizi Owner app.</p>
    @else
        <h1>Thank you</h1>
        <p>You can close this page now. Go back to the Pizi app or website, or tell the person who sent you this link. The result shows up there.</p>
    @endif
</div>
</body>
</html>
