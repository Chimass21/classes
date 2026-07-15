<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        return view('landing');
    }

    public function schoolConfig()
    {
        JsonDb::init();
        $db = JsonDb::get();
        return response()->json(['schoolConfig' => $db['schoolConfig']]);
    }

    public function updateSchoolConfig(Request $request)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['schoolConfig'] = $request->all();
        JsonDb::save($db);
        return response()->json(['schoolConfig' => $db['schoolConfig']]);
    }

    public function subjects()
    {
        JsonDb::init();
        return response()->json(['subjects' => JsonDb::get()['subjects']]);
    }

    public function apiTeacherLessonPlans($teacherId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $plans = array_filter($db['lessonPlans'] ?? [], fn($p) => $p['teacherId'] === $teacherId);
        return response()->json(['lessonPlans' => array_values($plans)]);
    }

    public function apiTeacherLessonNotes($teacherId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $notes = array_filter($db['lessonNotes'] ?? [], fn($n) => $n['teacherId'] === $teacherId);
        return response()->json(['lessonNotes' => array_values($notes)]);
    }

    public function apiAllLessonNotes()
    {
        JsonDb::init();
        return response()->json(['lessonNotes' => JsonDb::get()['lessonNotes'] ?? []]);
    }

    public function apiTeacherDashboard()
    {
        JsonDb::init();
        $db = JsonDb::get();
        $user = \Illuminate\Support\Facades\Session::get('user');
        $userId = $user['id'] ?? '';
        return response()->json([
            'plans' => array_values(array_filter($db['lessonPlans'] ?? [], fn($p) => ($p['teacherId'] ?? '') === $userId)),
            'notes' => array_values(array_filter($db['lessonNotes'] ?? [], fn($n) => ($n['teacherId'] ?? '') === $userId)),
            'exams' => array_values(array_filter($db['exams'] ?? [], fn($e) => ($e['creatorId'] ?? '') === $userId)),
            'results' => $db['results'] ?? [],
            'questionSets' => array_values(array_filter($db['questionSets'] ?? [], fn($q) => ($q['teacherId'] ?? '') === $userId)),
            'schoolConfig' => $db['schoolConfig'] ?? [],
        ]);
    }

    public function apiTeacherInit()
    {
        JsonDb::init();
        $db = JsonDb::get();
        $user = \Illuminate\Support\Facades\Session::get('user');
        $userId = $user['id'] ?? '';
        return response()->json([
            'subjects' => $db['subjects'] ?? [],
            'classes' => \App\Helpers\CurriculumData::getClasses(),
            'terms' => \App\Helpers\CurriculumData::getTerms(),
            'weeks' => \App\Helpers\CurriculumData::getWeeks(),
            'plans' => array_values(array_filter($db['lessonPlans'] ?? [], fn($p) => ($p['teacherId'] ?? '') === $userId)),
            'notes' => array_values(array_filter($db['lessonNotes'] ?? [], fn($n) => ($n['teacherId'] ?? '') === $userId)),
            'exams' => array_values(array_filter($db['exams'] ?? [], fn($e) => ($e['creatorId'] ?? '') === $userId)),
            'results' => $db['results'] ?? [],
            'questionSets' => array_values(array_filter($db['questionSets'] ?? [], fn($q) => ($q['teacherId'] ?? '') === $userId)),
            'schoolConfig' => $db['schoolConfig'] ?? [],
        ]);
    }

    public function apiUserNotifications($userId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $notifications = array_values(array_filter($db['notifications'] ?? [], fn($n) => $n['userId'] === $userId));
        return response()->json(['notifications' => $notifications]);
    }

    public function apiReadNotification($id)
    {
        JsonDb::init();
        $db = JsonDb::get();
        foreach ($db['notifications'] as &$n) {
            if ($n['id'] === $id) {
                $n['read'] = true;
                break;
            }
        }
        JsonDb::save($db);
        return response()->json(['success' => true]);
    }

    public function submitFeedback(Request $request)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $feedback = [
            'id' => 'fb_' . uniqid(),
            'name' => $request->input('name', 'Anonymous'),
            'email' => $request->input('email', ''),
            'message' => $request->input('message', ''),
            'date' => now()->toIso8601String(),
        ];
        $db['feedback'][] = $feedback;
        JsonDb::save($db);
        return response()->json(['success' => true, 'feedback' => $feedback]);
    }

    public function sharedNoteView($id)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $note = null;
        foreach ($db['lessonNotes'] ?? [] as $n) {
            if ($n['id'] === $id) { $note = $n; break; }
        }
        if (!$note) {
            abort(404, 'Lesson note not found.');
        }
        return view('shared-note', ['note' => $note]);
    }

    public function chatFeedback(Request $request)
    {
        $message = $request->input('message', '');
        $responses = [
            'lesson' => 'I can help you create lesson notes! Please specify the subject, class, and topic you would like to cover.',
            'exam' => 'For CBT exam creation, you can use the teacher dashboard to build exams with multiple choice questions and set timers.',
            'complaint' => 'I have noted your concern. Please submit a formal ticket using the "Submit Ticket" tab, and Austin will review it shortly.',
            'default' => 'Thank you for your message! I am here to help with lesson plans, CBT exams, and general platform support. Could you please provide more details about what you need?',
        ];
        $lowerMsg = strtolower($message);
        $reply = $responses['default'];
        if (strpos($lowerMsg, 'lesson') !== false || strpos($lowerMsg, 'note') !== false) {
            $reply = $responses['lesson'];
        } elseif (strpos($lowerMsg, 'exam') !== false || strpos($lowerMsg, 'cbt') !== false || strpos($lowerMsg, 'question') !== false) {
            $reply = $responses['exam'];
        } elseif (strpos($lowerMsg, 'complaint') !== false || strpos($lowerMsg, 'issue') !== false || strpos($lowerMsg, 'problem') !== false) {
            $reply = $responses['complaint'];
        }
        return response()->json(['text' => $reply]);
    }
}
