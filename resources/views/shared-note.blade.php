<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $note['topic'] ?? 'Lesson Note' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6">
    <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <div class="text-center border-b-2 border-emerald-600 pb-4 mb-6">
            <h1 class="text-2xl font-bold text-emerald-700">{{ $note['topic'] ?? '' }}</h1>
            <p class="text-sm text-slate-500">
                {{ $note['subject'] ?? '' }} | {{ $note['class'] ?? '' }} | {{ $note['term'] ?? '' }} | Week {{ $note['week'] ?? '' }} | {{ $note['periods'] ?? '' }}
            </p>
        </div>
        <div class="prose max-w-none text-sm">
            {!! $note['content'] ?? '' !!}
        </div>

        @if(!empty($note['sections']))
            @foreach($note['sections'] as $section)
                @if(!empty($section['heading']) && !empty($section['content']))
                <div class="mt-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-3">{{ $section['heading'] }}</h3>
                    <div class="text-sm">{!! $section['content'] !!}</div>
                </div>
                @endif
            @endforeach
        @endif

        @if(!empty($note['evaluationQuestions']))
        <div class="mt-6">
            <h3 class="text-lg font-bold text-slate-800 mb-3">Evaluation Questions</h3>
            <ol class="text-sm pl-5 space-y-1">
                @foreach($note['evaluationQuestions'] as $eq)
                <li>{{ $eq }}</li>
                @endforeach
            </ol>
        </div>
        @endif

        @if(!empty($note['assignment']))
        <div class="mt-6">
            <h3 class="text-lg font-bold text-slate-800 mb-3">Assignment</h3>
            <div class="text-sm">{{ $note['assignment'] }}</div>
        </div>
        @endif

        @if(!empty($note['keyPoints']))
        <div class="mt-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
            <h3 class="text-sm font-bold text-emerald-700 mb-2">Key Points to Remember</h3>
            <ul class="text-sm space-y-1 list-disc pl-5 text-emerald-700">
                @foreach($note['keyPoints'] as $kp)
                <li>{{ $kp }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</body>
</html>
