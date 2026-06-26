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

    public function apiStudentResults($studentId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $results = array_values(array_filter($db['results'], fn($r) => $r['studentId'] === $studentId));
        return response()->json(['results' => $results]);
    }
}
