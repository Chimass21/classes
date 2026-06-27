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
        @if(!empty($note['examples']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Examples</h3>
        @foreach($note['examples'] as $ex)
        <div class="p-4 bg-slate-50 border-l-4 border-emerald-500 rounded mb-3">
            <strong class="text-sm">{{ $ex['title'] ?? 'Example' }}:</strong>
            <p class="text-sm mt-1">{{ $ex['description'] ?? '' }}</p>
        </div>
        @endforeach
        @endif
        @if(!empty($note['classroomActivities']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Classroom Activities</h3>
        @foreach($note['classroomActivities'] as $act)
        <div class="mb-3">
            <strong class="text-sm">{{ $act['title'] ?? '' }}:</strong>
            <p class="text-sm mt-1">{{ $act['description'] ?? '' }}</p>
        </div>
        @endforeach
        @endif
        @if(!empty($note['evaluationQuestions']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Evaluation Questions</h3>
        <ol class="text-sm pl-5 space-y-1">
            @foreach($note['evaluationQuestions'] as $eq)
            <li>{{ $eq }}</li>
            @endforeach
        </ol>
        @endif
        @if(!empty($note['summary']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Summary</h3>
        <p class="text-sm">{{ $note['summary'] }}</p>
        @endif
        @if(!empty($note['assignment']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Assignment</h3>
        <div class="text-sm whitespace-pre-wrap">{{ $note['assignment'] }}</div>
        @endif
    </div>
</body>
</html>
