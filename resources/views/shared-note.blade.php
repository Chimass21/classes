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
        @if(!empty($note['definitions']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Definitions of Key Terms</h3>
        <table class="w-full text-sm border-collapse mb-4">
            @foreach($note['definitions'] as $def)
            <tr class="border-b border-slate-200">
                <td class="py-2 pr-3 font-semibold text-emerald-700 w-1/3">{{ $def['term'] ?? '' }}</td>
                <td class="py-2 text-slate-600">{{ $def['definition'] ?? '' }}</td>
            </tr>
            @endforeach
        </table>
        @endif
        @if(!empty($note['examples']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Examples</h3>
        @foreach($note['examples'] as $ex)
        <div class="p-4 bg-slate-50 border-l-4 border-emerald-500 rounded mb-3">
            <strong class="text-sm">{{ $ex['title'] ?? 'Example' }}:</strong>
            <p class="text-sm mt-1">{{ $ex['description'] ?? '' }}</p>
        </div>
        @endforeach
        @endif
        @if(!empty($note['illustrations']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Illustrations / Diagrams</h3>
        @foreach($note['illustrations'] as $ill)
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg mb-3 text-sm text-slate-600 font-mono">{{ $ill }}</div>
        @endforeach
        @endif
        @if(!empty($note['practicalApplications']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Practical Applications</h3>
        <ul class="text-sm space-y-2 list-disc pl-5 text-slate-600 mb-4">
            @foreach($note['practicalApplications'] as $app)
            <li>{{ $app }}</li>
            @endforeach
        </ul>
        @endif
        @if(!empty($note['advantagesDisadvantages']))
            @if(!empty($note['advantagesDisadvantages']['advantages']))
            <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Advantages</h3>
            <ul class="text-sm space-y-1 list-disc pl-5 text-green-700 mb-3">
                @foreach($note['advantagesDisadvantages']['advantages'] as $adv)
                <li>{{ $adv }}</li>
                @endforeach
            </ul>
            @endif
            @if(!empty($note['advantagesDisadvantages']['disadvantages']))
            <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Disadvantages</h3>
            <ul class="text-sm space-y-1 list-disc pl-5 text-red-700 mb-3">
                @foreach($note['advantagesDisadvantages']['disadvantages'] as $dis)
                <li>{{ $dis }}</li>
                @endforeach
            </ul>
            @endif
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
        @if(!empty($note['keyPoints']))
        <h3 class="text-lg font-bold text-slate-800 mt-6 mb-3">Key Points to Remember</h3>
        <ul class="text-sm space-y-1 list-disc pl-5 text-emerald-700">
            @foreach($note['keyPoints'] as $kp)
            <li>{{ $kp }}</li>
            @endforeach
        </ul>
        @endif
    </div>
</body>
</html>
