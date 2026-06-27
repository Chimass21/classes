<?php

namespace App\Helpers;

class ContentGenerator
{
    private static array $subjectQuestionBanks = [];

    public static function generateLessonPlan(string $subject, string $class, string $term, int $week, string $topic, string $schoolName, string $teacherName, string $duration, string $ageRange): array
    {
        $objectives = self::generateObjectives($subject, $class, $topic);
        $materials = self::generateMaterials($subject, $class, $topic);
        $previousKnowledge = self::generatePreviousKnowledge($subject, $class, $topic);
        $steps = self::generateLessonSteps($objectives, $subject, $class, $topic);
        $evaluation = self::generateEvaluation($objectives, $subject, $topic);
        $assignment = self::generateAssignment($subject, $class, $topic, 'lesson_plan');
        $summary = self::generateSummary($subject, $topic, $objectives);
        $conclusion = self::generateConclusion($subject, $topic);

        return compact('objectives', 'materials', 'previousKnowledge', 'steps', 'evaluation', 'assignment', 'summary', 'conclusion');
    }

    public static function generateLessonNote(string $subject, string $class, string $term, int $week, string $topic, string $periods, string $difficulty, string $ageRange): array
    {
        $scheme = CurriculumData::getSchemeOfWork($subject, $class, $term);
        $weekSubtopics = [];
        foreach ($scheme as $s) {
            if (($s['week'] ?? 0) == $week) {
                $weekSubtopics = $s['subtopics'] ?? [];
                break;
            }
        }

        $subtopics = !empty($weekSubtopics)
            ? array_map(fn($st) => $st . ' (' . $topic . ')', $weekSubtopics)
            : self::generateSubtopics($subject, $class, $topic);

        $learningObjectives = self::generateObjectives($subject, $class, $topic);
        $introduction = self::generateIntroduction($subject, $class, $topic);
        $content = self::generateContentHtml($subject, $class, $topic, $subtopics, $difficulty);
        $examples = self::generateExamples($subject, $class, $topic, $difficulty);
        $activities = self::generateActivities($subject, $class, $topic);
        $summary = self::generateSummary($subject, $topic, $learningObjectives);
        $conclusion = self::generateConclusion($subject, $topic);
        $evaluationQuestions = self::generateEvaluationQuestions($subject, $topic, $learningObjectives);
        $assignment = self::generateAssignment($subject, $class, $topic, 'lesson_note');
        $detailedNote = self::generateDetailedNote($subject, $class, $topic, $subtopics, $learningObjectives, $difficulty);

        return compact('topic', 'subtopics', 'learningObjectives', 'introduction', 'content', 'examples', 'activities', 'summary', 'conclusion', 'evaluationQuestions', 'assignment', 'detailedNote');
    }

    public static function generateQuestions(string $subject, string $topic, int $count, bool $includeTheory): array
    {
        $bank = self::getQuestionBank($subject, $topic);
        $objectives = [];

        if (!empty($bank)) {
            $bank = array_filter($bank, fn($q) => stripos($q['topic'] ?? '', $topic) !== false || stripos($q['subject'] ?? '', $subject) !== false);
            if (empty($bank)) {
                $bank = self::getQuestionBank($subject, $topic);
            }
        }

        for ($i = 1; $i <= $count; $i++) {
            if (!empty($bank) && isset($bank[$i - 1])) {
                $objectives[] = $bank[$i - 1];
            } else {
                $objectives[] = self::generateSingleQuestion($subject, $topic, $i + ($i * 7));
            }
        }

        $result = ['objectives' => $objectives];
        if ($includeTheory) {
            $result['theoryQuestions'] = self::generateTheoryQuestions($subject, $topic);
            $result['essayQuestions'] = self::generateEssayQuestions($subject, $topic);
            $result['structuredQuestions'] = self::generateStructuredQuestions($subject, $topic);
        }
        return $result;
    }

    private static function getQuestionBank(string $subject, string $topic): array
    {
        $key = strtolower(str_replace(' ', '_', $subject));
        $questions = self::getSubjectQuestions($key);
        if (empty($questions)) {
            return [];
        }

        $filtered = array_filter($questions, fn($q) => empty($q['topic']) || stripos($q['topic'], $topic) !== false || stripos($topic, $q['topic'] ?? '') !== false);
        if (empty($filtered)) {
            return $questions;
        }
        return array_values($filtered);
    }

    private static function getSubjectQuestions(string $key): array
    {
        if (!empty(self::$subjectQuestionBanks)) {
            return self::$subjectQuestionBanks[$key] ?? [];
        }

        self::$subjectQuestionBanks = [

            'mathematics' => [],

            'english_language' => [],

            'physics' => [],

            'chemistry' => [],

            'biology' => [],

            'economics' => [],

            'government' => [],
        ];

        return self::$subjectQuestionBanks[$key] ?? [];
    }

    private static function generateObjectives(string $subject, string $class, string $topic): array
    {
        $level = CurriculumData::getClassCategory($class);
        $verbs = $level === 'primary'
            ? ['define', 'identify', 'mention', 'list', 'give examples of']
            : ($level === 'junior'
                ? ['define', 'explain', 'describe', 'differentiate', 'apply']
                : ['define', 'explain', 'analyse', 'evaluate', 'synthesise']);

        $contexts = self::getObjectiveContexts($subject, $class, $topic, $verbs);
        return array_slice($contexts, 0, rand(3, 5));
    }

    private static function getObjectiveContexts(string $subject, string $class, string $topic, array $verbs): array
    {
        $subj = strtolower($subject);
        $isMath = str_contains($subj, 'mathemat');
        $isScience = str_contains($subj, 'physic') || str_contains($subj, 'chemist') || str_contains($subj, 'biology');
        $isEnglish = str_contains($subj, 'english') || str_contains($subj, 'literature');

        $patterns = [
            "By the end of the lesson, students should be able to {$verbs[0]} " . lcfirst($topic) . ".",
            "Students will {$verbs[1]} the key features and characteristics of " . lcfirst($topic) . ".",
            "Students will {$verbs[2]} the relationship between " . lcfirst($topic) . " and related concepts in {$subject}.",
            "Students will {$verbs[3]} between the different aspects of " . lcfirst($topic) . ".",
            "Students will {$verbs[4]} the principles of " . lcfirst($topic) . " to solve practical problems.",
        ];

        if ($isMath) {
            $patterns = [
                "By the end of the lesson, students should be able to solve problems involving " . lcfirst($topic) . ".",
                "Students will correctly apply the formula for " . lcfirst($topic) . " to given problems.",
                "Students will interpret and solve word problems related to " . lcfirst($topic) . ".",
                "Students will demonstrate the step-by-step working for " . lcfirst($topic) . " problems.",
                "Students will verify their solutions to " . lcfirst($topic) . " problems using alternative methods.",
            ];
        } elseif ($isScience) {
            $patterns = [
                "By the end of the lesson, students should be able to {$verbs[0]} " . lcfirst($topic) . " with accurate scientific terminology.",
                "Students will {$verbs[1]} the principles and processes involved in " . lcfirst($topic) . ".",
                "Students will {$verbs[2]} the practical applications of " . lcfirst($topic) . " in everyday life.",
                "Students will {$verbs[3]} between related concepts within " . lcfirst($topic) . ".",
                "Students will {$verbs[4]} the scientific method to investigate " . lcfirst($topic) . ".",
            ];
        } elseif ($isEnglish) {
            $patterns = [
                "By the end of the lesson, students should be able to {$verbs[0]} " . lcfirst($topic) . " in their own words.",
                "Students will {$verbs[1]} examples of " . lcfirst($topic) . " in given passages.",
                "Students will {$verbs[2]} the usage of " . lcfirst($topic) . " in different contexts.",
                "Students will {$verbs[3]} between correct and incorrect usage of " . lcfirst($topic) . ".",
                "Students will {$verbs[4]} their knowledge of " . lcfirst($topic) . " in their own writing.",
            ];
        }

        return $patterns;
    }

    private static function generateMaterials(string $subject, string $class, string $topic): array
    {
        $materials = [
            'Whiteboard and markers', 'Recommended textbook', 'Student notebooks',
            'Charts and diagrams', 'Handouts and worksheets',
        ];

        $specific = self::getSubjectSpecificMaterials($subject, $topic);
        if (!empty($specific)) {
            $materials = array_merge($materials, $specific);
        }

        return $materials;
    }

    private static function generatePreviousKnowledge(string $subject, string $class, string $topic): string
    {
        $level = CurriculumData::getClassCategory($class);
        $subj = strtolower($subject);

        $statements = [
            "Students have prior knowledge of basic concepts in {$subject} and can identify simple examples from their environment. They have previously been introduced to related topics in previous lessons.",
            "Students are familiar with fundamental principles in {$subject} and can recall previous lessons that connect to the new topic of {$topic}.",
            "Students possess foundational literacy and numeracy skills relevant to understanding {$topic}. They have experience with {$subject} concepts from previous terms.",
        ];

        if ($level === 'primary') {
            return "Students can count, read, and write basic numbers and words. They have been introduced to simple {$subject} concepts in previous lessons and can identify examples from their immediate environment. This lesson builds on their existing knowledge by introducing {$topic} in a fun and engaging way.";
        }
        if ($level === 'junior') {
            return "Students have foundational knowledge of {$subject} from their primary education. They can identify basic terms and apply simple principles. They have previously studied related topics that serve as a foundation for understanding {$topic}.";
        }
        return "Students have studied introductory concepts in {$subject} at the JSS level and can analyse basic principles. They are familiar with standard terminology and can engage with abstract concepts. Their prior knowledge provides a solid foundation for the advanced study of {$topic}.";
    }

    private static function generateLessonSteps(array $objectives, string $subject, string $class, string $topic): array
    {
        $steps = [];
        $stepCount = count($objectives);

        for ($i = 0; $i < $stepCount; $i++) {
            $stepNum = $i + 1;
            $isFirst = $i === 0;
            $isLast = $i === $stepCount - 1;
            $isMiddle = !$isFirst && !$isLast;

            $teacherAct = $isFirst
                ? "The teacher introduces the topic '{$topic}' by asking guiding questions related to students' prior knowledge. The teacher writes the topic and learning objectives on the board and explains what students will achieve by the end of the lesson. A brief discussion on the relevance of {$topic} in {$subject} is facilitated."
                : ($isLast
                    ? "The teacher guides students in applying the knowledge of {$topic} to solve practical problems and real-life situations in {$subject}. Students work in groups on assigned tasks while the teacher moves around to provide individual support and feedback. The teacher then leads a class discussion on the solutions."
                    : ($i === 1
                        ? "The teacher explains the key concepts and features of {$topic} using clear examples and illustrations on the board. Students are encouraged to ask questions and share their understanding. The teacher uses a step-by-step approach to ensure comprehension."
                        : ($i === 2
                            ? "The teacher discusses the different types and classifications of {$topic}, using visual aids and real-world examples. Students participate by identifying examples from their own experience. The teacher clarifies misconceptions and reinforces correct understanding."
                            : "The teacher elaborates on the applications and importance of {$topic} in {$subject} and in everyday life. Practical demonstrations and case studies are used to illustrate key points. Students engage in critical thinking exercises."
                        )
                    )
                );

            $learnerAct = $isFirst
                ? "Students respond to questions based on their prior knowledge, share what they already know about {$topic}, and write down the learning objectives in their notebooks. They ask questions about what they hope to learn."
                : ($isLast
                    ? "Students work in small groups to solve problems and complete tasks related to {$topic}. Each group presents their findings and answers to the class. Students peer-review each other's work and provide constructive feedback."
                    : ($isMiddle
                        ? "Students listen attentively, take notes, and ask questions for clarification. They participate in class discussions, answer questions posed by the teacher, and complete short exercises to reinforce their understanding."
                        : "Students engage in guided practice activities, working through examples with the teacher's support. They ask and answer questions to deepen their understanding."
                    )
                );

            $learningPoint = $isFirst
                ? "Students understand the scope and importance of {$topic} in {$subject} and are prepared for the lesson."
                : ($isLast
                    ? "Students can apply the knowledge of {$topic} to solve problems in {$subject} and relate it to real-world situations."
                    : ($i === 1
                        ? "Students gain a clear understanding of the key concepts and features of {$topic}."
                        : ($i === 2
                            ? "Students can identify and differentiate between the types and classifications of {$topic}."
                            : "Students understand the practical applications of {$topic} and can connect them to everyday life."
                        )
                    )
                );

            $steps[] = [
                'step' => $stepNum,
                'teacherActivities' => $teacherAct,
                'learnerActivities' => $learnerAct,
                'learningPoints' => $learningPoint,
            ];
        }

        return $steps;
    }

    private static function generateEvaluation(array $objectives, string $subject, string $topic): string
    {
        $questions = [];
        foreach ($objectives as $i => $obj) {
            $num = $i + 1;
            $verbs = ['Define', 'List three features of', 'Explain the importance of', 'Differentiate between', 'Give two examples of', 'Describe the process of', 'State the function of'];
            $v = $verbs[$i % count($verbs)];
            $questions[] = "{$num}. {$v} {$topic} in the context of {$subject}.";
        }
        $questions[] = (count($questions) + 1) . ". Solve a practical problem related to {$topic}.";
        return implode("\n", $questions);
    }

    private static function generateAssignment(string $subject, string $class, string $topic, string $type): string
    {
        $level = CurriculumData::getClassCategory($class);
        $tasks = [
            "1. Write a comprehensive note on {$topic} in your notebook.",
            "2. Answer questions 1–5 on {$topic} from your textbook.",
            "3. Research and write two additional examples of {$topic} from your local environment.",
        ];
        if ($level !== 'primary') {
            $tasks[] = "4. Prepare a 2-minute oral presentation on {$topic} for the next class.";
            $tasks[] = "5. Solve the attached practice problems on {$topic}.";
        } else {
            $tasks[] = "4. Draw and label a diagram related to {$topic}.";
            $tasks[] = "5. Ask a parent or guardian to help you find one more example of {$topic} at home.";
        }
        if ($type === 'lesson_plan') {
            $tasks[] = "6. Write five objective questions based on {$topic}.";
        }
        return implode("\n", $tasks);
    }

    private static function generateSummary(string $subject, string $topic, array $objectives): string
    {
        return "In this lesson, students learned about {$topic} in {$subject}. The key points covered include: " .
            implode('; ', array_map(function($o) {
                $clean = strip_tags($o);
                return substr($clean, 0, 80) . '...';
            }, array_slice($objectives, 0, 3))) .
            ". Students are encouraged to review these concepts and practice with the provided exercises to reinforce their understanding. The next lesson will build on these foundations to explore more advanced aspects of {$subject}.";
    }

    private static function generateConclusion(string $subject, string $topic): string
    {
        return "The teacher concludes by summarizing the key points about {$topic} and emphasizing its relevance in {$subject} and everyday life. Students are encouraged to continue practicing at home and to observe examples of {$topic} in their environment. The next lesson will build on these concepts to explore more advanced topics in {$subject}.";
    }

    private static function generateSubtopics(string $subject, string $class, string $topic): array
    {
        $level = CurriculumData::getClassCategory($class);
        $count = $level === 'primary' ? 3 : ($level === 'junior' ? 4 : 5);

        $generic = [
            "Definition and Meaning of {$topic}",
            "Key Characteristics and Features of {$topic}",
            "Types and Classifications of {$topic}",
            "Importance and Applications of {$topic}",
            "Practical Examples and Case Studies of {$topic}",
        ];

        $result = [];
        foreach (array_slice($generic, 0, $count) as $item) {
            $result[] = str_replace(['{$topic}', '{$subject}', '{$class}'], [$topic, $subject, $class], $item);
        }
        return $result;
    }

    private static function generateIntroduction(string $subject, string $class, string $topic): string
    {
        $level = CurriculumData::getClassCategory($class);

        if ($level === 'primary') {
            return "The teacher begins the lesson by asking students to mention what they already know about {$topic}. A fun story, song, or real-life example related to {$topic} is shared to capture the learners' attention. The teacher then announces: 'Today, we are going to learn about {$topic}.' The learning objectives are written on the board in simple, child-friendly language.";
        }
        if ($level === 'junior') {
            return "The teacher reviews the previous lesson and asks questions to assess students' prior knowledge of {$subject}. A thought-provoking question related to {$topic} is posed to stimulate curiosity and engage critical thinking. The teacher then introduces the new topic '{$topic}' and explains the learning objectives for the lesson, relating the topic to real-life situations in Nigeria.";
        }
        return "The teacher begins by connecting the lesson to students' existing knowledge of {$subject}. A brief discussion on the relevance of {$topic} in the Nigerian context and in the broader field of {$subject} is facilitated. Students are informed of what they will achieve by the end of the lesson, and the teacher sets clear expectations for the learning outcomes.";
    }

    private static function generateContentHtml(string $subject, string $class, string $topic, array $subtopics, string $difficulty = 'Medium'): string
    {
        $level = CurriculumData::getClassCategory($class);
        $html = '';

        foreach ($subtopics as $st) {
            $html .= "<h4>" . htmlspecialchars($st) . "</h4>";
            $html .= "<p>" . self::generateContentParagraph($subject, $class, $topic, $st, $difficulty) . "</p>";
        }

        $html .= "<h4>Key Points to Remember</h4><ul>";
        $bulletCount = $level === 'primary' ? 3 : ($level === 'junior' ? 4 : 5);
        $bullets = self::generateBulletPoints($subject, $topic, $bulletCount);
        foreach ($bullets as $b) {
            $html .= "<li>" . htmlspecialchars($b) . "</li>";
        }
        $html .= "</ul>";

        return $html;
    }

    private static function generateContentParagraph(string $subject, string $class, string $topic, string $subtopic, string $difficulty): string
    {
        $level = CurriculumData::getClassCategory($class);

        $paragraphs = [
            "In {$subject}, {$topic} is an important concept that helps us understand fundamental principles and their applications. At the {$class} level, students engage with this topic progressively, building from basic definitions to more complex applications.",
            "The study of " . htmlspecialchars($subtopic) . " focuses on understanding the core principles that govern {$topic}. This aspect of {$subject} is essential for developing a strong foundation and preparing for more advanced studies.",
            "{$topic} plays a vital role in {$subject} education at the {$class} level. By mastering the content covered under " . htmlspecialchars($subtopic) . ", students develop critical thinking skills and gain a deeper appreciation of how {$subject} relates to the world around them.",
            "When studying {$topic} in {$subject}, it is important to pay attention to the key aspects discussed in " . htmlspecialchars($subtopic) . ". This knowledge will be built upon in subsequent lessons and is essential for academic success in {$subject}.",
        ];

        if ($difficulty === 'Hard' || $level === 'senior') {
            $paragraphs[] = "A deeper analysis of {$topic} reveals complex interrelationships between its various components. Advanced students should focus on developing analytical frameworks to evaluate competing perspectives and apply theoretical knowledge to novel situations in {$subject}.";
        }

        $key = abs(crc32($subtopic)) % count($paragraphs);
        return $paragraphs[$key];
    }

    private static function generateBulletPoints(string $subject, string $topic, int $count): array
    {
        $bullets = [
            "{$topic} is a fundamental concept in {$subject} that every student should understand.",
            "The principles of {$topic} apply to many real-life situations in Nigeria and around the world.",
            "Mastery of {$topic} helps students perform better in examinations and practical applications.",
            "{$topic} connects to other important topics in {$subject}, creating a comprehensive learning experience.",
            "Understanding {$topic} develops analytical and problem-solving skills essential for academic success.",
        ];

        return array_slice($bullets, 0, $count);
    }

    private static function generateExamples(string $subject, string $class, string $topic, string $difficulty = 'Medium'): array
    {
        $level = CurriculumData::getClassCategory($class);
        $isMath = str_contains(strtolower($subject), 'mathemat');
        $isPhysics = str_contains(strtolower($subject), 'physic');
        $isChem = str_contains(strtolower($subject), 'chemist');
        $isScience = $isPhysics || $isChem || str_contains(strtolower($subject), 'biology');

        $count = $level === 'primary' ? 2 : ($isMath || $isScience ? 10 : 3);

        $examples = [];
        for ($i = 1; $i <= $count; $i++) {
            $examples[] = [
                'title' => "Example {$i}: " . ($i === 1 ? "Basic Concept" : ($i === 2 ? "Practical Application" : ($i >= 10 ? "Advanced Worked Example {$i}" : ($i >= 4 ? "Worked Example {$i}" : "Application {$i}")))),
                'description' => ($isMath && $i >= 3)
                    ? self::generateMathExample($topic, $i, $level)
                    : ($isPhysics && $i >= 3
                        ? self::generatePhysicsExample($topic, $i, $level)
                        : ($isChem && $i >= 3
                            ? self::generateChemistryExample($topic, $i, $level)
                            : "Consider the following example related to {$topic} in {$subject}: " . self::getTopicExample($subject, $topic, $i, $class)
                        )
                    ),
            ];
        }
        return $examples;
    }

    private static function generateMathExample(string $topic, int $num, string $level): string
    {
        $examples = [
            3 => "Step 1: Identify the given information. Step 2: Write the relevant formula. Step 3: Substitute the values carefully. Step 4: Solve step by step. Step 5: Check your answer by working backwards. Practice with different numbers to build confidence.",
            4 => "A student scored 25 out of 40 in a test. What is the percentage score? Solution: (25 ÷ 40) × 100 = 62.5%. Answer: 62.5%. This shows moderate performance requiring improvement.",
            5 => "If a car travels 240 kilometres in 3 hours, what is its average speed? Solution: Speed = Distance ÷ Time = 240 km ÷ 3 h = 80 km/h. Therefore, the car travels at an average speed of 80 kilometres per hour.",
            6 => "Solve for x: 3x + 7 = 22. Solution: 3x + 7 = 22 → 3x = 22 - 7 → 3x = 15 → x = 15 ÷ 3 → x = 5. Answer: x = 5. Check: 3(5) + 7 = 15 + 7 = 22. Correct.",
            7 => "Find the area of a rectangle with length 12 cm and width 8 cm. Solution: Area = length × width = 12 cm × 8 cm = 96 cm². The area of the rectangle is 96 square centimetres.",
            8 => "Simplify: 2/3 + 3/4. Solution: LCM of 3 and 4 is 12. 2/3 = 8/12, 3/4 = 9/12. 8/12 + 9/12 = 17/12 = 1 5/12. Answer: 1 5/12.",
            9 => "A man bought a television for ₦85,000 and sold it for ₦93,500. What is his percentage profit? Profit = ₦93,500 - ₦85,000 = ₦8,500. Percentage profit = (₦8,500 ÷ ₦85,000) × 100 = 10%. Answer: 10% profit.",
            10 => "Calculate the volume of a cylinder with radius 7 cm and height 10 cm. (Take π = 22/7) Solution: Volume = πr²h = (22/7) × 7² × 10 = (22/7) × 49 × 10 = 22 × 7 × 10 = 1,540 cm³. The volume is 1,540 cubic centimetres.",
        ];
        return $examples[$num] ?? "Worked example {$num}: Apply the principles of {$topic} step by step. First, identify the given values. Second, recall the appropriate formula. Third, substitute and compute. Fourth, verify your answer. Practice makes perfect!";
    }

    private static function generatePhysicsExample(string $topic, int $num, string $level): string
    {
        $examples = [
            3 => "Step 1: Read the problem carefully and note all given quantities. Step 2: Identify the relevant physical principle or formula. Step 3: Convert all quantities to SI units if necessary. Step 4: Substitute values into the formula. Step 5: Calculate and include correct units in your answer.",
            4 => "A car accelerates uniformly from rest to 20 m/s in 10 seconds. Calculate its acceleration. Solution: a = (v - u)/t = (20 - 0)/10 = 2 m/s². The car's acceleration is 2 metres per second squared.",
            5 => "A stone of mass 2 kg is dropped from a height of 45 m. Calculate its potential energy at the top. (g = 10 m/s²) Solution: PE = mgh = 2 × 10 × 45 = 900 J. The potential energy is 900 joules.",
            6 => "A current of 3 A flows through a resistor of 5 Ω. Calculate the voltage across the resistor. Solution: V = IR = 3 × 5 = 15 V. The voltage across the resistor is 15 volts.",
            7 => "Calculate the force required to accelerate a 50 kg object at 4 m/s². Solution: F = ma = 50 × 4 = 200 N. A force of 200 newtons is required.",
            8 => "A wave has frequency 50 Hz and wavelength 6 m. Calculate its speed. Solution: v = fλ = 50 × 6 = 300 m/s. The wave speed is 300 metres per second.",
            9 => "An electric bulb rated 60 W is used for 5 hours daily. Calculate the energy consumed in kWh. Solution: Energy = Power × Time = 60 W × 5 h = 300 Wh = 0.3 kWh per day.",
            10 => "A lens has a focal length of 10 cm. Calculate its power. Solution: P = 1/f = 1/0.10 = 10 D. The power of the lens is 10 dioptres.",
        ];
        return $examples[$num] ?? "Worked example {$num}: Apply the relevant physics formula. Write down all known quantities. Select the correct equation. Substitute and solve. Always include units in your final answer.";
    }

    private static function generateChemistryExample(string $topic, int $num, string $level): string
    {
        $examples = [
            3 => "Step 1: Write the balanced chemical equation. Step 2: Identify the known and unknown quantities. Step 3: Use mole ratios from the balanced equation. Step 4: Convert between mass, moles, and volume as needed. Step 5: Calculate the answer with correct significant figures.",
            4 => "Calculate the relative molecular mass of H₂SO₄. (H=1, S=32, O=16) Solution: 2(1) + 32 + 4(16) = 2 + 32 + 64 = 98 g/mol. The relative molecular mass of sulphuric acid is 98 grams per mole.",
            5 => "What is the percentage by mass of oxygen in H₂O? (H=1, O=16) Solution: Molar mass of H₂O = 2(1) + 16 = 18 g/mol. Mass of oxygen = 16 g. % O = (16/18) × 100 = 88.89%. Water is 88.89% oxygen by mass.",
            6 => "How many moles are in 20 g of calcium carbonate (CaCO₃)? (Ca=40, C=12, O=16) Solution: Molar mass = 40 + 12 + 48 = 100 g/mol. Moles = 20/100 = 0.20 mol. There are 0.20 moles in 20 g of CaCO₃.",
            7 => "Balance the equation: Fe + O₂ → Fe₂O₃. Solution: 4Fe + 3O₂ → 2Fe₂O₃. The balanced equation shows that 4 iron atoms react with 3 oxygen molecules to produce 2 formula units of iron(III) oxide.",
            8 => "Calculate the concentration of a solution containing 5 g of NaOH dissolved in 500 mL of water. (NaOH = 40 g/mol) Solution: Moles = 5/40 = 0.125 mol. Concentration = 0.125/0.500 = 0.25 mol/dm³.",
            9 => "What volume of 0.1 M HCl is needed to neutralise 25 cm³ of 0.2 M NaOH? Solution: HCl + NaOH → NaCl + H₂O. Moles of NaOH = 0.2 × 0.025 = 0.005 mol. Volume of HCl = 0.005/0.1 = 0.05 L = 50 cm³.",
            10 => "Calculate the pH of a 0.01 M HCl solution. Solution: [H⁺] = 0.01 M = 10⁻² M. pH = -log[H⁺] = -log(10⁻²) = 2. The pH of 0.01 M HCl is 2, indicating a strongly acidic solution.",
        ];
        return $examples[$num] ?? "Worked example {$num}: Apply stoichiometric principles. Write the balanced equation. Convert given quantities to moles. Use mole ratios. Calculate the required quantity. Always include appropriate units.";
    }

    private static function getTopicExample(string $subject, string $topic, int $num, string $class): string
    {
        $examples = [
            "A student in {$class} wants to understand {$topic}. Here is a simple way to think about it using everyday items found in a Nigerian home. Relating new concepts to familiar objects helps make learning more meaningful and memorable.",
            "In a {$class} classroom, students can demonstrate their understanding of {$topic} by working through exercises from their textbook. This practical approach helps reinforce the concept through active learning.",
            "Let us apply {$topic} to a real situation: Consider how this concept appears in the daily life of a Nigerian family, business, or community. Understanding the practical relevance of topics makes {$subject} more engaging and valuable.",
        ];
        return $examples[($num - 1) % count($examples)];
    }

    private static function generateActivities(string $subject, string $class, string $topic): array
    {
        $level = CurriculumData::getClassCategory($class);
        $count = $level === 'primary' ? 2 : 3;

        $activities = [
            [
                'title' => 'Group Discussion',
                'description' => "Divide the class into small groups. Each group discusses the concept of {$topic} in {$subject} and prepares a summary of their understanding. Groups take turns presenting their findings to the class. The teacher facilitates and provides clarification where needed.",
            ],
            [
                'title' => 'Individual Exercise',
                'description' => "Students work independently to complete a worksheet on {$topic}. The teacher moves around the classroom to provide individual support and guidance. This allows each student to practice at their own pace and receive personalized feedback.",
            ],
            [
                'title' => 'Class Presentation',
                'description' => "Selected students present their understanding of {$topic} to the class. This builds confidence, develops communication skills, and reinforces learning through peer teaching. The audience is encouraged to ask questions.",
            ],
        ];

        return array_slice($activities, 0, $count);
    }

    private static function generateEvaluationQuestions(string $subject, string $topic, array $objectives): array
    {
        $questions = [];
        foreach ($objectives as $i => $obj) {
            $num = $i + 1;
            preg_match('/:\s*\d+\.\s*(.+?)(?:\.|$)/', $obj, $m);
            $task = $m[1] ?? "explain {$topic}";
            $questions[] = "{$num}. " . ucfirst(trim($task)) . " as it relates to {$topic} in {$subject}.";
        }
        $questions[] = (count($questions) + 1) . ". Give a practical example of {$topic} from your community or environment.";
        return $questions;
    }

    private static function generateDetailedNote(string $subject, string $class, string $topic, array $subtopics, array $objectives, string $difficulty): string
    {
        $year = date('Y');
        $note = "LESSON NOTE: {$subject} - {$topic}\n";
        $note .= "Class: {$class}\n";
        $note .= "Topic: {$topic}\n";
        $note .= "Subject: {$subject}\n";
        $note .= "Difficulty Level: {$difficulty}\n";
        $note .= "Academic Year: {$year}\n";
        $note .= str_repeat("=", 50) . "\n\n";

        $note .= "LEARNING OBJECTIVES:\n";
        foreach ($objectives as $o) {
            $note .= "  • {$o}\n";
        }
        $note .= "\n";

        $note .= "INTRODUCTION:\n";
        $note .= "The teacher introduces the topic '{$topic}' by relating it to real-life experiences and prior knowledge of the students. A stimulating question or scenario is used to arouse curiosity and set the stage for learning. Students are encouraged to share what they already know about the topic.\n\n";

        $note .= "CONTENT DEVELOPMENT:\n\n";
        foreach ($subtopics as $i => $st) {
            $note .= ($i + 1) . ". " . strip_tags($st) . ":\n";
            $note .= "   {$topic} in {$subject} involves understanding the key principles and applications of this concept. At the {$class} level, students are expected to grasp these ideas progressively, building from basic definitions to more complex applications.\n\n";
        }

        $note .= "KEY POINTS TO NOTE:\n";
        $note .= "  • {$topic} is a fundamental concept in {$subject}.\n";
        $note .= "  • Understanding {$topic} requires practice and application.\n";
        $note .= "  • {$topic} connects to other important areas of {$subject}.\n";
        $note .= "  • Mastery of {$topic} is essential for academic progression.\n\n";

        $note .= "SUMMARY:\n";
        $note .= "{$topic} is an important concept in {$subject} that encompasses various aspects including definition, characteristics, types, and applications. Mastery of this topic requires consistent practice and active engagement with the learning materials. Students should review their notes regularly and attempt all practice exercises.\n\n";

        $note .= "EVALUATION:\n";
        $note .= "Students will be assessed through:\n";
        $note .= "  • Oral questions during the lesson\n";
        $note .= "  • Written exercises and worksheets\n";
        $note .= "  • End-of-lesson quiz\n";
        $note .= "  • Homework assignments\n\n";

        $note .= "ASSIGNMENT:\n";
        $note .= "  1. Write a comprehensive note on {$topic}\n";
        $note .= "  2. Answer questions 1–5 in the textbook\n";
        $note .= "  3. Research current developments related to {$topic}\n";
        $note .= "  4. Prepare for a class presentation on {$topic}\n";

        return $note;
    }

    private static function generateSingleQuestion(string $subject, string $topic, int $num): array
    {
        $isMath = str_contains(strtolower($subject), 'mathemat');
        $isEnglish = str_contains(strtolower($subject), 'english');
        $isScience = str_contains(strtolower($subject), 'physic') || str_contains(strtolower($subject), 'chemist') || str_contains(strtolower($subject), 'biology');

        if ($isMath) {
            $questions = [
                "What is the value of 25 + 17?",
                "Which of the following is a prime number?",
                "The square root of 144 is:",
                "Convert 0.25 to a fraction in its simplest form:",
                "What is the LCM of 12 and 18?",
                "A triangle with all sides equal is called:",
                "What is 20% of 250?",
                "The perimeter of a square with side 8 cm is:",
                "Solve for x: 2x + 5 = 15",
                "What is the product of 15 and 8?",
            ];
            $answers = ["37", "17", "12", "1/4", "36", "Equilateral triangle", "50", "32 cm", "5", "120"];
        } elseif ($isEnglish) {
            $questions = [
                "Which of the following is a noun?",
                "The opposite of 'generous' is:",
                "Choose the correct spelling:",
                "'The boy ran quickly.' The word 'quickly' is a/an:",
                "Which sentence is in the past tense?",
                "A synonym for 'happy' is:",
                "Which of the following is a complete sentence?",
                "The plural of 'child' is:",
                "Which word is an adjective?",
                "Identify the pronoun in this sentence: 'She went to school.'",
            ];
            $answers = ["Happiness", "Stingy", "Beautiful", "Adverb", "He walked home.", "Joyful", "The sun is bright.", "Children", "Beautiful", "She"];
        } elseif ($isScience) {
            $questions = [
                "The basic unit of life is the:",
                "What is the chemical symbol for water?",
                "The process by which plants make food is called:",
                "What is the SI unit of force?",
                "Which organ pumps blood around the body?",
                "The three states of matter are:",
                "What gas do plants absorb from the atmosphere?",
                "The speed of light in a vacuum is approximately:",
                "Which planet is known as the Red Planet?",
                "What is the pH of a neutral solution?",
            ];
            $answers = ["Cell", "H2O", "Photosynthesis", "Newton", "Heart", "Solid, liquid, gas", "Carbon dioxide", "3.0 × 10⁸ m/s", "Mars", "7"];
        } else {
            $questions = [
                "What is {$topic} in {$subject}?",
                "Which of the following best describes {$topic}?",
                "The concept of {$topic} refers to:",
                "One of the main features of {$topic} is:",
                "The importance of {$topic} in {$subject} is:",
                "Which of the following is NOT a characteristic of {$topic}?",
                "{$topic} can be classified into:",
                "A practical example of {$topic} is:",
                "The term '{$topic}' in {$subject} means:",
                "All of the following are types of {$topic} EXCEPT:",
            ];
            $answers = ["The correct definition", "The correct description", "A key concept in {$subject}", "A defining feature", "Its relevance to {$subject}", "An unrelated characteristic", "Various categories", "A real-world application", "Its meaning in context", "A non-related type"];
        }

        $qIndex = abs($num) % count($questions);
        $q = $questions[$qIndex];
        $correctIdx = rand(0, 3);
        $letters = ['A', 'B', 'C', 'D'];
        $options = [];
        for ($j = 0; $j < 4; $j++) {
            $options[$letters[$j]] = $j === $correctIdx
                ? ($answers[$qIndex] ?? "The correct answer related to {$topic}.")
                : ($isMath
                    ? ["15", "23", "19", "45", "3/8", "24", "Scalene triangle", "25%", "50", "112"][($j + $qIndex * 3) % 10]
                    : "An incorrect option related to {$topic}.");
        }

        return [
            'id' => $num,
            'question' => $q,
            'A' => $options['A'],
            'B' => $options['B'],
            'C' => $options['C'],
            'D' => $options['D'],
            'answer' => $letters[$correctIdx],
        ];
    }

    private static function generateTheoryQuestions(string $subject, string $topic): array
    {
        return [
            [
                'question' => "Explain in detail the concept of {$topic} as it applies to {$subject}. Include its definition, key characteristics, and importance.",
                'answer' => "{$topic} is a fundamental concept in {$subject} that encompasses various aspects including principles, applications, and real-world relevance. In the Nigerian curriculum context, {$topic} is taught across multiple class levels with increasing depth and complexity, preparing students for both examinations and practical application."
            ],
            [
                'question' => "Discuss the practical applications of {$topic} in everyday life, with specific reference to Nigeria.",
                'answer' => "{$topic} has numerous practical applications in everyday life. In Nigeria, {$topic} can be observed in areas such as education, business, technology, and community development. Understanding {$topic} helps students appreciate how {$subject} concepts apply to real-world situations and prepares them for higher education and careers in various fields."
            ],
        ];
    }

    private static function generateEssayQuestions(string $subject, string $topic): array
    {
        return [
            [
                'question' => "Write a comprehensive essay on the importance of {$topic} in {$subject}.",
                'guidance' => "Include definition, key concepts, types/classifications, importance, real-world examples, and relevance to the Nigerian context. Support your points with specific examples."
            ],
        ];
    }

    private static function generateStructuredQuestions(string $subject, string $topic): array
    {
        return [
            [
                'question' => "Describe {$topic} with reference to {$subject}.",
                'parts' => [
                    'a' => "Define {$topic}",
                    'b' => "List five key features of {$topic}",
                    'c' => "Explain three importance of {$topic}",
                    'd' => "Give two practical examples of {$topic} from the Nigerian context",
                ],
            ],
        ];
    }

    private static function getSubjectSpecificMaterials(string $subject, string $topic): array
    {
        $subj = strtolower($subject);
        if (str_contains($subj, 'mathemat')) return ['Counters and abacus', 'Geometric shapes', 'Number line chart', 'Measuring tools', 'Graph paper'];
        if (str_contains($subj, 'physic')) return ['Laboratory apparatus', 'Meters and measuring devices', 'Experimental setup materials', 'Circuit components'];
        if (str_contains($subj, 'chemist')) return ['Laboratory chemicals and apparatus', 'Safety goggles', 'Periodic table chart', 'Test tubes and beakers'];
        if (str_contains($subj, 'biology') || str_contains($subj, 'science')) return ['Specimens and models', 'Magnifying glass/microscope', 'Charts of biological systems', 'Dissection tools'];
        if (str_contains($subj, 'english') || str_contains($subj, 'literature')) return ['Reading passages', 'Dictionary', 'Grammar charts', 'Storybooks', 'Thesaurus'];
        if (str_contains($subj, 'geograph')) return ['Maps and globes', 'Atlas', 'Compass', 'Weather charts', 'Satellite images'];
        if (str_contains($subj, 'history')) return ['Timeline charts', 'Historical pictures', 'Textbook with Nigerian history', 'Primary source documents'];
        if (str_contains($subj, 'govern') || str_contains($subj, 'civic')) return ['Nigerian Constitution booklet', 'Posters of government structures', 'Newspaper cuttings', 'Voter education materials'];
        if (str_contains($subj, 'econom')) return ['Graph charts', 'Price lists', 'Newspaper business sections', 'Economic indicators data'];
        if (str_contains($subj, 'account') || str_contains($subj, 'commerce')) return ['Sample ledgers and journals', 'Invoice templates', 'Business transaction examples', 'Financial statements'];
        if (str_contains($subj, 'agric')) return ['Farm tools and equipment', 'Seed samples', 'Posters of crop/livestock', 'Soil samples'];
        return [];
    }
}
