<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Brain4') }}</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 0; background: #f8fafc; color: #111827; }
        .app-shell { display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 2rem; }
        .card { background: #ffffff; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.08); padding: 2rem; max-width: 800px; width: 100%; }
        .branding { color: #4f46e5; margin-bottom: 1rem; }
        .footer { margin-top: 2rem; color: #6b7280; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="app-shell">
        <div class="card">
            @yield('content')
            <div class="footer">Powered by Laravel. Legacy React app remains in the workspace.</div>
        </div>
    </div>
</body>
</html>
