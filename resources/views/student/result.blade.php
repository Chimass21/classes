@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-slate-800 mb-6">Exam Result</h1>
            
            <div class="mb-8 p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl">
                <h2 class="text-xl font-bold text-slate-800 mb-2">{{ $exam->title }}</h2>
                <p class="text-3xl font-bold text-blue-600 mb-2">{{ $result->score }} / {{ count($exam->questions) * 5 }}</p>
                <p class="text-xl text-slate-700">{{ $result->percentage }}% Correct</p>
                <p class="text-slate-600">{{ $result->correct_answers }} out of {{ $result->total_questions }} questions correct</p>
            </div>

            @if(count($result->failed_questions) > 0)
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Questions to Review:</h3>
                    @foreach($result->failed_questions as $failed)
                        <div class="mb-4 p-4 border border-slate-200 rounded-xl bg-red-50">
                            <p class="font-semibold text-slate-800 mb-2">{{ $failed['question'] }}</p>
                            <p class="text-red-600 mb-1">Your Answer: {{ $failed['selectedAnswer'] }}</p>
                            <p class="text-green-600">Correct Answer: {{ $failed['correctAnswer'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <a href="{{ route('student.dashboard') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
