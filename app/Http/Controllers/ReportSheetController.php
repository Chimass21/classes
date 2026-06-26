<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;

class ReportSheetController extends Controller
{
    public function index()
    {
        JsonDb::init();
        $reports = JsonDb::get()['reportSheets'] ?? [];
        return view('teacher.reports', compact('reports'));
    }

    public function apiIndex()
    {
        JsonDb::init();
        return response()->json(['reportSheets' => JsonDb::get()['reportSheets'] ?? []]);
    }

    public function store(Request $request)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $sheet = $request->all();
        $sheet['id'] = 'rpt_' . uniqid();
        $db['reportSheets'][] = $sheet;
        JsonDb::save($db);
        return response()->json(['success' => true, 'reportSheet' => $sheet]);
    }

    public function collate(Request $request)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $collated = $request->input('sheets', []);
        foreach ($collated as $sheet) {
            $sheet['id'] = 'rpt_' . uniqid();
            $db['reportSheets'][] = $sheet;
        }
        JsonDb::save($db);
        return response()->json(['success' => true, 'count' => count($collated)]);
    }

    public function delete(Request $request)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $ids = (array) $request->input('ids', []);
        $db['reportSheets'] = array_values(array_filter($db['reportSheets'] ?? [], fn($r) => !in_array($r['id'] ?? '', $ids)));
        JsonDb::save($db);
        return response()->json(['success' => true]);
    }
}
