<?php

namespace App\Http\Controllers;


use App\Helpers\ContentGenerator;
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
                $responsePreview = substr($response, 0, 2000);
                Log::warning('AI returned non-JSON response for lesson note', [
                    'response_length' => strlen($response),
                    'response_start' => $responsePreview,
                ]);
                $errorMsg = 'Failed to generate a valid lesson note. ';
                if (empty(trim($response))) {
                    $errorMsg .= 'The AI service returned an empty response. Please check your API configuration and try again.';
                } elseif (strlen($response) > 15000) {
                    $errorMsg .= 'The AI response was too large. Try a more specific topic.';
                } else {
                    $errorMsg .= 'The AI response was not in the expected format. Please try again.';
                }
                return response()->json([
                    'success' => false,
                    'error' => $errorMsg,
                ], 422);
            }

            if (!$this->isRelevantToTopic($note, 'lesson_note', $data['subject'], $data['topic'], $data['class'])) {
                Log::warning('Lesson note rejected - not relevant to topic', [
                    'subject' => $data['subject'],
                    'topic' => $data['topic'],
                ]);

                if (self::MAX_RETRIES > 0) {
                    $retryPrompt = $this->buildStrictRetryPrompt($prompt, $data['subject'], $data['topic'], $data['class'], 'lesson_note');
                    $retryResponse = $this->ai->generate($retryPrompt, true);

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
                'classLevel' => 'nullable|string',
                'term' => 'nullable|string',
                'week' => 'nullable|integer',
                'count' => 'required|integer|min:1|max:200',
                'includeTheory' => 'nullable|boolean',
                'lessonNoteId' => 'nullable|string',
                'noteContent' => 'nullable|string',
            ]);

            // Normalise frontend fields
            if (empty($data['class']) && !empty($data['classLevel'])) {
                $data['class'] = $data['classLevel'];
            }

            $lessonNoteContent = '';
            if (!empty($data['noteContent'])) {
                $lessonNoteContent = $data['noteContent'];
            } elseif (!empty($data['lessonNoteId'])) {
                JsonDb::init();
                $db = JsonDb::get();
                foreach ($db['lessonNotes'] as $n) {
                    if ($n['id'] === $data['lessonNoteId']) {
                        $lessonNoteContent = json_encode($n);
                        break;
                    }
                }
            }

            // Ensure every generation starts with a completely fresh context
            ContentGenerator::reset();

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
                'note_content_length' => strlen($lessonNoteContent),
            ]);

            $response = $this->ai->generate($prompt, true);

            Log::info('AI Questions Response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            if (empty(trim($response))) {
                Log::warning('AI returned empty response for questions', [
                    'has_lesson_note' => !empty($lessonNoteContent),
                    'prompt_length' => strlen($prompt),
                    'lesson_note_length' => strlen($lessonNoteContent),
                ]);
                if (!empty($lessonNoteContent)) {
                    return response()->json(['success' => false, 'error' => 'The AI could not generate questions from this lesson note. Please try again or adjust the lesson note content.'], 422);
                }
                return $this->fallbackToContentGenerator($data);
            }

            if ($this->isRefusal($response)) {
                Log::warning('AI refused questions request', [
                    'topic' => $data['topic'],
                    'has_lesson_note' => !empty($lessonNoteContent),
                ]);
                if (!empty($lessonNoteContent)) {
                    return response()->json(['success' => false, 'error' => 'The AI could not generate questions from this lesson note. Please try again.'], 422);
                }
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
                Log::warning('AI returned non-JSON for questions', [
                    'response_length' => strlen($response),
                    'response_preview' => substr($response, 0, 3000),
                ]);
                if (!empty($lessonNoteContent)) {
                    return response()->json(['success' => false, 'error' => 'The AI returned an invalid response. Please try again.'], 422);
                }
                return $this->fallbackToContentGenerator($data);
            }

            $questionsArray = $questions['objectives'] ?? $questions;
            $hasValidFormat = is_array($questionsArray) && !empty($questionsArray) && isset($questionsArray[0]);

            if (!$hasValidFormat) {
                Log::warning('Questions rejected - invalid format', [
                    'decoded_structure' => is_array($questions) ? array_keys($questions) : 'not_array',
                ]);
                if (!empty($lessonNoteContent)) {
                    return response()->json(['success' => false, 'error' => 'The AI returned questions in an unexpected format. Please try again.'], 422);
                }
                return $this->fallbackToContentGenerator($data);
            }

            $questionItems = $questions['objectives'] ?? $questions;

            // Validate quality and retry if needed
            $validated = $this->validateAndRetryQuestions($questionItems, $prompt, $data, !empty($lessonNoteContent));
            if ($validated === null) {
                if (!empty($lessonNoteContent)) {
                    return response()->json(['success' => false, 'error' => 'Generated questions did not pass quality validation. Please try again.'], 422);
                }
                return $this->fallbackToContentGenerator($data);
            }
            $questionItems = $validated;

            // Normalize field names from various AI output formats
            $questionItems = $this->normalizeQuestionFields($questionItems);

            // Skip topic relevance check when generating from a lesson note
            // (questions are based on note content, not topic keyword matching)
            if (empty($lessonNoteContent)) {
                $topicKeywords = array_filter(explode(' ', strtolower(trim($data['topic']))), fn($w) => strlen($w) > 2);
                if (empty($topicKeywords)) { $topicKeywords = [strtolower(trim($data['topic']))]; }
                $offTopicCount = 0;
                foreach ($questionItems as $q) {
                    $qText = strtolower($q['question'] ?? '');
                    $matches = 0;
                    foreach ($topicKeywords as $kw) {
                        if (str_contains($qText, $kw)) { $matches++; }
                    }
                    if ($matches === 0) { $offTopicCount++; }
                }
                if ($offTopicCount > 0) {
                    Log::warning("{$offTopicCount} off-topic questions detected — prepending topic to stems", [
                        'topic' => $data['topic'],
                        'total' => count($questionItems),
                    ]);
                    foreach ($questionItems as $i => $q) {
                        $qText = strtolower($q['question'] ?? '');
                        $matches = 0;
                        foreach ($topicKeywords as $kw) {
                            if (str_contains($qText, $kw)) { $matches++; }
                        }
                        if ($matches === 0) {
                            $questionItems[$i]['question'] = 'In the context of ' . $data['topic'] . ', ' . lcfirst(ltrim($q['question'] ?? '', '?.,;:!'));
                        }
                    }
                }
            }

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
        try {
            $data = $request->validate([
                'subject' => 'required|string',
                'topic' => 'required|string',
                'subTopic' => 'nullable|string',
                'questions' => 'required|array',
                'questions.*.question' => 'required|string',
            ]);

            $questions = $request->input('questions');
            Log::info('Save questions request', [
                'subject' => $data['subject'],
                'topic' => $data['topic'],
                'question_count' => count($questions),
                'sample' => count($questions) > 0 ? array_keys($questions[0] ?? []) : 'empty',
            ]);

            // Normalize question field names before validation
            $questions = $this->normalizeQuestionFields($questions);

            // Validate questions — warn on quality issues but do NOT block save
            $validationErrors = $this->validateQuestionPool($questions, $data['topic'], $data['subject']);
            if (!empty($validationErrors)) {
                Log::warning('Save questions quality warnings (non-blocking)', [
                    'warnings' => $validationErrors,
                    'count' => count($questions),
                ]);
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

            Log::info('Questions saved successfully', ['qsId' => $qsId, 'count' => count($questions)]);

            return response()->json(['success' => true, 'questionSetId' => $qsId, 'message' => 'Questions saved successfully.']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Save questions validation failed', ['errors' => $e->errors()]);
            return response()->json(['success' => false, 'error' => 'Validation error: ' . json_encode($e->errors())], 422);
        } catch (\Exception $e) {
            Log::error('Save questions failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'error' => 'Failed to save questions: ' . $e->getMessage()], 500);
        }
    }

    public function convertQuestionsToExam(Request $request)
    {
        try {
            $data = $request->validate([
                'questionSetId' => 'required|string',
                'title' => 'nullable|string',
                'duration' => 'nullable|integer|min:1|max:180',
                'defaultMarks' => 'nullable|integer|min:1|max:100',
            ]);

            Log::info('Convert to CBT request', ['questionSetId' => $data['questionSetId']]);

            $user = Session::get('user');
            JsonDb::init();
            $db = JsonDb::get();

            $qs = null;
            foreach ($db['questionSets'] as $q) {
                if ($q['id'] === $data['questionSetId']) { $qs = $q; break; }
            }
            if (!$qs) {
                Log::error('Question set not found for conversion', ['id' => $data['questionSetId']]);
                return response()->json(['success' => false, 'error' => 'Question set not found.'], 404);
            }

            // Get all questions — they were already normalized on save
            $allQuestions = $qs['questions'] ?? [];
            if (empty($allQuestions)) {
                Log::error('Question set has no questions', ['id' => $data['questionSetId']]);
                return response()->json(['success' => false, 'error' => 'Question set has no questions.'], 400);
            }

            // Try to find objective questions (have A/B/C/D options)
            $mcq = array_filter($allQuestions, fn($q) => isset($q['A']) || isset($q['B']) || isset($q['C']) || isset($q['D']) || isset($q['options']));
            $mcq = array_values($mcq);

            // If no objective questions found, use ALL questions as-is
            if (empty($mcq)) {
                Log::warning('No objective format detected, using all questions', ['count' => count($allQuestions)]);
                $mcq = $allQuestions;
            }

            $examId = 'exam_' . uniqid();
            $formattedQuestions = [];
            foreach ($mcq as $i => $q) {
                if (!is_array($q)) continue;
                $questionText = $q['question'] ?? $q['text'] ?? $q['stem'] ?? '';
                $formattedQuestions[] = [
                    'id' => $i + 1,
                    'question' => $questionText,
                    'optionA' => $q['A'] ?? $q['options']['A'] ?? $q['option_a'] ?? $q['optionA'] ?? '',
                    'optionB' => $q['B'] ?? $q['options']['B'] ?? $q['option_b'] ?? $q['optionB'] ?? '',
                    'optionC' => $q['C'] ?? $q['options']['C'] ?? $q['option_c'] ?? $q['optionC'] ?? '',
                    'optionD' => $q['D'] ?? $q['options']['D'] ?? $q['option_d'] ?? $q['optionD'] ?? '',
                    'correctAnswer' => $q['answer'] ?? $q['correctAnswer'] ?? $q['correct_answer'] ?? $q['correct'] ?? $q['ans'] ?? 'A',
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

            Log::info('CBT exam created successfully', [
                'examId' => $examId,
                'questionCount' => count($formattedQuestions),
                'duration' => $duration,
            ]);

            return response()->json([
                'success' => true,
                'exam' => $exam,
                'examId' => $examId,
                'message' => count($formattedQuestions) . ' questions converted to CBT exam format.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Convert to CBT validation failed', ['errors' => $e->errors()]);
            return response()->json(['success' => false, 'error' => 'Validation error: ' . json_encode($e->errors())], 422);
        } catch (\Exception $e) {
            Log::error('Convert to CBT failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'error' => 'Failed to convert to CBT: ' . $e->getMessage()], 500);
        }
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
        $minSteps = max(4, $numSources);
        $maxSteps = max(6, $numSources + 2);
        $evalCount = $minSteps;
        $stepCountInstruction = "Generate {$minSteps}-{$maxSteps} behavioural objectives, {$minSteps}-{$maxSteps} corresponding lesson steps, and {$minSteps}-{$maxSteps} evaluation questions. The exact number depends on how many distinct sub-topics naturally arise from \"{$topic}\". Each sub-topic becomes one objective → one step → one evaluation question. Aim for at least 4 steps; use up to {$maxSteps} if the topic merits it.";

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
- Evaluation must contain {$evalCount}-{$maxSteps} numbered questions (one per objective), each a full sentence.
- Summary and conclusion must each be at least 3-4 sentences.
- Previous knowledge must be 2-3 sentences about what students already know.

Return ONLY valid JSON with this exact structure (no markdown, no code fences). The arrays must have the SAME length (behaviouralObjectives count === lessonSteps count === evaluation question count):
{
  "behaviouralObjectives": ["Full detailed objective sentence 1.", "Full detailed objective sentence 2.", ...],
  "instructionalMaterials": ["Material 1", "Material 2", "Material 3", ...],
  "previousKnowledge": "2-3 sentences about what students already know related to this topic.",
  "lessonSteps": [
    {
      "step": 1,
      "teacherActivities": "2-3 detailed sentences describing what the teacher does in this step.",
      "learnerActivities": "2-3 detailed sentences describing what learners do in this step.",
      "learningPoints": "Substantive paragraph about the key learning point from this step."
    }
  ],
  "evaluation": "1. First evaluation question?\\n2. Second evaluation question?\\n3. Third evaluation question?\\n...",
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
        if ($lessonNoteContent) {
            return $this->buildQuestionsFromNotePrompt($subject, $topic, $count, $class, $term, $week, $includeTheory, $lessonNoteContent);
        }

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

        return <<<PROMPT
You are a Nigerian examination expert generating questions for the Nigerian {$subject} curriculum (NERDC/UBEC/WASSCE/NECO/JAMB).

Your task: Generate {$count} UNIQUE objective (multiple-choice) questions{$theoryPart} about "{$topic}" in {$subject} for {$class} ({$term}, Week {$week}).

STEP 1 — THINK ABOUT THE TOPIC SCOPE
Before writing any questions, think carefully about what "{$topic}" means in {$subject}. Consider its:
- Definition and key concepts
- Types, classifications, or categories
- Properties, characteristics, or features
- Causes, effects, or applications
- Related formulas, laws, or principles
- Real-world examples in Nigeria

Use ALL of these aspects to create diverse questions that cover the full scope of "{$topic}".

STEP 2 — STRICT TOPIC ENFORCEMENT
This is the MOST IMPORTANT rule. FAILURE means your response is rejected.

The topic is "{$topic}" in {$subject}. You MUST obey ALL of these:
- EVERY question's text (the stem) MUST contain the word "{$topic}" or a direct synonym/keyword from it
- EVERY question MUST test knowledge about "{$topic}" — not about a different topic
- If "{$topic}" is a subtopic (e.g., "Flame" in Chemistry), DO NOT write questions about the parent subject area ("Chemistry")
- If you don't know enough about "{$topic}" to write {$count} quality questions, write what you do know — do NOT invent questions about other topics
- ZERO questions about unrelated topics — this means NO questions about atoms, molecules, chemical bonding, etc. when the topic is "Flame"

STEP 3 — QUESTION STYLE DIVERSITY (CRITICAL)
You MUST vary every single question's opening and structure. Do NOT start most questions with What, Why, When, Where, Who, Which, or How. At most 2 out of every 10 questions may begin with a WH word. Use a balanced mix of the following styles:

1. COMMAND / DIRECTIVE — "State the function of {$topic} in the human body." / "List three characteristics of {$topic}." / "Define {$topic}." / "Identify the type of {$topic} shown in the diagram."
2. COMPLETION / FILL-THE-BLANK — "The process of {$topic} in plants is called ___." (presented as a stem with 4 options to complete it)
3. SCENARIO / APPLICATION — "A farmer notices that his crops are wilting despite adequate watering. This is most likely due to a problem with {$topic}. What should he check first?" / "If a student mixes solution X and solution Y and observes a colour change to blue, which of the following {$topic} reactions has occurred?"
4. TRUE / FALSE (presented as MCQ) — "Which of the following statements about {$topic} is correct?" or "Which of the following statements is true regarding {$topic}?"
5. COMPARISON / CONTRAST — "Which of the following distinguishes {$topic} from a related concept?" / "The main difference between type A and type B of {$topic} is that type A ___."
6. CLASSIFICATION — "Which of the following is an example of {$topic}?" / "Which of the following belongs to the category of {$topic}?"
7. CAUSE-EFFECT — "The main reason {$topic} occurs during the rainy season in Nigeria is ___." / "What is the primary effect of {$topic} on the ecosystem?"
8. FORMULA / CALCULATION — "Using the formula for {$topic}, calculate the value of ___." (stem contains the problem, options are numeric answers)
9. NEGATIVE / EXCEPTION — "All of the following are examples of {$topic} EXCEPT:" / "Which of the following is NOT a characteristic of {$topic}?"
10. SEQUENCE / ORDER — "Arrange the following steps of {$topic} in the correct order." / "Which of the following is the correct sequence for {$topic}?"

For every set of 10 questions, aim to cover at least 6 different styles from the list above.
If the subject is Mathematics or a calculation-based science, include at least 2-3 problem-solving/calculation questions.
If the subject is a language or arts, include more application/scenario and classification questions.

STEP 4 — WRITE THE QUESTIONS
For each question:
1. Pick one specific aspect of "{$topic}" to test
2. Choose a question style from the list above that best suits that aspect
3. Write a clear question stem using that style — NEVER default to a WH question
4. Write 4 distinct options (A, B, C, D) — one correct, three wrong but plausible
5. Randomize which letter has the correct answer (aim for ~25% A, 25% B, 25% C, 25% D across all questions)
6. NEVER repeat the same question or answer concept

STEP 5 — SELF-VERIFICATION
After writing all {$count} questions, check EVERY SINGLE ONE:
- Does the question stem contain "{$topic}" keyword? If NO, rewrite it.
- Is this question about "{$topic}" and NOT about some other topic? If NO, delete and replace it.
- Does this question start with What, Why, When, Where, Who, Which, or How? If YES, count it. At most 2 per 10 questions may be WH questions. Rewrite any excess WH questions into a different style.
- Are all 4 options unique? If NO, fix them.
- Is exactly one option correct and the other three wrong? If NO, fix.
- Did I use at least 6 different styles across every 10 questions? If NO, rewrite some to add variety.

Return ONLY valid JSON in this exact format (no text before or after):
{
  "objectives": [
    {
      "id": 1,
      "question": "Complete the following statement about {$topic}:",
      "A": "First unique option",
      "B": "Second unique option",
      "C": "Third unique option",
      "D": "Fourth unique option",
      "answer": "C"
    }
  ]{$theoryPart}
}
PROMPT;
    }

    protected function buildQuestionsFromNotePrompt($subject, $topic, $count, $class, $term, $week, $includeTheory, $lessonNoteContent): string
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

        $extract = $this->extractNoteText($lessonNoteContent);

        // Truncate lesson note to avoid exceeding AI context window
        // Prioritize the most content-rich parts
        $maxLength = 8000;
        if (strlen($extract) > $maxLength) {
            $truncated = mb_substr($extract, 0, $maxLength);
            $lastBreak = mb_strrpos($truncated, "\n\n");
            if ($lastBreak !== false && $lastBreak > $maxLength * 0.7) {
                $extract = mb_substr($extract, 0, $lastBreak);
            } else {
                $extract = $truncated;
            }
            $extract .= "\n\n[Note: Lesson note truncated to fit within context limits. Focus on the content above to generate questions.]";
        }

        Log::info('Building questions from lesson note', [
            'note_length' => strlen($lessonNoteContent),
            'extract_length' => strlen($extract),
            'truncated' => strlen($lessonNoteContent) > $maxLength,
        ]);

        return <<<PROMPT
You are a Nigerian examination expert. Your task is to generate {$count} objective (multiple-choice) questions based STRICTLY on the lesson note provided below for {$subject} ({$class}, {$term}, Week {$week}).

CRITICAL INSTRUCTION — READ CAREFULLY:
"Generate objective questions strictly from the lesson note provided below. Do not use outside knowledge or information that is not contained in the lesson note. Every question and answer must be supported directly by the lesson note."

LESSON NOTE CONTENT:
{$extract}

GENERATION RULES:
1. Read the complete lesson note above before generating any questions.
2. Generate questions ONLY from the content contained in the lesson note.
3. Never introduce facts, examples, or concepts that are not covered in the lesson note.
4. Ensure every question can be answered using only the lesson note.
5. Cover all major sections and subtopics in the lesson note.
6. Avoid repeating the same concept unnecessarily.
7. Produce balanced and comprehensive objective questions that fairly cover the full lesson.

QUESTION STYLE GUIDELINES:
- Vary question openings — do not start most questions with What, Why, When, Where, Who, Which, or How
- Use a mix of styles: command/directive, fill-the-blank, true/false, classification, cause-effect, scenario/application, negative/exception
- Each question must have 4 distinct options (A, B, C, D) — one correct, three wrong but plausible
- Randomize which letter has the correct answer across all questions
- Ensure wrong options are also derived from the lesson note content (not made up from outside knowledge)

SELF-VERIFICATION:
After writing all {$count} questions, verify EVERY SINGLE ONE:
- Can this question be answered using ONLY the lesson note above? If NO, rewrite or delete it.
- Does this question test a concept actually present in the lesson note? If NO, rewrite or delete it.
- Are all 4 options based on the lesson note content? If NO, fix them.
- Is exactly one option correct? If NO, fix.

Return ONLY valid JSON in this exact format (no text before or after):
{
  "objectives": [
    {
      "id": 1,
      "question": "Question text based strictly on the lesson note",
      "A": "Option A",
      "B": "Option B",
      "C": "Option C",
      "D": "Option D",
      "answer": "C"
    }
  ]{$theoryPart}
}
PROMPT;
    }

    protected function extractNoteText($lessonNoteContent): string
    {
        $data = json_decode($lessonNoteContent, true);
        if (!is_array($data)) {
            return $lessonNoteContent;
        }

        $parts = [];

        // Metadata header
        $meta = [];
        if (!empty($data['topic'])) $meta[] = $data['topic'];
        if (!empty($data['subTopic'])) $meta[] = $data['subTopic'];
        if (!empty($data['subject'])) $meta[] = $data['subject'];
        if (!empty($data['class'])) $meta[] = $data['class'];
        if (!empty($data['term'])) $meta[] = $data['term'];
        if (!empty($data['week'])) $meta[] = "Week {$data['week']}";
        if ($meta) $parts[] = implode(' | ', $meta);

        // Core content sections — ordered by importance, most important first
        // (truncation will cut from the bottom)

        if (!empty($data['learningObjectives'])) {
            $objectives = is_array($data['learningObjectives']) ? implode("\n", $data['learningObjectives']) : $data['learningObjectives'];
            $parts[] = "LEARNING OBJECTIVES:\n" . $objectives;
        }

        if (!empty($data['introduction'])) $parts[] = "INTRODUCTION:\n" . html_entity_decode(strip_tags($data['introduction']), ENT_QUOTES | ENT_HTML5);

        if (!empty($data['content'])) $parts[] = "CONTENT:\n" . html_entity_decode(strip_tags($data['content']), ENT_QUOTES | ENT_HTML5);

        if (!empty($data['subtopics'])) {
            $subtopics = is_array($data['subtopics']) ? implode("\n- ", $data['subtopics']) : $data['subtopics'];
            $parts[] = "SUBTOPICS:\n- " . $subtopics;
        }

        if (!empty($data['definitions'])) {
            $defs = '';
            foreach ($data['definitions'] as $d) {
                $term = $d['term'] ?? '';
                $def = $d['definition'] ?? '';
                if ($term || $def) $defs .= "- {$term}: {$def}\n";
            }
            if ($defs) $parts[] = "DEFINITIONS:\n" . $defs;
        }

        if (!empty($data['examples'])) {
            $exs = '';
            foreach ($data['examples'] as $ex) {
                $title = $ex['title'] ?? '';
                $desc = $ex['description'] ?? '';
                if ($title || $desc) $exs .= "- {$title}: {$desc}\n";
            }
            if ($exs) $parts[] = "EXAMPLES:\n" . $exs;
        }

        if (!empty($data['practicalApplications'])) {
            $apps = is_array($data['practicalApplications']) ? implode("\n", $data['practicalApplications']) : $data['practicalApplications'];
            $parts[] = "PRACTICAL APPLICATIONS:\n" . $apps;
        }

        if (!empty($data['keyPoints'])) {
            $points = is_array($data['keyPoints']) ? implode("\n", $data['keyPoints']) : $data['keyPoints'];
            $parts[] = "KEY POINTS:\n" . $points;
        }

        if (!empty($data['summary'])) $parts[] = "SUMMARY:\n" . html_entity_decode(strip_tags($data['summary']), ENT_QUOTES | ENT_HTML5);

        if (!empty($data['detailedNote'])) $parts[] = "DETAILED NOTE:\n" . html_entity_decode(strip_tags($data['detailedNote']), ENT_QUOTES | ENT_HTML5);

        $text = implode("\n\n", $parts);

        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        if (!empty($text)) {
            return $text;
        }

        // Fallback: try to extract plain text from the raw JSON
        $fallback = strip_tags(json_encode($data, JSON_UNESCAPED_UNICODE));
        $fallback = html_entity_decode($fallback, ENT_QUOTES | ENT_HTML5);
        return $fallback;
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
     * Tries one final AI call with a minimal prompt, then falls back to ContentGenerator.
     */
    private function fallbackToContentGenerator(array $data): \Illuminate\Http\JsonResponse
    {
        Log::warning('Attempting last-resort AI fallback for questions', [
            'subject' => $data['subject'],
            'topic' => $data['topic'],
            'count' => $data['count'],
        ]);

        // First try: simple AI prompt without json_mode
        try {
            $subject = $data['subject'];
            $topic = $data['topic'];
            $count = $data['count'];
            $class = $data['class'] ?? 'SS1';

            $simplePrompt = "You are a Nigerian exam expert. Generate {$count} multiple-choice questions STRICTLY about \"{$topic}\" in {$subject} for {$class} level.\n\n"
                . "CRITICAL: EVERY question stem MUST contain the word \"{$topic}\". Questions about any other topic are FORBIDDEN.\n\n"
                . "QUESTION STYLE: Do NOT start questions with What, Why, When, Where, Who, Which, or How. Instead, vary every question's opening. Use these styles:\n"
                . "  - Completion: \"The process of {$topic} is called ___\" (stem + options to complete)\n"
                . "  - Directive: \"State the main function of {$topic}.\" / \"Define {$topic}.\"\n"
                . "  - Scenario: \"If a student observes X, which aspect of {$topic} does this demonstrate?\"\n"
                . "  - Comparison: \"Which of the following distinguishes type A of {$topic} from type B?\"\n"
                . "  - Classification: \"Which of the following is an example of {$topic}?\"\n"
                . "  - Negative: \"All of the following are true about {$topic} EXCEPT:\"\n"
                . "  - Calculation: (for math/science) present a problem with numeric answer options\n"
                . "  - True/False: \"Which of the following statements about {$topic} is correct?\"\n"
                . "Use a different style for every question. At most 2 out of every 10 questions may start with a WH word.\n\n"
                . "Return ONLY a JSON array with NO other text. Each question must have: 'id' (number), 'question' (text containing '{$topic}'), 'A','B','C','D' (options with one correct), and 'answer' (A/B/C/D).\n\n"
                . "Example: [{\"id\":1,\"question\":\"The correct definition of {$topic} is:\",\"A\":\"opt1\",\"B\":\"opt2\",\"C\":\"opt3\",\"D\":\"opt4\",\"answer\":\"A\"}]";

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
            Log::error('Simple AI fallback failed', ['error' => $e->getMessage()]);
        }

        // Second try: ContentGenerator as absolute last resort
        Log::warning('Falling back to ContentGenerator for questions', $data);
        try {
            $fallback = ContentGenerator::generateQuestions(
                $data['subject'], $data['topic'], $data['count'],
                $data['includeTheory'] ?? false
            );
            $items = $fallback['objectives'] ?? $fallback;
            if (is_array($items) && !empty($items)) {
                $items = $this->normalizeQuestionFields($items);
                $items = $this->shuffleAnswers($items);
                return response()->json([
                    'success' => true,
                    'questions' => ['objectives' => $items],
                    'count' => $data['count'],
                    'message' => $data['count'] . ' questions generated.',
                    'fallback' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ContentGenerator fallback also failed', ['error' => $e->getMessage()]);
        }

        Log::error('All question generation attempts failed');
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
    private function validateAndRetryQuestions(array $questionItems, string $prompt, array $data, bool $hasLessonNote = false): ?array
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
            $validationErrors = $this->validateQuestionPool($currentItems, $topic, $subject, $hasLessonNote);

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
    private function validateQuestionPool(array $questions, string $topic, string $subject, bool $skipTopicCheck = false): array
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
            if (!is_array($q)) continue;
            $qNum = $i + 1;
            $questionText = trim($q['question'] ?? '');

            // Check question text exists
            if (empty($questionText)) {
                $errors[] = "Question {$qNum} has empty question text";
                continue;
            }

            // Check for duplicate question text — strict threshold to prevent repeats
            $qLower = strtolower(trim(preg_replace('/[^a-z0-9\s]/', '', $questionText)));
            if (!empty($qLower)) {
                foreach ($seenQuestions as $seen) {
                    similar_text($qLower, $seen, $pct);
                    if ($pct > 75) {
                        $errors[] = "Question {$qNum} is too similar to another question ({$pct}% match)";
                        break;
                    }
                }
                $seenQuestions[] = $qLower;
            }

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

            // Check topic relevance — every question MUST mention the topic
            // (skipped when generating from a lesson note since questions are
            // based on the note's content, which may cover subtopics without
            // repeating the broad topic name)
            if (!$skipTopicCheck) {
                $qTextLower = strtolower($questionText);
                $optionsLower = strtolower(implode(' ', [$q['A'] ?? '', $q['B'] ?? '', $q['C'] ?? '', $q['D'] ?? '']));
                $topicMatchCount = 0;
                foreach ($topicWords as $word) {
                    if (str_contains($qTextLower, $word)) {
                        $topicMatchCount++;
                    }
                }
                // The question stem MUST contain at least one topic keyword
                if (count($topicWords) > 0 && $topicMatchCount === 0) {
                    $errors[] = "Question {$qNum} does not contain any topic keyword from '{$topic}' in its stem";
                }
                // At least one option should reference the topic — soft check, not a blocker
                $optionTopicMatch = false;
                foreach ($topicWords as $word) {
                    if (str_contains($optionsLower, $word)) {
                        $optionTopicMatch = true;
                        break;
                    }
                }
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
            if (!is_array($q)) continue;
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
            if (!is_array($q)) continue;
            $currentAnswer = strtoupper(trim($q['answer'] ?? ''));
            if (!in_array($currentAnswer, $letters, true)) {
                $currentAnswer = $letters[$i % 4];
            }
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
                    $newOptions[$l] = $correctContent ?: ($q[$l] ?? '');
                } else {
                    $newOptions[$l] = $wrongContents[$wrongIdx++] ?? '';
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

    protected function buildStrictRetryPrompt(string $originalPrompt, string $subject, string $topic, string $class, string $type = 'questions'): string
    {
        if ($type === 'lesson_note') {
            return "You are a Nigerian curriculum expert. Your ONLY task: Write a DETAILED LESSON NOTE about \"{$topic}\" in {$subject} for {$class}.\n\n"
                 . "PREVIOUS ATTEMPT REJECTED — REASON: The lesson note did not focus on the requested topic.\n\n"
                 . "CRITICAL INSTRUCTIONS:\n"
                 . "- The topic is \"{$topic}\". Write ONLY about \"{$topic}\".\n"
                 . "- Follow the exact JSON structure requested in the original prompt.\n"
                 . "- Every sentence must be about \"{$topic}\".\n"
                 . "- Cover definitions, types, causes, effects, examples, and applications of \"{$topic}\".\n"
                 . "- Use Nigeria-centric examples (₦aira, Nigerian cities, local culture).\n"
                 . "- Return ONLY valid JSON with no text before or after.\n";
        }

        return "You are a Nigerian examination expert. Your ONLY task: Generate questions about \"{$topic}\" in {$subject} for {$class}.\n\n"
             . "PREVIOUS ATTEMPT REJECTED — REASON: Questions were off-topic (not about \"{$topic}\").\n\n"
             . "CRITICAL INSTRUCTIONS:\n"
             . "- The topic is \"{$topic}\". Generate {$originalPrompt}\n"
             . "- EVERY question stem MUST contain the word \"{$topic}\" in its text.\n"
             . "- If a question does not contain \"{$topic}\", it is WRONG and will be rejected.\n"
             . "- Cover different aspects of \"{$topic}\": definition, types, properties, causes, effects, examples, applications.\n"
             . "- Do NOT write about any other topic in {$subject}.\n"
             . "- VARY question styles: use commands (\"State...\", \"Define...\"), completions, scenarios, comparisons, negatives (\"All EXCEPT\"), calculations, and true/false statements. Do NOT start questions with What, Why, How, When, Where, Who, or Which.\n"
             . "- Write questions that a {$class} student would face in a WAEC/NECO/JAMB exam.\n"
             . "- Return ONLY valid JSON.\n";
    }

    private function extractJson(string $text): ?array
    {
        $text = trim($text);
        if (empty($text)) {
            return null;
        }

        // Remove BOM characters and all markdown fences
        $text = preg_replace('/^[\xEF\xBB\xBF\xFE\xFF]+/', '', $text);
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

        // Last resort: brute-force search — try every substring between { and }
        // that balances braces, to catch deeply nested or oddly formatted JSON
        $decoded = $this->bruteForceFindJson($text);
        if ($decoded !== null) {
            return $decoded;
        }

        return null;
    }

    /**
     * Brute-force search for any valid JSON object in the text.
     * Tries every substring starting with { and ending with a balanced }.
     */
    private function bruteForceFindJson(string $text): ?array
    {
        $len = strlen($text);
        for ($start = 0; $start < $len; $start++) {
            if ($text[$start] !== '{') continue;

            $depth = 0;
            $inString = false;
            $escaped = false;

            for ($end = $start; $end < $len; $end++) {
                $c = $text[$end];
                if ($escaped) { $escaped = false; continue; }
                if ($c === '\\') { $escaped = true; continue; }
                if ($c === '"') { $inString = !$inString; continue; }
                if ($inString) continue;

                if ($c === '{') $depth++;
                if ($c === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $candidate = substr($text, $start, $end - $start + 1);
                        $candidate = preg_replace('/,\s*([}\]])/', '$1', $candidate);
                        $decoded = json_decode($candidate, true);
                        if (is_array($decoded) && !empty($decoded)) {
                            return $decoded;
                        }
                        break;
                    }
                }
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
