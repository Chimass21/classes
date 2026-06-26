@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white p-8 rounded-3xl border border-slate-150 text-center space-y-6 shadow-xl">
        <h1 class="text-xl font-black text-slate-900">Exam Engine: {{ $examId }}</h1>
        <p class="text-sm text-slate-600">This is the CBT exam engine page.</p>
        <a href="{{ route('landing') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Back to Home</a>
    </div>
</div>
@endsection
