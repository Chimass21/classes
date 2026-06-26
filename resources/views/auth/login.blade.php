@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-slate-100 px-4">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full">
        <div class="text-center mb-6">
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white font-bold text-xl mb-3">A</span>
            <h2 class="text-2xl font-bold text-slate-900">Admin Login</h2>
            <p class="text-sm text-slate-500 mt-1">Enter your credentials to access the dashboard</p>
        </div>
        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username or Email</label>
                <input type="text" name="login" required placeholder="admin or admin@admin.com" value="{{ old('login') }}" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @error('login') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                <input type="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 transition shadow-md">
                Sign In
            </button>
        </form>
        <div class="mt-6 p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Admin Credentials</p>
            <p class="text-sm text-slate-700 font-mono">Username: <span class="font-bold text-indigo-700">admin</span></p>
            <p class="text-sm text-slate-700 font-mono">Password: <span class="font-bold text-indigo-700">admin</span></p>
        </div>
    </div>
</div>
@endsection
