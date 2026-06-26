@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-8 shadow-sm">
            <h1 class="text-4xl font-semibold text-slate-900">ClassPortal Laravel + Tailwind</h1>
            <p class="mt-4 text-slate-600">This is the Laravel version of your project. The original workspace is preserved in <code>legacy-app/</code>.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Laravel Setup</h2>
                <ul class="mt-4 space-y-3 text-slate-600">
                    <li><strong>Routes:</strong> <code>routes/web.php</code></li>
                    <li><strong>Controller:</strong> <code>app/Http/Controllers/HomeController.php</code></li>
                    <li><strong>Views:</strong> <code>resources/views/</code></li>
                    <li><strong>Styles:</strong> <code>resources/css/app.css</code></li>
                </ul>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Legacy Project</h2>
                <p class="mt-4 text-slate-600">The original app with React, Vite, and Supabase support is copied into the <code>legacy-app/</code> folder.</p>
            </div>
        </div>
    </div>
@endsection
