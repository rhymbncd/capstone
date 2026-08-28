<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\TeacherApprovalController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
// Controllers
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\Student\FeedbackController as StudentFeedbackController;
use App\Http\Controllers\Student\ModuleController as StudentModuleController;
use App\Http\Controllers\Student\ProgressController as StudentProgressController;
use App\Http\Controllers\Student\PublishedQuizController as StudentPublishedQuizController;
use App\Http\Controllers\Student\QuizAnswerController as StudentQuizAnswerController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\Teacher\CustomTopicController;
use App\Http\Controllers\Teacher\FeedbackController as TeacherFeedbackController;
use App\Http\Controllers\Teacher\PublishedQuizController;
use App\Http\Controllers\Teacher\QuizDraftController;
use App\Http\Controllers\Teacher\SectionController as TeacherSectionController;
use App\Http\Controllers\Teacher\StudentAnswersController;
use App\Http\Controllers\Teacher\StudentApprovalController;
use App\Http\Controllers\TeacherDashboardController;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Route;

// AI chat assistant — students only, throttled since each call is a paid
// OpenRouter request. Kept at this URL (not nested under /student) so the
// existing frontend fetch('/chatbot/ask') call doesn't need to change.
Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])
    ->middleware(['auth', 'student', 'throttle:20,1'])
    ->name('chatbot.ask');

/* ----------- Homepage ----------- */
Route::get('/', function () {
    $platformDescription = PlatformSetting::get(
        'platform_desc',
        'Interactive learning platform for Junior High School Mathematics at Bubog National High School'
    );

    return view('dashboard.homepage', ['platformDescription' => $platformDescription]);
})->name('homepage');

// Client-side observability events (report downloaded, quiz published, …)
// from the teacher/admin dashboards. Actor is derived from the session.
Route::post('/activity-log', [ActivityController::class, 'store'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('activity-log.store');

// Shared module library (Supabase Storage + module_status). Behind `auth`;
// the controller re-checks the caller is teacher/admin, and only an admin
// may change moderation status.
Route::middleware('auth')->prefix('modules')->name('modules.')->group(function () {
    Route::get('/', [ModuleController::class, 'index'])->name('index');
    Route::post('/', [ModuleController::class, 'store'])->name('store');
    Route::get('/{moduleStatus}/file', [ModuleController::class, 'file'])->name('file');
    Route::patch('/{moduleStatus}', [ModuleController::class, 'update'])->name('update');
    Route::patch('/{moduleStatus}/topic', [ModuleController::class, 'updateTopic'])->name('updateTopic');
    Route::patch('/{moduleStatus}/status', [ModuleController::class, 'updateStatus'])->name('updateStatus');
    Route::delete('/{moduleStatus}', [ModuleController::class, 'destroy'])->name('destroy');
});

/* ----------- Auth Portal ----------- */
Route::get('/signin', function () {
    return view('login.signin');
})->name('signin-signin');

Route::get('/signup', function () {
    return view('login.signup');
})->name('signin-signup');

// ============ STUDENT ROUTES ============
Route::prefix('student')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showStudentLoginForm'])->name('student.login');
    Route::post('/login', [AuthController::class, 'studentLogin'])->middleware('throttle:login')->name('student.login.submit');

    // Register
    Route::get('/register', [AuthController::class, 'showStudentRegisterForm'])->name('student.register.form');
    Route::post('/register', [AuthController::class, 'studentRegister'])->middleware('throttle:auth-actions')->name('student.register');

    // Password reset (emailed to the student's own inbox)
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('student.password.request')->defaults('portalType', 'student');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:auth-actions')->name('student.password.email')->defaults('portalType', 'student');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('student.password.reset')->defaults('portalType', 'student');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:auth-actions')->name('student.password.update')->defaults('portalType', 'student');

    // Google signup completion
    Route::get('/complete-signup', [AuthController::class, 'showGoogleSignupCompletion'])->name('student.complete-google-signup');
    Route::post('/complete-signup', [AuthController::class, 'completeGoogleSignup'])->middleware('throttle:auth-actions')->name('student.complete-google-signup.submit');

    // Dashboard (Protected with student middleware - checks role and approval status)
    Route::middleware(['auth', 'student'])->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
        Route::get('/modules', function () {
            return view('dashboard.module');
        })->name('student.modules');
        Route::post('/logout', [AuthController::class, 'logout'])->name('student.logout');

        // Account
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('student.account.password');

        // Quiz progress + saved answers — replaces the browser's direct
        // anon-key writes to student_progress / student_quiz_answers.
        Route::get('/progress', [StudentProgressController::class, 'index'])->name('student.progress.index');
        Route::post('/progress', [StudentProgressController::class, 'store'])->name('student.progress.store');
        Route::post('/quiz-answers', [StudentQuizAnswerController::class, 'store'])->name('student.quiz-answers.store');

        // Teacher-published quizzes + custom topic names for the modules page.
        Route::get('/modules/published', [StudentPublishedQuizController::class, 'index'])->name('student.modules.published');

        // Approved module list + signed access to module PDFs.
        Route::get('/modules/list', [StudentModuleController::class, 'index'])->name('student.modules.list');
        Route::get('/modules/file', [StudentModuleController::class, 'file'])->name('student.modules.file');
        Route::get('/modules/{moduleStatus}/download', [StudentModuleController::class, 'download'])->name('student.modules.download');

        // Feedback from teachers
        Route::prefix('feedback')->group(function () {
            Route::get('/', [StudentFeedbackController::class, 'index'])->name('student.feedback.index');
            Route::post('/read-all', [StudentFeedbackController::class, 'markAllRead'])->name('student.feedback.read-all');
        });
    });
});

// ============ TEACHER ROUTES ============
Route::prefix('teacher')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showTeacherLoginForm'])->name('teacher.login');
    Route::post('/login', [AuthController::class, 'teacherLogin'])->middleware('throttle:login')->name('teacher.login.submit');

    // Register
    Route::get('/register', [AuthController::class, 'showTeacherRegisterForm'])->name('teacher.register.form');
    Route::post('/register', [AuthController::class, 'teacherRegister'])->middleware('throttle:auth-actions')->name('teacher.register');

    // Password reset (emailed to the teacher's own inbox)
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('teacher.password.request')->defaults('portalType', 'teacher');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:auth-actions')->name('teacher.password.email')->defaults('portalType', 'teacher');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('teacher.password.reset')->defaults('portalType', 'teacher');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:auth-actions')->name('teacher.password.update')->defaults('portalType', 'teacher');

    // Dashboard (Protected with role middleware)
    Route::middleware(['auth', 'role:teacher'])->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('teacher.logout');

        // Account
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('teacher.account.password');

        Route::post('/generate-quiz', [QuizController::class, 'generate'])->middleware('throttle:ai')->name('quiz.generate');
        Route::post('/quiz/generate-text', [QuizController::class, 'generateText'])->middleware('throttle:ai')->name('quiz.generate-text');

        // Quiz library — drafts, published quizzes, custom topics. Replaces
        // the dashboard's direct anon-key access to quizzes / quiz_published
        // / quiz_custom_topics.
        Route::prefix('quiz')->group(function () {
            Route::get('/drafts', [QuizDraftController::class, 'index'])->name('teacher.quiz.drafts.index');
            Route::post('/drafts', [QuizDraftController::class, 'store'])->name('teacher.quiz.drafts.store');
            Route::put('/drafts/{quiz}', [QuizDraftController::class, 'update'])->name('teacher.quiz.drafts.update');
            Route::delete('/drafts/{quiz}', [QuizDraftController::class, 'destroy'])->name('teacher.quiz.drafts.destroy');

            Route::get('/published', [PublishedQuizController::class, 'index'])->name('teacher.quiz.published.index');
            Route::post('/published', [PublishedQuizController::class, 'store'])->name('teacher.quiz.published.store');
            Route::delete('/published/{topicKey}', [PublishedQuizController::class, 'destroy'])->name('teacher.quiz.published.destroy');

            Route::get('/custom-topics', [CustomTopicController::class, 'index'])->name('teacher.quiz.custom-topics.index');
            Route::post('/custom-topics', [CustomTopicController::class, 'store'])->name('teacher.quiz.custom-topics.store');
            Route::delete('/custom-topics/{customTopic}', [CustomTopicController::class, 'destroy'])->name('teacher.quiz.custom-topics.destroy');
        });

        // Student Approvals
        Route::prefix('students')->group(function () {
            Route::get('/', [StudentController::class, 'getTeacherStudents'])->name('teacher.students.index');
            Route::get('/approvals', [StudentApprovalController::class, 'index'])->name('teacher.student-approvals');
            Route::get('/approvals-data', [StudentApprovalController::class, 'approvalsData'])->name('teacher.student-approvals.data');
            Route::post('/approve/{user}', [StudentApprovalController::class, 'approve'])->name('teacher.student.approve');
            Route::post('/reject/{user}', [StudentApprovalController::class, 'reject'])->name('teacher.student.reject');
            Route::post('/reset/{user}', [StudentApprovalController::class, 'reset'])->name('teacher.student.reset');
            Route::get('/{student}/answers', [StudentAnswersController::class, 'index'])->name('teacher.students.answers');
        });

        // Sections Management
        Route::prefix('sections')->group(function () {
            Route::post('/', [TeacherSectionController::class, 'store'])->name('teacher.section.store');
            Route::put('/{section}', [TeacherSectionController::class, 'update'])->name('teacher.section.update');
            Route::delete('/{section}', [TeacherSectionController::class, 'destroy'])->name('teacher.section.destroy');
            Route::get('/list', [TeacherSectionController::class, 'list'])->name('teacher.section.list');
        });

        // Feedback to students
        Route::prefix('feedback')->group(function () {
            Route::get('/', [TeacherFeedbackController::class, 'index'])->name('teacher.feedback.index');
            Route::post('/', [TeacherFeedbackController::class, 'store'])->name('teacher.feedback.store');
        });
    });
});

// ============ API ROUTES ============
// Read-only and public: used by the registration form (before login) and the
// teacher dashboard. Writes are handled exclusively by the ownership-checked
// Teacher\SectionController routes under /teacher/sections/*.
Route::get('/api/sections', [SectionController::class, 'index'])->name('api.sections');

// ============ ADMIN ROUTES ============
Route::prefix('admin')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showAdminLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'adminLogin'])->middleware('throttle:login')->name('admin.login.submit');

    // Admin accounts are provisioned directly (seeder / console), never
    // self-registered — there is deliberately no admin registration route.

    // Dashboard (Protected with role middleware)
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

        // Teacher Approvals
        Route::prefix('teachers')->group(function () {
            Route::get('/approvals', [TeacherApprovalController::class, 'index'])->name('admin.teacher-approvals');
            Route::get('/approvals-data', [TeacherApprovalController::class, 'approvalsData'])->name('admin.teacher-approvals.data');
            Route::post('/approve/{user}', [TeacherApprovalController::class, 'approve'])->name('admin.teacher.approve');
            Route::post('/reject/{user}', [TeacherApprovalController::class, 'reject'])->name('admin.teacher.reject');
            Route::post('/reset/{user}', [TeacherApprovalController::class, 'reset'])->name('admin.teacher.reset');
        });

        // User Management
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('admin.users.index');
            Route::patch('/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
            Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
        });

        // Maintenance Mode
        Route::prefix('maintenance')->group(function () {
            Route::get('/status', [MaintenanceController::class, 'status'])->name('admin.maintenance.status');
            Route::post('/toggle', [MaintenanceController::class, 'toggle'])->name('admin.maintenance.toggle');
        });

        // Platform settings key/value store (replaces the dashboard's direct
        // anon-key access to platform_settings).
        Route::get('/settings', [PlatformSettingController::class, 'index'])->name('admin.settings.index');
        Route::put('/settings', [PlatformSettingController::class, 'update'])->name('admin.settings.update');

        // Platform-wide progress rows for the Analytics tab (was a direct
        // anon-key read of student_progress).
        Route::get('/analytics/progress', [AnalyticsController::class, 'progress'])->name('admin.analytics.progress');

        // Activity Log
        Route::prefix('activity')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->name('admin.activity.index');
            Route::get('/export-data', [ActivityLogController::class, 'exportData'])->name('admin.activity.export-data');
            Route::post('/archive-old', [ActivityLogController::class, 'archiveOld'])->name('admin.activity.archive-old');
            Route::delete('/{activityLog}', [ActivityLogController::class, 'destroy'])->name('admin.activity.destroy');
            Route::post('/{activityLog}/restore', [ActivityLogController::class, 'restore'])->name('admin.activity.restore');
        });
    });
});

// ============ GOOGLE OAUTH ROUTES ============
Route::get('/auth/google/{role}', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect')
    ->middleware('throttle:auth-actions')
    ->where('role', 'student|teacher|admin');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->middleware('throttle:auth-actions')->name('auth.google.callback');
