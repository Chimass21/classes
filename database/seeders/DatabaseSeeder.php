<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Notification;
use App\Models\Result;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $db = json_decode(file_get_contents(base_path('-Chimass21-BRAIN4-main/brain_db.json')), true);

        // Seed users
        foreach ($db['users'] as $userData) {
            User::updateOrCreate(
                ['id' => $userData['id']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'reg_number' => $userData['regNumber'] ?? null,
                    'class_level' => $userData['classLevel'] ?? null,
                    'is_suspended' => $userData['isSuspended'],
                    'wallet_balance' => $userData['walletBalance'],
                    'created_at' => $userData['createdAt'],
                ]
            );
        }

        // Seed exams
        foreach ($db['exams'] as $examData) {
            Exam::updateOrCreate(
                ['id' => $examData['id']],
                [
                    'title' => $examData['title'],
                    'subject' => $examData['subject'],
                    'level' => $examData['level'],
                    'duration' => $examData['duration'],
                    'total_marks' => $examData['totalMarks'],
                    'instructions' => $examData['instructions'],
                    'questions' => $examData['questions'],
                    'creator_id' => $examData['creatorId'],
                    'creator_name' => $examData['creatorName'],
                    'exam_link' => $examData['examLink'],
                    'is_published' => $examData['isPublished'],
                    'created_at' => $examData['createdAt'],
                ]
            );
        }

        // Seed results
        foreach ($db['results'] as $resultData) {
            Result::updateOrCreate(
                ['id' => $resultData['id']],
                [
                    'exam_id' => $resultData['examId'],
                    'exam_title' => $resultData['examTitle'],
                    'subject' => $resultData['subject'],
                    'student_id' => $resultData['studentId'],
                    'student_name' => $resultData['studentName'],
                    'score' => $resultData['score'],
                    'percentage' => $resultData['percentage'],
                    'total_questions' => $resultData['totalQuestions'],
                    'correct_answers' => $resultData['correctAnswers'],
                    'failed_questions' => $resultData['failedQuestions'],
                    'created_at' => $resultData['date'],
                ]
            );
        }

        // Seed transactions
        foreach ($db['transactions'] as $txData) {
            Transaction::updateOrCreate(
                ['id' => $txData['id']],
                [
                    'user_id' => $txData['userId'],
                    'user_name' => $txData['userName'],
                    'amount' => $txData['amount'],
                    'type' => $txData['type'],
                    'purpose' => $txData['purpose'],
                    'created_at' => $txData['date'],
                ]
            );
        }

        // Seed notifications
        foreach ($db['notifications'] as $notifData) {
            Notification::updateOrCreate(
                ['id' => $notifData['id']],
                [
                    'user_id' => $notifData['userId'],
                    'title' => $notifData['title'],
                    'message' => $notifData['message'],
                    'read' => $notifData['read'],
                    'created_at' => $notifData['date'],
                ]
            );
        }
    }
}
