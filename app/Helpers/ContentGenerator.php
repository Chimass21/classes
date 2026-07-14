<?php

namespace App\Helpers;

/**
 * @deprecated All content generation is now handled by GeminiService via AIController.
 * This class is retained only for reference and is no longer called by any code.
 */
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
        return array_slice($contexts, 0, rand(3, 7));
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
        $subj = strtolower($subject);
        $level = CurriculumData::getClassCategory($class);
        $count = $level === 'primary' ? 3 : ($level === 'junior' ? 4 : 5);

        $generic = [
            "Definition and Meaning of {$topic}",
            "Key Characteristics and Features of {$topic}",
            "Types and Classifications of {$topic}",
            "Importance and Applications of {$topic}",
            "Practical Examples and Case Studies of {$topic}",
        ];

        if (str_contains($subj, 'chemist')) {
            $generic = [
                "Chemical Composition and Structure of {$topic}",
                "Physical and Chemical Properties of {$topic}",
                "Reactions and Equations Involving {$topic}",
                "Importance and Applications of {$topic} in Industry and Daily Life",
                "Quantitative Analysis and Calculations Related to {$topic}",
            ];
        } elseif (str_contains($subj, 'physic')) {
            $generic = [
                "Definition and Fundamental Principles of {$topic}",
                "Mathematical Formulations and Calculations in {$topic}",
                "Practical Applications and Examples of {$topic}",
                "Experimental Investigations of {$topic}",
                "{$topic} in Technology and Everyday Life",
            ];
        } elseif (str_contains($subj, 'biology') || str_contains($subj, 'science')) {
            $generic = [
                "Structure and Organization of {$topic}",
                "Functions and Processes of {$topic}",
                "Types and Classification of {$topic}",
                "Importance of {$topic} to Living Organisms and the Environment",
                "Practical Observations and Laboratory Studies of {$topic}",
            ];
        } elseif (str_contains($subj, 'mathemat')) {
            $generic = [
                "Basic Concepts and Definitions of {$topic}",
                "Formulae and Methods for Solving {$topic} Problems",
                "Worked Examples and Step-by-Step Solutions for {$topic}",
                "Real-Life Applications of {$topic}",
                "Advanced Problems and Challenges in {$topic}",
            ];
        } elseif (str_contains($subj, 'english') || str_contains($subj, 'literature')) {
            $generic = [
                "Definition and Explanation of {$topic}",
                "Rules and Conventions Governing {$topic}",
                "Examples and Illustrations of {$topic}",
                "Common Errors and How to Avoid Them in {$topic}",
                "Practical Exercises and Applications of {$topic}",
            ];
        }

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
        $subj = strtolower($subject);
        $level = CurriculumData::getClassCategory($class);

        $paragraphs = self::getContentParagraphs($subj, $subject, $class, $topic, $subtopic, $difficulty, $level);

        if (empty($paragraphs)) {
            $paragraphs = [
                "When studying " . htmlspecialchars($subtopic) . ", it is important to focus on the specific facts, definitions, and principles that define this area of {$subject}. Students should take notes, ask questions, and practice applying what they have learned to ensure a thorough understanding of the material.",
                "The concept of " . htmlspecialchars($subtopic) . " in {$subject} requires careful attention to detail. Learners should work through examples step by step, identifying patterns and relationships that help connect new knowledge to what they already know.",
                htmlspecialchars($subtopic) . " is a key area within the broader topic of {$topic}. By mastering the content in this section, students develop critical thinking skills and gain confidence in their ability to tackle more advanced concepts in {$subject}.",
            ];
        }

        $key = abs(crc32($subtopic)) % count($paragraphs);
        return $paragraphs[$key];
    }

    private static function getContentParagraphs(string $subj, string $subject, string $class, string $topic, string $subtopic, string $difficulty, string $level): array
    {
        if (str_contains($subj, 'chemist')) {
            return [
                "The study of " . htmlspecialchars($subtopic) . " in Chemistry focuses on the composition, structure, and behaviour of substances. Students analyse how atoms and molecules interact through chemical bonds, reactions, and energy changes, forming the basis for understanding matter at the molecular level.",
                "In Chemistry, " . htmlspecialchars($subtopic) . " involves understanding the specific properties and transformations of chemical substances. This includes observing reactions, balancing equations, calculating quantities using mole concepts, and predicting outcomes based on chemical principles.",
                htmlspecialchars($subtopic) . " is fundamental to the Nigerian Chemistry curriculum at the {$class} level. Learners are expected to master the key concepts, perform relevant calculations, and connect theoretical knowledge to practical laboratory observations and real-world applications.",
                "When studying " . htmlspecialchars($subtopic) . ", Chemistry students must pay careful attention to symbols, formulae, and equations. Understanding the quantitative relationships between reactants and products through stoichiometry is essential for solving numerical problems and interpreting experimental data.",
            ];
        }

        if (str_contains($subj, 'physic')) {
            return [
                "In Physics, " . htmlspecialchars($subtopic) . " deals with the fundamental laws and principles that govern physical phenomena. Students learn to describe, measure, and predict the behaviour of physical systems using mathematical models and experimental methods.",
                "The study of " . htmlspecialchars($subtopic) . " requires Physics students to apply mathematical reasoning to physical situations. This involves identifying relevant variables, selecting appropriate formulae, performing calculations with correct units, and interpreting results in a physical context.",
                htmlspecialchars($subtopic) . " is a core component of the Nigerian Physics syllabus. Students explore the relationships between forces, energy, motion, and other physical quantities through theoretical study and practical laboratory investigations.",
                "Understanding " . htmlspecialchars($subtopic) . " in Physics helps students explain natural phenomena and technological applications. From the motion of vehicles to the operation of electrical devices, these principles are observable in everyday life and in advanced scientific contexts.",
            ];
        }

        if (str_contains($subj, 'biology') || str_contains($subj, 'science')) {
            return [
                "In Biology, " . htmlspecialchars($subtopic) . " examines the structures and processes that sustain life. Students explore the organization of living organisms from cells to systems, understanding how each level contributes to the functioning of the whole organism.",
                "The study of " . htmlspecialchars($subtopic) . " in Biology involves investigating the diversity of life, ecological relationships, and the physiological mechanisms that allow organisms to grow, reproduce, and respond to their environment.",
                htmlspecialchars($subtopic) . " is an important part of the Nigerian Biology curriculum for {$class}. Students learn to identify, describe, and explain the biological principles that underpin health, agriculture, and environmental conservation.",
                "When studying " . htmlspecialchars($subtopic) . " in Biology, learners develop skills in observation, classification, and analysis. Laboratory work involving specimens, models, and experiments helps reinforce theoretical knowledge and develops practical scientific skills.",
            ];
        }

        if (str_contains($subj, 'mathemat')) {
            return [
                "In Mathematics, " . htmlspecialchars($subtopic) . " requires students to understand and apply specific mathematical procedures and relationships. Mastery comes from practising problems systematically, checking work for accuracy, and building speed and confidence over time.",
                "The study of " . htmlspecialchars($subtopic) . " in Mathematics involves learning the relevant formulae, methods, and problem-solving strategies. Students should work through examples step by step, paying attention to each stage of the calculation or proof process.",
                htmlspecialchars($subtopic) . " is a key topic in the Nigerian Mathematics curriculum for {$class}. Regular practice with varied problems helps students develop fluency and the ability to apply mathematical thinking to both theoretical questions and real-life situations.",
                "When studying " . htmlspecialchars($subtopic) . " in Mathematics, learners should focus on understanding the underlying principles rather than memorizing steps. Connecting mathematical concepts to practical applications makes learning more meaningful and improves long-term retention.",
            ];
        }

        if (str_contains($subj, 'english') || str_contains($subj, 'literature')) {
            return [
                "In English Language, " . htmlspecialchars($subtopic) . " focuses on developing competence in understanding and using the English language effectively. This includes mastering the rules, structures, and conventions that govern communication in both spoken and written forms.",
                "The study of " . htmlspecialchars($subtopic) . " helps students improve their reading comprehension, writing skills, and oral communication. Learners are encouraged to practise regularly through reading, writing exercises, and class discussions.",
                htmlspecialchars($subtopic) . " is an essential component of the Nigerian English Language curriculum. Mastery of this area enables students to express themselves clearly, understand complex texts, and perform well in examinations.",
                "When studying " . htmlspecialchars($subtopic) . " in English Language, students should pay attention to examples and practise applying the rules in their own writing and speech. Regular practice and exposure to varied texts reinforces learning and builds confidence.",
            ];
        }

        if (str_contains($subj, 'econom')) {
            return [
                "In Economics, " . htmlspecialchars($subtopic) . " deals with the principles that govern the production, distribution, and consumption of goods and services. Students analyse how individuals, businesses, and governments make decisions about resource allocation in the face of scarcity.",
                "The study of " . htmlspecialchars($subtopic) . " in Economics requires students to understand key concepts, interpret data, and apply economic models to real-world situations. Learners should be able to explain economic phenomena using appropriate terminology and analytical frameworks.",
                htmlspecialchars($subtopic) . " is a significant area in the Nigerian Economics curriculum. Students explore how economic principles apply to the Nigerian context, including issues related to development, trade, monetary policy, and financial markets.",
            ];
        }

        if (str_contains($subj, 'govern') || str_contains($subj, 'civic')) {
            return [
                "In Government, " . htmlspecialchars($subtopic) . " examines the structures, processes, and institutions through which societies are governed. Students learn about political systems, constitutions, the rule of law, and the rights and responsibilities of citizens.",
                "The study of " . htmlspecialchars($subtopic) . " in Government provides learners with an understanding of how political power is organized and exercised. This includes analysing different forms of government, the electoral process, and the role of citizens in a democracy.",
                htmlspecialchars($subtopic) . " is an integral part of the Nigerian Government curriculum. Students explore the historical development of Nigeria's political system, the structure of government at federal and state levels, and contemporary political issues.",
            ];
        }

        if (str_contains($subj, 'geograph')) {
            return [
                "In Geography, " . htmlspecialchars($subtopic) . " involves the study of the Earth's physical features, atmosphere, and human activities across different regions. Students learn to interpret maps, analyse spatial patterns, and understand the relationships between people and their environment.",
                "The study of " . htmlspecialchars($subtopic) . " in Geography requires learners to develop skills in observation, data collection, and map reading. Fieldwork and the use of geographical tools help students connect theoretical knowledge to real-world observations.",
                htmlspecialchars($subtopic) . " is a key component of the Geography curriculum in Nigerian schools. Students explore both the physical geography of Nigeria and the human geographical factors that shape settlement patterns, economic activities, and environmental management.",
            ];
        }

        if (str_contains($subj, 'history')) {
            return [
                "In History, " . htmlspecialchars($subtopic) . " explores past events, societies, and developments that have shaped the present. Students learn to analyse historical sources, understand cause and effect, and develop perspective on contemporary issues through the study of the past.",
                "The study of " . htmlspecialchars($subtopic) . " in History provides insight into the political, social, economic, and cultural developments that have influenced Nigeria and the wider world. Learners develop critical thinking skills through the evaluation of evidence and interpretation of historical narratives.",
                htmlspecialchars($subtopic) . " is a significant area of the Nigerian History curriculum. Students examine key events, personalities, and movements in Nigerian history, from pre-colonial times through independence to the present day.",
            ];
        }

        return [];
    }

    private static function generateBulletPoints(string $subject, string $topic, int $count): array
    {
        $subj = strtolower($subject);

        if (str_contains($subj, 'chemist')) {
            $pool = [
                "Chemistry explains the composition, structure, and properties of all forms of matter around us.",
                "Understanding chemical reactions helps us explain everyday phenomena from cooking to rusting.",
                "The mole concept and stoichiometry are essential tools for quantitative chemical analysis.",
                "Chemical equations must be balanced to satisfy the law of conservation of mass.",
                "The periodic table organizes elements by their atomic number and chemical properties.",
            ];
        } elseif (str_contains($subj, 'physic')) {
            $pool = [
                "Physics explains the fundamental laws that govern motion, energy, forces, and matter.",
                "Understanding physics principles is essential for technological innovation and engineering.",
                "Measurements and SI units are the foundation of all physical calculations.",
                "Energy exists in various forms and can be converted from one form to another.",
                "Forces cause changes in the motion of objects according to Newton's laws.",
            ];
        } elseif (str_contains($subj, 'biology') || str_contains($subj, 'science')) {
            $pool = [
                "Biology is the study of living organisms and their interactions with the environment.",
                "The cell is the basic structural and functional unit of all living organisms.",
                "Living organisms are classified into kingdoms based on shared characteristics.",
                "Ecosystems consist of living and non-living components that interact in complex ways.",
                "Understanding biological processes helps us maintain health and manage natural resources.",
            ];
        } elseif (str_contains($subj, 'mathemat')) {
            $pool = [
                "Mathematics uses logical reasoning and precise methods to solve problems.",
                "Understanding mathematical concepts builds a foundation for science and technology.",
                "Regular practice with varied problems is essential for developing mathematical fluency.",
                "Mathematics helps develop critical thinking and analytical problem-solving skills.",
                "Mathematical principles are applied in everyday life from budgeting to measurements.",
            ];
        } elseif (str_contains($subj, 'english') || str_contains($subj, 'literature')) {
            $pool = [
                "English Language skills are essential for effective communication in all subjects.",
                "Understanding grammar rules helps in constructing clear and correct sentences.",
                "Reading widely improves vocabulary, comprehension, and writing ability.",
                "Literature exposes students to diverse cultures, ideas, and forms of creative expression.",
                "Effective writing requires planning, drafting, revising, and editing.",
            ];
        } elseif (str_contains($subj, 'econom')) {
            $pool = [
                "Economics studies how societies allocate scarce resources to meet unlimited wants.",
                "Supply and demand determine the prices of goods and services in a market economy.",
                "Understanding economic principles helps individuals make informed financial decisions.",
                "Government policies influence economic growth, employment, and price stability.",
                "Nigeria's economy is shaped by both domestic policies and global economic trends.",
            ];
        } elseif (str_contains($subj, 'govern') || str_contains($subj, 'civic')) {
            $pool = [
                "Government is the system through which a society is organized and governed.",
                "The Nigerian Constitution outlines the structure and powers of the three arms of government.",
                "Citizenship comes with both rights and responsibilities in a democratic society.",
                "The rule of law ensures that all persons and institutions are accountable to the law.",
                "Active civic participation strengthens democracy and promotes good governance.",
            ];
        } else {
            $pool = [
                "{$topic} is a key area of study within {$subject} that helps students understand important concepts and principles.",
                "Active engagement with the learning materials and regular revision are essential for mastering this subject.",
                "Connecting theoretical knowledge to practical examples helps deepen understanding and improve retention.",
                "Students are encouraged to ask questions, participate in discussions, and seek clarification when needed.",
                "Consistent practice and review of past topics builds confidence and prepares students for assessments.",
            ];
        }

        return array_slice($pool, 0, $count);
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
        $subj = strtolower($subject);

        $note = "LESSON NOTE: {$subject} - {$topic}\n";
        $note .= "Class: {$class}\n";
        $note .= "Academic Year: {$year}\n";
        $note .= "Difficulty Level: {$difficulty}\n";
        $note .= str_repeat("=", 50) . "\n\n";

        $note .= "LEARNING OBJECTIVES:\n";
        foreach ($objectives as $o) {
            $note .= "  \u{2022} {$o}\n";
        }
        $note .= "\n";

        $note .= "INTRODUCTION:\n";
        $note .= "This lesson focuses on {$topic} in {$subject} for {$class}. Students will explore the key concepts, principles, and applications of this topic through structured learning activities, examples, and practice exercises.\n\n";

        $note .= "CONTENT DEVELOPMENT:\n\n";
        foreach ($subtopics as $i => $st) {
            $note .= ($i + 1) . ". " . strip_tags($st) . ":\n";
            $note .= "   " . self::generateDetailedSection($subject, $class, $topic, $st, $subj, $difficulty) . "\n\n";
        }

        $note .= "KEY POINTS TO NOTE:\n";
        if (str_contains($subj, 'chemist')) {
            $note .= "  \u{2022} Chemical substances have specific compositions and properties that determine their behaviour.\n";
            $note .= "  \u{2022} Balanced chemical equations are essential for quantitative analysis of reactions.\n";
            $note .= "  \u{2022} Understanding chemical principles helps explain natural phenomena and industrial processes.\n";
        } elseif (str_contains($subj, 'physic')) {
            $note .= "  \u{2022} Physical quantities must be measured using appropriate instruments and SI units.\n";
            $note .= "  \u{2022} Scientific laws and principles form the foundation for understanding physical phenomena.\n";
            $note .= "  \u{2022} Practical experimentation and data analysis are essential skills in Physics.\n";
        } elseif (str_contains($subj, 'biology') || str_contains($subj, 'science')) {
            $note .= "  \u{2022} Life processes are governed by complex biological systems and mechanisms.\n";
            $note .= "  \u{2022} Classification helps organize the diversity of living organisms into manageable groups.\n";
            $note .= "  \u{2022} Ecological relationships between organisms and their environment maintain balance in nature.\n";
        } elseif (str_contains($subj, 'mathemat')) {
            $note .= "  \u{2022} Mathematical problems require systematic approaches and careful calculations.\n";
            $note .= "  \u{2022} Understanding formulae and knowing when to apply them is key to success.\n";
            $note .= "  \u{2022} Regular practice with varied problems builds speed, accuracy, and confidence.\n";
        } else {
            $note .= "  \u{2022} Key concepts and definitions should be clearly understood before moving forward.\n";
            $note .= "  \u{2022} Regular revision and practice help reinforce learning and improve retention.\n";
            $note .= "  \u{2022} Applying knowledge to practical situations deepens understanding.\n";
        }
        $note .= "\n";

        $note .= "SUMMARY:\n";
        $note .= "This lesson covered the topic of {$topic} in {$subject}. Students explored the main concepts, worked through relevant examples, and engaged in learning activities designed to reinforce their understanding. Continued practice and revision will help consolidate these concepts and prepare students for the next stage of learning.\n\n";

        $note .= "EVALUATION:\n";
        $note .= "Students will be assessed through:\n";
        $note .= "  \u{2022} Oral questions and class participation during the lesson\n";
        $note .= "  \u{2022} Written exercises and problem-solving tasks\n";
        $note .= "  \u{2022} End-of-lesson review questions\n";
        $note .= "  \u{2022} Homework assignments for independent practice\n\n";

        $note .= "ASSIGNMENT:\n";
        if (str_contains($subj, 'mathemat') || str_contains($subj, 'physic') || str_contains($subj, 'chemist')) {
            $note .= "  1. Complete all practice problems related to this topic in your textbook\n";
            $note .= "  2. Create a summary note of the key formulae and concepts covered\n";
            $note .= "  3. Attempt the end-of-chapter review questions\n";
            $note .= "  4. Prepare three of your own questions to ask in the next class\n";
        } else {
            $note .= "  1. Write a summary of the key points covered in today's lesson\n";
            $note .= "  2. Answer the review questions at the end of the chapter\n";
            $note .= "  3. Research and note down two real-life applications of this topic\n";
            $note .= "  4. Prepare for a brief class discussion on this topic in the next lesson\n";
        }

        return $note;
    }

    private static function generateDetailedSection(string $subject, string $class, string $topic, string $subtopic, string $subj, string $difficulty): string
    {
        if (str_contains($subj, 'chemist')) {
            $sections = [
                "This section examines the chemical nature of {$topic}, focusing on its composition, structure, and the principles that govern its behaviour in chemical reactions.",
                "Here we explore the properties and characteristics of {$topic}, including how it interacts with other substances and the conditions that affect these interactions.",
                "This part covers the quantitative aspects of {$topic}, including relevant calculations, measurements, and the application of chemical formulae and equations.",
                "The practical importance of {$topic} is examined through its applications in industry, medicine, agriculture, and everyday life in Nigeria.",
                "This section deals with laboratory procedures, observations, and experimental techniques related to the study of {$topic}.",
            ];
        } elseif (str_contains($subj, 'physic')) {
            $sections = [
                "This section introduces the fundamental principles of {$topic}, including the key definitions and physical laws that describe its behaviour.",
                "Here we examine the mathematical relationships and formulae used to calculate and predict physical quantities related to {$topic}.",
                "This part explores real-world applications and examples of {$topic}, demonstrating how physics principles operate in everyday situations.",
                "Experimental methods for investigating {$topic} are covered, including measurement techniques, data collection, and analysis procedures.",
                "The technological and industrial applications of {$topic} are discussed, highlighting the connection between physics and innovation.",
            ];
        } elseif (str_contains($subj, 'biology') || str_contains($subj, 'science')) {
            $sections = [
                "This section describes the basic structure and organization of {$topic}, including the key components and how they are arranged.",
                "Here we examine the functions and processes associated with {$topic}, understanding how living systems operate and maintain life.",
                "This part covers the different types and categories of {$topic}, providing a framework for understanding its diversity and complexity.",
                "The ecological and environmental significance of {$topic} is explored, including its role in maintaining balance in nature.",
                "Laboratory and field studies related to {$topic} are discussed, including observation techniques and practical investigations.",
            ];
        } elseif (str_contains($subj, 'mathemat')) {
            $sections = [
                "This section introduces the basic concepts and definitions of {$topic}, establishing the foundation for understanding more complex applications.",
                "Here we learn the formulae and methods used to solve problems involving {$topic}, working through examples step by step.",
                "This part provides worked examples with complete solutions, demonstrating the correct approach to solving problems on {$topic}.",
                "Real-life applications of {$topic} are explored, showing how mathematical concepts are used in practical situations.",
                "Advanced problems and challenges related to {$topic} are presented to develop higher-order thinking and problem-solving skills.",
            ];
        } elseif (str_contains($subj, 'english') || str_contains($subj, 'literature')) {
            $sections = [
                "This section defines and explains {$topic}, providing a clear understanding of its meaning and usage in English Language.",
                "Here we examine the rules and conventions that govern {$topic}, including guidelines for correct usage in writing and speech.",
                "Examples and illustrations of {$topic} are provided to demonstrate how it is correctly used in different contexts.",
                "Common errors related to {$topic} are identified and explained, helping students avoid mistakes in their own work.",
                "Practical exercises and activities help students practise and apply their knowledge of {$topic} in meaningful ways.",
            ];
        } else {
            $sections = [
                "This section provides an overview of the key concepts and ideas related to this topic in {$subject}.",
                "Here we examine the main features and characteristics that define this area of study.",
                "The practical applications and real-world relevance of this topic are explored with relevant examples.",
                "This section covers important facts, data, and information that students need to master.",
            ];
        }

        $key = abs(crc32($subtopic)) % count($sections);
        return $sections[$key];
    }

    private static function generateSingleQuestion(string $subject, string $topic, int $num): array
    {
        $seed = crc32($topic . $subject . $num);
        srand($seed);

        // Each template is [question_stem, correct_answer_pattern]
        // The options are generated per-question using the topic/seed to vary
        $pairs = [
            ["What is the main focus of {$topic} in {$subject}?", "The study of {$topic} focuses on {$topic}-related concepts within {$subject}"],
            ["Which of the following best defines {$topic}?", "{$topic} is a key concept in {$subject} that deals with specific principles and applications"],
            ["The term '{$topic}' in {$subject} refers to:", "{$topic} refers to the core ideas and practices related to this topic in {$subject}"],
            ["One of the key characteristics of {$topic} is:", "A defining characteristic of {$topic} is its focus on relevant {$subject} principles"],
            ["Why is {$topic} important in the study of {$subject}?", "{$topic} is important because it forms the foundation for understanding advanced {$subject} concepts"],
            ["Which of the following is NOT directly related to {$topic}?", "An unrelated concept from a different area of {$subject} that does not involve {$topic}"],
            ["{$topic} can best be described as:", "{$topic} encompasses the fundamental ideas and methods used in this area of {$subject}"],
            ["A practical application of {$topic} in real life is:", "Applying {$topic} principles helps solve real-world problems in {$subject}-related fields"],
            ["All of the following are aspects of {$topic} EXCEPT:", "A topic from {$subject} that is studied separately from {$topic}"],
            ["Which statement about {$topic} is correct?", "{$topic} involves understanding key principles and applying them to {$subject} problems"],
            ["The study of {$topic} helps students to:", "Students learn to apply {$topic} concepts to analyze and solve {$subject} problems effectively"],
            ["In {$subject}, the concept of {$topic} is used to:", "{$topic} provides the tools and framework for understanding {$subject} at a deeper level"],
            ["Which of the following best illustrates {$topic}?", "A real-world example or scenario that demonstrates {$topic} in the context of {$subject}"],
            ["A student mastering {$topic} should be able to:", "Identify and apply the key principles of {$topic} to solve {$subject} problems independently"],
            ["Which of the following is a common misunderstanding about {$topic}?", "Confusing {$topic} with a related but different concept taught elsewhere in {$subject}"],
            ["{$topic} contributes to {$subject} by:", "Providing essential knowledge and skills that are built upon in more advanced {$subject} topics"],
            ["When learning {$topic}, it is important to first understand:", "The prerequisite concepts in {$subject} that form the basis for studying {$topic}"],
            ["Which of the following correctly applies {$topic} principles?", "Using {$topic} methods to correctly analyze a given {$subject} scenario or problem"],
            ["The scope of {$topic} in {$subject} includes:", "The key areas, subtopics, and applications that fall under {$topic} within {$subject}"],
            ["Which question would {$topic} help a {$subject} student answer?", "A question that requires knowledge of {$topic} to solve or explain in {$subject}"],
            ["A key skill developed through studying {$topic} is:", "The ability to apply {$topic}-specific reasoning to {$subject} problems and questions"],
            ["{$topic} relates to other {$subject} topics by:", "Building on previously learned concepts and providing groundwork for more advanced study"],
            ["The best way to understand {$topic} is to:", "Study the core principles of {$topic} and practice applying them to {$subject} examples"],
            ["Which of the following is a direct application of {$topic}?", "Using {$topic} knowledge to address a specific problem or question in {$subject}"],
        ];

        $index = abs($num + $seed) % count($pairs);
        $q = $pairs[$index][0];
        $correctAnswer = $pairs[$index][1];

        // Vary phrasing to reduce repetition across large question sets
        $prefixes = ['', 'Specifically, ', 'In practice, ', 'Generally speaking, ', 'In the context of this topic, '];
        $q = $prefixes[$num % count($prefixes)] . $q;

        // Replace placeholders in question and correct answer
        $q = str_replace(['{$topic}', '{$subject}'], [$topic, $subject], $q);
        $correctAnswer = str_replace(['{$topic}', '{$subject}'], [$topic, $subject], $correctAnswer);

        // Generate per-question wrong options that differ from the correct answer and from each other
        $wrongPatterns = [
            'An unrelated concept from a different part of {$subject}',
            'A common error where students confuse {$topic} with another topic',
            'A simplified explanation that omits key details about {$topic}',
            'A topic from {$subject} that is studied in a different term',
            'The opposite or inverse of the correct {$topic} principle',
            'A concept that applies to a different subject, not {$subject}',
            'An outdated or incorrect understanding of {$topic}',
            'A general statement about {$subject} that does not specifically relate to {$topic}',
            'A definition that applies to a different topic within {$subject}',
            'An example from outside {$subject} that does not illustrate {$topic}',
            'A principle that contradicts the established understanding of {$topic}',
            'A vague description that could apply to many topics, not just {$topic}',
            'A concept studied before {$topic} that provides background but is not {$topic} itself',
            'An advanced topic that requires knowledge of {$topic} but is not {$topic}',
            'A memorization-based approach rather than understanding {$topic} concepts',
            'A definition that confuses {$topic} with a broader {$subject} concept',
            'An incorrect application of {$topic} principles to a {$subject} problem',
            'A topic that is related to {$subject} but outside the current curriculum scope',
        ];

        // Replace placeholders in wrong patterns
        $wrongPatterns = array_map(fn($p) => str_replace(['{$topic}', '{$subject}'], [$topic, $subject], $p), $wrongPatterns);

        // Pick 3 unique wrong options using a seeded shuffle
        shuffle($wrongPatterns);
        $selectedWrong = array_slice($wrongPatterns, 0, 3);

        // Ensure all 4 options are different
        $allOptions = [$correctAnswer, ...$selectedWrong];
        $attempts = 0;
        while (count(array_unique($allOptions)) < 4 && $attempts < 10) {
            $allOptions[] = "Another aspect of {$subject} related to but not the same as {$topic}";
            $allOptions = array_unique(array_values($allOptions));
            $attempts++;
        }
        $allOptions = array_values(array_slice($allOptions, 0, 4));

        // Shuffle options and track which one is the correct answer
        $letters = ['A', 'B', 'C', 'D'];
        shuffle($allOptions);
        $options = [];
        $correctLetter = 'A';
        foreach ($letters as $i => $letter) {
            $options[$letter] = $allOptions[$i];
            if ($allOptions[$i] === $correctAnswer) {
                $correctLetter = $letter;
            }
        }

        srand();

        return [
            'id' => $num,
            'question' => $q,
            'A' => $options['A'],
            'B' => $options['B'],
            'C' => $options['C'],
            'D' => $options['D'],
            'answer' => $correctLetter,
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
