<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ============ STUDENT ============
    public function showStudentLoginForm()
    {
        return view('login.signin', ['portalType' => 'student']);
    }

    public function showStudentRegisterForm()
    {
        $sections = Section::all();

        return view('login.signup', ['sections' => $sections, 'portalType' => 'student']);
    }

    public function studentLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $userExists = User::where('email', $request->email)->exists();
        if (! $userExists) {
            return redirect()->route('student.login')
                ->withErrors(['email' => 'No account found with this email. Please register first.'])
                ->onlyInput('email');
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            if ($user->role !== 'student') {
                Auth::logout();

                return redirect()->route('student.login')->withErrors([
                    'email' => "This account is registered as a {$user->role}. Please use the {$user->role} login portal.",
                ]);
            }

            if ($user->approval_status !== 'approved') {
                Auth::logout();

                return redirect()->route('student.login')->withErrors([
                    'email' => 'Your account is awaiting teacher approval. Please check back later.',
                ]);
            }

            $request->session()->regenerate();

            ActivityLog::record('login', 'Student Logged In', "{$user->name} ({$user->email})", user: $user);

            return redirect()->route('student.dashboard');
        }

        return redirect()->route('student.login')
            ->withErrors(['email' => 'Invalid email or password.'])
            ->onlyInput('email');
    }

    public function studentRegister(Request $request)
    {
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'section_id' => 'required|exists:sections,id',
            'student_id' => 'required|string|max:50',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $fullName = $validated['firstName'].' '.$validated['lastName'];

        $newStudent = User::create([
            'name' => $fullName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'section_id' => $validated['section_id'],
            'student_id' => $validated['student_id'],
            'approval_status' => 'pending',
        ]);

        ActivityLog::record('registration', 'New Student Registered', "{$fullName} ({$validated['email']}) signed up", user: $newStudent);

        // Do NOT auto-login
        return redirect()->route('student.login')
            ->with('success', 'Account created successfully! Please wait for your teacher to approve your account before logging in.');
    }

    /**
     * Show form for completing Google signup (section selection).
     */
    public function showGoogleSignupCompletion()
    {
        $userId = session('google_user_id');
        if (! $userId) {
            return redirect()->route('student.login')->withErrors(['error' => 'Invalid session.']);
        }

        $user = User::find($userId);
        if (! $user || $user->section_id !== null) {
            return redirect()->route('student.login');
        }

        $sections = Section::all();

        return view('login.google-signup-completion', ['sections' => $sections, 'user' => $user]);
    }

    /**
     * Complete Google signup by setting section.
     */
    public function completeGoogleSignup(Request $request)
    {
        $userId = session('google_user_id');
        if (! $userId) {
            return redirect()->route('student.login')->withErrors(['error' => 'Invalid session.']);
        }

        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'student_id' => 'required|string|max:50',
        ]);

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('student.login')->withErrors(['error' => 'User not found.']);
        }

        // Update user with section and student ID
        $user->update([
            'section_id' => $validated['section_id'],
            'student_id' => $validated['student_id'],
            'approval_status' => 'pending', // Mark for teacher approval
        ]);

        // Clear session
        Session::forget('google_user_id');

        return redirect()->route('student.login')
            ->with('success', 'Account completed! Please wait for your teacher to approve your account before logging in.');
    }

    // ============ TEACHER ============
    public function showTeacherLoginForm()
    {
        return view('login.signin', ['portalType' => 'teacher']);
    }

    public function showTeacherRegisterForm()
    {
        return view('login.signup', ['portalType' => 'teacher']);
    }

    public function teacherLogin(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $userExists = User::where('email', $request->email)->exists();
        if (! $userExists) {
            return redirect()->route('teacher.login')
                ->withErrors(['email' => 'No account found with this email. Please register first.'])
                ->onlyInput('email');
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->role !== 'teacher') {
                Auth::logout();

                return redirect()->route('teacher.login')->withErrors([
                    'email' => "This account is registered as a {$user->role}. Please use the {$user->role} login portal.",
                ]);
            }

            if ($user->approval_status !== 'approved') {
                Auth::logout();

                return redirect()->route('teacher.login')->withErrors([
                    'email' => 'Your account is awaiting approval from an administrator. Please contact support.',
                ]);
            }

            $request->session()->regenerate();

            ActivityLog::record('login', 'Teacher Logged In', "{$user->name} ({$user->email})", user: $user);

            return redirect()->route('teacher.dashboard');
        }

        return redirect()->route('teacher.login')
            ->withErrors(['email' => 'Invalid email or password.'])
            ->onlyInput('email');
    }

    public function teacherRegister(Request $request)
    {
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $fullName = $validated['firstName'].' '.$validated['lastName'];

        $newTeacher = User::create([
            'name' => $fullName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
            'approval_status' => 'pending',
        ]);

        ActivityLog::record('registration', 'New Teacher Registered', "{$fullName} ({$validated['email']}) signed up", user: $newTeacher);

        return redirect()->route('teacher.login')
            ->with('success', 'Account created successfully! Please wait for admin approval before logging in.');
    }

    // ============ ADMIN ============
    public function showAdminLoginForm()
    {
        return view('login.signin', ['portalType' => 'admin']);
    }

    public function showAdminRegisterForm()
    {
        return view('login.signup', ['portalType' => 'admin']);
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $userExists = User::where('email', $request->email)->exists();
        if (! $userExists) {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'No account found with this email. Please register first.'])
                ->onlyInput('email');
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                Auth::logout();

                return redirect()->route('admin.login')->withErrors([
                    'email' => "This account is registered as a {$user->role}. Please use the {$user->role} login portal.",
                ]);
            }

            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('admin.login')
            ->withErrors(['email' => 'Invalid email or password.'])
            ->onlyInput('email');
    }

    public function adminRegister(Request $request)
    {
        // List of approved admin emails
        $approvedEmails = [
            'tardio@gmail.com',
            'carman@gmail.com',
            'villamor@gmail.com',
            'tamayuza@gmail.com',
            'embanecido@gmail.com',
        ];

        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                function ($attribute, $value, $fail) use ($approvedEmails) {
                    if (! in_array($value, $approvedEmails)) {
                        $fail('This email is not authorized to register as admin.');
                    }
                },
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $fullName = $validated['firstName'].' '.$validated['lastName'];

        User::create([
            'name' => $fullName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        return redirect()->route('admin.login')
            ->with('success', 'Account created successfully! Please log in.');
    }

    // ============ LOGOUT ============
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('homepage');
    }

    // ============ GOOGLE OAUTH ============
    public function redirectToGoogle(string $role)
    {
        // Validate role
        if (! in_array($role, ['student', 'teacher', 'admin'])) {
            return redirect()->route('homepage')->withErrors(['error' => 'Invalid role.']);
        }

        // Store the role in session for later use
        session(['oauth_role' => $role]);

        // Check if using mock mode for testing
        if (config('services.google.mode') === 'mock') {
            return $this->mockGoogleLogin();
        }

        $redirect = Socialite::driver('google')->redirect();
        $url = $redirect->getTargetUrl();
        $separator = strpos($url, '?') ? '&' : '?';

        return redirect($url.$separator.'prompt=select_account');
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('student.login')
                ->withErrors(['error' => 'Failed to authenticate with Google.'])
                ->with('notification_error', 'Failed to authenticate with Google.');
        }

        if (! $googleUser) {
            return redirect()->route('student.login')
                ->withErrors(['error' => 'Failed to retrieve Google user data.'])
                ->with('notification_error', 'Failed to retrieve Google user data.');
        }

        // Get the stored role from session
        $role = session('oauth_role') ?? 'student';

        // Validate admin email authorization
        if ($role === 'admin') {
            $approvedEmails = [
                'tardio@gmail.com',
                'carman@gmail.com',
                'villamor@gmail.com',
                'tamayuza@gmail.com',
                'embanecido@gmail.com',
            ];

            if (! in_array($googleUser->getEmail(), $approvedEmails)) {
                return redirect()->route('admin.login')
                    ->withErrors(['error' => 'This email is not authorized to register as admin.'])
                    ->with('notification_error', 'This email is not authorized to register as admin.');
            }
        }

        // Find an existing account: first one already linked to this Google
        // ID, otherwise one registered with the same email via the normal
        // signup form (so "Continue with Google" links it instead of
        // crashing on the email's unique constraint).
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        $isNewUser = $user === null;

        if ($isNewUser) {
            $user = User::create([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName() ?? 'Google User',
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(uniqid()),
                'role' => $role,
                'approval_status' => $role !== 'admin' ? 'pending' : 'approved',
            ]);

            ActivityLog::record('registration', 'New '.ucfirst($role).' Registered (Google)', "{$user->name} ({$user->email}) signed up via Google", user: $user);
        } elseif ($user->google_id === null) {
            // Existing password-based account signing in with Google for the
            // first time — link the accounts. Never touch role/approval_status
            // here: doing so on every login would silently re-approve an
            // account a teacher/admin had since rejected or reset to pending.
            $user->google_id = $googleUser->getId();
            $user->save();
        }

        if (! ($user instanceof User)) {
            return redirect()->route('student.login')->withErrors(['error' => 'Failed to create or retrieve user account.']);
        }

        // Check if role matches (for existing users who logged in with different role)
        if ($user->role !== $role) {
            return redirect()->route($role.'.login')
                ->withErrors(['email' => "This account is registered as a {$user->role}. Please use the {$user->role} login portal."]);
        }

        // Check if teacher is approved
        if ($user->role === 'teacher' && $user->approval_status !== 'approved') {
            Auth::logout();

            return redirect()->route('teacher.login')
                ->withErrors(['email' => 'Your account is awaiting admin approval. Please check back later.']);
        }

        // Check if student needs to complete section selection (Google sign-up)
        if ($user->role === 'student' && $user->section_id === null) {
            // Store user ID in session and redirect to section selection
            session(['google_user_id' => $user->id, 'oauth_role' => 'student']);

            return redirect()->route('student.complete-google-signup');
        }

        // Check if student is approved
        if ($user->role === 'student' && $user->approval_status !== 'approved') {
            Auth::logout();

            return redirect()->route('student.login')
                ->withErrors(['email' => 'Your account is awaiting teacher approval. Please check back later.']);
        }

        // Log the user in
        Auth::login($user);

        ActivityLog::record('login', ucfirst($role).' Logged In (Google)', "{$user->name} ({$user->email})", user: $user);

        // Clear session only after successful authentication
        Session::forget('oauth_role');

        // Redirect to appropriate dashboard
        return redirect()->route($role.'.dashboard');
    }

    // Mock Google Login for Testing
    private function mockGoogleLogin()
    {
        $testEmails = [
            'student' => 'test.student@gmail.com',
            'teacher' => 'test.teacher@gmail.com',
            'admin' => 'tardio@gmail.com',
        ];

        $role = session('oauth_role') ?? 'student';
        $email = $testEmails[$role] ?? 'test.user@gmail.com';

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user !== null) {
            // User exists, check if role matches
            if ($user->role !== $role) {
                return redirect()->route($role.'.login')
                    ->withErrors(['email' => "This account is registered as a {$user->role}. Please use the {$user->role} login portal."]);
            }
        } else {
            // Create new test user - teachers start with pending status
            $approvalStatus = $role === 'teacher' ? 'pending' : 'approved';
            $user = User::create([
                'name' => ucfirst($role).' Test User',
                'email' => $email,
                'password' => Hash::make('test12345'),
                'role' => $role,
                'approval_status' => $approvalStatus,
            ]);
        }
        assert($user instanceof User, 'User must exist or be created');

        // Check if teacher is approved
        if ($user->role === 'teacher' && $user->approval_status !== 'approved') {
            return redirect()->route('teacher.login')
                ->withErrors(['email' => 'Your account is awaiting admin approval. Please check back later.']);
        }

        // Log the user in
        Auth::login($user);

        // Show success message
        session(['google_auth_success' => true, 'google_auth_email' => $email]);

        // Redirect to appropriate dashboard
        return redirect()->route($role.'.dashboard');
    }
}
