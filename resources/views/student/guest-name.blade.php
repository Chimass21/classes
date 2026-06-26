@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4">
    <form method="GET" action="{{ route('student.exam.start', ['examId' => $exam->id]) }}" class="max-w-md w-full bg-white p-8 rounded-3xl border border-slate-150 text-center space-y-6 shadow-xl">
        <div class="w-16 h-16 bg-gradient-to-tr from-violet-600 to-indigo-600 text-white rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-md">
            ✍
        </div>
        <div class="space-y-1">
            <h1 class="text-xl font-black text-slate-900">Enter Your Name & Start Exam</h1>
            <p class="text-xs text-slate-500 font-medium">
                Please enter your name below to unlock your standard CBT exam access immediately.
            </p>
        </div>

        @if(session('error'))
        <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs text-left rounded-2xl font-bold">
            {{ session('error') }}
        </div>
        @endif

        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-left space-y-2 text-xs">
            <p class="font-semibold text-slate-600"><strong class="text-slate-800">CBT Exam:</strong> {{ $exam->title }}</p>
            <p class="font-semibold text-slate-600"><strong class="text-slate-800">Subject:</strong> {{ $exam->subject }}</p>
            <p class="font-semibold text-slate-600"><strong class="text-slate-800">Duration:</strong> {{ $exam->duration }} Minutes</p>
            <p class="font-bold text-emerald-600 p-1 bg-emerald-50/50 rounded-lg text-[10px] uppercase tracking-wider block text-center">CBT Standard Taking: 100% FREE</p>
        </div>

        <div class="space-y-2 text-left">
            <label class="text-xs font-black text-slate-600 uppercase tracking-wider block">
                Your Full Name:
            </label>
            <input type="text" name="name" required placeholder="e.g., John Doe"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition text-sm" />
        </div>

        <div class="flex flex-col gap-2 pt-2">
            <button type="submit"
                class="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-extrabold text-sm rounded-xl shadow-lg transition cursor-pointer">
                Start Exam (Free)
            </button>
            <a href="{{ route('landing') }}"
                class="w-full block py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition text-center">
                Cancel and Go Back
            </a>
        </div>

        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
            Secure CBT Engine by ClassPortal
        </p>
    </form>
</div>
@endsection
