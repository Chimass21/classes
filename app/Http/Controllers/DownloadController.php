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

        $examplesHtml = '';
        foreach ($examples as $ex) {
            $examplesHtml .= '<div class="example"><strong>' . ($ex['title'] ?? 'Example') . ':</strong> ' . ($ex['description'] ?? '') . '</div>';
        }

        $evalHtml = '';
        foreach ($evaluation as $eq) {
            $evalHtml .= '<li>' . $eq . '</li>';
        }

        return $this->wrapHtml('
            <div class="header">
                <h1>' . $topic . '</h1>
                <p class="meta">' . $subject . ' | ' . $class . ' | ' . $term . ' | Week ' . $week . '</p>
            </div>
            <div class="content">
                ' . $content . '
                ' . ($examplesHtml ? '<h3>Examples</h3>' . $examplesHtml : '') . '
                ' . ($evalHtml ? '<h3>Evaluation Questions</h3><ol>' . $evalHtml . '</ol>' : '') . '
                ' . ($assignment ? '<h3>Assignment</h3><p>' . nl2br(e($assignment)) . '</p>' : '') . '
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
