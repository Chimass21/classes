<?php

namespace App\Http\Controllers;

use App\Helpers\ContentGenerator;
use App\Helpers\CurriculumData;
use App\Helpers\JsonDb;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AIController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function generateLessonPlan(Request $request)
    {
        try {
            $data = $request->validate([
                'subject' => 'required|string',
                'class' => 'required|string',
                'term' => 'required|string',
                'week' => 'required|integer|min:1|max:13',
                'topic' => 'required|string',
                'schoolName' => 'nullable|string',
                'teacherName' => 'nullable|string',
                'duration' => 'nullable|string',
            ]);

            $user = Session::get('user');
            $teacherName = $data['teacherName'] ?? $user['name'] ?? 'Teacher';
            $schoolName = $data['schoolName'] ?? 'ClassPortal Academy';
            $duration = $data['duration'] ?? '40 Minutes';
            $ageRange = CurriculumData::getAgeRange($data['class']);
            $scheme = CurriculumData::getSchemeOfWork($data['subject'], $data['class'], $data['term']);

            $prompt = $this->buildLessonPlanPrompt(
                $data['subject'], $data['class'], $data['term'], $data['week'],
                $data['topic'], $schoolName, $teacherName, $duration, $ageRange, $scheme
            );

            $response = $this->gemini->generate($prompt);
            $plan = json_decode($response, true);

            if (!is_array($plan) || empty($plan)) {
                $plan = $this->fallbackLessonPlan(
                    $data['subject'], $data['class'], $data['term'], $data['week'],
                    $data['topic'], $schoolName, $teacherName, $duration, $ageRange
                );
            }

            $plan['subject'] = $data['subject'];
            $plan['class'] = $data['class'];
            $plan['term'] = $data['term'];
            $plan['week'] = $data['week'];
            $plan['topic'] = $data['topic'];
            $plan['schoolName'] = $schoolName;
            $plan['teacherName'] = $teacherName;
            $plan['duration'] = $duration;
            $plan['ageRange'] = $ageRange;
            $plan['date'] = now()->format('l, F j, Y');

            JsonDb::init();
            $db = JsonDb::get();
            $teacherId = $user['id'] ?? 'unknown';
            $planId = 'plan_' . uniqid();
            $db['lessonPlans'][] = array_merge($plan, [
                'id' => $planId,
                'teacherId' => $teacherId,
                'createdAt' => now()->toIso8601String(),
            ]);
            JsonDb::save($db);

            return response()->json([
                'success' => true,
                'plan' => $plan,
                'planId' => $planId,
                'message' => 'Lesson plan generated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateLessonNote(Request $request)
    {
        try {
            $data = $request->validate([
                'subject' => 'required|string',
                'class' => 'required|string',
                'term' => 'required|string',
                'week' => 'required|integer|min:1|max:13',
                'topic' => 'required|string',
                'difficulty' => 'nullable|string',
                'periods' => 'nullable|string',
            ]);

            $user = Session::get('user');
            $difficulty = $data['difficulty'] ?? 'Medium';
            $periods = $data['periods'] ?? '2 Periods';
            $ageRange = CurriculumData::getAgeRange($data['class']);
            $scheme = CurriculumData::getSchemeOfWork($data['subject'], $data['class'], $data['term']);

            $prompt = $this->buildLessonNotePrompt(
                $data['subject'], $data['class'], $data['term'], $data['week'],
                $data['topic'], $periods, $difficulty, $ageRange, $scheme
            );

            $response = $this->gemini->generate($prompt);
            $note = json_decode($response, true);

            if (!is_array($note) || empty($note)) {
                $note = $this->fallbackLessonNote(
                    $data['subject'], $data['class'], $data['term'], $data['week'],
                    $data['topic'], $periods, $difficulty, $ageRange
                );
            } else {
                $contentText = ($note['content'] ?? '') . ' ' . ($note['introduction'] ?? '') . ' ' . ($note['summary'] ?? '');
                $topicLower = strtolower($data['topic']);
                $relevanceScore = 0;
                foreach (explode(' ', $topicLower) as $word) {
                    if (strlen($word) > 3 && substr_count(strtolower($contentText), $word) > 0) {
                        $relevanceScore++;
                    }
                }
                $wordCount = str_word_count($topicLower);
                if ($wordCount > 1 && $relevanceScore < min(2, $wordCount)) {
                    $note = $this->fallbackLessonNote(
                        $data['subject'], $data['class'], $data['term'], $data['week'],
                        $data['topic'], $periods, $difficulty, $ageRange
                    );
                }
            }

            $note['subject'] = $data['subject'];
            $note['class'] = $data['class'];
            $note['term'] = $data['term'];
            $note['week'] = $data['week'];
            $note['topic'] = $data['topic'];
            $note['difficulty'] = $difficulty;
            $note['periods'] = $periods;
            $note['ageRange'] = $ageRange;

            JsonDb::init();
            $db = JsonDb::get();
            $teacherId = $user['id'] ?? 'unknown';
            $noteId = 'note_' . uniqid();
            $db['lessonNotes'][] = array_merge($note, [
                'id' => $noteId,
                'teacherId' => $teacherId,
                'createdAt' => now()->toIso8601String(),
            ]);
            JsonDb::save($db);

            return response()->json([
                'success' => true,
                'note' => $note,
                'noteId' => $noteId,
                'message' => 'Lesson note generated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateQuestions(Request $request)
    {
        try {
            $data = $request->validate([
                'subject' => 'required|string',
                'topic' => 'required|string',
                'class' => 'nullable|string',
                'term' => 'nullable|string',
                'week' => 'nullable|integer',
                'count' => 'required|integer|in:10,20,30,50,100',
                'includeTheory' => 'nullable|boolean',
                'lessonNoteId' => 'nullable|string',
            ]);

            $lessonNoteContent = '';
            if (!empty($data['lessonNoteId'])) {
                JsonDb::init();
                $db = JsonDb::get();
                foreach ($db['lessonNotes'] as $n) {
                    if ($n['id'] === $data['lessonNoteId']) {
                        $lessonNoteContent = json_encode($n);
                        break;
                    }
                }
            }

            $prompt = $this->buildQuestionsPrompt(
                $data['subject'], $data['topic'], $data['count'],
                $data['class'] ?? 'SS1', $data['term'] ?? 'First Term',
                $data['week'] ?? 1, $data['includeTheory'] ?? false, $lessonNoteContent
            );

            $response = $this->gemini->generate($prompt);
            $questions = json_decode($response, true);

            if (!is_array($questions) || empty($questions)) {
                $questions = $this->fallbackQuestions($data['subject'], $data['topic'], $data['count'], $data['includeTheory'] ?? false);
            } else {
                $objectives = $questions['objectives'] ?? [];
                $hasOptions = !empty($objectives) && (
                    isset($objectives[0]['A']) ||
                    isset($objectives[0]['options']) ||
                    isset($objectives[0]['optionA'])
                );
                if (!$hasOptions) {
                    $questions = $this->fallbackQuestions($data['subject'], $data['topic'], $data['count'], $data['includeTheory'] ?? false);
                }
            }

            return response()->json([
                'success' => true,
                'questions' => $questions,
                'count' => $data['count'],
                'message' => $data['count'] . ' questions generated with ' . ($data['includeTheory'] ? 'theory questions' : 'MCQ only') . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function saveGeneratedQuestions(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string',
            'topic' => 'required|string',
            'questions' => 'required|array',
            'questions.*.question' => 'required|string',
        ]);

        $user = Session::get('user');
        JsonDb::init();
        $db = JsonDb::get();

        $qsId = 'qs_' . uniqid();
        $qs = [
            'id' => $qsId,
            'teacherId' => $user['id'] ?? 'unknown',
            'source' => 'ai_generated',
            'sourceId' => null,
            'questions' => $request->input('questions'),
            'subject' => $data['subject'],
            'topic' => $data['topic'],
            'createdAt' => now()->toIso8601String(),
        ];
        $db['questionSets'][] = $qs;
        JsonDb::save($db);

        return response()->json(['success' => true, 'questionSetId' => $qsId, 'message' => 'Questions saved successfully.']);
    }

    public function convertQuestionsToExam(Request $request)
    {
        $data = $request->validate([
            'questionSetId' => 'required|string',
            'title' => 'nullable|string',
            'duration' => 'nullable|integer|min:1|max:180',
        ]);

        $user = Session::get('user');
        JsonDb::init();
        $db = JsonDb::get();

        $qs = null;
        foreach ($db['questionSets'] as $q) {
            if ($q['id'] === $data['questionSetId']) { $qs = $q; break; }
        }
        if (!$qs) {
            return response()->json(['success' => false, 'error' => 'Question set not found.'], 404);
        }

        $mcq = array_filter($qs['questions'], fn($q) => isset($q['options']) || isset($q['A']));
        $mcq = array_values($mcq);
        if (empty($mcq)) {
            return response()->json(['success' => false, 'error' => 'No objective questions found in the question set.'], 400);
        }

        $examId = 'exam_' . uniqid();
        $formattedQuestions = [];
        foreach ($mcq as $i => $q) {
            $formattedQuestions[] = [
                'id' => $i + 1,
                'question' => $q['question'] ?? $q['text'] ?? '',
                'optionA' => $q['A'] ?? $q['options']['A'] ?? $q['optionA'] ?? '',
                'optionB' => $q['B'] ?? $q['options']['B'] ?? $q['optionB'] ?? '',
                'optionC' => $q['C'] ?? $q['options']['C'] ?? $q['optionC'] ?? '',
                'optionD' => $q['D'] ?? $q['options']['D'] ?? $q['optionD'] ?? '',
                'correctAnswer' => $q['answer'] ?? $q['correctAnswer'] ?? $q['correct'] ?? 'A',
            ];
        }

        $totalMarks = count($formattedQuestions);
        $duration = $data['duration'] ?? min(30, max(10, intdiv($totalMarks, 2)));

        $exam = [
            'id' => $examId,
            'title' => $data['title'] ?? ($qs['subject'] ?? 'Generated') . ' CBT Exam',
            'subject' => $qs['subject'] ?? 'General',
            'level' => 'Mixed',
            'duration' => $duration,
            'totalMarks' => $totalMarks,
            'instructions' => 'Answer all questions. Each question carries 1 mark. No negative marking.',
            'questions' => $formattedQuestions,
            'creatorId' => $user['id'] ?? 'unknown',
            'creatorName' => $user['name'] ?? 'AI System',
            'isPublished' => false,
            'createdAt' => now()->toIso8601String(),
        ];

        $db['exams'][] = $exam;
        JsonDb::save($db);

        return response()->json([
            'success' => true,
            'exam' => $exam,
            'examId' => $examId,
            'message' => count($formattedQuestions) . ' questions converted to CBT exam format.',
        ]);
    }

    public function getQuestionSets()
    {
        JsonDb::init();
        $db = JsonDb::get();
        $user = Session::get('user');
        $userId = $user['id'] ?? '';
        $sets = array_filter($db['questionSets'] ?? [], fn($qs) => $qs['teacherId'] === $userId);
        return response()->json(['questionSets' => array_values($sets)]);
    }

    public function getQuestionSet($id)
    {
        JsonDb::init();
        $db = JsonDb::get();
        foreach ($db['questionSets'] as $qs) {
            if ($qs['id'] === $id) {
                return response()->json(['questionSet' => $qs]);
            }
        }
        return response()->json(['success' => false, 'error' => 'Question set not found.'], 404);
    }

    // --- PROMPT BUILDERS ---

    protected function buildLessonPlanPrompt($subject, $class, $term, $week, $topic, $schoolName, $teacherName, $duration, $ageRange, $scheme): string
    {
        $weekScheme = '';
        foreach ($scheme as $s) {
            if (($s['week'] ?? 0) == $week) {
                $weekScheme = 'Scheme of Work topic: ' . ($s['topic'] ?? '') . '. Subtopics: ' . implode(', ', $s['subtopics'] ?? []);
                break;
            }
        }

        return <<<PROMPT
You are a Nigerian curriculum expert and professional lesson plan writer for the Nigerian (NERDC/UBEC) curriculum.

Generate a COMPLETE LESSON PLAN in STRICT JSON format only. No markdown, no explanations, just JSON.

CONTEXT:
- Subject: {$subject}
- Class: {$class} (Age range: {$ageRange})
- Term: {$term}
- Week: {$week}
- Topic: {$topic}
- School: {$schoolName}
- Teacher: {$teacherName}
- Duration: {$duration}
{$weekScheme}

The lesson plan MUST be designed for the Nigerian curriculum and Nigerian classroom context.

Return JSON in this exact structure:
{
  "behaviouralObjectives": ["By the end of the lesson, students should be able to: 1. ...", "2. ...", "3. ..."],
  "instructionalMaterials": ["List of materials needed"],
  "previousKnowledge": "Statement about what students already know",
  "lessonSteps": [
    {
      "step": 1,
      "teacherActivities": "What teacher does in this step",
      "learnerActivities": "What learners do in this step",
      "learningPoints": "Key learning point from this step"
    }
  ],
  "evaluation": "Evaluation questions based on objectives",
  "assignment": "Take-home assignment",
  "summary": "Brief summary of the lesson",
  "conclusion": "Conclusion and wrap-up"
}

RULES:
- Number of lessonSteps MUST equal number of behaviouralObjectives
- Each objective must have a corresponding lesson step
- Each objective must have a corresponding evaluation item
- Teacher and learner activities must be practical and curriculum-based
- Content must be appropriate for {$class} level ({$ageRange})
- Use Nigerian examples (₦aira, Nigerian locations, cultural contexts)
- Lesson steps should progress logically from introduction to conclusion
PROMPT;
    }

    protected function buildLessonNotePrompt($subject, $class, $term, $week, $topic, $periods, $difficulty, $ageRange, $scheme): string
    {
        $weekScheme = '';
        foreach ($scheme as $s) {
            if (($s['week'] ?? 0) == $week) {
                $weekScheme = 'Scheme sub-topics: ' . implode(', ', $s['subtopics'] ?? []);
                break;
            }
        }

        $topicUpper = strtoupper($topic);
        return <<<PROMPT
You are a Nigerian curriculum expert and experienced subject teacher. Your ONLY task is to write a DETAILED LESSON NOTE about the EXACT topic specified below. DO NOT write about any other topic.

CRITICAL: Every single sentence you generate must be directly about the topic "{$topic}". If you write about anything else, the lesson note will be rejected.

CONTEXT:
- Subject: {$subject}
- Class: {$class} (Age range: {$ageRange})
- Term: {$term}
- Week: {$week}
- TOPIC (MUST FOLLOW EXACTLY): {$topic}
- Periods: {$periods}
- Difficulty: {$difficulty}
{$weekScheme}

Return ONLY valid JSON with this exact structure (no markdown, no code fences):
{
  "topic": "{$topic}",
  "subtopics": ["Sub-topic 1 related to {$topic}", "Sub-topic 2 related to {$topic}", "Sub-topic 3 related to {$topic}"],
  "learningObjectives": ["By the end of the lesson, students should be able to: 1. ...", "2. ...", "3. ..."],
  "introduction": "Engaging introduction paragraph connecting to prior knowledge, directly about {$topic}",
  "content": "Detailed FULL HTML content with <h4> headings, <p> paragraphs, <ul>/<ol> lists. ALL content must be about {$topic}. Include definitions, explanations, and illustrations relevant to {$topic}.",
  "examples": [
    {"title": "Example 1 about {$topic}", "description": "Worked example with step-by-step solution related to {$topic}"}
  ],
  "classroomActivities": [
    {"title": "Activity 1 about {$topic}", "description": "Description of classroom activity related to {$topic}"}
  ],
  "summary": "Concise summary of the lesson focusing on {$topic}",
  "conclusion": "Concluding remarks about {$topic} and connection to next lesson",
  "evaluationQuestions": ["Question 1 about {$topic}", "Question 2 about {$topic}", "Question 3 about {$topic}"],
  "assignment": "Home assignment tasks related to {$topic}",
  "detailedNote": "Full comprehensive lesson note text about {$topic} with all subtopics explained in detail"
}

REQUIREMENTS:
1. EVERY sentence MUST be about "{$topic}" - nothing else
2. Content MUST be academically accurate and follow Nigerian curriculum standards
3. Cover ALL relevant subtopics under "{$topic}" for {$class} level ({$ageRange})
4. Be teacher-friendly and student-friendly
5. Use Nigeria-centric examples (₦aira, Nigerian cities, cultural references)
6. For Mathematics and Physics: include at least 10 fully solved examples progressing from simple to advanced, ALL related to {$topic}
7. For Chemistry with calculations: include at least 10 fully solved examples about {$topic}
8. Content must be curriculum-compliant (NERDC/UBEC approved)
9. The topic "{$topic}" MUST appear in every section
PROMPT;
    }

    protected function buildQuestionsPrompt($subject, $topic, $count, $class, $term, $week, $includeTheory, $lessonNoteContent): string
    {
        $theoryPart = $includeTheory ? '
  "theoryQuestions": [
    {"question": "Theory question 1", "answer": "Model answer"}
  ],
  "essayQuestions": [
    {"question": "Essay question 1", "guidance": "Key points to cover"}
  ],
  "structuredQuestions": [
    {"question": "Question with parts a, b, c", "parts": {"a": "Part a", "b": "Part b", "c": "Part c"}}
  ]' : '';

        $noteContext = $lessonNoteContent ? "\n\nBASE THE QUESTIONS ON THIS LESSON NOTE CONTENT:\n" . $lessonNoteContent : '';

        return <<<PROMPT
You are a Nigerian examination expert generating questions for the Nigerian curriculum.

Generate {$count} OBJECTIVE (multiple-choice) questions{$theoryPart} about "{$topic}" in {$subject} for {$class} ({$term}, Week {$week}).

{$noteContext}

Return ONLY valid JSON in this exact format:
{
  "objectives": [
    {
      "id": 1,
      "question": "Question text?",
      "A": "Option A",
      "B": "Option B",
      "C": "Option C",
      "D": "Option D",
      "answer": "A"
    }
  ]{$theoryPart}
}

RULES:
- Strictly multiple-choice with exactly 4 options (A, B, C, D)
- Each question must have exactly one correct answer
- Questions should test understanding, not just recall
- Difficulty should range from simple to challenging
- Follow WAEC/NECO/JAMB standards
- Use Nigeria-centric contexts
- Ensure the correct answer is accurate
- Questions must be directly based on the topic "{$topic}"
PROMPT;
    }

    // --- FALLBACK GENERATORS ---

    protected function fallbackLessonPlan($subject, $class, $term, $week, $topic, $schoolName, $teacherName, $duration, $ageRange): array
    {
        $gen = ContentGenerator::generateLessonPlan($subject, $class, $term, $week, $topic, $schoolName, $teacherName, $duration, $ageRange);

        return [
            'behaviouralObjectives' => $gen['objectives'],
            'instructionalMaterials' => $gen['materials'],
            'previousKnowledge' => $gen['previousKnowledge'],
            'lessonSteps' => $gen['steps'],
            'evaluation' => $gen['evaluation'],
            'assignment' => $gen['assignment'],
            'summary' => $gen['summary'],
            'conclusion' => $gen['conclusion'],
        ];
    }

    protected function fallbackLessonNote($subject, $class, $term, $week, $topic, $periods, $difficulty, $ageRange): array
    {
        $gen = ContentGenerator::generateLessonNote($subject, $class, $term, $week, $topic, $periods, $difficulty, $ageRange);

        return [
            'topic' => $gen['topic'],
            'subtopics' => $gen['subtopics'],
            'learningObjectives' => $gen['learningObjectives'],
            'introduction' => $gen['introduction'],
            'content' => $gen['content'],
            'examples' => $gen['examples'],
            'classroomActivities' => $gen['activities'],
            'summary' => $gen['summary'],
            'conclusion' => $gen['conclusion'],
            'evaluationQuestions' => $gen['evaluationQuestions'],
            'assignment' => $gen['assignment'],
            'detailedNote' => $gen['detailedNote'],
        ];
    }

    public function deleteLessonNote($noteId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['lessonNotes'] = array_values(array_filter($db['lessonNotes'], fn($n) => $n['id'] !== $noteId));
        JsonDb::save($db);
        return response()->json(['success' => true, 'message' => 'Lesson note deleted.']);
    }

    protected function fallbackQuestions($subject, $topic, $count, $includeTheory): array
    {
        return ContentGenerator::generateQuestions($subject, $topic, $count, $includeTheory);
    }
}
