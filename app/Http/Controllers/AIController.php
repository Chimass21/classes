<?php

namespace App\Http\Controllers;


use App\Helpers\CurriculumData;
use App\Helpers\JsonDb;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AIController extends Controller
{
    protected OpenAIService $ai;
    protected const MAX_RETRIES = 2;

    public function __construct(OpenAIService $ai)
    {
        $this->ai = $ai;
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
                $data['topic'], $schoolName, $teacherName, $duration, $ageRange, $scheme,
                $data['subTopic'] ?? ''
            );

            Log::info('AI Lesson Plan Request', [
                'subject' => $data['subject'],
                'class' => $data['class'],
                'topic' => $data['topic'],
                'subTopic' => $data['subTopic'] ?? '',
                'prompt_length' => strlen($prompt),
            ]);

            $response = $this->ai->generate($prompt, true);

            Log::info('AI Lesson Plan Response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            if ($this->isRefusal($response)) {
                Log::warning('AI refused lesson plan request', ['topic' => $data['topic']]);
                return response()->json([
                    'success' => false,
                    'error' => 'The AI model declined to generate content for this topic. Please rephrase your topic or try a different subject.',
                ], 422);
            }

            $plan = json_decode($response, true);

            if (!is_array($plan) || empty($plan)) {
                $cleaned = $this->extractJson($response);
                if ($cleaned !== null) {
                    $plan = $cleaned;
                }
            }

            if (!is_array($plan) || empty($plan)) {
                Log::warning('AI returned non-JSON response for lesson plan', [
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
                    $retryResponse = $this->ai->generate($this->buildStrictRetryPrompt($prompt, $data['subject'], $data['topic'], $data['class']), true);

                    Log::info('AI Lesson Plan Retry Response', [
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
                    if (!is_array($plan) || empty($plan)) {
                        $cleaned = $this->extractJson($retryResponse);
                        if ($cleaned !== null) {
                            $plan = $cleaned;
                        }
                    }

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
            $difficulty = $data['difficulty'] ?? 'Standard';
            $periods = $data['periods'] ?? '2 Periods';
            $ageRange = CurriculumData::getAgeRange($data['class']);
            $scheme = CurriculumData::getSchemeOfWork($data['subject'], $data['class'], $data['term']);
            $userSubtopics = $data['subtopics'] ?? '';

            $prompt = $this->buildLessonNotePrompt(
                $data['subject'], $data['class'], $data['term'], $data['week'],
                $data['topic'], $periods, $difficulty, $ageRange, $scheme, $userSubtopics
            );

            Log::info('AI Lesson Note Request', [
                'subject' => $data['subject'],
                'class' => $data['class'],
                'topic' => $data['topic'],
                'difficulty' => $difficulty,
                'prompt_length' => strlen($prompt),
            ]);

            $response = $this->ai->generate($prompt, true);

            Log::info('AI Lesson Note Response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            if ($this->isRefusal($response)) {
                Log::warning('AI refused lesson note request', ['topic' => $data['topic']]);
                return response()->json([
                    'success' => false,
                    'error' => 'The AI model declined to generate content for this topic. Please rephrase your topic or try a different subject.',
                ], 422);
            }

            $note = json_decode($response, true);

            if (!is_array($note) || empty($note)) {
                $cleaned = $this->extractJson($response);
                if ($cleaned !== null) {
                    $note = $cleaned;
                }
            }

            if (!is_array($note) || empty($note)) {
                Log::warning('AI returned non-JSON response for lesson note', [
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
                    $retryResponse = $this->ai->generate($this->buildStrictRetryPrompt($prompt, $data['subject'], $data['topic'], $data['class']), true);

                    Log::info('AI Lesson Note Retry Response', [
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
                    if (!is_array($note) || empty($note)) {
                        $cleaned = $this->extractJson($retryResponse);
                        if ($cleaned !== null) {
                            $note = $cleaned;
                        }
                    }

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
                'count' => 'required|integer|min:1|max:200',
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

            Log::info('AI Questions Request', [
                'subject' => $data['subject'],
                'topic' => $data['topic'],
                'count' => $data['count'],
                'includeTheory' => $data['includeTheory'] ?? false,
                'hasLessonNote' => !empty($lessonNoteContent),
                'prompt_length' => strlen($prompt),
            ]);

            $response = $this->ai->generate($prompt, true);

            Log::info('AI Questions Response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            // If AI returned empty after all retries, fall back immediately
            if (empty(trim($response))) {
                Log::warning('AI returned empty response for questions, retrying with simpler prompt');
                return $this->fallbackToContentGenerator($data);
            }

            if ($this->isRefusal($response)) {
                Log::warning('AI refused questions request, retrying with simpler prompt', ['topic' => $data['topic']]);
                return $this->fallbackToContentGenerator($data);
            }

            $questions = json_decode($response, true);

            if (!is_array($questions) || empty($questions)) {
                $cleaned = $this->extractJson($response);
                if ($cleaned !== null) {
                    $questions = $cleaned;
                }
            }

            if (!is_array($questions) || empty($questions)) {
                Log::warning('AI returned non-JSON for questions, retrying with simpler prompt', [
                    'response_length' => strlen($response),
                    'response_preview' => substr($response, 0, 3000),
                ]);
                return $this->fallbackToContentGenerator($data);
            }

            $questionsArray = $questions['objectives'] ?? $questions;
            $hasValidFormat = is_array($questionsArray) && !empty($questionsArray) && isset($questionsArray[0]);

            if (!$hasValidFormat) {
                Log::warning('Questions rejected - invalid format', [
                    'decoded_structure' => is_array($questions) ? array_keys($questions) : 'not_array',
                ]);
                return $this->fallbackToContentGenerator($data);
            }

            $questionItems = $questions['objectives'] ?? $questions;

            // Validate quality and retry if needed
            $validated = $this->validateAndRetryQuestions($questionItems, $prompt, $data);
            if ($validated === null) {
                return $this->fallbackToContentGenerator($data);
            }
            $questionItems = $validated;

            // Normalize field names from various AI output formats
            $questionItems = $this->normalizeQuestionFields($questionItems);

            // Shuffle answers for better distribution
            $questionItems = $this->shuffleAnswers($questionItems);

            // Build response preserving theory questions if present
            $responseData = ['objectives' => $questionItems];
            if (!empty($questions['theoryQuestions'])) {
                $responseData['theoryQuestions'] = $questions['theoryQuestions'];
            }
            if (!empty($questions['essayQuestions'])) {
                $responseData['essayQuestions'] = $questions['essayQuestions'];
            }
            if (!empty($questions['structuredQuestions'])) {
                $responseData['structuredQuestions'] = $questions['structuredQuestions'];
            }

            return response()->json([
                'success' => true,
                'questions' => $responseData,
                'count' => $data['count'],
                'message' => $data['count'] . ' questions generated successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Questions generation failed, retrying with simpler prompt', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->fallbackToContentGenerator($data);
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

        $questions = $request->input('questions');

        // Normalize question field names before validation
        $questions = $this->normalizeQuestionFields($questions);

        // Validate questions before saving
        $validationErrors = $this->validateQuestionPool($questions, $data['topic'], $data['subject']);
        if (!empty($validationErrors)) {
            Log::warning('Save questions rejected due to quality issues', [
                'errors' => $validationErrors,
                'count' => count($questions),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Questions failed quality check: ' . implode('; ', array_slice($validationErrors, 0, 5)),
                'details' => $validationErrors,
            ], 422);
        }

        $user = Session::get('user');
        JsonDb::init();
        $db = JsonDb::get();

        $qsId = 'qs_' . uniqid();
        $qs = [
            'id' => $qsId,
            'teacherId' => $user['id'] ?? 'unknown',
            'source' => 'ai_generated',
            'sourceId' => null,
            'questions' => $questions,
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

    protected function buildLessonPlanPrompt($subject, $class, $term, $week, $topic, $schoolName, $teacherName, $duration, $ageRange, $scheme, $subTopic = ''): string
    {
        $weekScheme = '';
        $schemeSubtopics = [];
        foreach ($scheme as $s) {
            if (($s['week'] ?? 0) == $week) {
                $weekScheme = 'Scheme of Work topic: ' . ($s['topic'] ?? '') . '. Subtopics: ' . implode(', ', $s['subtopics'] ?? []);
                $schemeSubtopics = $s['subtopics'] ?? [];
                break;
            }
        }

        $userSubtopicsList = [];
        if (!empty($subTopic)) {
            $userSubtopicsList = array_map('trim', preg_split('/[,\n]+/', $subTopic));
            $userSubtopicsList = array_filter($userSubtopicsList);
        }

        $subtopicsInstruction = '';
        $objectiveSources = [];

        if (!empty($userSubtopicsList)) {
            $subtopicsInstruction = "\nUSER-PROVIDED SUB-TOPICS (each is ONE behavioural objective):\n";
            foreach ($userSubtopicsList as $i => $st) {
                $num = $i + 1;
                $subtopicsInstruction .= "  {$num}. {$st}\n";
                $objectiveSources[] = $st;
            }
            $subtopicsInstruction .= "\nGenerate exactly " . count($userSubtopicsList) . " behavioural objectives matching these sub-topics. Each objective becomes one lesson step and one evaluation question.";
        } elseif (!empty($schemeSubtopics)) {
            $subtopicsInstruction = "\nSCHEME SUB-TOPICS (each is ONE behavioural objective):\n";
            foreach ($schemeSubtopics as $i => $st) {
                $num = $i + 1;
                $subtopicsInstruction .= "  {$num}. {$st}\n";
                $objectiveSources[] = $st;
            }
        }

        $numSources = count($objectiveSources);
        $evalCount = $numSources > 0 ? $numSources : 5;
        $stepCountInstruction = '';
        if ($numSources > 0) {
            $stepCountInstruction = "Generate exactly {$numSources} behavioural objectives, {$numSources} lesson steps, and {$numSources} evaluation questions. Objective 1 → Step 1 → Evaluation Q1, Objective 2 → Step 2 → Evaluation Q2, etc.";
        } else {
            $stepCountInstruction = "Generate exactly 5 behavioural objectives, 5 lesson steps, and 5 evaluation questions. Objective 1 → Step 1 → Evaluation Q1, etc. This ensures the lesson plan fills the entire A4 page.";
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
{$subtopicsInstruction}

Generate a COMPLETE, DETAILED LESSON PLAN in STRICT JSON format for "{$topic}" in {$subject} for {$class}.

CRITICAL — FILL THE ENTIRE A4 PAGE:
- Write each behavioural objective as a full, detailed sentence (not a phrase).
- Each step's teacherActivities and learnerActivities must be 2-3 detailed sentences each (not just one line).
- learningPoints must be a substantive paragraph.
- Evaluation must contain {$evalCount} numbered questions (one per objective), each a full sentence.
- Summary and conclusion must each be at least 3-4 sentences.
- Previous knowledge must be 2-3 sentences about what students already know.

Return ONLY valid JSON with this exact structure (no markdown, no code fences):
{
  "behaviouralObjectives": ["Full detailed objective sentence 1.", "Full detailed objective sentence 2.", "Full detailed objective sentence 3."],
  "instructionalMaterials": ["Material 1", "Material 2", "Material 3"],
  "previousKnowledge": "2-3 sentences about what students already know related to this topic.",
  "lessonSteps": [
    {
      "step": 1,
      "teacherActivities": "2-3 detailed sentences describing what the teacher does in this step.",
      "learnerActivities": "2-3 detailed sentences describing what learners do in this step.",
      "learningPoints": "Substantive paragraph about the key learning point from this step."
    }
  ],
  "evaluation": "1. First evaluation question (matches Objective 1)?\\n2. Second evaluation question (matches Objective 2)?\\n3. Third evaluation question (matches Objective 3)?",
  "assignment": "Detailed take-home assignment (2-3 sentences).",
  "summary": "Substantive summary of the lesson (3-4 sentences).",
  "conclusion": "Concluding remarks connecting to next lesson (3-4 sentences)."
}

RULES:
- {$stepCountInstruction}
- Each objective, its corresponding step, and its evaluation question must cover the SAME sub-topic.
- Teacher and learner activities must be practical, detailed, and curriculum-based.
- ALL content must be about "{$topic}" specifically for {$class} level ({$ageRange}).
- Use Nigerian examples (₦aira, Nigerian locations, cultural contexts).
- Every field must contain substantial content — no empty or one-line entries.
- If the topic is "{$topic}", do NOT write about anything else.
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
            $subtopicInstruction = "\n\nYOU MUST COVER THESE SPECIFIC SUB-TOPICS IN ORDER:\n" . $userSubtopics;
        }

        return <<<PROMPT
You are a Nigerian curriculum expert. Write a DETAILED LESSON NOTE about "{$topic}" for {$subject} ({$class}, {$term}, Week {$week}). Difficulty: {$difficulty}.

{$weekScheme}
{$subtopicInstruction}

Return ONLY valid JSON (no markdown, no code fences). Use this exact structure:
{
  "topic": "{$topic}",
  "subtopics": ["All relevant subtopics under {$topic}", "one per array item"],
  "learningObjectives": ["5 specific learning objectives starting with 'By the end of the lesson, students should be able to:'"],
  "introduction": "4-6 sentence engaging intro connecting to prior knowledge, Nigeria context",
  "content": "FULL DETAILED HTML — 4 A4 pages when printed. Structure with <h3> and <h4> headings. Include ALL of these sections in order inside the content field:
   1. Introduction to {$topic}
   2. Definitions of key terms (use <ul> or <table>)
   3. Main body: detailed explanation of EACH subtopic — this is the longest section (2-3 A4 pages)
   4. Illustrations/diagrams (describe with <table> or structured text)
   5. Practical applications in Nigeria (₦aira, Nigerian cities, local contexts)
   6. Advantages and disadvantages (where relevant)
   7. Key points to remember (<ul> with 5-8 items)
   8. Conclusion
   Use <p>, <ul>/<ol>, <table> throughout. EVERY sentence about {$topic}.",
  "definitions": [
    {"term": "Key term 1", "definition": "Clear definition in context of {$topic}"}
  ],
  "examples": [
    {"title": "Example 1", "description": "Detailed worked example or illustration. 4-6 sentences."}
  ],
  "practicalApplications": ["Real-life application 1 of {$topic} in Nigeria", "Application 2"],
  "illustrations": ["Description of diagram, chart, or illustration for {$topic}"],
  "advantagesDisadvantages": {
    "advantages": ["Advantage 1", "Advantage 2"],
    "disadvantages": ["Disadvantage 1", "Disadvantage 2"]
  },
  "classroomActivities": [
    {"title": "Activity 1", "description": "Description of classroom activity"}
  ],
  "evaluationQuestions": ["5 evaluation questions about {$topic}"],
  "summary": "4-6 sentence comprehensive summary of the lesson",
  "assignment": "4-5 specific homework tasks for students",
  "keyPoints": ["5-8 key takeaways from the lesson"]
}

STRICT RULES:
- Every sentence MUST be about "{$topic}"
- Follow NERDC/UBEC Nigerian curriculum standards
- Cover ALL subtopics under {$topic} for {$class} level ({$ageRange})
- The content field MUST be ~4 A4 pages when printed — thorough, detailed, complete
- Use Nigeria-centric examples (₦aira, Nigerian cities, local culture)
- For calculation topics: include 5 fully solved examples. For non-calculation topics: include 3-4 illustrative examples
- Match language to {$class} level — simpler for primary, advanced for secondary
- Match difficulty "{$difficulty}": Simple=foundational, Standard=curriculum depth, Deep=advanced
- Suitable for both classroom teaching and self-study
- No placeholders — every field must be fully written
- Complete enough for a teacher to use directly in class
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
You are a Nigerian examination expert generating questions for the Nigerian {$subject} curriculum (NERDC/UBEC/WASSCE/NECO/JAMB).

Generate {$count} UNIQUE objective (multiple-choice) questions{$theoryPart} about "{$topic}" in {$subject} for {$class} ({$term}, Week {$week}).

CRITICAL RULES — FOLLOW EVERY ONE:

1. TOPIC RELEVANCE: Every single question MUST directly test knowledge of "{$topic}" in {$subject}. Do NOT write about any other topic. Each question must contain keywords from "{$topic}" in its text.

2. NO REPETITION: Every question stem must be completely unique. Do not repeat the same question with different wording. Do not repeat the same answer concept across different questions.

3. FOUR DIFFERENT OPTIONS: Each question must have exactly 4 answer options (A, B, C, D). All four options MUST be different from each other. Never use the same text for two different options in the same question.

4. ONE CORRECT ANSWER: Exactly one option must be correct. The other three must be plausible distractors that are factually incorrect but reasonable enough that a student who hasn't studied might choose them.

5. RANDOM ANSWER POSITION: Distribute the correct answer randomly among A, B, C, and D. Do NOT put the correct answer in the same position for multiple consecutive questions. Aim for roughly equal distribution (25% each).

6. REALISTIC OPTIONS: Options must be realistic, specific, and directly related to the question. Never use generic options like "All of the above", "None of the above", "Both A and B", or vague filler text.

7. CLASS-APPROPRIATE: Match difficulty to {$class} level. Difficulty should range from simple recall to application/analysis within the topic.

8. NIGERIA-CENTRIC: Use Nigerian contexts, names, places (Lagos, Abuja, Kano, Port Harcourt), currency (₦aira), and examples relevant to Nigerian students.

9. EXAM STANDARD: Follow WAEC/NECO/JAMB style — clear, unambiguous, testing understanding not trickery.

10. GRAMMAR & SPELLING: Use correct English grammar and spelling throughout.

11. INCLUDE id FIELD: Every question object MUST have an "id" field (sequential number starting from 1).

{$noteContext}

Return ONLY valid JSON in this exact format (no text before or after):
{
  "objectives": [
    {
      "id": 1,
      "question": "Unique question text about {$topic}?",
      "A": "First unique option",
      "B": "Second unique option",
      "C": "Third unique option",
      "D": "Fourth unique option",
      "answer": "C"
    }
  ]{$theoryPart}
}

VERIFY your output:
- Does every question mention "{$topic}"?
- Is every question stem unique?
- Are all 4 options different in each question?
- Is the correct answer in a different position per question (roughly 25% A, 25% B, 25% C, 25% D)?
- Are all options realistic and topic-specific?
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

    /**
     * Last-resort fallback when AI fails to produce valid questions.
     * Tries one final AI call with a minimal prompt, then returns error.
     */
    private function fallbackToContentGenerator(array $data): \Illuminate\Http\JsonResponse
    {
        Log::warning('Attempting last-resort AI fallback for questions', [
            'subject' => $data['subject'],
            'topic' => $data['topic'],
            'count' => $data['count'],
        ]);

        try {
            $subject = $data['subject'];
            $topic = $data['topic'];
            $count = $data['count'];
            $class = $data['class'] ?? 'SS1';

            $simplePrompt = "Generate {$count} multiple-choice questions about \"{$topic}\" in {$subject} for {$class} level. Return ONLY a JSON array with NO other text. Each question must have: 'id' (number), 'question' (text), 'A','B','C','D' (options), and 'answer' (A/B/C/D). Example: [{\"id\":1,\"question\":\"What is X?\",\"A\":\"opt1\",\"B\":\"opt2\",\"C\":\"opt3\",\"D\":\"opt4\",\"answer\":\"A\"}]";

            $retryResponse = $this->ai->generate($simplePrompt, false, 8192, 0.5);

            if (!empty(trim($retryResponse))) {
                $retryData = json_decode($retryResponse, true);
                if (!is_array($retryData) || empty($retryData)) {
                    $cleaned = $this->extractJson($retryResponse);
                    if ($cleaned !== null) {
                        $retryData = $cleaned;
                    }
                }
                if (is_array($retryData) && !empty($retryData)) {
                    $items = $retryData['objectives'] ?? $retryData;
                    if (is_array($items) && !empty($items) && isset($items[0])) {
                        $items = $this->normalizeQuestionFields($items);
                        $items = $this->shuffleAnswers($items);
                        return response()->json([
                            'success' => true,
                            'questions' => ['objectives' => $items],
                            'count' => $count,
                            'message' => $count . ' questions generated.',
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Last-resort AI fallback also failed', ['error' => $e->getMessage()]);
        }

        Log::error('All question generation attempts failed, returning error to user', [
            'subject' => $data['subject'],
            'topic' => $data['topic'],
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Unable to generate questions at this time. Please try again with a different topic or try again later.',
        ], 503);
    }

    /**
     * Validate questions quality and retry up to MAX_RETRIES times
     * if validation fails. Returns the validated items array or null
     * if all retries are exhausted (caller should handle fallback).
     */
    private function validateAndRetryQuestions(array $questionItems, string $prompt, array $data): ?array
    {
        $subject = $data['subject'];
        $topic = $data['topic'];
        $class = $data['class'] ?? 'SS1';
        $currentItems = $questionItems;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                // Retry with strict prompt
                try {
                    $retryPrompt = $this->buildStrictRetryPrompt($prompt, $subject, $topic, $class);
                    if (!empty($validationErrors)) {
                        $retryPrompt .= "\n\nPREVIOUS QUALITY ISSUES TO FIX:\n" . implode("\n", array_slice($validationErrors, 0, 5));
                    }
                    // Lower temperature for retries to get more focused/factual responses
                    $retryResponse = $this->ai->generate($retryPrompt, true, 16384, 0.4);

                    if ($this->isRefusal($retryResponse)) {
                        return null;
                    }

                    $retryData = json_decode($retryResponse, true);
                    if (!is_array($retryData) || empty($retryData)) {
                        $cleaned = $this->extractJson($retryResponse);
                        if ($cleaned !== null) {
                            $retryData = $cleaned;
                        }
                    }
                    if (!is_array($retryData) || empty($retryData)) {
                        continue;
                    }
                    $currentItems = $retryData['objectives'] ?? $retryData;
                } catch (\Exception $e) {
                    Log::warning('Question retry attempt failed', ['error' => $e->getMessage()]);
                    continue;
                }
            }

            // Validate the current items
            $validationErrors = $this->validateQuestionPool($currentItems, $topic, $subject);

            if (empty($validationErrors)) {
                return $currentItems;
            }

            Log::warning("Question validation failed (attempt {$attempt})", [
                'errors' => $validationErrors,
                'question_count' => count($currentItems),
            ]);
        }

        return null;
    }

    // --- QUALITY VALIDATION ---

    /**
     * Validate a set of questions for quality:
     * - No duplicate question text
     * - All 4 options are unique within each question
     * - Answer key is valid (A/B/C/D)
     * - Topic keywords appear in question text
     */
    private function validateQuestionPool(array $questions, string $topic, string $subject): array
    {
        $errors = [];
        $seenQuestions = [];
        $answerCount = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
        $topicLower = strtolower(trim($topic));
        $topicWords = array_filter(explode(' ', $topicLower), fn($w) => strlen($w) > 2);
        if (empty($topicWords)) {
            $topicWords = [$topicLower];
        }

        foreach ($questions as $i => $q) {
            $qNum = $i + 1;
            $questionText = trim($q['question'] ?? '');

            // Check question text exists
            if (empty($questionText)) {
                $errors[] = "Question {$qNum} has empty question text";
                continue;
            }

            // Check for duplicate question text
            $qLower = strtolower($questionText);
            foreach ($seenQuestions as $seen) {
                similar_text($qLower, $seen, $pct);
                if ($pct > 85) {
                    $errors[] = "Question {$qNum} is too similar to another question ({$pct}% match)";
                    break;
                }
            }
            $seenQuestions[] = $qLower;

            // Check answer key is valid
            $answer = strtoupper(trim($q['answer'] ?? ''));
            if (!in_array($answer, ['A', 'B', 'C', 'D'], true)) {
                $errors[] = "Question {$qNum} has invalid answer '{$answer}' (must be A, B, C, or D)";
                continue;
            }
            $answerCount[$answer]++;

            // Collect options
            $options = [];
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $opt = trim($q[$letter] ?? '');
                if (empty($opt)) {
                    $errors[] = "Question {$qNum} option {$letter} is empty";
                }
                $options[$letter] = $opt;
            }

            // Check all 4 options are different
            $uniqueOptions = array_unique(array_values($options));
            if (count($uniqueOptions) < 4) {
                $repeated = array_keys(array_filter(array_count_values(array_map('strtolower', $options)), fn($c) => $c > 1));
                $errors[] = "Question {$qNum} has duplicate options: " . implode(', ', $repeated);
            }

            // Check topic relevance
            $qTextLower = strtolower($questionText);
            $topicMatchCount = 0;
            foreach ($topicWords as $word) {
                if (str_contains($qTextLower, $word)) {
                    $topicMatchCount++;
                }
            }
            if (count($topicWords) > 0 && ($topicMatchCount / count($topicWords)) < 0.3) {
                $errors[] = "Question {$qNum} does not contain topic keywords from '{$topic}'";
            }
        }

        // Check answer distribution (should be roughly even)
        $total = count($questions);
        if ($total >= 10) {
            $expected = $total / 4;
            foreach ($answerCount as $letter => $count) {
                if ($count === 0) {
                    $errors[] = "No correct answers placed in option {$letter} — distribution is severely imbalanced";
                } elseif ($total >= 20 && $count < $expected * 0.3) {
                    $errors[] = "Option {$letter} has only {$count} correct answers (expected ~{$expected}) — distribution is imbalanced";
                }
            }
        }

        return $errors;
    }

    /**
     * Randomly shuffle the correct answer position among A/B/C/D
     * while keeping the correct answer content attached to the new position.
     */
    /**
     * Normalize question field names from various AI output formats to standard names.
     * Maps: text/stem/questionText -> question, correctAnswer/correct_answer/ans -> answer,
     * option_a/options.A -> A, etc.
     */
    private function normalizeQuestionFields(array $questions): array
    {
        $normalized = [];
        foreach ($questions as $i => $q) {
            $item = [
                'id' => $q['id'] ?? ($i + 1),
                'question' => $q['question'] ?? $q['text'] ?? $q['stem'] ?? $q['questionText'] ?? $q['q'] ?? '',
                'A' => $q['A'] ?? $q['option_a'] ?? (isset($q['options']['A']) ? $q['options']['A'] : ''),
                'B' => $q['B'] ?? $q['option_b'] ?? (isset($q['options']['B']) ? $q['options']['B'] : ''),
                'C' => $q['C'] ?? $q['option_c'] ?? (isset($q['options']['C']) ? $q['options']['C'] : ''),
                'D' => $q['D'] ?? $q['option_d'] ?? (isset($q['options']['D']) ? $q['options']['D'] : ''),
                'answer' => $q['answer'] ?? $q['correctAnswer'] ?? $q['correct_answer'] ?? $q['ans'] ?? '',
            ];
            $normalized[] = $item;
        }
        return $normalized;
    }

    private function shuffleAnswers(array $questions): array
    {
        $letters = ['A', 'B', 'C', 'D'];
        $positionsUsed = [];

        foreach ($questions as $i => $q) {
            $currentAnswer = strtoupper(trim($q['answer'] ?? 'A'));
            $correctContent = $q[$currentAnswer] ?? '';

            // Pick a random position, avoiding the same position as previous question
            $available = $letters;
            if (!empty($positionsUsed)) {
                $lastPos = end($positionsUsed);
                $available = array_values(array_filter($letters, fn($l) => $l !== $lastPos));
            }
            $newPos = $available[array_rand($available)];
            $positionsUsed[] = $newPos;

            // Build new option set with correct answer at new position
            $newOptions = [];
            $wrongContents = [];
            foreach ($letters as $l) {
                if ($l !== $currentAnswer) {
                    $wrongContents[] = $q[$l] ?? '';
                }
            }
            shuffle($wrongContents);

            $wrongIdx = 0;
            foreach ($letters as $l) {
                if ($l === $newPos) {
                    $newOptions[$l] = $correctContent;
                } else {
                    $newOptions[$l] = $wrongContents[$wrongIdx++];
                }
            }

            $questions[$i]['A'] = $newOptions['A'];
            $questions[$i]['B'] = $newOptions['B'];
            $questions[$i]['C'] = $newOptions['C'];
            $questions[$i]['D'] = $newOptions['D'];
            $questions[$i]['answer'] = $newPos;
        }

        return $questions;
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
                       (is_array($content['instructionalMaterials'] ?? null) ? implode(' ', $content['instructionalMaterials']) : ($content['instructionalMaterials'] ?? '')) . ' ' .
                       implode(' ', array_map(fn($s) => ($s['teacherActivities'] ?? '') . ' ' . ($s['learnerActivities'] ?? '') . ' ' . ($s['learningPoints'] ?? ''), $content['lessonSteps'] ?? [])) . ' ' .
                       ($content['evaluation'] ?? '') . ' ' .
                       ($content['summary'] ?? '');
        } elseif ($type === 'lesson_note') {
            $allText = ($content['content'] ?? '') . ' ' .
                       ($content['introduction'] ?? '') . ' ' .
                       ($content['summary'] ?? '') . ' ' .
                       ($content['detailedNote'] ?? '') . ' ' .
                       implode(' ', is_array($content['subtopics'] ?? []) ? $content['subtopics'] : []);
        } elseif ($type === 'questions') {
            $items = $content['objectives'] ?? $content;
            if (is_array($items)) {
                $parts = [];
                foreach ($items as $q) {
                    $parts[] = $q['question'] ?? '';
                    foreach (['A', 'B', 'C', 'D', 'options', 'optionA', 'optionB', 'optionC', 'optionD'] as $opt) {
                        if (isset($q[$opt])) {
                            $parts[] = is_string($q[$opt]) ? $q[$opt] : '';
                        }
                    }
                }
                $allText = implode(' ', $parts);
            }
        }

        $allText = strtolower($allText);

        if (empty(trim($allText))) {
            return false;
        }

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

        $subjectWords = array_filter(explode(' ', $subjectLower), fn($w) => strlen($w) > 2);
        $subjectFound = empty($subjectWords) || !empty(array_filter($subjectWords, fn($w) => str_contains($allText, $w)));

        $pass = true;
        $reasons = [];

        if ($topicScore < 0.3) {
            $pass = false;
            $reasons[] = "topicScore={$topicScore}";
        }
        if (!$subjectFound && $topicScore < 0.6) {
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

    private function extractJson(string $text): ?array
    {
        $text = trim($text);
        if (empty($text)) {
            return null;
        }

        // Remove BOM characters and all markdown fences
        $text = preg_replace('/^\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE/', '', $text);
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = str_replace('`', '', $text);
        $text = trim($text);

        // Fix trailing commas before closing braces/brackets
        $text = preg_replace('/,\s*([}\]])/', '$1', $text);

        // Try direct decode first
        $decoded = json_decode($text, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }

        // Try to extract each top-level JSON block with proper bracket matching
        $blocks = $this->extractJsonBlocks($text);
        foreach ($blocks as $block) {
            $candidate = preg_replace('/,\s*([}\]])/', '$1', trim($block));
            $decoded = json_decode($candidate, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Extract all top-level JSON objects/arrays from text,
     * properly handling nested braces/brackets and strings.
     */
    private function extractJsonBlocks(string $text): array
    {
        $blocks = [];
        $len = strlen($text);
        $i = 0;

        while ($i < $len) {
            $char = $text[$i];
            if ($char === '{' || $char === '[') {
                $depth = 0;
                $inString = false;
                $escaped = false;
                $openChar = $char;
                $closeChar = $openChar === '{' ? '}' : ']';
                $start = $i;
                $j = $i;

                while ($j < $len) {
                    $c = $text[$j];
                    if ($escaped) {
                        $escaped = false;
                        $j++;
                        continue;
                    }
                    if ($c === '\\') {
                        $escaped = true;
                        $j++;
                        continue;
                    }
                    if ($c === '"') {
                        $inString = !$inString;
                        $j++;
                        continue;
                    }
                    if (!$inString) {
                        if ($c === $openChar) {
                            $depth++;
                        } elseif ($c === $closeChar) {
                            $depth--;
                            if ($depth === 0) {
                                $blocks[] = substr($text, $start, $j - $start + 1);
                                break;
                            }
                        }
                    }
                    $j++;
                }
                $i = $j;
            }
            $i++;
        }

        return $blocks;
    }
}
