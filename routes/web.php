<?php

use App\Http\Controllers\Admin\TeacherApprovalController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
// Controllers
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\Student\FeedbackController as StudentFeedbackController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\SupabaseController;
use App\Http\Controllers\Teacher\FeedbackController as TeacherFeedbackController;
use App\Http\Controllers\Teacher\SectionController as TeacherSectionController;
use App\Http\Controllers\Teacher\StudentAnswersController;
use App\Http\Controllers\Teacher\StudentApprovalController;
use App\Http\Controllers\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

// Gawin itong ganito sa web.php
Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask');
/* ----------- Homepage ----------- */
Route::get('/', function () {
    return view('dashboard.homepage');
})->name('homepage');

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
    Route::post('/login', [AuthController::class, 'studentLogin'])->name('student.login.submit');

    // Register
    Route::get('/register', [AuthController::class, 'showStudentRegisterForm'])->name('student.register.form');
    Route::post('/register', [AuthController::class, 'studentRegister'])->name('student.register');

    // Password reset (emailed to the student's own inbox)
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('student.password.request')->defaults('portalType', 'student');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('student.password.email')->defaults('portalType', 'student');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('student.password.reset')->defaults('portalType', 'student');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('student.password.update')->defaults('portalType', 'student');

    // Google signup completion
    Route::get('/complete-signup', [AuthController::class, 'showGoogleSignupCompletion'])->name('student.complete-google-signup');
    Route::post('/complete-signup', [AuthController::class, 'completeGoogleSignup'])->name('student.complete-google-signup.submit');

    // Dashboard (Protected with student middleware - checks role and approval status)
    Route::middleware(['auth', 'student'])->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
        Route::get('/modules', function () {
            return view('dashboard.module');
        })->name('student.modules');
        Route::post('/logout', [AuthController::class, 'logout'])->name('student.logout');

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
    Route::post('/login', [AuthController::class, 'teacherLogin'])->name('teacher.login.submit');

    // Register
    Route::get('/register', [AuthController::class, 'showTeacherRegisterForm'])->name('teacher.register.form');
    Route::post('/register', [AuthController::class, 'teacherRegister'])->name('teacher.register');

    // Password reset (emailed to the teacher's own inbox)
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('teacher.password.request')->defaults('portalType', 'teacher');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('teacher.password.email')->defaults('portalType', 'teacher');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('teacher.password.reset')->defaults('portalType', 'teacher');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('teacher.password.update')->defaults('portalType', 'teacher');

    // Dashboard (Protected with role middleware)
    Route::middleware(['auth', 'role:teacher'])->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('teacher.logout');
        Route::post('/generate-quiz', [QuizController::class, 'generate'])->name('quiz.generate');
        Route::post('/quiz/generate-text', [QuizController::class, 'generateText'])->name('quiz.generate-text');

        // Student Approvals
        Route::prefix('students')->group(function () {
            Route::get('/', [StudentController::class, 'getTeacherStudents'])->name('teacher.students.index');
            Route::get('/approvals', [StudentApprovalController::class, 'index'])->name('teacher.student-approvals');
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
Route::get('/api/test', function () {
    return response()->json(['message' => 'API routing works']);
})->name('api.test');

// Supabase connection test route
Route::get('/supabase-test', [SupabaseController::class, 'test'])->name('supabase.test');

// ============ ADMIN ROUTES ============
Route::prefix('admin')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showAdminLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'adminLogin'])->name('admin.login.submit');

    // Register
    Route::get('/register', [AuthController::class, 'showAdminRegisterForm'])->name('admin.register.form');
    Route::post('/register', [AuthController::class, 'adminRegister'])->name('admin.register');

    // Dashboard (Protected with role middleware)
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

        // Teacher Approvals
        Route::prefix('teachers')->group(function () {
            Route::get('/approvals', [TeacherApprovalController::class, 'index'])->name('admin.teacher-approvals');
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
    });
});

// ============ GOOGLE OAUTH ROUTES ============
Route::get('/auth/google/{role}', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect')
    ->where('role', 'student|teacher|admin');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
