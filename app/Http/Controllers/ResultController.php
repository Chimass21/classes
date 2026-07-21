<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function apiIndex()
    {
        JsonDb::init();
        return response()->json(['results' => JsonDb::get()['results']]);
    }

    public function apiShow($resultId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        foreach ($db['results'] as $result) {
            if (($result['id'] ?? '') === $resultId) {
                // Attach exam questions for answer review
                $exam = null;
                foreach ($db['exams'] as $e) {
                    if (($e['id'] ?? '') === ($result['examId'] ?? '')) {
                        $exam = $e;
                        break;
                    }
                }
                return response()->json([
                    'success' => true,
                    'result' => $result,
                    'exam' => $exam,
                ]);
            }
        }
        return response()->json(['success' => false, 'error' => 'Result not found.'], 404);
    }

    public function apiStudentResults($studentId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $results = array_values(array_filter($db['results'], fn($r) => ($r['studentId'] ?? '') === $studentId));
        return response()->json(['results' => $results]);
    }
}
