<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

class DownloadController extends Controller
{
    public function downloadLessonNote($id, $format)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $note = null;
        foreach ($db['lessonNotes'] as $n) {
            if ($n['id'] === $id) { $note = $n; break; }
        }
        if (!$note) return abort(404);

        $html = $this->buildNoteHtml($note);
        if ($format === 'pdf') return $this->downloadPdf($html, 'lesson_note_' . $id);
        if ($format === 'docx') return $this->downloadDocx($html, 'lesson_note_' . $id);
        return abort(400);
    }

    public function downloadLessonPlan($id, $format)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $plan = null;
        foreach ($db['lessonPlans'] as $p) {
            if ($p['id'] === $id) { $plan = $p; break; }
        }
        if (!$plan) return abort(404);

        if ($format === 'pdf') {
            $html = $this->buildPlanHtml($plan);
            return $this->downloadPdf($html, 'lesson_plan_' . $id);
        }
        if ($format === 'docx') {
            return $this->downloadPlanDocx($plan, 'lesson_plan_' . $id);
        }
        return abort(400);
    }

    public function downloadExam($id, $format)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $exam = null;
        foreach ($db['exams'] as $e) {
            if ($e['id'] === $id) { $exam = $e; break; }
        }
        if (!$exam) return abort(404);

        $html = $this->buildExamHtml($exam);
        if ($format === 'pdf') return $this->downloadPdf($html, 'exam_' . $id);
        if ($format === 'docx') return $this->downloadDocx($html, 'exam_' . $id);
        return abort(400);
    }

    public function downloadGradedScript($examId, $resultId)
    {
        JsonDb::init();
        $db = JsonDb::get();

        $exam = null;
        foreach ($db['exams'] as $e) {
            if ($e['id'] === $examId) { $exam = $e; break; }
        }
        if (!$exam) return abort(404, 'Exam not found');

        $result = null;
        foreach ($db['results'] as $r) {
            if ($r['id'] === $resultId && ($r['examId'] ?? '') === $examId) { $result = $r; break; }
        }
        if (!$result) return abort(404, 'Result not found');

        $html = $this->buildGradedScriptHtml($exam, $result);
        return $this->downloadPdf($html, 'graded_script_' . $resultId);
    }

    protected function buildGradedScriptHtml(array $exam, array $result): string
    {
        // School config
        $db = JsonDb::get();
        $schoolName = $db['schoolConfig']['name'] ?? 'ClassPortal Academy';

        $title = $exam['title'] ?? 'Examination';
        $subject = $exam['subject'] ?? '';
        $classLevel = $exam['level'] ?? '';
        $studentName = $result['studentName'] ?? 'Student';
        $studentId = $result['studentId'] ?? '';
        $score = $result['score'] ?? 0;
        $totalPossible = $result['totalPossibleMarks'] ?? 0;
        $percentage = $result['percentage'] ?? 0;
        $correctAnswers = $result['correctAnswers'] ?? 0;
        $totalQuestions = $result['totalQuestions'] ?? 0;
        $timeSpent = $result['timeSpent'] ?? 0;
        $grade = $percentage >= 75 ? 'A' : ($percentage >= 60 ? 'B' : ($percentage >= 50 ? 'C' : ($percentage >= 40 ? 'D' : 'F')));
        $isPassed = $percentage >= 50;
        $failedQuestions = $result['failedQuestions'] ?? [];
        $date = $result['date'] ?? now()->toIso8601String();
        $resultId = $result['id'] ?? '';

        $timeStr = $timeSpent < 60 ? $timeSpent . 's' : floor($timeSpent / 60) . 'm ' . ($timeSpent % 60) . 's';

        $questionsHtml = '';
        foreach ($failedQuestions as $idx => $item) {
            $qNum = $idx + 1;
            $isCorrect = $item['selectedAnswer'] === $item['correctAnswer'];
            $isNotAnswered = !$item['selectedAnswer'];
            $qMarks = $item['marks'] ?? 0;
            $earnedMarks = $isCorrect ? $qMarks : 0;
            $statusBadge = $isCorrect
                ? '<span style="color:#059669;font-weight:800;font-size:10px">✓ Correct</span>'
                : ($isNotAnswered
                    ? '<span style="color:#d97706;font-weight:800;font-size:10px">— Not Answered</span>'
                    : '<span style="color:#dc2626;font-weight:800;font-size:10px">✗ Wrong</span>');

            $optKeys = ['A', 'B', 'C', 'D'];
            $optLabels = [];
            foreach ($optKeys as $k) {
                $v = $item['option' . $k] ?? $item['options'][$k] ?? $item[$k] ?? '';
                $optLabels[] = ['k' => $k, 'v' => $v];
            }

            $optsHtml = '';
            foreach ($optLabels as $opt) {
                $isCorrectOpt = $opt['k'] === $item['correctAnswer'];
                $isSelectedOpt = $opt['k'] === $item['selectedAnswer'];
                $bg = '#ffffff';
                $border = '#e2e8f0';
                $marker = '#f1f5f9';
                $mark = '';
                $markColor = 'transparent';
                if ($isCorrectOpt) {
                    $bg = '#f0fdf4';
                    $border = '#86efac';
                    $marker = '#22c55e';
                    $mark = ' ✓';
                    $markColor = '#059669';
                } elseif ($isSelectedOpt && !$isCorrectOpt) {
                    $bg = '#fff1f2';
                    $border = '#fda4af';
                    $marker = '#e11d48';
                    $mark = ' ✗';
                    $markColor = '#dc2626';
                }
                $optsHtml .= '<div style="padding:5px 8px;border:1px solid ' . $border . ';border-radius:4px;background:' . $bg . ';font-size:10px;font-weight:600;color:#334155;display:flex;align-items:center;gap:6px;page-break-inside:avoid;break-inside:avoid">'
                    . '<span style="width:18px;height:18px;border-radius:3px;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;color:#fff;background:' . $marker . ';border:1px solid ' . $border . '">' . $opt['k'] . '</span>'
                    . '<span style="flex:1">' . e($opt['v']) . '</span>'
                    . ($mark ? '<span style="font-size:10px;font-weight:800;color:' . $markColor . '">' . $mark . '</span>' : '')
                    . '</div>';
            }

            $explanation = $item['explanation'] ?? ('The correct answer is Option ' . $item['correctAnswer'] . '.');

            $questionsHtml .= '
            <div style="border:1px solid #e2e8f0;border-radius:6px;padding:10px;margin-bottom:10px;page-break-inside:avoid;break-inside:avoid">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span style="font-size:9px;font-weight:800;color:#64748b">Question ' . $qNum . ' of ' . $totalQuestions . '</span>
                    <div style="display:flex;align-items:center;gap:6px">
                        <span style="font-size:9px;font-weight:700;color:#475569">Marks: ' . $earnedMarks . '/' . $qMarks . '</span>
                        ' . $statusBadge . '
                    </div>
                </div>
                <p style="font-size:11px;font-weight:700;color:#1e293b;margin-bottom:6px;line-height:1.4">' . e($item['question']) . '</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px">' . $optsHtml . '</div>
                <div style="margin-top:6px;padding:6px 10px;background:#eef2ff;border:1px solid #e0e7ff;border-radius:4px;font-size:10px;color:#334155;line-height:1.3"><strong style="color:#4338ca">Explanation:</strong> ' . e($explanation) . '</div>
            </div>';
        }

        $dateFormatted = date('M j, Y', strtotime($date));
        $dateTimeFormatted = date('M j, Y \a\t g:i A', strtotime($date));
        $gradeColor = $grade === 'A' ? '#059669' : ($grade === 'B' ? '#2563eb' : ($grade === 'C' ? '#d97706' : ($grade === 'D' ? '#ea580c' : '#dc2626')));

        $body = '
        <div style="max-width:190mm;margin:0 auto;padding:5mm 0">
            <!-- School Header -->
            <div style="text-align:center;border-bottom:3px double #1e293b;padding-bottom:6px;margin-bottom:12px">
                <h1 style="font-size:18px;font-weight:900;color:#0f172a;margin:0;text-transform:uppercase;letter-spacing:1px">' . e($schoolName) . '</h1>
                <p style="font-size:8px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:2px 0 0 0">Official Assessment Center &bull; Computer Based Testing Division</p>
            </div>

            <!-- Student Info Row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:8px 12px">
                <div>
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 1px 0">Student Name</p>
                    <p style="font-size:11px;font-weight:800;color:#0f172a;margin:0">' . e($studentName) . '</p>
                </div>
                <div style="text-align:right">
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 1px 0">Student ID</p>
                    <p style="font-size:11px;font-weight:800;color:#0f172a;margin:0;font-family:monospace">' . e($studentId) . '</p>
                </div>
                <div>
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 1px 0">Class / Level</p>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0">' . e($classLevel ?: 'N/A') . '</p>
                </div>
                <div style="text-align:right">
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 1px 0">Date Taken</p>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0">' . $dateTimeFormatted . '</p>
                </div>
            </div>

            <!-- Exam Info -->
            <div style="margin-bottom:10px">
                <h2 style="font-size:13px;font-weight:900;color:#0f172a;margin:0 0 3px 0">' . e($title) . '</h2>
                <p style="font-size:9px;color:#64748b;margin:0;font-weight:600">Subject: ' . e($subject) . ' &bull; Time Used: ' . $timeStr . '</p>
            </div>

            <!-- Score Card -->
            <div style="background:' . ($isPassed ? '#059669' : '#dc2626') . ';color:#fff;border-radius:6px;padding:10px 14px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
                <div>
                    <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:0.9;margin:0 0 2px 0">Final Score</p>
                    <p style="font-size:22px;font-weight:900;margin:0">' . $score . ' / ' . $totalPossible . '</p>
                </div>
                <div style="text-align:right">
                    <p style="font-size:26px;font-weight:900;margin:0">' . $percentage . '%</p>
                    <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:0.9;margin:2px 0 0 0">' . ($isPassed ? 'Passed' : 'Failed') . '</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:5px;margin-bottom:12px">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:5px 6px;text-align:center">
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 2px 0">Total Score</p>
                    <p style="font-size:12px;font-weight:900;color:#0f172a;margin:0">' . $score . '/' . $totalPossible . '</p>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:5px 6px;text-align:center">
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 2px 0">Correct</p>
                    <p style="font-size:12px;font-weight:900;color:#0f172a;margin:0">' . $correctAnswers . '/' . $totalQuestions . '</p>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:5px 6px;text-align:center">
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 2px 0">Percentage</p>
                    <p style="font-size:12px;font-weight:900;color:' . ($isPassed ? '#059669' : '#dc2626') . ';margin:0">' . $percentage . '%</p>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:5px 6px;text-align:center">
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 2px 0">Grade</p>
                    <p style="font-size:14px;font-weight:900;color:' . $gradeColor . ';margin:0">' . $grade . '</p>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:5px 6px;text-align:center">
                    <p style="font-size:7px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin:0 0 2px 0">Time Used</p>
                    <p style="font-size:12px;font-weight:900;color:#0f172a;margin:0">' . $timeStr . '</p>
                </div>
            </div>

            <!-- Questions -->
            <h3 style="font-size:11px;font-weight:900;color:#0f172a;text-transform:uppercase;border-bottom:2px solid #1e293b;padding-bottom:4px;margin:0 0 10px 0">Question-by-Question Breakdown</h3>
            ' . $questionsHtml . '

            <!-- Legend -->
            <div style="display:flex;gap:12px;margin-top:4px;padding:6px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;font-size:8px;color:#64748b">
                <span><span style="display:inline-block;width:10px;height:10px;background:#f0fdf4;border:1px solid #86efac;border-radius:2px;vertical-align:middle;margin-right:3px"></span> Correct answer</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#fff1f2;border:1px solid #fda4af;border-radius:2px;vertical-align:middle;margin-right:3px"></span> Selected wrong answer</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#ffffff;border:1px solid #e2e8f0;border-radius:2px;vertical-align:middle;margin-right:3px"></span> Not selected</span>
            </div>

            <!-- Certificate Section (if passed) -->
            ' . ($isPassed ? '
            <div style="page-break-before:always;break-before:page;margin-top:20px">
                <div style="border:4px double #d97706;padding:20px 16px;text-align:center;background:linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);border-radius:8px;position:relative">
                    <div style="position:absolute;top:8px;left:8px;width:24px;height:24px;border-top:3px solid #d97706;border-left:3px solid #d97706;border-radius:4px 0 0 0"></div>
                    <div style="position:absolute;top:8px;right:8px;width:24px;height:24px;border-top:3px solid #d97706;border-right:3px solid #d97706;border-radius:0 4px 0 0"></div>
                    <div style="position:absolute;bottom:8px;left:8px;width:24px;height:24px;border-bottom:3px solid #d97706;border-left:3px solid #d97706;border-radius:0 0 0 4px"></div>
                    <div style="position:absolute;bottom:8px;right:8px;width:24px;height:24px;border-bottom:3px solid #d97706;border-right:3px solid #d97706;border-radius:0 0 4px 0"></div>
                    <h2 style="font-size:18px;font-weight:900;color:#92400e;text-transform:uppercase;letter-spacing:2px;margin:0 0 4px 0;font-family:serif">Certificate of Excellence</h2>
                    <div style="width:60px;height:2px;background:#d97706;margin:6px auto;border-radius:2px"></div>
                    <p style="font-size:10px;color:#64748b;font-style:italic;margin:4px 0">Presented to</p>
                    <h3 style="font-size:20px;font-weight:900;color:#0f172a;margin:4px 0;text-transform:uppercase;text-decoration:underline;text-underline-offset:6px;font-family:serif">' . e($studentName) . '</h3>
                    <p style="font-size:10px;color:#475569;max-width:400px;margin:6px auto;line-height:1.5">For completing the computer-based evaluation test in <strong>' . e($title) . ' (' . e($subject) . ')</strong> with a final score of <strong>' . $score . '/' . $totalPossible . ' (' . $percentage . '%)</strong> achieving a grade of <strong>' . $grade . '</strong>.</p>
                    <p style="font-size:36px;font-weight:900;color:#d97706;margin:8px 0">' . $percentage . '%</p>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:8px;color:#94a3b8;margin-top:20px;padding-top:10px;border-top:1px dashed #d97706">
                        <div style="text-align:left">Principal Assessor:<br><strong style="color:#1e293b;font-size:10px">Nwaigbo Augustine</strong></div>
                        <div style="text-align:right">Verification Code:<br><strong style="color:#1e293b;font-family:monospace;font-size:9px">' . e($resultId) . '</strong></div>
                    </div>
                </div>
            </div>
            ' : '') . '

            <!-- Footer -->
            <div style="margin-top:16px;padding-top:8px;border-top:1px solid #e2e8f0;font-size:7px;color:#94a3b8;text-align:center">
                Generated on ' . date('F j, Y \a\t g:i A') . ' &bull; ' . e($schoolName) . ' &bull; Official Document
            </div>
        </div>';

        return $this->wrapHtml($body);
    }

    protected function buildNoteHtml(array $note): string
    {
        $topic = $note['topic'] ?? 'Lesson Note';
        $subject = $note['subject'] ?? '';
        $class = $note['class'] ?? '';
        $term = $note['term'] ?? '';
        $week = $note['week'] ?? '';
        $content = $note['content'] ?? '';
        $detailedNote = $note['detailedNote'] ?? '';
        $examples = $note['examples'] ?? [];
        $evaluation = $note['evaluationQuestions'] ?? [];
        $assignment = $note['assignment'] ?? '';
        $definitions = $note['definitions'] ?? [];
        $practicalApps = $note['practicalApplications'] ?? [];
        $illustrations = $note['illustrations'] ?? [];
        $advDisadv = $note['advantagesDisadvantages'] ?? [];
        $keyPoints = $note['keyPoints'] ?? [];

        $examplesHtml = '';
        foreach ($examples as $ex) {
            $examplesHtml .= '<div class="example"><strong>' . ($ex['title'] ?? 'Example') . ':</strong> ' . ($ex['description'] ?? '') . '</div>';
        }

        $evalHtml = '';
        foreach ($evaluation as $eq) {
            $evalHtml .= '<li>' . $eq . '</li>';
        }

        $definitionsHtml = '';
        if (!empty($definitions)) {
            $definitionsHtml .= '<h3>Definitions of Key Terms</h3><table class="definitions" style="width:100%;border-collapse:collapse;margin-bottom:10px">';
            foreach ($definitions as $def) {
                $definitionsHtml .= '<tr style="border-bottom:1px solid #ddd"><td style="padding:6px 8px;font-weight:700;width:30%">' . e($def['term'] ?? '') . '</td><td style="padding:6px 8px">' . e($def['definition'] ?? '') . '</td></tr>';
            }
            $definitionsHtml .= '</table>';
        }

        $illustrationsHtml = '';
        if (!empty($illustrations)) {
            $illustrationsHtml .= '<h3>Illustrations / Diagrams</h3>';
            foreach ($illustrations as $ill) {
                $illustrationsHtml .= '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:10px;margin-bottom:8px;font-family:monospace;font-size:10pt">' . e($ill) . '</div>';
            }
        }

        $practicalHtml = '';
        if (!empty($practicalApps)) {
            $practicalHtml .= '<h3>Practical Applications</h3><ul>';
            foreach ($practicalApps as $app) {
                $practicalHtml .= '<li>' . e($app) . '</li>';
            }
            $practicalHtml .= '</ul>';
        }

        $advHtml = '';
        if (!empty($advDisadv['advantages'])) {
            $advHtml .= '<h3>Advantages</h3><ul style="color:#15803d">';
            foreach ($advDisadv['advantages'] as $adv) {
                $advHtml .= '<li>' . e($adv) . '</li>';
            }
            $advHtml .= '</ul>';
        }
        if (!empty($advDisadv['disadvantages'])) {
            $advHtml .= '<h3>Disadvantages</h3><ul style="color:#b91c1c">';
            foreach ($advDisadv['disadvantages'] as $dis) {
                $advHtml .= '<li>' . e($dis) . '</li>';
            }
            $advHtml .= '</ul>';
        }

        $keyPointsHtml = '';
        if (!empty($keyPoints)) {
            $keyPointsHtml .= '<h3>Key Points to Remember</h3><ul style="color:#047857">';
            foreach ($keyPoints as $kp) {
                $keyPointsHtml .= '<li>' . e($kp) . '</li>';
            }
            $keyPointsHtml .= '</ul>';
        }

        return $this->wrapHtml('
            <div class="header">
                <h1>' . $topic . '</h1>
                <p class="meta">' . $subject . ' | ' . $class . ' | ' . $term . ' | Week ' . $week . '</p>
            </div>
            <div class="content">
                ' . $content . '
                ' . $definitionsHtml . '
                ' . ($examplesHtml ? '<h3>Examples</h3>' . $examplesHtml : '') . '
                ' . $illustrationsHtml . '
                ' . $practicalHtml . '
                ' . $advHtml . '
                ' . ($evalHtml ? '<h3>Evaluation Questions</h3><ol>' . $evalHtml . '</ol>' : '') . '
                ' . ($assignment ? '<h3>Assignment</h3><p>' . nl2br(e($assignment)) . '</p>' : '') . '
                ' . $keyPointsHtml . '
                ' . ($detailedNote ? '<h3>Detailed Note</h3><div class="detailed-note">' . nl2br(e($detailedNote)) . '</div>' : '') . '
            </div>
        ');
    }

    protected function truncate(string $text, int $limit = 200): string
    {
        $clean = strip_tags($text);
        if (mb_strlen($clean) <= $limit) return $clean;
        $trimmed = mb_substr($clean, 0, $limit);
        $lastSpace = mb_strrpos($trimmed, ' ');
        return mb_substr($trimmed, 0, $lastSpace ?: $limit) . '...';
    }

    protected function downloadPlanDocx(array $plan, string $filename)
    {
        $topic = $plan['topic'] ?? 'Lesson Plan';
        $subject = $plan['subject'] ?? '';
        $class = $plan['class'] ?? '';
        $term = $plan['term'] ?? '';
        $week = $plan['week'] ?? '';
        $schoolName = $plan['schoolName'] ?? '';
        $teacherName = $plan['teacherName'] ?? '';
        $duration = $plan['duration'] ?? '40 Minutes';
        $ageRange = $plan['ageRange'] ?? '';
        $date = $plan['date'] ?? now()->format('l, F j, Y');

        $objectives = $plan['behaviouralObjectives'] ?? [];
        $materials = $plan['instructionalMaterials'] ?? [];
        $previousKnowledge = $plan['previousKnowledge'] ?? '';
        $steps = $plan['lessonSteps'] ?? [];
        $evaluation = $plan['evaluation'] ?? '';
        $assignment = $plan['assignment'] ?? '';
        $summary = $plan['summary'] ?? '';
        $conclusion = $plan['conclusion'] ?? '';

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(7);

        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginLeft' => 454,
            'marginRight' => 454,
            'marginTop' => 283,
            'marginBottom' => 283,
        ]);

        $pStyle = ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $pStyleSmall = ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 0.9];

        $border = ['borderSize' => 6, 'borderColor' => '000000'];
        $cm = 15;

        $table = $section->addTable(array_merge($border, [
            'cellMargin' => $cm,
            'cellMarginTop' => $cm,
            'cellMarginLeft' => $cm,
            'cellMarginRight' => $cm,
            'cellMarginBottom' => $cm,
        ]));

        $titleCell = $table->addRow()->addCell(9000, array_merge($border, ['gridSpan' => 4, 'valign' => 'center']));
        $titleCell->addText('LESSON PLAN', ['bold' => true, 'size' => 9], $pStyle);

        $labels = ['bold' => true, 'size' => 7];
        $vals = ['size' => 7];

        // School / Teacher
        $table->addRow();
        $table->addCell(1080, array_merge($border, $labels))->addText('School:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText($schoolName, $vals, $pStyle);
        $table->addCell(1080, array_merge($border, $labels))->addText('Teacher:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText($teacherName, $vals, $pStyle);

        // Subject / Class
        $table->addRow();
        $table->addCell(1080, array_merge($border, $labels))->addText('Subject:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText($subject, $vals, $pStyle);
        $table->addCell(1080, array_merge($border, $labels))->addText('Class:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText($class . ($ageRange ? ' (' . $ageRange . ')' : ''), $vals, $pStyle);

        // Term / Week
        $table->addRow();
        $table->addCell(1080, array_merge($border, $labels))->addText('Term:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText($term, $vals, $pStyle);
        $table->addCell(1080, array_merge($border, $labels))->addText('Week:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText((string)$week, $vals, $pStyle);

        // Date / Duration
        $table->addRow();
        $table->addCell(1080, array_merge($border, $labels))->addText('Date:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText($date, $vals, $pStyle);
        $table->addCell(1080, array_merge($border, $labels))->addText('Duration:', $labels, $pStyle);
        $table->addCell(3420, $border)->addText($duration, $vals, $pStyle);

        // Topic
        $table->addRow();
        $table->addCell(1080, array_merge($border, $labels))->addText('Topic:', $labels, $pStyle);
        $table->addCell(7920, array_merge($border, ['gridSpan' => 3]))->addText($topic, $vals, $pStyle);

        // Behavioural Objectives header
        $table->addRow();
        $table->addCell(9000, array_merge($border, ['gridSpan' => 4, 'bold' => true]))->addText('Behavioural Objectives', $labels, $pStyle);
        foreach ($objectives as $obj) {
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4]))->addText('• ' . $this->truncate($obj, 150), $vals, $pStyleSmall);
        }

        // Instructional Materials
        if (!empty($materials)) {
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4, 'bold' => true]))->addText('Instructional Materials', $labels, $pStyle);
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4]))->addText($this->truncate(implode('; ', $materials), 200), $vals, $pStyleSmall);
        }

        // Previous Knowledge
        if ($previousKnowledge) {
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4, 'bold' => true]))->addText('Previous Knowledge', $labels, $pStyle);
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4]))->addText($this->truncate($previousKnowledge, 200), $vals, $pStyleSmall);
        }

        // Steps header
        $table->addRow();
        $table->addCell(900, array_merge($border, $labels))->addText('Step', $labels, $pStyle);
        $table->addCell(2700, array_merge($border, $labels))->addText("Teacher's Activities", $labels, $pStyle);
        $table->addCell(2700, array_merge($border, $labels))->addText("Learners' Activities", $labels, $pStyle);
        $table->addCell(2700, array_merge($border, $labels))->addText('Learning Points', $labels, $pStyle);

        foreach ($steps as $s) {
            $table->addRow();
            $sn = $table->addCell(900, array_merge($border, ['valign' => 'top']));
            $sn->addText($s['step'] ?? '', $vals, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
            $table->addCell(2700, array_merge($border, ['valign' => 'top']))->addText($this->truncate($s['teacherActivities'] ?? '', 200), $vals, $pStyleSmall);
            $table->addCell(2700, array_merge($border, ['valign' => 'top']))->addText($this->truncate($s['learnerActivities'] ?? '', 200), $vals, $pStyleSmall);
            $table->addCell(2700, array_merge($border, ['valign' => 'top']))->addText($this->truncate($s['learningPoints'] ?? '', 150), $vals, $pStyleSmall);
        }

        // Evaluation
        if ($evaluation) {
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4, 'bold' => true]))->addText('Evaluation / Assessment', $labels, $pStyle);
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4]))->addText($this->truncate(strip_tags($evaluation), 300), $vals, $pStyleSmall);
        }

        // Assignment
        if ($assignment) {
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4, 'bold' => true]))->addText('Assignment / Homework', $labels, $pStyle);
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4]))->addText($this->truncate(strip_tags($assignment), 200), $vals, $pStyleSmall);
        }

        // Summary
        if ($summary) {
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4, 'bold' => true]))->addText('Summary', $labels, $pStyle);
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4]))->addText($this->truncate($summary, 200), $vals, $pStyleSmall);
        }

        // Conclusion
        if ($conclusion) {
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4, 'bold' => true]))->addText('Conclusion', $labels, $pStyle);
            $table->addRow();
            $table->addCell(9000, array_merge($border, ['gridSpan' => 4]))->addText($this->truncate($conclusion, 200), $vals, $pStyleSmall);
        }

        // Remarks + Signature (combined in one row to save space)
        $table->addRow(200);
        $sigCell = $table->addCell(9000, array_merge($border, ['gridSpan' => 4]));
        $sigCell->addText("Remarks: ____________________________________________________________________", $vals, $pStyle);
        $sigCell->addText("Teacher's Signature: _______________   Date: _______________   Head Teacher's Signature: _______________   Date: _______________", $vals, $pStyle);

        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        return response()->download($tempFile, $filename . '.docx')->deleteFileAfterSend(true);
    }

    protected function buildPlanHtml(array $plan): string
    {
        $topic = $plan['topic'] ?? 'Lesson Plan';
        $subject = $plan['subject'] ?? '';
        $class = $plan['class'] ?? '';
        $term = $plan['term'] ?? '';
        $week = $plan['week'] ?? '';
        $schoolName = $plan['schoolName'] ?? '';
        $teacherName = $plan['teacherName'] ?? '';
        $duration = $plan['duration'] ?? '40 Minutes';
        $ageRange = $plan['ageRange'] ?? '';
        $date = $plan['date'] ?? now()->format('l, F j, Y');

        $objectives = $plan['behaviouralObjectives'] ?? [];
        $materials = $plan['instructionalMaterials'] ?? [];
        $previousKnowledge = $plan['previousKnowledge'] ?? '';
        $steps = $plan['lessonSteps'] ?? [];
        $evaluation = $plan['evaluation'] ?? '';
        $assignment = $plan['assignment'] ?? '';
        $summary = $plan['summary'] ?? '';
        $conclusion = $plan['conclusion'] ?? '';

        $objRows = '';
        foreach ($objectives as $obj) {
            $objRows .= '<tr><td style="padding:0 2px;font-size:7.5pt;border:1px solid #000" colspan="4">' . $obj . '</td></tr>';
        }

        $matRow = '';
        if (!empty($materials)) {
            $matRow = '<tr><td style="padding:1px 2px;font-size:7pt;font-weight:700;border:1px solid #000" colspan="4">Instructional Materials</td></tr>'
                . '<tr><td style="padding:1px 2px;font-size:7pt;border:1px solid #000" colspan="4">' . implode('; ', $materials) . '</td></tr>';
        }

        $prevRow = '';
        if ($previousKnowledge) {
            $prevRow = '<tr><td style="padding:1px 2px;font-size:7pt;font-weight:700;border:1px solid #000" colspan="4">Previous Knowledge</td></tr>'
                . '<tr><td style="padding:1px 2px;font-size:7pt;border:1px solid #000" colspan="4">' . $previousKnowledge . '</td></tr>';
        }

        $stepsHtml = '';
        foreach ($steps as $s) {
            $stepsHtml .= '<tr>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700;vertical-align:top;text-align:center">' . ($s['step'] ?? '') . '</td>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;vertical-align:top">' . ($s['teacherActivities'] ?? '') . '</td>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;vertical-align:top">' . ($s['learnerActivities'] ?? '') . '</td>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;vertical-align:top">' . ($s['learningPoints'] ?? '') . '</td>
            </tr>';
        }

        $evalRow = '';
        if ($evaluation) {
            $evalRow = '<tr><td style="padding:1px 2px;font-size:7pt;font-weight:700;border:1px solid #000" colspan="4">Evaluation / Assessment</td></tr>'
                . '<tr><td style="padding:1px 2px;font-size:7pt;border:1px solid #000" colspan="4">' . nl2br(e($evaluation)) . '</td></tr>';
        }

        $assignRow = '';
        if ($assignment) {
            $assignRow = '<tr><td style="padding:1px 2px;font-size:7pt;font-weight:700;border:1px solid #000" colspan="4">Assignment / Homework</td></tr>'
                . '<tr><td style="padding:1px 2px;font-size:7pt;border:1px solid #000" colspan="4">' . nl2br(e($assignment)) . '</td></tr>';
        }

        $summaryRow = '';
        if ($summary) {
            $summaryRow = '<tr><td style="padding:1px 2px;font-size:7pt;font-weight:700;border:1px solid #000" colspan="4">Summary</td></tr>'
                . '<tr><td style="padding:1px 2px;font-size:7pt;border:1px solid #000" colspan="4">' . $summary . '</td></tr>';
        }

        $conclusionRow = '';
        if ($conclusion) {
            $conclusionRow = '<tr><td style="padding:1px 2px;font-size:7pt;font-weight:700;border:1px solid #000" colspan="4">Conclusion</td></tr>'
                . '<tr><td style="padding:1px 2px;font-size:7pt;border:1px solid #000" colspan="4">' . $conclusion . '</td></tr>';
        }

        return $this->wrapHtml('
        <div style="max-width:210mm;margin:0 auto;page-break-inside:avoid;break-inside:avoid">
        <table style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:7.5pt">
            <tr>
                <th colspan="4" style="padding:3px;font-size:9pt;font-weight:700;text-align:center;background:#1a56db;color:#fff;border:1px solid #000">LESSON PLAN</th>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000;width:12%">School:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000;width:38%">' . $schoolName . '</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000;width:12%">Teacher:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000;width:38%">' . $teacherName . '</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000">Subject:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000">' . $subject . '</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000">Class:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000">' . $class . ($ageRange ? ' (' . $ageRange . ')' : '') . '</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000">Term:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000">' . $term . '</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000">Week:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000">' . $week . '</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000">Date:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000">' . $date . '</td>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000">Duration:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000">' . $duration . '</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000" colspan="2">Topic:</td>
                <td style="padding:1px 3px;font-size:7.5pt;border:1px solid #000" colspan="2">' . $topic . '</td>
            </tr>
            <tr>
                <td style="padding:1px 3px;font-size:7.5pt;font-weight:700;border:1px solid #000" colspan="4">Behavioural Objectives</td>
            </tr>
            ' . $objRows . '
            ' . $matRow . '
            ' . $prevRow . '
            <tr>
                <td style="padding:1px 2px;font-size:7pt;font-weight:700;text-align:center;background:#1a56db;color:#fff;border:1px solid #1a56db">Step</td>
                <td style="padding:1px 2px;font-size:7pt;font-weight:700;text-align:left;background:#1a56db;color:#fff;border:1px solid #1a56db">Teacher\'s Activities</td>
                <td style="padding:1px 2px;font-size:7pt;font-weight:700;text-align:left;background:#1a56db;color:#fff;border:1px solid #1a56db">Learners\' Activities</td>
                <td style="padding:1px 2px;font-size:7pt;font-weight:700;text-align:left;background:#1a56db;color:#fff;border:1px solid #1a56db">Learning Points</td>
            </tr>
            ' . ($stepsHtml ?: '<tr><td style="padding:2px;border:1px solid #000;font-size:7pt" colspan="4">No steps available</td></tr>') . '
            ' . $evalRow . '
            ' . $assignRow . '
            ' . $summaryRow . '
            ' . $conclusionRow . '
            <tr>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7pt;font-weight:700" colspan="4">Remarks</td>
            </tr>
            <tr>
                <td colspan="4" style="padding:1px 2px;border:1px solid #000;font-size:7pt;height:12px"></td>
            </tr>
            <tr>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Teacher\'s Signature:</b> _______________</td>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Date:</b> _______________</td>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Head Teacher\'s Signature:</b> _______________</td>
                <td style="padding:1px 2px;border:1px solid #000;font-size:7.5pt;width:25%"><b>Date:</b> _______________</td>
            </tr>
        </table>
        </div>
        ');
    }

    protected function buildExamHtml(array $exam): string
    {
        $title = $exam['title'] ?? 'Examination';
        $subject = $exam['subject'] ?? '';
        $duration = $exam['duration'] ?? 0;
        $totalMarks = $exam['totalMarks'] ?? 0;
        $instructions = $exam['instructions'] ?? '';
        $questions = $exam['questions'] ?? [];

        $qHtml = '';
        foreach ($questions as $i => $q) {
            $num = $i + 1;
            $options = $q['options'] ?? $q;
            $optA = $options['A'] ?? $q['optionA'] ?? '';
            $optB = $options['B'] ?? $q['optionB'] ?? '';
            $optC = $options['C'] ?? $q['optionC'] ?? '';
            $optD = $options['D'] ?? $q['optionD'] ?? '';
            $qHtml .= "<div class='question'>
                <p><strong>{$num}.</strong> {$q['question']}</p>
                <ul class='options'>
                    <li>A. {$optA}</li>
                    <li>B. {$optB}</li>
                    <li>C. {$optC}</li>
                    <li>D. {$optD}</li>
                </ul>
            </div>";
        }

        $answerHtml = '';
        foreach ($questions as $i => $q) {
            $num = $i + 1;
            $ans = $q['answer'] ?? $q['correctAnswer'] ?? '';
            $answerHtml .= "<tr><td>{$num}</td><td>{$ans}</td></tr>";
        }

        return $this->wrapHtml('
            <div class="header">
                <h1>' . $title . '</h1>
                <p class="meta">' . $subject . ' | Duration: ' . $duration . ' min | Total Marks: ' . $totalMarks . '</p>
            </div>
            ' . ($instructions ? '<h3>Instructions</h3><p>' . $instructions . '</p>' : '') . '
            <h3>Questions</h3>
            ' . $qHtml . '
            <div style="page-break-before: always;">
                <h3>Answer Key</h3>
                <table class="procedure-table">
                    <thead><tr><th>Question</th><th>Answer</th></tr></thead>
                    <tbody>' . $answerHtml . '</tbody>
                </table>
            </div>
        ');
    }

    protected function wrapHtml(string $body): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { size: A4; margin: 8mm 10mm; }
                body { font-family: Arial, sans-serif; font-size: 7.5pt; color: #000; margin: 0; padding: 0; }
                table { page-break-inside: avoid; break-inside: avoid; }
                tr { page-break-inside: avoid; break-inside: avoid; }
                td, th { page-break-inside: avoid; break-inside: avoid; }
                @media print {
                    body { margin: 0; padding: 0; }
                    table { font-size: 7pt !important; }
                }
            </style>
        </head>
        <body>' . $body . '</body></html>';
    }

    /**
     * Strip full-document wrapper for DOCX generation (PhpWord expects HTML fragment)
     */
    protected function extractBodyFragment(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*)<\/body>/si', $html, $m)) {
            $fragment = $m[1];
        } else {
            $fragment = $html;
        }
        // Remove <style> blocks (unsupported in DOCX)
        $fragment = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $fragment);
        // Fix void elements for XML compliance (skip already self-closed tags)
        $fragment = preg_replace('~<(br|hr|img)([^>]*?)(?<!/)>~i', '<$1$2 />', $fragment);
        return $fragment;
    }

    protected function downloadPdf(string $html, string $filename)
    {
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'serif']);
        return $pdf->download($filename . '.pdf');
    }

    protected function downloadDocx(string $html, string $filename)
    {
        $fragment = $this->extractBodyFragment($html);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->getStyle()->setPageSizeW(11906);
        $section->getStyle()->setPageSizeH(16838);
        Html::addHtml($section, $fragment, false, false);
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        return response()->download($tempFile, $filename . '.docx')->deleteFileAfterSend(true);
    }
}
