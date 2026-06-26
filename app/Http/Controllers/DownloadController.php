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

        $html = $this->buildPlanHtml($plan);
        if ($format === 'pdf') return $this->downloadPdf($html, 'lesson_plan_' . $id);
        if ($format === 'docx') return $this->downloadDocx($html, 'lesson_plan_' . $id);
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

        $objHtml = '';
        foreach ($objectives as $obj) {
            $objHtml .= '<li>' . $obj . '</li>';
        }

        $matHtml = '';
        foreach ($materials as $mat) {
            $matHtml .= '<li>' . $mat . '</li>';
        }

        $stepsHtml = '';
        foreach ($steps as $s) {
            $stepsHtml .= '<tr>
                <td>' . ($s['step'] ?? '') . '</td>
                <td>' . ($s['teacherActivities'] ?? '') . '</td>
                <td>' . ($s['learnerActivities'] ?? '') . '</td>
                <td>' . ($s['learningPoints'] ?? '') . '</td>
            </tr>';
        }

        return $this->wrapHtml('
            <div class="header">
                <h1>LESSON PLAN</h1>
                <p class="meta">' . $subject . ' | ' . $class . ' | ' . $term . ' | Week ' . $week . '</p>
            </div>
            <table class="info-table">
                <tr><td><strong>School:</strong> ' . $schoolName . '</td><td><strong>Teacher:</strong> ' . $teacherName . '</td></tr>
                <tr><td><strong>Subject:</strong> ' . $subject . '</td><td><strong>Class:</strong> ' . $class . ' (' . $ageRange . ')</td></tr>
                <tr><td><strong>Term:</strong> ' . $term . '</td><td><strong>Week:</strong> ' . $week . '</td></tr>
                <tr><td><strong>Date:</strong> ' . $date . '</td><td><strong>Duration:</strong> ' . $duration . '</td></tr>
                <tr><td colspan="2"><strong>Topic:</strong> ' . $topic . '</td></tr>
            </table>
            <h3>Behavioural Objectives</h3>
            <ol>' . $objHtml . '</ol>
            ' . ($matHtml ? '<h3>Instructional Materials</h3><ul>' . $matHtml . '</ul>' : '') . '
            ' . ($previousKnowledge ? '<h3>Previous Knowledge</h3><p>' . $previousKnowledge . '</p>' : '') . '
            <h3>Lesson Procedure</h3>
            <table class="procedure-table">
                <thead><tr><th>Step</th><th>Teacher&#039;s Activities</th><th>Learners&#039; Activities</th><th>Learning Points</th></tr></thead>
                <tbody>' . $stepsHtml . '</tbody>
            </table>
            ' . ($evaluation ? '<h3>Evaluation</h3><p>' . nl2br(e($evaluation)) . '</p>' : '') . '
            ' . ($assignment ? '<h3>Assignment</h3><p>' . nl2br(e($assignment)) . '</p>' : '') . '
            ' . ($summary ? '<h3>Summary</h3><p>' . $summary . '</p>' : '') . '
            ' . ($conclusion ? '<h3>Conclusion</h3><p>' . $conclusion . '</p>' : '') . '
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
            $qHtml .= "<div class='question'>
                <p><strong>{$num}.</strong> {$q['question']}</p>
                <ul class='options'>
                    <li>A. " . ($options['A'] ?? '') . "</li>
                    <li>B. " . ($options['B'] ?? '') . "</li>
                    <li>C. " . ($options['C'] ?? '') . "</li>
                    <li>D. " . ($options['D'] ?? '') . "</li>
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
                body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.6; color: #000; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1a56db; padding-bottom: 10px; }
                .header h1 { font-size: 18pt; font-weight: bold; color: #1a56db; margin: 0 0 5px 0; }
                .header .meta { font-size: 11pt; color: #555; margin: 0; }
                h2 { font-size: 14pt; color: #1a56db; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
                h3 { font-size: 12pt; color: #333; margin-top: 15px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 11pt; }
                table.info-table td { padding: 4px 8px; border: 1px solid #ddd; }
                table.procedure-table th, table.procedure-table td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
                table.procedure-table th { background-color: #1a56db; color: white; font-weight: bold; }
                table.procedure-table tr:nth-child(even) { background-color: #f8f9fa; }
                .example { margin: 8px 0; padding: 8px; background: #f8f9fa; border-left: 3px solid #1a56db; }
                .question { margin: 12px 0; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
                .options { list-style: none; padding-left: 20px; }
                .options li { margin: 3px 0; }
                .detailed-note { white-space: pre-wrap; font-size: 11pt; }
                ol, ul { margin: 5px 0; padding-left: 20px; }
                li { margin: 3px 0; }
                .content { margin: 15px 0; }
                @media print { body { padding: 0; } table.procedure-table th { background-color: #1a56db !important; color: white !important; } }
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
