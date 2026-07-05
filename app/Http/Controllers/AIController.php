<?php

namespace App\Http\Controllers;

use App\Helpers\CurriculumData;
use App\Helpers\JsonDb;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AIController extends Controller
{
    protected GeminiService $gemini;
    protected const MAX_RETRIES = 1;

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
                'subTopic' => 'nullable|string',
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

            Log::info('Gemini Lesson Plan Request', [
                'subject' => $data['subject'],
                'class' => $data['class'],
                'topic' => $data['topic'],
                'subTopic' => $data['subTopic'] ?? '',
                'prompt_length' => strlen($prompt),
            ]);

            $response = $this->gemini->generate($prompt);

            Log::info('Gemini Lesson Plan Response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            if ($this->isRefusal($response)) {
                Log::warning('Gemini refused lesson plan request', ['topic' => $data['topic']]);
                return response()->json([
                    'success' => false,
                    'error' => 'The AI model declined to generate content for this topic. Please rephrase your topic or try a different subject.',
                ], 422);
            }

            $plan = json_decode($response, true);

            if (!is_array($plan) || empty($plan)) {
                Log::warning('Gemini returned non-JSON response for lesson plan', [
                    'response' => substr($response, 0, 1000),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate a valid lesson plan. The AI response was not in the expected format. Please try again.',
                ], 422);
            }

            if (!$this->isRelevantToTopic($plan, 'lesson_plan', $data['subject'], $data['topic'], $data['class'])) {
                Log::warning('Lesson plan rejected - not relevant to topic', [
                    'subject' => $data['subject'],
                    'topic' => $data['topic'],
                ]);

                if (self::MAX_RETRIES > 0) {
                    $retryResponse = $this->gemini->generate($this->buildStrictRetryPrompt($prompt, $data['subject'], $data['topic'], $data['class']));

                    Log::info('Gemini Lesson Plan Retry Response', [
                        'response_length' => strlen($retryResponse),
                        'response_preview' => substr($retryResponse, 0, 500),
                    ]);

                    if ($this->isRefusal($retryResponse)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'The AI model declined to generate content for this topic. Please rephrase your topic.',
                        ], 422);
                    }

                    $plan = json_decode($retryResponse, true);

                    if (is_array($plan) && !empty($plan) && $this->isRelevantToTopic($plan, 'lesson_plan', $data['subject'], $data['topic'], $data['class'])) {
                        return $this->storeAndReturnLessonPlan($plan, $data, $user, $teacherName, $schoolName, $duration, $ageRange);
                    }
                }

                return response()->json([
                    'success' => false,
                    'error' => 'The generated lesson plan did not focus on the requested topic. Please try again with a more specific topic.',
                ], 422);
            }

            return $this->storeAndReturnLessonPlan($plan, $data, $user, $teacherName, $schoolName, $duration, $ageRange);

        } catch (\Exception $e) {
            Log::error('Lesson plan generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
                'class' => 'nullable|string',
                'term' => 'nullable|string',
                'week' => 'nullable|integer|min:1|max:13',
                'topic' => 'required|string',
                'subTopic' => 'nullable|string',
                'difficulty' => 'nullable|string',
                'periods' => 'nullable|string',
                'subtopics' => 'nullable|string',
            ]);

            $user = Session::get('user');
            $data['class'] = $data['class'] ?? 'SS1';
            $data['term'] = $data['term'] ?? 'First Term';
            $data['week'] = $data['week'] ?? 1;
            $difficulty = $data['difficulty'] ?? 'Medium';
            $periods = $data['periods'] ?? '2 Periods';
            $ageRange = CurriculumData::getAgeRange($data['class']);
            $scheme = CurriculumData::getSchemeOfWork($data['subject'], $data['class'], $data['term']);
            $userSubtopics = $data['subtopics'] ?? '';

            $prompt = $this->buildLessonNotePrompt(
                $data['subject'], $data['class'], $data['term'], $data['week'],
                $data['topic'], $periods, $difficulty, $ageRange, $scheme, $userSubtopics
            );

            Log::info('Gemini Lesson Note Request', [
                'subject' => $data['subject'],
                'class' => $data['class'],
                'topic' => $data['topic'],
                'difficulty' => $difficulty,
                'prompt_length' => strlen($prompt),
            ]);

            $response = $this->gemini->generate($prompt);

            Log::info('Gemini Lesson Note Response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            if ($this->isRefusal($response)) {
                Log::warning('Gemini refused lesson note request', ['topic' => $data['topic']]);
                return response()->json([
                    'success' => false,
                    'error' => 'The AI model declined to generate content for this topic. Please rephrase your topic or try a different subject.',
                ], 422);
            }

            $note = json_decode($response, true);

            if (!is_array($note) || empty($note)) {
                Log::warning('Gemini returned non-JSON response for lesson note', [
                    'response' => substr($response, 0, 1000),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate a valid lesson note. The AI response was not in the expected format. Please try again.',
                ], 422);
            }

            if (!$this->isRelevantToTopic($note, 'lesson_note', $data['subject'], $data['topic'], $data['class'])) {
                Log::warning('Lesson note rejected - not relevant to topic', [
                    'subject' => $data['subject'],
                    'topic' => $data['topic'],
                ]);

                if (self::MAX_RETRIES > 0) {
                    $retryResponse = $this->gemini->generate($this->buildStrictRetryPrompt($prompt, $data['subject'], $data['topic'], $data['class']));

                    Log::info('Gemini Lesson Note Retry Response', [
                        'response_length' => strlen($retryResponse),
                        'response_preview' => substr($retryResponse, 0, 500),
                    ]);

                    if ($this->isRefusal($retryResponse)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'The AI model declined to generate content for this topic. Please rephrase your topic.',
                        ], 422);
                    }

                    $note = json_decode($retryResponse, true);

                    if (is_array($note) && !empty($note) && $this->isRelevantToTopic($note, 'lesson_note', $data['subject'], $data['topic'], $data['class'])) {
                        return $this->storeAndReturnLessonNote($note, $data, $user, $periods, $difficulty, $ageRange);
                    }
                }

                return response()->json([
                    'success' => false,
                    'error' => 'The generated lesson note did not focus on the requested topic. Please try again with a more specific topic.',
                ], 422);
            }

            return $this->storeAndReturnLessonNote($note, $data, $user, $periods, $difficulty, $ageRange);

        } catch (\Exception $e) {
            Log::error('Lesson note generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
                'subTopic' => 'nullable|string',
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

            Log::info('Gemini Questions Request', [
                'subject' => $data['subject'],
                'topic' => $data['topic'],
                'count' => $data['count'],
                'includeTheory' => $data['includeTheory'] ?? false,
                'hasLessonNote' => !empty($lessonNoteContent),
                'prompt_length' => strlen($prompt),
            ]);

            $response = $this->gemini->generate($prompt);

            Log::info('Gemini Questions Response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            if ($this->isRefusal($response)) {
                Log::warning('Gemini refused questions request', ['topic' => $data['topic']]);
                return response()->json([
                    'success' => false,
                    'error' => 'The AI model declined to generate questions for this topic. Please rephrase your topic or try a different subject.',
                ], 422);
            }

            $questions = json_decode($response, true);

            if (!is_array($questions) || empty($questions)) {
                Log::warning('Gemini returned non-JSON for questions', [
                    'response' => substr($response, 0, 1000),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate valid questions. The AI response was not in the expected format. Please try again.',
                ], 422);
            }

            $questionsArray = $questions['objectives'] ?? $questions;
            $hasValidFormat = is_array($questionsArray) && !empty($questionsArray) && isset($questionsArray[0]);

            if (!$hasValidFormat) {
                Log::warning('Questions rejected - invalid format');
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate questions. The response format was invalid. Please try again.',
                ], 422);
            }

            $questionItems = $questions['objectives'] ?? $questions;
            $hasOptions = !empty($questionItems) && (
                isset($questionItems[0]['A']) ||
                isset($questionItems[0]['options']) ||
                isset($questionItems[0]['optionA'])
            );

            if (!$hasOptions) {
                Log::warning('Questions rejected - no options found in response');

                if (self::MAX_RETRIES > 0) {
                    $retryResponse = $this->gemini->generate($this->buildStrictRetryPrompt($prompt, $data['subject'], $data['topic'], $data['class'] ?? 'SS1'));

                    Log::info('Gemini Questions Retry Response', [
                        'response_length' => strlen($retryResponse),
                        'response_preview' => substr($retryResponse, 0, 500),
                    ]);

                    if ($this->isRefusal($retryResponse)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'The AI model declined to generate questions. Please try again.',
                        ], 422);
                    }

                    $questions = json_decode($retryResponse, true);
                    if (is_array($questions) && !empty($questions)) {
                        $questionItems = $questions['objectives'] ?? $questions;
                        $hasOptionsAfterRetry = !empty($questionItems) && (
                            isset($questionItems[0]['A']) ||
                            isset($questionItems[0]['options']) ||
                            isset($questionItems[0]['optionA'])
                        );
                        if ($hasOptionsAfterRetry) {
                            $questionsArray = $questionItems;
                            $hasValidFormat = true;
                        }
                    }
                }

                if (!$hasValidFormat) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to generate questions with proper options. Please try again.',
                    ], 422);
                }
            }

            if (isset($questions['objectives'])) {
                $questionsArray = $questions['objectives'];
            }

            return response()->json([
                'success' => true,
                'questions' => $questionsArray,
                'count' => $data['count'],
                'message' => $data['count'] . ' questions generated with ' . ($data['includeTheory'] ? 'theory questions' : 'MCQ only') . '.',
            ]);

        } catch (\Exception $e) {
            Log::error('Questions generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
            'subTopic' => 'nullable|string',
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
            'subTopic' => $data['subTopic'] ?? '',
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
            'defaultMarks' => 'nullable|integer|min:1|max:100',
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

        $defaultMarks = $data['defaultMarks'] ?? 1;
        $totalMarks = count($formattedQuestions) * $defaultMarks;
        $duration = $data['duration'] ?? min(30, max(10, intdiv(count($formattedQuestions), 2)));

        $exam = [
            'id' => $examId,
            'title' => $data['title'] ?? ($qs['subject'] ?? 'Generated') . ' CBT Exam',
            'subject' => $qs['subject'] ?? 'General',
            'level' => 'Mixed',
            'topic' => $qs['topic'] ?? '',
            'subTopic' => $qs['subTopic'] ?? '',
            'duration' => $duration,
            'defaultMarks' => $defaultMarks,
            'totalMarks' => $totalMarks,
            'instructions' => 'Answer all questions. Each question carries ' . $defaultMarks . ' mark(s). No negative marking.',
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

CRITICAL — You MUST write ONLY about the EXACT topic specified below. Do NOT change the topic or write about anything else.

TOPIC (do not deviate): {$topic}

CONTEXT:
- Subject: {$subject}
- Class: {$class} (Age range: {$ageRange})
- Term: {$term}
- Week: {$week}
- School: {$schoolName}
- Teacher: {$teacherName}
- Duration: {$duration}
{$weekScheme}

Generate a COMPLETE LESSON PLAN in STRICT JSON format about "{$topic}" in {$subject} for {$class}. Every single objective, step, and evaluation must be directly about {$topic}.

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
- ALL content must be about "{$topic}" specifically for {$class} level ({$ageRange})
- Use Nigerian examples (₦aira, Nigerian locations, cultural contexts)
- Before writing, re-read the topic: "{$topic}"
- If the topic is "{$topic}", do NOT write about anything else
PROMPT;
    }

    protected function buildLessonNotePrompt($subject, $class, $term, $week, $topic, $periods, $difficulty, $ageRange, $scheme, $userSubtopics = ''): string
    {
        $weekScheme = '';
        foreach ($scheme as $s) {
            if (($s['week'] ?? 0) == $week) {
                $weekScheme = 'Scheme sub-topics: ' . implode(', ', $s['subtopics'] ?? []);
                break;
            }
        }

        $subtopicInstruction = '';
        if (!empty($userSubtopics)) {
            $subtopicInstruction = "\n\nYOU MUST COVER THESE SPECIFIC SUB-TOPICS IN ORDER:\n" . $userSubtopics . "\n\nStructure the content section with each sub-topic as a separate <h4> heading followed by detailed <p> explanations and <ul>/<ol> lists.";
        }

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
{$subtopicInstruction}

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
10. Tailor the depth and language to {$class} level ({$ageRange}) — simpler explanations for primary, more advanced for secondary
11. Match the difficulty level "{$difficulty}" — "Easy" means foundational concepts, "Medium" means standard curriculum depth, "Hard" means advanced/extension content
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
You are a Nigerian examination expert. Generate questions for the Nigerian curriculum.

Generate {$count} OBJECTIVE (multiple-choice) questions{$theoryPart} about "{$topic}" in {$subject} for {$class} ({$term}, Week {$week}).

CRITICAL: Every question MUST be directly about "{$topic}" in {$subject}. Do NOT write questions about any other topic.

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
- Every single question must test knowledge of "{$topic}"
- Strictly multiple-choice with exactly 4 options (A, B, C, D)
- Each question must have exactly one correct answer
- Questions should test understanding, not just recall
- Difficulty should range from simple to challenging for {$class} level
- Follow WAEC/NECO/JAMB standards
- Use Nigeria-centric contexts, names, and examples
- Ensure the correct answer is accurate
- Do NOT write about any topic other than "{$topic}"
PROMPT;
    }

    // --- STORE HELPERS ---

    protected function storeAndReturnLessonPlan(array $plan, array $data, $user, string $teacherName, string $schoolName, string $duration, string $ageRange)
    {
        $plan['subject'] = $data['subject'];
        $plan['class'] = $data['class'];
        $plan['term'] = $data['term'];
        $plan['week'] = $data['week'];
        $plan['topic'] = $data['topic'];
        $plan['subTopic'] = $data['subTopic'] ?? '';
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

        Log::info('Lesson plan stored successfully', ['planId' => $planId, 'topic' => $data['topic']]);

        return response()->json([
            'success' => true,
            'plan' => $plan,
            'planId' => $planId,
            'message' => 'Lesson plan generated successfully.',
        ]);
    }

    protected function storeAndReturnLessonNote(array $note, array $data, $user, string $periods, string $difficulty, string $ageRange)
    {
        $note['subject'] = $data['subject'];
        $note['class'] = $data['class'];
        $note['term'] = $data['term'];
        $note['week'] = $data['week'];
        $note['topic'] = $data['topic'];
        $note['subTopic'] = $data['subTopic'] ?? '';
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

        Log::info('Lesson note stored successfully', ['noteId' => $noteId, 'topic' => $data['topic']]);

        return response()->json([
            'success' => true,
            'note' => $note,
            'noteId' => $noteId,
            'message' => 'Lesson note generated successfully.',
        ]);
    }

    public function deleteLessonNote($noteId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['lessonNotes'] = array_values(array_filter($db['lessonNotes'], fn($n) => $n['id'] !== $noteId));
        JsonDb::save($db);
        return response()->json(['success' => true, 'message' => 'Lesson note deleted.']);
    }

    public function deleteLessonPlan($planId)
    {
        JsonDb::init();
        $db = JsonDb::get();
        $db['lessonPlans'] = array_values(array_filter($db['lessonPlans'], fn($p) => $p['id'] !== $planId));
        JsonDb::save($db);
        return response()->json(['success' => true, 'message' => 'Lesson plan deleted.']);
    }

    // --- VALIDATION ---

    private function isRelevantToTopic(array $content, string $type, string $subject, string $topic, string $class): bool
    {
        $topicLower = strtolower(trim($topic));
        $subjectLower = strtolower(trim($subject));

        $allText = '';

        if ($type === 'lesson_plan') {
            $allText = implode(' ', $content['behaviouralObjectives'] ?? []) . ' ' .
                       ($content['previousKnowledge'] ?? '') . ' ' .
                       implode(' ', array_map(fn($s) => ($s['teacherActivities'] ?? '') . ' ' . ($s['learnerActivities'] ?? '') . ' ' . ($s['learningPoints'] ?? ''), $content['lessonSteps'] ?? [])) . ' ' .
                       ($content['evaluation'] ?? '') . ' ' .
                       ($content['summary'] ?? '');
        } elseif ($type === 'lesson_note') {
            $allText = ($content['content'] ?? '') . ' ' .
                       ($content['introduction'] ?? '') . ' ' .
                       ($content['summary'] ?? '') . ' ' .
                       ($content['detailedNote'] ?? '') . ' ' .
                       implode(' ', is_array($content['subtopics'] ?? []) ? $content['subtopics'] : []);
        }

        $allText = strtolower($allText);

        if (empty(trim($allText))) {
            return false;
        }

        $subjectFound = str_contains($allText, $subjectLower);

        $topicWords = array_filter(explode(' ', $topicLower), fn($w) => strlen($w) > 2);
        if (empty($topicWords)) {
            $topicWords = [$topicLower];
        }

        $topicMatchCount = 0;
        foreach ($topicWords as $word) {
            if (str_contains($allText, $word)) {
                $topicMatchCount++;
            }
        }
        $topicScore = $topicMatchCount / count($topicWords);

        $pass = true;
        $reasons = [];

        if ($topicScore < 0.3) {
            $pass = false;
            $reasons[] = "topicScore={$topicScore}";
        }
        if (!$subjectFound && $type !== 'questions') {
            $pass = false;
            $reasons[] = 'subjectMissing';
        }

        if (!$pass) {
            Log::warning("AI relevance rejected [{$type}]: " . implode(', ', $reasons), [
                'subject' => $subject, 'topic' => $topic, 'class' => $class,
            ]);
        }

        return $pass;
    }

    private function isRefusal(string $response): bool
    {
        $lower = strtolower(trim($response));

        if (str_starts_with($lower, 'i cannot') && (str_contains($lower, 'generate') || str_contains($lower, 'provide') || str_contains($lower, 'write') || str_contains($lower, 'answer'))) {
            return true;
        }

        $refusalPhrases = [
            'cannot generate content',
            'cannot provide content',
            "can't generate content",
            "can't provide content",
            'outside my knowledge',
            'outside your knowledge',
            'outside my expertise',
            'i am not able to generate',
            'i am not able to provide',
            'i don\'t have information about',
            'i do not have information about',
            'not within my knowledge',
            'beyond my capabilities',
            'unable to generate content',
            'unable to provide content',
            'sorry, but i cannot',
            'i apologize, but i cannot',
            'as an ai, i cannot generate',
            'as an ai language model, i cannot',
        ];
        foreach ($refusalPhrases as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }
        return false;
    }

    protected function buildStrictRetryPrompt(string $originalPrompt, string $subject, string $topic, string $class): string
    {
        return $originalPrompt . "\n\n--- STRICT CORRECTION ---\n\nYour previous response was REJECTED because it was NOT about the requested topic.\n\nCRITICAL — READ CAREFULLY:\n- You MUST write ONLY about \"{$topic}\" in {$subject} for {$class}.\n- EVERY sentence must directly relate to \"{$topic}\".\n- Do NOT write about anything else.\n- Include the exact phrase \"{$topic}\" throughout your response.\n";
    }
}
