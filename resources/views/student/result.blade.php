@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header Card -->
        <div class="p-6 sm:p-8 bg-gradient-to-br from-indigo-700 via-indigo-800 to-indigo-900 rounded-2xl sm:rounded-3xl text-white shadow-xl relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_2px,transparent_2px)]" style="background-size:20px 20px;pointer-events:none"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-white/10 rounded-full text-xs font-bold text-indigo-200 border border-white/5 mb-4 w-fit">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Exam Result
                </div>
                <h1 class="text-xl sm:text-2xl font-black mb-2 break-words">{{ $exam->title }}</h1>
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10 text-center min-w-[120px]">
                        <span class="text-[10px] uppercase tracking-widest text-indigo-200 font-extrabold block mb-1">Score</span>
                        <p class="text-3xl sm:text-4xl font-black text-white">{{ $result->score }} / {{ count($exam->questions) * 5 }}</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10 text-center min-w-[100px]">
                        <span class="text-[10px] uppercase tracking-widest text-indigo-200 font-extrabold block mb-1">Percentage</span>
                        <p class="text-3xl sm:text-4xl font-black text-white">{{ $result->percentage }}%</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10">
                        <span class="text-[10px] uppercase tracking-widest text-indigo-200 font-extrabold block mb-1">Correct</span>
                        <p class="text-lg font-black text-white">{{ $result->correct_answers }} / {{ $result->total_questions }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Questions Review -->
        @if(count($result->failed_questions) > 0)
            <div class="p-5 sm:p-7 bg-white border border-slate-200 rounded-2xl sm:rounded-3xl shadow-sm space-y-5">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <h3 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        Questions to Review
                    </h3>
                    <span class="text-xs bg-rose-50 text-rose-700 py-1.5 px-3.5 rounded-full font-extrabold border border-rose-200">{{ count($result->failed_questions) }} Questions</span>
                </div>
                <div class="space-y-4">
                    @foreach($result->failed_questions as $failed)
                        <div class="p-4 sm:p-5 border border-slate-200 rounded-xl bg-white space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs bg-slate-100 text-slate-600 py-1 px-2.5 rounded-full font-extrabold font-mono">Q{{ $loop->iteration }}</span>
                                <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full border {{ !empty($failed['selectedAnswer']) ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">{{ !empty($failed['selectedAnswer']) ? 'Incorrect' : 'Not Answered' }}</span>
                            </div>
                            <p class="text-sm sm:text-base font-bold text-slate-800 break-words">{{ $failed['question'] }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach(['A','B','C','D'] as $optKey)
                                    @php
                                        $optField = 'option' . $optKey;
                                        $optLabel = $failed[$optField] ?? '';
                                        $isCorrectOpt = $optKey === $failed['correctAnswer'];
                                        $isSelectedOpt = $optKey === ($failed['selectedAnswer'] ?? '');
                                        $optClass = 'border-slate-200 bg-white';
                                        $markerClass = 'bg-slate-100 text-slate-500 border-slate-200';
                                        $badge = '';
                                        if ($isCorrectOpt) { $optClass = 'border-emerald-300 bg-emerald-50/50 ring-1 ring-emerald-200'; $markerClass = 'bg-emerald-500 text-white border-emerald-500'; $badge = '<span class="text-[10px] font-extrabold text-emerald-600 shrink-0 ml-auto">✓ Correct</span>'; }
                                        elseif ($isSelectedOpt && !$isCorrectOpt) { $optClass = 'border-rose-300 bg-rose-50/50 ring-1 ring-rose-200'; $markerClass = 'bg-rose-500 text-white border-rose-500'; $badge = '<span class="text-[10px] font-extrabold text-rose-600 shrink-0 ml-auto">✗ Your Answer</span>'; }
                                    @endphp
                                    <div class="p-3 border rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2.5 transition-all {!! $optClass !!}">
                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center font-mono font-bold shrink-0 text-xs shadow-xs border {!! $markerClass !!}">{{ $optKey }}</span>
                                        <span class="break-words min-w-0 flex-1 leading-snug">{{ $optLabel }}</span>
                                        {!! $badge !!}
                                    </div>
                                @endforeach
                            </div>
                            @if(!empty($failed['explanation']))
                                <div class="p-3 sm:p-4 bg-indigo-50/60 border border-indigo-100 rounded-xl flex items-start gap-3 mt-1">
                                    <svg class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div class="text-xs sm:text-sm text-slate-700 leading-relaxed font-medium break-words min-w-0">
                                        <strong class="text-indigo-700 font-extrabold">Explanation:</strong> {{ $failed['explanation'] }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="p-8 bg-white border border-slate-200 rounded-2xl sm:rounded-3xl shadow-sm text-center">
                <div class="text-5xl mb-4">🎉</div>
                <h3 class="text-lg font-black text-slate-800 mb-2">Perfect Score!</h3>
                <p class="text-sm text-slate-500 font-medium">All questions answered correctly. Excellent work!</p>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('student.dashboard') }}" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Back to Dashboard
            </a>
            <button onclick="window.open('/api/download/exam/{{ $exam->id }}/pdf', '_blank')" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Download Exam PDF
            </button>
            <button onclick="window.open('/api/download/exam/{{ $exam->id }}/docx', '_blank')" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download Exam Word
            </button>
        </div>
    </div>
</div>
@endsection
