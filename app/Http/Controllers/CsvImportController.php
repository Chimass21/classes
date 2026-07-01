<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
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
            'Correct Answer', 'Explanation', 'Marks', 'Difficulty', 'Topic', 'Image URL'
        ];

        $sample = [
            'What is the capital of Nigeria?',
            'Lagos', 'Abuja', 'Kano', 'Ibadan',
            'B', 'Abuja is the capital city of Nigeria.', '1', 'Easy', 'Geography', ''
        ];

        $sample2 = [
            'Which planet is known as the Red Planet?',
            'Earth', 'Mars', 'Venus', 'Jupiter',
            'B', 'Mars is called the Red Planet due to its reddish appearance.', '1', 'Easy', 'Space', ''
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

        while (($line = fgetcsv($handle)) !== false) {
            $rowIndex++;
            $rowErrors = [];

            $question = trim($line[$headerMap['Question']] ?? '');
            $optionA = trim($line[$headerMap['Option A']] ?? '');
            $optionB = trim($line[$headerMap['Option B']] ?? '');
            $optionC = trim($headerMap['Option C'] ?? false) !== false ? trim($line[$headerMap['Option C']] ?? '') : '';
            $optionD = trim($headerMap['Option D'] ?? false) !== false ? trim($line[$headerMap['Option D']] ?? '') : '';
            $correctAnswer = strtoupper(trim($line[$headerMap['Correct Answer']] ?? ''));
            $explanation = trim($line[$headerMap['Explanation'] ?? -1] ?? '');
            $marks = trim($line[$headerMap['Marks'] ?? -1] ?? '');
            $difficulty = trim($line[$headerMap['Difficulty'] ?? -1] ?? '');
            $topic = trim($line[$headerMap['Topic'] ?? -1] ?? '');
            $imageUrl = trim($line[$headerMap['Image URL'] ?? -1] ?? '');

            if (empty($question)) {
                $rowErrors[] = 'Question text is required.';
            }

            if (empty($optionA) && empty($optionB) && empty($optionC) && empty($optionD)) {
                $rowErrors[] = 'At least one option is required.';
            }

            $validAnswers = ['A', 'B', 'C', 'D'];
            if (empty($correctAnswer) || !in_array($correctAnswer, $validAnswers)) {
                $rowErrors[] = 'Correct Answer must be A, B, C, or D.';
            }

            if ($marks !== '' && (!is_numeric($marks) || (int)$marks < 0)) {
                $rowErrors[] = 'Marks must be a positive number.';
            }

            if ($difficulty !== '' && !in_array(strtolower($difficulty), ['easy', 'medium', 'hard'])) {
                $rowErrors[] = 'Difficulty must be Easy, Medium, or Hard.';
            }

            if (!empty($rowErrors)) {
                $errors[$rowIndex] = $rowErrors;
            }

            $rows[] = [
                'row' => $rowIndex,
                'question' => $question,
                'optionA' => $optionA,
                'optionB' => $optionB,
                'optionC' => $optionC,
                'optionD' => $optionD,
                'correctAnswer' => $correctAnswer,
                'explanation' => $explanation,
                'marks' => $marks !== '' ? (int)$marks : 1,
                'difficulty' => $difficulty !== '' ? ucfirst(strtolower($difficulty)) : 'Medium',
                'topic' => $topic,
                'imageUrl' => $imageUrl,
                'valid' => empty($rowErrors),
                'errors' => $rowErrors,
            ];

            if (empty($rowErrors)) {
                $validCount++;
            }

            if (count($rows) > 5000) {
                $errors[] = 'File exceeds maximum of 5000 questions.';
                break;
            }
        }

        fclose($handle);

        $duplicateCount = 0;
        if ($validCount > 0) {
            $duplicateCount = $this->countDuplicates($request, $rows);
        }

        return response()->json([
            'success' => true,
            'total_rows' => count($rows),
            'valid_rows' => $validCount,
            'error_rows' => count($rows) - $validCount,
            'duplicate_count' => $duplicateCount,
            'errors' => $errors,
            'rows' => array_slice($rows, 0, 100),
            'has_more' => count($rows) > 100,
            'total_all_rows' => count($rows),
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

        $imported = [];
        $skipped = [];
        $replaced = [];
        $errors = [];
        $rowIndex = 0;

        $examTitle = $request->subject . ' ' . $request->class . ' ' . $request->term . ' (' . $request->session . ')';
        if ($request->topic) {
            $examTitle .= ' - ' . $request->topic;
        }

        while (($line = fgetcsv($handle)) !== false) {
            $rowIndex++;
            $rowErrors = [];

            $question = trim($line[$headerMap['Question'] ?? -1] ?? '');
            $optionA = trim($headerMap['Option A'] ?? false) !== false ? trim($line[$headerMap['Option A']] ?? '') : '';
            $optionB = trim($headerMap['Option B'] ?? false) !== false ? trim($line[$headerMap['Option B']] ?? '') : '';
            $optionC = trim($headerMap['Option C'] ?? false) !== false ? trim($line[$headerMap['Option C']] ?? '') : '';
            $optionD = trim($headerMap['Option D'] ?? false) !== false ? trim($line[$headerMap['Option D']] ?? '') : '';
            $correctAnswer = strtoupper(trim($line[$headerMap['Correct Answer'] ?? -1] ?? ''));
            $explanation = trim($line[$headerMap['Explanation'] ?? -1] ?? '');
            $marks = trim($line[$headerMap['Marks'] ?? -1] ?? '');
            $difficulty = trim($line[$headerMap['Difficulty'] ?? -1] ?? '');
            $topic = trim($line[$headerMap['Topic'] ?? -1] ?? '');
            $imageUrl = trim($line[$headerMap['Image URL'] ?? -1] ?? '');

            if (empty($question)) {
                $rowErrors[] = 'Question is required.';
            }
            if (empty($correctAnswer) || !in_array($correctAnswer, ['A', 'B', 'C', 'D'])) {
                $rowErrors[] = 'Correct Answer must be A, B, C, or D.';
            }

            if (!empty($rowErrors)) {
                $errors[$rowIndex] = $rowErrors;
                continue;
            }

            $questionData = [
                'id' => $rowIndex,
                'question' => $question,
                'optionA' => $optionA,
                'optionB' => $optionB,
                'optionC' => $optionC,
                'optionD' => $optionD,
                'correctAnswer' => $correctAnswer,
                'explanation' => $explanation,
                'marks' => $marks !== '' ? (int)$marks : 1,
                'difficulty' => $difficulty !== '' ? ucfirst(strtolower($difficulty)) : 'Medium',
                'topic' => $topic ?: ($request->topic ?: 'General'),
                'imageUrl' => $imageUrl,
            ];

            if ($request->duplicate_handling !== 'import_all') {
                $existing = $this->findDuplicateInDb($db, $question, $request->subject);
                if ($existing !== null) {
                    if ($request->duplicate_handling === 'skip') {
                        $skipped[] = $rowIndex;
                        continue;
                    }
                    if ($request->duplicate_handling === 'replace') {
                        foreach ($db['exams'] as &$exam) {
                            if ($exam['id'] === $existing['examId']) {
                                foreach ($exam['questions'] as &$eq) {
                                    if ($eq['question'] === $question) {
                                        $eq = $questionData;
                                        $eq['id'] = $eq['id'] ?? $rowIndex;
                                        break;
                                    }
                                }
                                unset($eq);
                                break;
                            }
                        }
                        unset($exam);
                        $replaced[] = $rowIndex;
                        continue;
                    }
                }
            }

            $imported[] = $questionData;
        }

        fclose($handle);

        if (!empty($imported)) {
            $examId = 'exam_' . uniqid();
            $exam = [
                'id' => $examId,
                'title' => $examTitle,
                'subject' => $request->subject,
                'level' => $request->class,
                'class' => $request->class,
                'term' => $request->term,
                'session' => $request->session,
                'examType' => $request->exam_type,
                'topic' => $request->topic ?: 'General',
                'duration' => max(10, min(120, intdiv(count($imported), 2))),
                'totalMarks' => count($imported),
                'instructions' => 'Answer all questions. Each question carries 1 mark.',
                'questions' => $imported,
                'creatorId' => $user['id'] ?? 'unknown',
                'creatorName' => $user['name'] ?? 'Unknown',
                'isPublished' => false,
                'source' => 'csv_import',
                'createdAt' => now()->toIso8601String(),
            ];
            $db['exams'][] = $exam;
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

        JsonDb::save($db);

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

    private function countDuplicates(Request $request, array $rows): int
    {
        JsonDb::init();
        $db = JsonDb::get();
        $count = 0;
        $existingQuestions = $this->getExistingQuestions($db, $request->subject);
        foreach ($rows as $row) {
            if (!$row['valid']) continue;
            foreach ($existingQuestions as $eq) {
                if (strcasecmp(trim($eq['question']), trim($row['question'])) === 0) {
                    $count++;
                    break;
                }
            }
        }
        return $count;
    }

    private function findDuplicateInDb(array &$db, string $question, string $subject): ?array
    {
        foreach ($db['exams'] as $exam) {
            if ($exam['subject'] !== $subject) continue;
            foreach ($exam['questions'] as $qIdx => $eq) {
                if (strcasecmp(trim($eq['question'] ?? ''), trim($question)) === 0) {
                    return [
                        'examId' => $exam['id'],
                        'questionIndex' => $qIdx,
                        'question' => $eq,
                    ];
                }
            }
        }
        return null;
    }

    private function getExistingQuestions(array &$db, string $subject): array
    {
        $questions = [];
        foreach ($db['exams'] as $exam) {
            if ($exam['subject'] !== $subject) continue;
            foreach ($exam['questions'] as $q) {
                $questions[] = $q;
            }
        }
        return $questions;
    }
}
