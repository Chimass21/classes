<?php

namespace App\Helpers;

class ContentGenerator
{
    public static function generateLessonPlan(string $subject, string $class, string $term, int $week, string $topic, string $schoolName, string $teacherName, string $duration, string $ageRange): array
    {
        $objectives = self::generateObjectives($subject, $class, $topic);
        $materials = self::generateMaterials($subject, $class, $topic);
        $previousKnowledge = self::generatePreviousKnowledge($subject, $class, $topic);
        $steps = self::generateLessonSteps($objectives, $subject, $class, $topic);
        $evaluation = self::generateEvaluation($objectives, $subject, $topic);
        $assignment = self::generateAssignment($subject, $class, $topic);
        $summary = self::generateSummary($subject, $topic, $objectives);
        $conclusion = self::generateConclusion($subject, $topic);

        return compact('objectives', 'materials', 'previousKnowledge', 'steps', 'evaluation', 'assignment', 'summary', 'conclusion');
    }

    public static function generateLessonNote(string $subject, string $class, string $term, int $week, string $topic, string $periods, string $difficulty, string $ageRange): array
    {
        $subtopics = self::generateSubtopics($subject, $class, $topic);
        $learningObjectives = self::generateObjectives($subject, $class, $topic);
        $introduction = self::generateIntroduction($subject, $class, $topic);
        $content = self::generateContentHtml($subject, $class, $topic, $subtopics);
        $examples = self::generateExamples($subject, $class, $topic);
        $activities = self::generateActivities($subject, $class, $topic);
        $summary = self::generateSummary($subject, $topic, $learningObjectives);
        $conclusion = self::generateConclusion($subject, $topic);
        $evaluationQuestions = self::generateEvaluationQuestions($subject, $topic, $learningObjectives);
        $assignment = self::generateAssignment($subject, $class, $topic);
        $detailedNote = self::generateDetailedNote($subject, $class, $topic, $subtopics, $learningObjectives);

        return compact('topic', 'subtopics', 'learningObjectives', 'introduction', 'content', 'examples', 'activities', 'summary', 'conclusion', 'evaluationQuestions', 'assignment', 'detailedNote');
    }

    public static function generateQuestions(string $subject, string $topic, int $count, bool $includeTheory): array
    {
        $objectives = [];
        for ($i = 1; $i <= $count; $i++) {
            $objectives[] = self::generateSingleQuestion($subject, $topic, $i);
        }
        $result = ['objectives' => $objectives];
        if ($includeTheory) {
            $result['theoryQuestions'] = [
                ['question' => "Explain in detail the concept of {$topic} as it applies to {$subject}.", 'answer' => self::getTheoryAnswer($subject, $topic)],
                ['question' => "Discuss the practical applications of {$topic} in everyday life.", 'answer' => self::getApplicationAnswer($subject, $topic)],
            ];
            $result['essayQuestions'] = [
                ['question' => "Write a comprehensive essay on the importance of {$topic} in {$subject}.", 'guidance' => "Include definition, types, characteristics, importance, and real-world examples."],
            ];
            $result['structuredQuestions'] = [
                ['question' => "Describe {$topic} with reference to {$subject}.", 'parts' => ['a' => "Define {$topic}", 'b' => "List five key features of {$topic}", 'c' => "Explain three importance of {$topic}", 'd' => "Give two practical examples of {$topic}"]],
            ];
        }
        return $result;
    }

    // ---- SMART OBJECTIVE/TASK GENERATORS ----

    private static function generateObjectives(string $subject, string $class, string $topic): array
    {
        $level = CurriculumData::getClassCategory($class);
        $verbs = $level === 'primary'
            ? ['define', 'identify', 'mention', 'list', 'give examples of']
            : ($level === 'junior'
                ? ['define', 'explain', 'describe', 'differentiate', 'apply']
                : ['define', 'explain', 'analyse', 'evaluate', 'synthesise']);

        $patterns = [
            "By the end of the lesson, students should be able to: 1. {$verbs[0]} " . lcfirst($topic) . ".",
            "Students will be able to {$verbs[1]} the key features of " . lcfirst($topic) . ".",
            "Students will {$verbs[2]} the relationship between " . lcfirst($topic) . " and other concepts in {$subject}.",
            "Students will {$verbs[3]} between different types of " . lcfirst($topic) . ".",
            "Students will {$verbs[4]} the knowledge of " . lcfirst($topic) . " to solve practical problems.",
        ];

        $objectives = self::applyTopicContext($patterns, $subject, $class, $topic);

        return array_slice($objectives, 0, rand(3, 5));
    }

    private static function generateMaterials(string $subject, string $class, string $topic): array
    {
        $materials = [
            'Whiteboard and markers', 'Recommended textbook', 'Student notebooks',
            'Charts and diagrams', 'Handouts',
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
        $priorTopics = self::getRelatedTopics($subject, $topic);
        $priorTopic = $priorTopics[0] ?? 'related topics';

        $statements = [
            "Students have prior knowledge of basic concepts in {$subject} and can identify simple examples from their environment.",
            "Students are familiar with fundamental principles of {$subject} and have previously been introduced to related topics.",
            "Students can recall previous lessons on {$priorTopic} and can connect these to the new topic.",
            "Students possess basic numeracy/literacy skills relevant to understanding {$topic} in {$subject}.",
        ];

        if ($level === 'primary') {
            return "Students can count, read, and write basic numbers/words. They have experience with simple {$subject} activities from their previous classes.";
        }
        if ($level === 'junior') {
            return "Students have foundational knowledge of {$subject} concepts from their previous classes. They can identify basic terms and apply simple principles.";
        }
        return "Students have studied introductory concepts in {$subject} at the JSS level and can analyze basic principles. They are familiar with standard {$subject} terminology.";
    }

    private static function generateLessonSteps(array $objectives, string $subject, string $class, string $topic): array
    {
        $steps = [];
        $stepCount = count($objectives);

        for ($i = 0; $i < $stepCount; $i++) {
            $stepNum = $i + 1;
            $isFirst = $i === 0;
            $isLast = $i === $stepCount - 1;

            $teacherAct = $isFirst
                ? "The teacher introduces the topic '{$topic}' by asking guiding questions related to students' prior knowledge. The teacher writes the topic and learning objectives on the board and explains what students will learn."
                : ($isLast
                    ? "The teacher guides students in applying the knowledge of {$topic} to solve practical problems and real-life situations in {$subject}. The teacher provides feedback and correction."
                    : "The teacher explains " . ($i === 1 ? "the key concepts and features of {$topic}" : ($i === 2 ? "the different types and classifications of {$topic}" : "the applications and importance of {$topic}")) . " using examples and illustrations on the board. Students are encouraged to ask questions."
                );

            $learnerAct = $isFirst
                ? "Students respond to questions, share what they already know about {$topic}, and write down the learning objectives in their notebooks."
                : ($isLast
                    ? "Students work in small groups to solve problems related to {$topic}. Each group presents their answers to the class."
                    : "Students listen attentively, take notes, and ask questions for clarification. They participate in class discussions."
                );

            $learningPoint = $isFirst
                ? "Students understand the scope and importance of {$topic} in {$subject}."
                : ($isLast
                    ? "Students can apply the knowledge of {$topic} to solve real-world problems in {$subject}."
                    : "Students gain a deeper understanding of " . ($i === 1 ? "the key concepts" : ($i === 2 ? "the types and classifications" : "the practical applications")) . " of {$topic}."
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
            $verbs = ['Define', 'List three features of', 'Explain the importance of', 'Differentiate between', 'Give two examples of'];
            $v = $verbs[$i % count($verbs)];
            $questions[] = "{$num}. {$v} {$topic} in the context of {$subject}.";
        }
        $questions[] = (count($questions) + 1) . ". Solve a practical problem involving {$topic}.";
        return implode("\n", $questions);
    }

    private static function generateAssignment(string $subject, string $class, string $topic): string
    {
        $level = CurriculumData::getClassCategory($class);
        $tasks = [
            "1. Write a comprehensive note on {$topic} in your notebook.",
            "2. Answer questions 1-5 on {$topic} from your textbook.",
            "3. Research and write two additional examples of {$topic} from your environment.",
        ];
        if ($level !== 'primary') {
            $tasks[] = "4. Prepare a 2-minute presentation on {$topic} for the next class.";
            $tasks[] = "5. Solve the practice problems on {$topic} attached to this lesson.";
        } else {
            $tasks[] = "4. Draw and label a diagram related to {$topic}.";
        }
        return implode("\n", $tasks);
    }

    private static function generateSummary(string $subject, string $topic, array $objectives): string
    {
        return "In this lesson, students learned about {$topic} in {$subject}. The key points covered include: " .
            implode('; ', array_map(function($o) {
                return strtolower(substr($o, 0, 60)) . '...';
            }, array_slice($objectives, 0, 3))) .
            ". Students should review these concepts and practice with the provided exercises to reinforce their understanding.";
    }

    private static function generateConclusion(string $subject, string $topic): string
    {
        return "The teacher concludes by summarizing the key points about {$topic} and emphasizing its relevance in {$subject} and everyday life. Students are encouraged to continue practicing at home. The next lesson will build on these concepts to explore more advanced topics in {$subject}.";
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
            return "The teacher begins the lesson by asking students to mention what they know about {$topic} in {$subject}. A fun story or real-life example is shared to capture the learners' attention. The teacher then states: 'Today, we are going to learn about {$topic}.' The learning objectives are written on the board in simple language.";
        }
        if ($level === 'junior') {
            return "The teacher reviews the previous lesson and asks questions to assess students' prior knowledge of {$subject}. A thought-provoking question related to {$topic} is posed to stimulate curiosity. The teacher then introduces the new topic '{$topic}' and explains the learning objectives for the lesson.";
        }
        return "The teacher begins by connecting the lesson to students' existing knowledge of {$subject}. A brief discussion on the relevance of {$topic} in the Nigerian context is facilitated. The learning objectives are stated clearly, and students are informed of what they will achieve by the end of the lesson.";
    }

    private static function generateContentHtml(string $subject, string $class, string $topic, array $subtopics): string
    {
        $level = CurriculumData::getClassCategory($class);
        $html = '';

        foreach ($subtopics as $st) {
            $html .= "<h4>{$st}</h4>";
            $html .= "<p>" . self::generateContentParagraph($subject, $class, $topic, $st) . "</p>";
        }

        $html .= "<h4>Key Points to Remember</h4><ul>";
        $bulletCount = $level === 'primary' ? 3 : ($level === 'junior' ? 4 : 5);
        $bullets = self::generateBulletPoints($subject, $topic, $bulletCount);
        foreach ($bullets as $b) {
            $html .= "<li>{$b}</li>";
        }
        $html .= "</ul>";

        return $html;
    }

    private static function generateContentParagraph(string $subject, string $class, string $topic, string $subtopic): string
    {
        $level = CurriculumData::getClassCategory($class);
        $complexity = $level === 'primary' ? 'simple' : ($level === 'junior' ? 'moderate' : 'detailed');

        $templates = [
            "In {$subject}, {$topic} refers to {$topic}. It is an important concept that helps us understand how things work in {$subject}. Students at the {$class} level will find this topic engaging and relevant to their studies.",
            "The study of {$subtopic} in {$subject} focuses on understanding the core principles that govern {$topic}. This concept is fundamental to building a strong foundation in {$subject}.",
            "{$topic} plays a vital role in {$subject} education at the {$class} level. By mastering {$subtopic}, students develop critical thinking skills and gain a deeper appreciation of how {$subject} relates to the world around them.",
            "When studying {$topic} in {$subject}, it is important to pay attention to the key aspects of {$subtopic}. This knowledge will be built upon in subsequent lessons and is essential for academic success.",
        ];

        $key = crc32($subtopic) % count($templates);
        return $templates[abs($key)];
    }

    private static function generateBulletPoints(string $subject, string $topic, int $count): array
    {
        $bullets = [
            "{$topic} is a key concept in {$subject} that every student should understand.",
            "The principles of {$topic} apply to many real-life situations in Nigeria and around the world.",
            "Mastery of {$topic} helps students perform better in examinations and practical applications.",
            "{$topic} connects to other important topics in {$subject}, creating a comprehensive learning experience.",
            "Understanding {$topic} develops analytical and problem-solving skills.",
        ];

        return array_slice($bullets, 0, $count);
    }

    private static function generateExamples(string $subject, string $class, string $topic): array
    {
        $level = CurriculumData::getClassCategory($class);
        $count = $level === 'primary' ? 2 : (str_contains(strtolower($subject), 'mathemat') || str_contains(strtolower($subject), 'physics') || str_contains(strtolower($subject), 'chemistry') ? 10 : 3);

        $examples = [];
        for ($i = 1; $i <= $count; $i++) {
            $examples[] = [
                'title' => "Example {$i}: " . ($i === 1 ? "Basic Concept" : ($i === 2 ? "Practical Application" : ($i === 3 ? "Advanced Application" : "Worked Example {$i}"))),
                'description' => "Consider the following example related to {$topic} in {$subject}: " .
                    ($i <= 3
                        ? self::getTopicExample($subject, $topic, $i, $class)
                        : "Step-by-step solution for example {$i} involving {$topic}. " .
                          "Step 1: Identify the given information. " .
                          "Step 2: Apply the relevant formula or principle. " .
                          "Step 3: Solve systematically. " .
                          "Step 4: Check your answer. " .
                          "Answer: The solution demonstrates the application of {$topic} principles.")
            ];
        }
        return $examples;
    }

    private static function getTopicExample(string $subject, string $topic, int $num, string $class): string
    {
        $examples = [
            "A student in {$class} wants to understand {$topic}. Here is a simple way to think about it using everyday items found in a Nigerian home.",
            "In a {$class} classroom, students can demonstrate {$topic} by working through an exercise from their textbook. This practical approach helps reinforce the concept.",
            "Let us apply {$topic} to a real situation: A Nigerian market woman uses {$topic} principles when calculating her daily profits and expenses.",
        ];
        return $examples[($num - 1) % count($examples)];
    }

    private static function generateActivities(string $subject, string $class, string $topic): array
    {
        $level = CurriculumData::getClassCategory($class);
        $count = $level === 'primary' ? 2 : 3;

        $activities = [
            [
                'title' => 'Activity 1: Group Discussion',
                'description' => "Divide the class into small groups. Each group discusses the concept of {$topic} in {$subject} and shares their understanding with the class. The teacher facilitates and provides clarification where needed.",
            ],
            [
                'title' => 'Activity 2: Individual Exercise',
                'description' => "Students work independently to complete a worksheet on {$topic}. The teacher moves around the classroom to provide individual support and guidance.",
            ],
            [
                'title' => 'Activity 3: Class Presentation',
                'description' => "Selected students present their understanding of {$topic} to the class. This builds confidence and reinforces learning through peer teaching.",
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
        return $questions;
    }

    private static function generateDetailedNote(string $subject, string $class, string $topic, array $subtopics, array $objectives): string
    {
        $note = "LESSON NOTE: {$subject} - {$topic}\n";
        $note .= "Class: {$class}\n";
        $note .= "Topic: {$topic}\n";
        $note .= "Subject: {$subject}\n";
        $note .= str_repeat("-", 50) . "\n\n";

        $note .= "LEARNING OBJECTIVES:\n";
        foreach ($objectives as $o) {
            $note .= "- {$o}\n";
        }
        $note .= "\n";

        $note .= "INTRODUCTION:\n";
        $note .= "The teacher introduces the topic '{$topic}' by relating it to real-life experiences and prior knowledge of the students. A stimulating question or scenario is used to arouse curiosity and set the stage for learning.\n\n";

        $note .= "CONTENT DEVELOPMENT:\n\n";
        foreach ($subtopics as $i => $st) {
            $note .= ($i + 1) . ". {$st}:\n";
            $note .= "   {$topic} in {$subject} involves understanding the key principles and applications of this concept. At the {$class} level, students are expected to grasp these ideas progressively.\n\n";
        }

        $note .= "SUMMARY:\n";
        $note .= "{$topic} is an important concept in {$subject} that encompasses various aspects including definition, characteristics, types, and applications. Mastery of this topic requires consistent practice and active engagement with the learning materials.\n\n";

        $note .= "EVALUATION:\n";
        $note .= "Students will be assessed through oral questions during the lesson, written exercises and worksheets, end-of-lesson quiz, and homework assignments.\n\n";

        $note .= "ASSIGNMENT:\n";
        $note .= "1. Write a comprehensive note on {$topic}\n";
        $note .= "2. Answer questions 1-5 in the textbook\n";
        $note .= "3. Research current developments related to {$topic}\n";
        $note .= "4. Prepare for a class presentation on {$topic}\n";

        return $note;
    }

    private static function generateSingleQuestion(string $subject, string $topic, int $num): array
    {
        $questionTemplates = [
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

        $q = $questionTemplates[($num - 1) % count($questionTemplates)];
        $correctIdx = rand(0, 3);
        $letters = ['A', 'B', 'C', 'D'];
        $options = [];
        for ($j = 0; $j < 4; $j++) {
            $options[$letters[$j]] = $j === $correctIdx
                ? "The correct definition/description of {$topic} in {$subject}."
                : "An incorrect definition/description related to {$topic}.";
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

    private static function getTheoryAnswer(string $subject, string $topic): string
    {
        return "{$topic} is a fundamental concept in {$subject} that refers to the systematic study of {$topic}. It covers various aspects including definitions, types, characteristics, importance, and practical applications. In the Nigerian curriculum context, {$topic} is taught across multiple class levels with increasing depth and complexity.";
    }

    private static function getApplicationAnswer(string $subject, string $topic): string
    {
        return "{$topic} has numerous practical applications in everyday life. In Nigeria, {$topic} can be observed in areas such as education, business, technology, and community development. Understanding {$topic} helps students appreciate how {$subject} concepts apply to real-world situations and prepares them for higher education and careers.";
    }

    private static function getSubjectSpecificMaterials(string $subject, string $topic): array
    {
        $subj = strtolower($subject);
        if (str_contains($subj, 'mathemat')) return ['Counters and abacus', 'Geometric shapes', 'Number line chart', 'Measuring tools'];
        if (str_contains($subj, 'physic')) return ['Laboratory apparatus', 'Meters and measuring devices', 'Experimental setup materials'];
        if (str_contains($subj, 'chemist')) return ['Laboratory chemicals and apparatus', 'Safety goggles', 'Periodic table chart'];
        if (str_contains($subj, 'biology') || str_contains($subj, 'science')) return ['Specimens and models', 'Magnifying glass/microscope', 'Charts of biological systems'];
        if (str_contains($subj, 'english') || str_contains($subj, 'literature')) return ['Reading passages', 'Dictionary', 'Grammar charts', 'Storybooks'];
        if (str_contains($subj, 'geograph')) return ['Maps and globes', 'Atlas', 'Compass', 'Weather charts'];
        if (str_contains($subj, 'history')) return ['Timeline charts', 'Historical pictures', 'Textbook with Nigerian history'];
        if (str_contains($subj, 'govern') || str_contains($subj, 'civic')) return ['Nigerian Constitution booklet', 'Posters of government structures', 'Newspaper cuttings'];
        if (str_contains($subj, 'econom')) return ['Graph charts', 'Price lists', 'Newspaper business sections'];
        if (str_contains($subj, 'account') || str_contains($subj, 'commerce')) return ['Sample ledgers and journals', 'Invoice templates', 'Business transaction examples'];
        if (str_contains($subj, 'agric')) return ['Farm tools and equipment', 'Seed samples', 'Posters of crop/livestock'];
        return [];
    }

    private static function getRelatedTopics(string $subject, string $topic): array
    {
        return [
            "previous lessons in {$subject}",
            "basic concepts of {$subject}",
            "introductory topics in {$subject}",
            "related themes in {$subject}",
        ];
    }

    private static function applyTopicContext(array $items, string $subject, string $class, string $topic): array
    {
        $subj = strtolower($subject);
        $isMath = str_contains($subj, 'mathemat');
        $isScience = str_contains($subj, 'physic') || str_contains($subj, 'chemist') || str_contains($subj, 'biology');
        $isEnglish = str_contains($subj, 'english');

        $contextualized = [];
        foreach ($items as $item) {
            if (str_contains($item, '{$topic}') || str_contains($item, lcfirst($topic))) {
                $contextualized[] = str_replace(
                    ['{$topic}', '{$subject}', '{$class}'],
                    [$topic, $subject, $class],
                    $item
                );
            } else {
                $contextualized[] = $item;
            }
        }

        if ($isMath && count($contextualized) > 0) {
            $contextualized[0] = "By the end of the lesson, students should be able to solve problems involving {$topic} using the correct mathematical methods.";
        }
        if ($isScience && count($contextualized) > 1) {
            $contextualized[1] = "Students will be able to perform practical demonstrations related to {$topic} and record their observations accurately.";
        }
        if ($isEnglish && count($contextualized) > 0) {
            $contextualized[0] = "By the end of the lesson, students should be able to correctly identify and use {$topic} in sentences and communication.";
        }

        return $contextualized;
    }
}
