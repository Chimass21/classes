<?php

namespace App\Helpers;

/**
 * Builds wa.me deep links used to forward exam results to the teacher's
 * WhatsApp number. No external API / credentials are required — the teacher
 * taps the link and sends the pre-filled message from WhatsApp.
 */
class WhatsApp
{
    /**
     * Normalised international phone number (digits only, no leading +).
     * Converts Nigerian local numbers (08062078597) to 2348062078597.
     */
    public static function phone(): string
    {
        $phone = trim((string) config('services.whatsapp.phone', ''));
        if ($phone === '') {
            return '';
        }

        $digits = preg_replace('/[^\d]/', '', $phone);
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '234' . substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '234' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Human-readable summary text for a single exam result.
     */
    public static function resultText(array $result, string $schoolName = ''): string
    {
        $pct = (int) ($result['percentage'] ?? 0);
        $grade = $pct >= 75 ? 'A' : ($pct >= 60 ? 'B' : ($pct >= 50 ? 'C' : ($pct >= 40 ? 'D' : 'F')));

        $lines = [
            'EXAM RESULT NOTIFICATION',
            '----------------------------',
        ];

        if (trim($schoolName) !== '') {
            $lines[] = 'School: ' . trim($schoolName);
        }
        $lines[] = 'Student: ' . ($result['studentName'] ?? 'Student');
        $lines[] = 'Exam: ' . ($result['examTitle'] ?? '');
        $lines[] = 'Subject: ' . ($result['subject'] ?? '');
        $lines[] = 'Score: ' . ($result['score'] ?? 0) . ' / ' . ($result['totalPossibleMarks'] ?? $result['totalQuestions'] ?? 0);
        $lines[] = 'Percentage: ' . $pct . '%';
        $lines[] = 'Grade: ' . $grade;
        $lines[] = 'Correct: ' . ($result['correctAnswers'] ?? 0) . ' / ' . ($result['totalQuestions'] ?? 0);
        if (!empty($result['date'])) {
            $lines[] = 'Date: ' . date('d M Y H:i', strtotime($result['date']));
        }
        $lines[] = 'Status: ' . ($pct >= 50 ? 'PASSED' : 'FAILED');

        return implode("\n", $lines);
    }

    /**
     * Full wa.me deep link pre-filled with the result summary, or '' if no
     * WhatsApp number is configured.
     */
    public static function resultLink(array $result, string $schoolName = ''): string
    {
        $phone = self::phone();
        if ($phone === '') {
            return '';
        }
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode(self::resultText($result, $schoolName));
    }
}
