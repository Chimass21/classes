<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ExamController extends Controller
{
    public function create()
    {
        return view('teacher.exams.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'duration' => 'required|integer',
            'instructions' => 'nullable|string',
            'questions' => 'required|array',
        ]);

        JsonDb::init();
        $db = JsonDb::get();
        $user = Session::get('user');

        $questions = array_values($request->questions);

        $exam = [
            'id' => 'exam_' . uniqid(),
            'title' => $request->title,
            'subject' => $request->subject,
            'level' => 'Senior Secondary School',
            'duration' => $request->duration,
            'totalMarks' => count($questions) * 5,
            'instructions' => $request->instructions,
            'questions' => $questions,
            'creatorId' => $user['id'],
            'creatorName' => $user['name'],
            'isPublished' => false,
            'createdAt' => now()->toIso8601String(),
        ];
        $db['exams'][] = $exam;
        JsonDb::save($db);
        return redirect()->route('teacher.dashboard')->with('success', 'Exam created successfully!');
    }

    public function publish($examId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        foreach ($db['exams'] as &$exam) {
            if ($exam['id'] === $examId) {
                $exam['isPublished'] = true;
                break;
            }
        }
        JsonDb::save($db);
        return back()->with('success', 'Exam published!');
    }

    public function destroy($examId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['exams'] = array_filter($db['exams'], fn($e) => $e['id'] !== $examId);
        $db['results'] = array_filter($db['results'], fn($r) => $r['examId'] !== $examId);
        JsonDb::save($db);
        return back()->with('success', 'Exam deleted!');
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'duration' => 'required|integer',
            'questions' => 'required|array',
        ]);

        JsonDb::init();
        $db = JsonDb::get();
        $user = Session::get('user');

        $exam = [
            'id' => 'exam_' . uniqid(),
            'title' => $request->title,
            'subject' => $request->subject,
            'level' => $request->level ?? 'Senior Secondary School',
            'duration' => $request->duration,
            'totalMarks' => $request->totalMarks ?? count($request->questions) * 5,
            'instructions' => $request->instructions ?? '',
            'questions' => array_values($request->questions),
            'creatorId' => $user['id'] ?? 'unknown',
            'creatorName' => $user['name'] ?? 'Unknown',
            'isPublished' => false,
            'createdAt' => now()->toIso8601String(),
        ];
        $db['exams'][] = $exam;
        JsonDb::save($db);
        return response()->json(['success' => true, 'exam' => $exam]);
    }

    public function apiPublish($examId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        foreach ($db['exams'] as &$exam) {
            if ($exam['id'] === $examId) {
                $exam['isPublished'] = true;
                break;
            }
        }
        JsonDb::save($db);
        return response()->json(['success' => true]);
    }

    public function apiDestroy($examId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['exams'] = array_values(array_filter($db['exams'], fn($e) => $e['id'] !== $examId));
        $db['results'] = array_values(array_filter($db['results'], fn($r) => $r['examId'] !== $examId));
        JsonDb::save($db);
        return response()->json(['success' => true]);
    }

    public function apiIndex()
    {
        JsonDb::init();
        return response()->json(['exams' => JsonDb::get()['exams']]);
    }

    public function apiShow($examId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $exam = null;
        foreach ($db['exams'] as $e) {
            if ($e['id'] === $examId) {
                $exam = $e;
                break;
            }
        }
        return response()->json($exam);
    }

    public function apiSubmitExam(Request $request, $examId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $exam = null;
        foreach ($db['exams'] as $e) {
            if ($e['id'] === $examId) {
                $exam = $e;
                break;
            }
        }
        if (!$exam) {
            return response()->json(['success' => false, 'error' => 'Exam not found'], 404);
        }

        $answers = $request->input('answers', []);
        $studentId = $request->input('studentId', 'usr_guest');
        $studentName = $request->input('studentName', 'Guest');
        $timeSpent = $request->input('timeSpent', 0);

        $score = 0;
        $correctAnswers = 0;
        $failedQuestions = [];
        $totalMarks = 0;

        $qTypecast = fn($q) => $q + ['marks' => 5, 'correctAnswer' => $q['correctAnswer'] ?? $q['answer'] ?? '', 'explanation' => $q['explanation'] ?? ''];
        foreach ($exam['questions'] as $index => $q) {
            $q = $qTypecast($q);
            $marks = (int)($q['marks'] ?? 5);
            $totalMarks += $marks;
            $selected = is_array($answers[$index] ?? null) ? ($answers[$index]['selectedAnswer'] ?? null) : ($answers[$index] ?? null);
            $correctAnswer = $q['correctAnswer'] ?? $q['answer'] ?? '';
            $isCorrect = $selected !== null && strtoupper((string)$selected) === strtoupper((string)$correctAnswer);
            if ($isCorrect) {
                $score += $marks;
                $correctAnswers++;
            }
            $failedQuestions[] = [
                'question' => $q['question'] ?? '',
                'optionA' => $q['optionA'] ?? '',
                'optionB' => $q['optionB'] ?? '',
                'optionC' => $q['optionC'] ?? '',
                'optionD' => $q['optionD'] ?? '',
                'selectedAnswer' => $selected,
                'correctAnswer' => $correctAnswer,
                'isCorrect' => $isCorrect,
                'marks' => $marks,
                'explanation' => $q['explanation'] ?? "The correct answer is Option {$correctAnswer}.",
                'topic' => $q['topic'] ?? 'General Topic',
            ];
        }

        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100) : 0;

        $result = [
            'id' => 'res_' . uniqid(),
            'examId' => $exam['id'],
            'examTitle' => $exam['title'],
            'subject' => $exam['subject'],
            'studentId' => $studentId,
            'studentName' => $studentName,
            'score' => $score,
            'percentage' => $percentage,
            'totalQuestions' => count($exam['questions']),
            'correctAnswers' => $correctAnswers,
            'failedQuestions' => $failedQuestions,
            'date' => now()->toIso8601String(),
            'timeSpent' => $timeSpent,
            'totalPossibleMarks' => $totalMarks,
        ];

        $db['results'][] = $result;
        JsonDb::save($db);

        return response()->json(['success' => true, 'result' => $result]);
    }
}
