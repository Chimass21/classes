<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TeacherController extends Controller
{
    public function dashboard()
    {
        JsonDb::init();
        $db = JsonDb::get();
        $user = Session::get('user');
        $exams = array_filter($db['exams'], fn($e) => $e['creatorId'] === $user['id']);
        $results = array_filter($db['results'], fn($r) => in_array($r['examId'], array_column($exams, 'id')));
        $lessonPlans = array_filter($db['lessonPlans'] ?? [], fn($lp) => $lp['teacherId'] === $user['id']);
        $lessonNotes = array_filter($db['lessonNotes'] ?? [], fn($ln) => $ln['teacherId'] === $user['id']);
        return view('teacher.dashboard', [
            'exams' => $exams,
            'results' => $results,
            'lessonPlans' => $lessonPlans,
            'lessonNotes' => $lessonNotes,
        ]);
    }
}
