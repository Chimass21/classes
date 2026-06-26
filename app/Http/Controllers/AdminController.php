<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    public function dashboard()
    {
        JsonDb::init();
        $db = JsonDb::get();
        self::ensureAdminExists();
        return view('admin.dashboard', ['users' => $db['users']]);
    }

    public static function ensureAdminExists()
    {
        JsonDb::init();
        $db = JsonDb::get();
        $adminExists = false;
        foreach ($db['users'] as $user) {
            if ($user['role'] === 'admin') {
                $adminExists = true;
                break;
            }
        }
        if (!$adminExists) {
            $id = 'usr_' . uniqid();
            $newAdmin = [
                'id' => $id,
                'name' => 'Administrator',
                'email' => 'admin@admin.com',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
                'walletBalance' => 0,
                'isSuspended' => false,
                'createdAt' => now()->toIso8601String(),
            ];
            $db['users'][] = $newAdmin;
            try { JsonDb::createUser($newAdmin); } catch (\Exception $e) {}
            JsonDb::save($db);
        }
    }

    public function apiUsers()
    {
        JsonDb::init();
        return response()->json(['users' => JsonDb::get()['users']]);
    }

    public function apiStats()
    {
        JsonDb::init();
        $db = JsonDb::get();
        return response()->json([
            'users' => $db['users'],
            'documents' => $db['lessonNotes'] ?? [],
            'exams' => $db['exams'],
            'results' => $db['results'],
            'feedback' => $db['feedback'] ?? [],
            'lessonPlans' => $db['lessonPlans'] ?? [],
        ]);
    }

    public function apiActivities()
    {
        JsonDb::init();
        $db = JsonDb::get();
        $activities = [];

        foreach ($db['users'] as $u) {
            $activities[] = [
                'type' => 'user_registered',
                'icon' => 'user',
                'title' => $u['name'] . ' registered as ' . $u['role'],
                'userName' => $u['name'],
                'userRole' => $u['role'],
                'timestamp' => $u['createdAt'] ?? null,
            ];
        }

        foreach ($db['results'] as $r) {
            $student = null;
            foreach ($db['users'] as $u) {
                if ($u['id'] === $r['studentId']) { $student = $u; break; }
            }
            $activities[] = [
                'type' => 'exam_submitted',
                'icon' => 'exam',
                'title' => ($student ? $student['name'] : $r['studentName']) . ' completed ' . ($r['examTitle'] ?? 'an exam') . ' — ' . ($r['percentage'] ?? 0) . '%',
                'userName' => $student['name'] ?? $r['studentName'] ?? 'Unknown',
                'userRole' => 'student',
                'timestamp' => $r['date'] ?? null,
            ];
        }

        foreach ($db['exams'] as $e) {
            $teacher = null;
            foreach ($db['users'] as $u) {
                if ($u['id'] === $e['creatorId']) { $teacher = $u; break; }
            }
            $activities[] = [
                'type' => 'exam_created',
                'icon' => 'exam',
                'title' => ($teacher ? $teacher['name'] : ($e['creatorName'] ?? 'A teacher')) . ' created exam "' . $e['title'] . '"',
                'userName' => $teacher['name'] ?? $e['creatorName'] ?? 'Unknown',
                'userRole' => 'teacher',
                'timestamp' => $e['createdAt'] ?? null,
            ];
        }

        foreach ($db['lessonNotes'] as $n) {
            $teacher = null;
            foreach ($db['users'] as $u) {
                if ($u['id'] === $n['teacherId']) { $teacher = $u; break; }
            }
            $activities[] = [
                'type' => 'lesson_note_created',
                'icon' => 'note',
                'title' => ($teacher ? $teacher['name'] : 'A teacher') . ' created lesson note "' . ($n['topic'] ?? 'Untitled') . '"',
                'userName' => $teacher['name'] ?? 'Unknown',
                'userRole' => 'teacher',
                'timestamp' => $n['createdAt'] ?? null,
            ];
        }

        foreach ($db['lessonPlans'] as $p) {
            $teacher = null;
            foreach ($db['users'] as $u) {
                if ($u['id'] === $p['teacherId']) { $teacher = $u; break; }
            }
            $activities[] = [
                'type' => 'lesson_plan_created',
                'icon' => 'plan',
                'title' => ($teacher ? $teacher['name'] : 'A teacher') . ' created lesson plan "' . ($p['topic'] ?? 'Untitled') . '"',
                'userName' => $teacher['name'] ?? 'Unknown',
                'userRole' => 'teacher',
                'timestamp' => $p['createdAt'] ?? null,
            ];
        }

        foreach ($db['feedback'] ?? [] as $f) {
            $activities[] = [
                'type' => 'feedback_submitted',
                'icon' => 'feedback',
                'title' => ($f['name'] ?? 'Someone') . ' submitted feedback: "' . mb_substr($f['message'] ?? '', 0, 80) . '"',
                'userName' => $f['name'] ?? 'Unknown',
                'userRole' => 'guest',
                'timestamp' => $f['date'] ?? null,
            ];
        }

        usort($activities, function ($a, $b) {
            $ta = $a['timestamp'] ?? '0';
            $tb = $b['timestamp'] ?? '0';
            return strcmp($tb, $ta);
        });

        return response()->json(['activities' => array_slice($activities, 0, 100)]);
    }

    public function apiUpdateUser(Request $request, $userId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $found = false;
        foreach ($db['users'] as &$user) {
            if ($user['id'] === $userId) {
                if ($request->has('isSuspended')) {
                    $user['isSuspended'] = $request->isSuspended;
                }
                if ($request->has('role')) {
                    $user['role'] = $request->role;
                }
                if ($request->has('walletBalance')) {
                    $user['walletBalance'] = (int) $request->walletBalance;
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            return response()->json(['success' => false, 'error' => 'User not found'], 404);
        }
        JsonDb::save($db);
        return response()->json(['success' => true]);
    }

    public function apiDeleteUser($userId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['users'] = array_values(array_filter($db['users'], fn($u) => $u['id'] !== $userId));
        JsonDb::save($db);
        return response()->json(['success' => true]);
    }

    public function apiDeleteFeedback($feedbackId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['feedback'] = array_values(array_filter($db['feedback'] ?? [], fn($f) => $f['id'] !== $feedbackId));
        JsonDb::save($db);
        return response()->json(['success' => true]);
    }
}
