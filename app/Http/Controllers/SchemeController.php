<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use App\Helpers\CurriculumData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SchemeController extends Controller
{
    public function upload(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string',
            'class' => 'required|string',
            'term' => 'required|string',
            'file' => 'required|file|mimes:pdf,doc,docx|max:20480',
            'topics' => 'nullable|string',
        ]);

        $user = Session::get('user');
        $file = $request->file('file');
        $path = $file->store('schemes', 'local');

        $topics = [];
        if (!empty($data['topics'])) {
            $lines = explode("\n", $data['topics']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                if (preg_match('/^(?:Week\s*)?(\d+)\s*[:\.-]?\s*(.+)$/i', $line, $m)) {
                    $topics[] = ['week' => (int)$m[1], 'topic' => trim($m[2])];
                } else {
                    $topics[] = ['week' => count($topics) + 1, 'topic' => $line];
                }
            }
        }

        JsonDb::init();
        $db = JsonDb::get();
        $scheme = [
            'id' => 'sow_' . uniqid(),
            'subject' => $data['subject'],
            'class' => $data['class'],
            'term' => $data['term'],
            'fileName' => $file->getClientOriginalName(),
            'filePath' => $path,
            'fileSize' => $file->getSize(),
            'topics' => $topics,
            'uploadedBy' => $user['name'] ?? 'Unknown',
            'uploadedAt' => now()->toIso8601String(),
        ];
        $db['schemes'][] = $scheme;
        JsonDb::save($db);

        return response()->json([
            'success' => true,
            'scheme' => $scheme,
            'message' => 'Scheme of Work uploaded successfully.',
        ]);
    }

    public function list()
    {
        JsonDb::init();
        $db = JsonDb::get();
        $schemes = array_reverse($db['schemes'] ?? []);
        return response()->json(['schemes' => $schemes]);
    }

    public function delete($id)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $found = false;
        foreach ($db['schemes'] as $i => $s) {
            if ($s['id'] === $id) {
                $path = storage_path('app/' . ($s['filePath'] ?? ''));
                if (file_exists($path)) @unlink($path);
                array_splice($db['schemes'], $i, 1);
                $found = true;
                break;
            }
        }
        if (!$found) {
            return response()->json(['success' => false, 'error' => 'Scheme not found.'], 404);
        }
        JsonDb::save($db);
        return response()->json(['success' => true, 'message' => 'Scheme of Work deleted.']);
    }

    public function getTopics(Request $request)
    {
        $subject = $request->query('subject');
        $class = $request->query('class');
        $term = $request->query('term');
        $week = $request->query('week');

        if (!$subject || !$class || !$term) {
            return response()->json(['success' => false, 'error' => 'subject, class, and term are required.'], 400);
        }

        JsonDb::init();
        $db = JsonDb::get();
        $scheme = null;
        foreach (array_reverse($db['schemes'] ?? []) as $s) {
            if (
                strtolower($s['subject']) === strtolower($subject) &&
                strtolower($s['class']) === strtolower($class) &&
                strtolower($s['term']) === strtolower($term)
            ) {
                $scheme = $s;
                break;
            }
        }

        $allTopics = [];
        if ($scheme && !empty($scheme['topics'])) {
            $allTopics = $scheme['topics'];
        } else {
            $builtIn = CurriculumData::getSchemeOfWork($subject, $class, $term);
            foreach ($builtIn as $b) {
                $allTopics[] = ['week' => $b['week'], 'topic' => $b['topic']];
            }
        }

        if ($week) {
            $weekVal = (int)$week;
            foreach ($allTopics as $t) {
                if ((int)$t['week'] === $weekVal) {
                    return response()->json(['success' => true, 'topic' => $t['topic'], 'week' => $weekVal]);
                }
            }
            return response()->json(['success' => true, 'topic' => '', 'week' => $weekVal]);
        }

        return response()->json(['success' => true, 'topics' => $allTopics, 'schemeName' => $scheme['fileName'] ?? '']);
    }
}
