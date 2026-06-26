<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonNoteController;
use App\Http\Controllers\LessonPlanController;
use App\Http\Controllers\ReportSheetController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\DownloadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Landing Page
Route::get('/', [HomeController::class, 'index'])->name('landing');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Student Dashboard (auth required)
Route::prefix('student')->middleware(['json.auth', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
});

// Exam start (guest-accessible)
Route::get('/student/exam/{examId}', [StudentController::class, 'startExam'])->name('student.exam.start');
Route::post('/student/exam/{examId}/submit', [StudentController::class, 'submitExam'])->name('student.exam.submit');

// Teacher Routes
Route::prefix('teacher')->middleware(['json.auth', 'role:teacher'])->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
    Route::get('/exams/create', [ExamController::class, 'create'])->name('exam.create');
    Route::post('/exams', [ExamController::class, 'store'])->name('exam.store');
    Route::post('/exams/{examId}/publish', [ExamController::class, 'publish'])->name('exam.publish');
    Route::delete('/exams/{examId}', [ExamController::class, 'destroy'])->name('exam.delete');
    Route::get('/lesson-plans/create', [LessonPlanController::class, 'create'])->name('lesson-plan.create');
    Route::post('/lesson-plans', [LessonPlanController::class, 'store'])->name('lesson-plan.store');
    Route::get('/lesson-notes/create', [LessonNoteController::class, 'create'])->name('lesson-note.create');
    Route::post('/lesson-notes', [LessonNoteController::class, 'store'])->name('lesson-note.store');
    Route::get('/reports', [ReportSheetController::class, 'index'])->name('report-sheet.index');
});

// Admin Routes
Route::prefix('admin')->middleware(['json.auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

// API Routes (for AJAX calls)
Route::prefix('api')->group(function () {
    // Auth API
    Route::post('/auth/login', [AuthController::class, 'apiLogin']);
    Route::post('/auth/register', [AuthController::class, 'apiRegister']);
    Route::get('/auth/session', [AuthController::class, 'apiSession']);
    Route::post('/auth/logout', [AuthController::class, 'apiLogout']);
    Route::post('/auth/reset', [AuthController::class, 'apiReset']);

    Route::get('/exams', [ExamController::class, 'apiIndex']);
    Route::get('/exams/{exam}', [ExamController::class, 'apiShow']);
    Route::post('/ai/lesson-plan', [AIController::class, 'generateLessonPlan']);
    Route::post('/ai/lesson-note', [AIController::class, 'generateLessonNote']);
    Route::post('/ai/questions', [AIController::class, 'generateQuestions']);
    Route::post('/ai/generate-questions', [AIController::class, 'generateQuestions']);
    Route::get('/school-config', [HomeController::class, 'schoolConfig']);
    Route::post('/school-config', [HomeController::class, 'updateSchoolConfig']);
    Route::get('/subjects', [HomeController::class, 'subjects']);
    Route::get('/curriculum/classes', function () { return response()->json(['classes' => \App\Helpers\CurriculumData::getClasses()]); });
    Route::get('/curriculum/terms', function () { return response()->json(['terms' => \App\Helpers\CurriculumData::getTerms()]); });
    Route::get('/curriculum/weeks', function () { return response()->json(['weeks' => \App\Helpers\CurriculumData::getWeeks()]); });
    Route::get('/curriculum/scheme/{subject}/{class}/{term}', function ($subject, $class, $term) {
        return response()->json(['scheme' => \App\Helpers\CurriculumData::getSchemeOfWork($subject, $class, $term)]);
    });
    Route::get('/results', [ResultController::class, 'apiIndex']);
    Route::get('/report-sheets', [ReportSheetController::class, 'apiIndex']);
    Route::post('/report-sheets', [ReportSheetController::class, 'store']);
    Route::post('/report-sheets/collate', [ReportSheetController::class, 'collate']);
    Route::post('/report-sheets/delete', [ReportSheetController::class, 'delete']);
    Route::get('/users', [AdminController::class, 'apiUsers']);
    Route::get('/schemes', [HomeController::class, 'schemes']);
    Route::get('/questions/sets', [AIController::class, 'getQuestionSets']);
    Route::get('/questions/sets/{id}', [AIController::class, 'getQuestionSet']);
    Route::post('/questions/save', [AIController::class, 'saveGeneratedQuestions']);
    Route::post('/questions/convert-to-exam', [AIController::class, 'convertQuestionsToExam']);
    Route::get('/admin/stats', [AdminController::class, 'apiStats']);
    Route::get('/admin/activities', [AdminController::class, 'apiActivities']);
    Route::post('/admin/users/{userId}/update', [AdminController::class, 'apiUpdateUser']);
    Route::post('/admin/users/{userId}/delete', [AdminController::class, 'apiDeleteUser']);
    Route::post('/admin/feedback/{feedbackId}/delete', [AdminController::class, 'apiDeleteFeedback']);
    Route::post('/exams', [ExamController::class, 'apiStore']);
    Route::post('/exams/{examId}/publish', [ExamController::class, 'apiPublish']);
    Route::delete('/exams/{examId}', [ExamController::class, 'apiDestroy']);
    Route::post('/exams/{examId}/submit', [ExamController::class, 'apiSubmitExam']);
    Route::get('/results', [ResultController::class, 'apiIndex']);
    Route::get('/results/student/{studentId}', [ResultController::class, 'apiStudentResults']);
    Route::get('/notifications/user/{userId}', [HomeController::class, 'apiUserNotifications']);
    Route::post('/notifications/{id}/read', [HomeController::class, 'apiReadNotification']);
    Route::get('/lesson-notes', [HomeController::class, 'apiAllLessonNotes']);
    Route::get('/teachers/{teacherId}/lesson-plans', [HomeController::class, 'apiTeacherLessonPlans']);
    Route::get('/teachers/{teacherId}/lesson-notes', [HomeController::class, 'apiTeacherLessonNotes']);
    Route::post('/feedback', [HomeController::class, 'submitFeedback']);
    Route::post('/feedback/chat', [HomeController::class, 'chatFeedback']);
    Route::get('/download/lesson-note/{id}/{format}', [DownloadController::class, 'downloadLessonNote']);
    Route::get('/download/lesson-plan/{id}/{format}', [DownloadController::class, 'downloadLessonPlan']);
    Route::get('/download/exam/{id}/{format}', [DownloadController::class, 'downloadExam']);
});

// Migrate legacy plaintext passwords to hashed
Route::get('/migrate-passwords', function () {
    $count = 0;
    \App\Helpers\JsonDb::init();
    $db = \App\Helpers\JsonDb::get();
    foreach ($db['users'] as &$user) {
        if (!empty($user['password']) && !\Illuminate\Support\Facades\Hash::isHashed($user['password'])) {
            $user['password'] = \Illuminate\Support\Facades\Hash::make($user['password']);
            $count++;
        }
    }
    \App\Helpers\JsonDb::save($db);
    return "$count passwords hashed.";
});

// Direct portal access
Route::get('/portal/student', function () {
    return redirect()->route('student.dashboard');
})->name('portal.student');

Route::get('/portal/teacher', function () {
    return redirect()->route('teacher.dashboard');
})->name('portal.teacher');

Route::get('/portal/admin', function () {
    return redirect()->route('admin.dashboard');
})->name('portal.admin');

// API fallback for unmatched API routes
Route::fallback(function (Request $request) {
    if (str_starts_with($request->path(), 'api/')) {
        return response()->json(['success' => false, 'error' => 'Route not found.'], 404);
    }
    return redirect()->route('landing');
});
