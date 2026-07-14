<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CsvImportController extends Controller
{
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="question_import_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Question', 'Option A', 'Option B', 'Option C', 'Option D',
            'Correct Answer', 'Explanation', 'Marks', 'Difficulty', 'Topic', 'Sub Topic', 'Image URL'
        ];

        $sample = [
            'What is the capital of Nigeria?',
            'Lagos', 'Abuja', 'Kano', 'Ibadan',
            'B', 'Abuja is the capital city of Nigeria.', '1', 'Easy', 'Geography', '', ''
        ];

        $sample2 = [
            'Which planet is known as the Red Planet?',
            'Earth', 'Mars', 'Venus', 'Jupiter',
            'B', 'Mars is called the Red Planet due to its reddish appearance.', '1', 'Easy', 'Space', '', ''
        ];

        $callback = function () use ($columns, $sample, $sample2) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns);
            fputcsv($handle, $sample);
            fputcsv($handle, $sample2);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'subject' => 'required|string',
            'class' => 'required|string',
            'term' => 'required|string',
            'session' => 'required|string',
            'exam_type' => 'required|string',
            'topic' => 'nullable|string',
            'subTopic' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headerLine = fgetcsv($handle);
        if (!$headerLine) {
            fclose($handle);
            return response()->json(['success' => false, 'error' => 'Empty or invalid CSV file.'], 400);
        }

        $header = array_map('trim', $headerLine);
        $expectedHeaders = ['Question', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer'];
        $headerMap = array_flip($header);

        $missingHeaders = [];
        foreach ($expectedHeaders as $h) {
            if (!isset($headerMap[$h])) {
                $missingHeaders[] = $h;
            }
        }
        if (!empty($missingHeaders)) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'error' => 'Missing required columns: ' . implode(', ', $missingHeaders) . '. Required: Question, Option A, Option B, Option C, Option D, Correct Answer.',
            ], 400);
        }

        $rows = [];
        $errors = [];
        $rowIndex = 0;
        $validCount = 0;
        $totalProcessed = 0;

        $optAIdx = $headerMap['Option A'] ?? null;
        $optBIdx = $headerMap['Option B'] ?? null;
        $optCIdx = $headerMap['Option C'] ?? null;
        $optDIdx = $headerMap['Option D'] ?? null;
        $qIdx = $headerMap['Question'] ?? null;
        $caIdx = $headerMap['Correct Answer'] ?? null;
        $explIdx = $headerMap['Explanation'] ?? null;
        $marksIdx = $headerMap['Marks'] ?? null;
        $diffIdx = $headerMap['Difficulty'] ?? null;
        $topicIdx = $headerMap['Topic'] ?? null;
        $imgIdx = $headerMap['Image URL'] ?? null;

        while (($line = fgetcsv($handle)) !== false) {
            $rowIndex++;
            $totalProcessed = $rowIndex;

            $question = trim($line[$qIdx] ?? '');
            if ($question === '') continue;

            $correctAnswer = strtoupper(trim($line[$caIdx] ?? ''));
            $optionA = trim($line[$optAIdx] ?? '');
            $optionB = trim($line[$optBIdx] ?? '');
            $optionC = $optCIdx !== null ? trim($line[$optCIdx] ?? '') : '';
            $optionD = $optDIdx !== null ? trim($line[$optDIdx] ?? '') : '';

            $rowErrors = [];

            if (empty($question)) {
                $rowErrors[] = 'Question text is required.';
            }
            if (empty($optionA) && empty($optionB) && empty($optionC) && empty($optionD)) {
                $rowErrors[] = 'At least one option is required.';
            }
            if (!in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
                $rowErrors[] = 'Correct Answer must be A, B, C, or D.';
            }

            if ($marksIdx !== null) {
                $marks = trim($line[$marksIdx] ?? '');
                if ($marks !== '' && (!is_numeric($marks) || (int)$marks < 0)) {
                    $rowErrors[] = 'Marks must be a positive number.';
                }
            }
            if ($diffIdx !== null) {
                $difficulty = trim($line[$diffIdx] ?? '');
                if ($difficulty !== '' && !in_array(strtolower($difficulty), ['easy', 'medium', 'hard'])) {
                    $rowErrors[] = 'Difficulty must be Easy, Medium, or Hard.';
                }
            }

            if (!empty($rowErrors)) {
                $errors[$rowIndex] = $rowErrors;
            }

            if (empty($rowErrors)) {
                $validCount++;
            }

            // Only build full row objects for the first 100 rows (preview limit)
            // to avoid processing thousands of rows that won't be returned.
            if (count($rows) < 100) {
                $rows[] = [
                    'row' => $rowIndex,
                    'question' => $question,
                    'optionA' => $optionA,
                    'optionB' => $optionB,
                    'optionC' => $optionC,
                    'optionD' => $optionD,
                    'correctAnswer' => $correctAnswer,
                    'explanation' => $explIdx !== null ? trim($line[$explIdx] ?? '') : '',
                    'marks' => ($marksIdx !== null && is_numeric(trim($line[$marksIdx] ?? ''))) ? (int)trim($line[$marksIdx]) : 1,
                    'difficulty' => $diffIdx !== null ? ucfirst(strtolower(trim($line[$diffIdx] ?? ''))) : 'Medium',
                    'topic' => $topicIdx !== null ? trim($line[$topicIdx] ?? '') : '',
                    'imageUrl' => $imgIdx !== null ? trim($line[$imgIdx] ?? '') : '',
                    'valid' => empty($rowErrors),
                    'errors' => $rowErrors,
                ];
            }

            if ($totalProcessed > 5000) {
                $errors['_limit'] = 'File exceeds maximum of 5000 questions.';
                break;
            }
        }

        fclose($handle);

        // Count duplicates using a hash set — O(n) instead of O(n*m)
        $duplicateCount = 0;
        if ($validCount > 0) {
            JsonDb::init();
            $db = JsonDb::get();
            $subjectKey = strtolower(trim($request->subject));
            $existingSet = [];
            foreach ($db['exams'] as $exam) {
                if (strtolower(trim($exam['subject'] ?? '')) !== $subjectKey) continue;
                foreach ($exam['questions'] as $eq) {
                    $key = strtolower(trim($eq['question'] ?? ''));
                    if ($key !== '') $existingSet[$key] = true;
                }
            }
            // Re-count valid rows properly (we stopped counting after 100)
            // Use the total processed count for a more accurate estimate
            // but only check rows we have (up to 100 for preview)
            foreach ($rows as $row) {
                if (!$row['valid']) continue;
                $key = strtolower(trim($row['question']));
                if (isset($existingSet[$key])) {
                    $duplicateCount++;
                }
            }
            // Estimate remaining duplicates based on ratio
            if ($totalProcessed > 100 && $validCount > count($rows)) {
                $ratio = $duplicateCount / max(1, count($rows));
                $duplicateCount = (int)round($ratio * $validCount);
            }
        }

        return response()->json([
            'success' => true,
            'total_rows' => $totalProcessed,
            'valid_rows' => $validCount,
            'error_rows' => $totalProcessed - $validCount,
            'duplicate_count' => $duplicateCount,
            'errors' => $errors,
            'rows' => $rows,
            'has_more' => $totalProcessed > 100,
            'total_all_rows' => $totalProcessed,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'subject' => 'required|string',
            'class' => 'required|string',
            'term' => 'required|string',
            'session' => 'required|string',
            'exam_type' => 'required|string',
            'topic' => 'nullable|string',
            'subTopic' => 'nullable|string',
            'duplicate_handling' => 'required|in:skip,replace,import_all',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headerLine = fgetcsv($handle);
        if (!$headerLine) {
            fclose($handle);
            return response()->json(['success' => false, 'error' => 'Invalid CSV file.'], 400);
        }

        $header = array_map('trim', $headerLine);
        $headerMap = array_flip($header);

        $user = Session::get('user');

        JsonDb::init();
        $db = JsonDb::get();

        // Build an indexed set of existing question texts for this subject
        // O(1) lookup per row instead of scanning all exams each time.
        $subjectKey = strtolower(trim($request->subject));
        $existingQuestionIndex = [];
        $examWithQuestion = []; // question text -> [examId, questionIdx]
        if ($request->duplicate_handling !== 'import_all') {
            foreach ($db['exams'] as $exam) {
                if (strtolower(trim($exam['subject'] ?? '')) !== $subjectKey) continue;
                foreach ($exam['questions'] as $qIdx => $eq) {
                    $key = strtolower(trim($eq['question'] ?? ''));
                    if ($key !== '') {
                        $existingQuestionIndex[$key] = true;
                        $examWithQuestion[$key] = ['examId' => $exam['id'], 'questionIndex' => $qIdx];
                    }
                }
            }
        }

        $imported = [];
        $skipped = [];
        $replaced = [];
        $errors = [];
        $rowIndex = 0;

        $examTitle = $request->subject . ' ' . $request->class . ' ' . $request->term . ' (' . $request->session . ')';
        if ($request->topic) {
            $examTitle .= ' - ' . $request->topic;
        }

        $optAIdx = $headerMap['Option A'] ?? null;
        $optBIdx = $headerMap['Option B'] ?? null;
        $optCIdx = $headerMap['Option C'] ?? null;
        $optDIdx = $headerMap['Option D'] ?? null;
        $qIdx = $headerMap['Question'] ?? null;
        $caIdx = $headerMap['Correct Answer'] ?? null;
        $explIdx = $headerMap['Explanation'] ?? null;
        $marksIdx = $headerMap['Marks'] ?? null;
        $diffIdx = $headerMap['Difficulty'] ?? null;
        $topicIdx = $headerMap['Topic'] ?? null;
        $imgIdx = $headerMap['Image URL'] ?? null;

        $handleReplace = $request->duplicate_handling === 'replace';

        while (($line = fgetcsv($handle)) !== false) {
            $rowIndex++;

            $question = trim($line[$qIdx] ?? '');
            if ($question === '') continue;

            $correctAnswer = strtoupper(trim($line[$caIdx] ?? ''));
            if (!in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
                $errors[$rowIndex] = ['Correct Answer must be A, B, C, or D.'];
                continue;
            }

            if ($request->duplicate_handling !== 'import_all') {
                $qKey = strtolower($question);
                if (isset($existingQuestionIndex[$qKey])) {
                    if ($request->duplicate_handling === 'skip') {
                        $skipped[] = $rowIndex;
                        continue;
                    }
                    if ($handleReplace) {
                        $replaced[] = $rowIndex;
                        // Swap the existing question in place — no O(n) scan needed
                        $loc = $examWithQuestion[$qKey];
                        foreach ($db['exams'] as &$exam) {
                            if ($exam['id'] === $loc['examId']) {
                                $exam['questions'][$loc['questionIndex']]['question'] = $question;
                                $exam['questions'][$loc['questionIndex']]['optionA'] = trim($line[$optAIdx] ?? '');
                                $exam['questions'][$loc['questionIndex']]['optionB'] = trim($line[$optBIdx] ?? '');
                                $exam['questions'][$loc['questionIndex']]['optionC'] = $optCIdx !== null ? trim($line[$optCIdx] ?? '') : '';
                                $exam['questions'][$loc['questionIndex']]['optionD'] = $optDIdx !== null ? trim($line[$optDIdx] ?? '') : '';
                                $exam['questions'][$loc['questionIndex']]['correctAnswer'] = $correctAnswer;
                                break;
                            }
                        }
                        unset($exam);
                        continue;
                    }
                } else {
                    $existingQuestionIndex[$qKey] = true;
                }
            }

            $imported[] = [
                'id' => $rowIndex,
                'question' => $question,
                'optionA' => trim($line[$optAIdx] ?? ''),
                'optionB' => trim($line[$optBIdx] ?? ''),
                'optionC' => $optCIdx !== null ? trim($line[$optCIdx] ?? '') : '',
                'optionD' => $optDIdx !== null ? trim($line[$optDIdx] ?? '') : '',
                'correctAnswer' => $correctAnswer,
                'explanation' => $explIdx !== null ? trim($line[$explIdx] ?? '') : '',
                'marks' => ($marksIdx !== null && is_numeric(trim($line[$marksIdx] ?? ''))) ? (int)trim($line[$marksIdx]) : 1,
                'difficulty' => $diffIdx !== null ? ucfirst(strtolower(trim($line[$diffIdx] ?? ''))) : 'Medium',
                'topic' => $topicIdx !== null ? (trim($line[$topicIdx] ?: $request->topic ?: 'General')) : ($request->topic ?: 'General'),
                'imageUrl' => $imgIdx !== null ? trim($line[$imgIdx] ?? '') : '',
            ];
        }

        fclose($handle);

        if (!empty($imported)) {
            $count = count($imported);
            $examId = 'exam_' . uniqid();
            $duration = $request->duration ? (int)$request->duration : max(10, min(120, intdiv($count, 2)));
            $defaultMarks = $request->defaultMarks ? (int)$request->defaultMarks : 1;

            $db['exams'][] = [
                'id' => $examId,
                'title' => $examTitle,
                'subject' => $request->subject,
                'level' => $request->class,
                'class' => $request->class,
                'term' => $request->term,
                'session' => $request->session,
                'examType' => $request->exam_type,
                'topic' => $request->topic ?: 'General',
                'subTopic' => $request->subTopic ?: '',
                'duration' => $duration,
                'defaultMarks' => $defaultMarks,
                'totalMarks' => $count * $defaultMarks,
                'instructions' => "Answer all questions. Each question carries {$defaultMarks} mark(s).",
                'questions' => $imported,
                'creatorId' => $user['id'] ?? 'unknown',
                'creatorName' => $user['name'] ?? 'Unknown',
                'isPublished' => false,
                'source' => 'csv_import',
                'createdAt' => now()->toIso8601String(),
            ];
        }

        if (!isset($db['importLogs'])) {
            $db['importLogs'] = [];
        }
        $db['importLogs'][] = [
            'id' => 'imp_' . uniqid(),
            'userId' => $user['id'] ?? 'unknown',
            'userName' => $user['name'] ?? 'Unknown',
            'subject' => $request->subject,
            'class' => $request->class,
            'term' => $request->term,
            'session' => $request->session,
            'imported' => count($imported),
            'skipped' => count($skipped),
            'replaced' => count($replaced),
            'totalRows' => $rowIndex,
            'date' => now()->toIso8601String(),
        ];

        // Use fast save — skips SQLite sync (expensive Eloquent operations per row).
        // The next regular save() will sync.
        JsonDb::saveWithoutSync($db);

        return response()->json([
            'success' => true,
            'imported' => count($imported),
            'skipped' => count($skipped),
            'replaced' => count($replaced),
            'errors' => $errors,
            'message' => 'Successfully imported ' . count($imported) . ' questions.' .
                (count($skipped) > 0 ? ' Skipped ' . count($skipped) . ' duplicates.' : '') .
                (count($replaced) > 0 ? ' Replaced ' . count($replaced) . ' duplicates.' : ''),
            'examId' => $exam['id'] ?? null,
        ]);
    }

    public function convertJsonToExam(Request $request)
    {
        $validated = $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.optionA' => 'required|string',
            'questions.*.optionB' => 'required|string',
            'questions.*.optionC' => 'required|string',
            'questions.*.optionD' => 'required|string',
            'questions.*.correctAnswer' => 'required|in:A,B,C,D',
            'title' => 'nullable|string',
            'subject' => 'required|string',
            'level' => 'nullable|string',
            'duration' => 'nullable|integer|min:1|max:180',
            'defaultMarks' => 'nullable|integer|min:1|max:100',
            'creatorId' => 'nullable|string',
            'creatorName' => 'nullable|string',
            'duplicate_handling' => 'nullable|in:import_all,skip,replace',
        ]);

        $user = Session::get('user');
        $duplicateHandling = $validated['duplicate_handling'] ?? 'import_all';

        // Direct file read/write avoids full JsonDb::init() which loads SQLite data
        $dbPath = base_path('brain_db.json');
        $db = [];
        if (file_exists($dbPath)) {
            $raw = @file_get_contents($dbPath);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $db = $decoded;
            }
        }
        if (!isset($db['exams'])) $db['exams'] = [];

        $defaultMarks = $validated['defaultMarks'] ?? 1;
        $subjectKey = strtolower(trim($validated['subject']));

        // Build index of existing questions for this subject — O(1) lookup per row
        $existingQuestionIndex = [];
        $examWithQuestion = [];
        if ($duplicateHandling !== 'import_all') {
            foreach ($db['exams'] as $exam) {
                if (strtolower(trim($exam['subject'] ?? '')) !== $subjectKey) continue;
                foreach ($exam['questions'] as $qIdx => $eq) {
                    $key = strtolower(trim($eq['question'] ?? ''));
                    if ($key !== '') {
                        $existingQuestionIndex[$key] = true;
                        $examWithQuestion[$key] = ['examId' => $exam['id'], 'questionIndex' => $qIdx];
                    }
                }
            }
        }

        // Batch-build questions with duplicate handling
        $finalQuestions = [];
        $imported = 0;
        $skipped = 0;
        $replaced = 0;
        $examId = 'exam_' . uniqid();
        $handleReplace = $duplicateHandling === 'replace';

        foreach ($validated['questions'] as $i => $q) {
            $qText = trim($q['question']);
            $qKey = strtolower($qText);
            $questionEntry = [
                'id' => $i + 1,
                'question' => $qText,
                'optionA' => $q['optionA'],
                'optionB' => $q['optionB'],
                'optionC' => $q['optionC'],
                'optionD' => $q['optionD'],
                'correctAnswer' => strtoupper($q['correctAnswer']),
                'marks' => $defaultMarks,
            ];

            if ($duplicateHandling !== 'import_all' && isset($existingQuestionIndex[$qKey])) {
                if ($duplicateHandling === 'skip') {
                    $skipped++;
                    continue;
                }
                if ($handleReplace) {
                    $replaced++;
                    // Update in-place in existing exam
                    $loc = $examWithQuestion[$qKey];
                    foreach ($db['exams'] as &$exam) {
                        if ($exam['id'] === $loc['examId']) {
                            $exam['questions'][$loc['questionIndex']] = $questionEntry;
                            break;
                        }
                    }
                    unset($exam);
                    continue;
                }
            }

            if ($duplicateHandling !== 'import_all') {
                $existingQuestionIndex[$qKey] = true;
            }

            $finalQuestions[] = $questionEntry;
            $imported++;
        }

        if (!empty($finalQuestions)) {
            $count = count($finalQuestions);
            $duration = $validated['duration'] ?? max(10, min(120, intdiv($count, 2)));

            $db['exams'][] = [
                'id' => $examId,
                'title' => $validated['title'] ?? ($validated['subject'] . ' CBT Exam'),
                'subject' => $validated['subject'],
                'level' => $validated['level'] ?? 'Mixed',
                'duration' => $duration,
                'defaultMarks' => $defaultMarks,
                'totalMarks' => $count * $defaultMarks,
                'instructions' => "Answer all questions. Each question carries {$defaultMarks} mark(s).",
                'questions' => $finalQuestions,
                'creatorId' => $validated['creatorId'] ?? $user['id'] ?? 'unknown',
                'creatorName' => $validated['creatorName'] ?? $user['name'] ?? 'CSV Import',
                'isPublished' => false,
                'source' => 'csv_import',
                'createdAt' => now()->toIso8601String(),
            ];
        }

        // Direct file write — skips full JsonDb init/sync cycle
        $written = @file_put_contents($dbPath, json_encode($db, JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            Log::error('Failed to write brain_db.json during CSV import', ['examId' => $examId]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to save exam data. Please check file permissions.',
            ], 500);
        }

        Log::info('CSV questions converted to CBT exam', [
            'examId' => $examId,
            'imported' => $imported,
            'skipped' => $skipped,
            'replaced' => $replaced,
            'subject' => $validated['subject'],
        ]);

        return response()->json([
            'success' => true,
            'examId' => $examId,
            'imported' => $imported,
            'skipped' => $skipped,
            'replaced' => $replaced,
            'message' => $imported . ' questions imported' .
                ($skipped > 0 ? ', ' . $skipped . ' skipped' : '') .
                ($replaced > 0 ? ', ' . $replaced . ' replaced' : '') .
                '.',
        ]);
    }


}
