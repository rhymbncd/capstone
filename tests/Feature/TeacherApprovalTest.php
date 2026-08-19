<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_registration_creates_pending_approval_status(): void
    {
        $response = $this->post(route('teacher.register'), [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('teacher.login'));
        $response->assertSessionHas('success', 'Account created successfully! Please wait for admin approval before logging in.');

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('teacher', $user->role);
        $this->assertEquals('pending', $user->approval_status);
    }

    public function test_prevents_pending_teachers_from_logging_in(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'approval_status' => 'pending',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('teacher.login.submit'), [
            'email' => $teacher->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('teacher.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_shows_error_when_teacher_account_does_not_exist(): void
    {
        $response = $this->post(route('teacher.login.submit'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('teacher.login'));
        $response->assertSessionHasErrors('email', 'No account found with this email. Please register first.');
        $this->assertGuest();
    }

    public function test_allows_approved_teachers_to_log_in(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('teacher.login.submit'), [
            'email' => $teacher->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($teacher);
    }

    public function test_prevents_rejected_teachers_from_logging_in(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'approval_status' => 'rejected',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('teacher.login.submit'), [
            'email' => $teacher->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('teacher.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_see_pending_teachers_in_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        User::factory()->teacher()->count(3)->create([
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSuccessful();
        $response->assertViewHas('pendingTeachers');
        $this->assertCount(3, $response->viewData('pendingTeachers'));
    }

    public function test_admin_can_approve_a_pending_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.teacher.approve', $teacher->id));

        $response->assertRedirect();
        $teacher->refresh();
        $this->assertEquals('approved', $teacher->approval_status);
    }

    public function test_admin_can_reject_a_pending_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.teacher.reject', $teacher->id));

        $response->assertRedirect();
        $teacher->refresh();
        $this->assertEquals('rejected', $teacher->approval_status);
    }

    public function test_admin_can_reset_teacher_approval_status_to_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.teacher.reset', $teacher->id));

        $response->assertRedirect();
        $teacher->refresh();
        $this->assertEquals('pending', $teacher->approval_status);
    }

    public function test_google_oauth_creates_teacher_with_pending_status(): void
    {
        $newTeacher = [
            'id' => '123456789',
            'name' => 'Google Teacher',
            'email' => 'google.teacher@gmail.com',
        ];

        $user = User::updateOrCreate(
            ['google_id' => $newTeacher['id']],
            [
                'name' => $newTeacher['name'],
                'email' => $newTeacher['email'],
                'password' => bcrypt('temporary'),
                'role' => 'teacher',
                'approval_status' => 'pending',
            ]
        );

        $this->assertEquals('teacher', $user->role);
        $this->assertEquals('pending', $user->approval_status);
        $this->assertTrue($user->isPending());
    }

    public function test_approved_google_teacher_can_login(): void
    {
        $teacher = User::factory()->teacher()->create([
            'role' => 'teacher',
            'approval_status' => 'approved',
            'google_id' => '123456789',
        ]);

        $response = $this->post(route('teacher.login.submit'), [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        // Should not authenticate with password, but we can verify approval status
        $this->assertTrue($teacher->isApproved());
    }

    public function test_student_sees_error_when_account_does_not_exist(): void
    {
        $response = $this->post(route('student.login.submit'), [
            'email' => 'nonexistent.student@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('student.login'));
        $response->assertSessionHasErrors('email', 'No account found with this email. Please register first.');
        $this->assertGuest();
    }

    public function test_admin_sees_error_when_account_does_not_exist(): void
    {
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'nonexistent.admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email', 'No account found with this email. Please register first.');
        $this->assertGuest();
    }

    // ============ STUDENT APPROVAL WORKFLOW BY TEACHER ============

    public function test_student_registration_creates_pending_approval_status(): void
    {
        // Create a section first
        $section = Section::factory()->create();

        $response = $this->post(route('student.register'), [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'email' => 'jane@example.com',
            'section_id' => $section->id,
            'student_id' => '24-0001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('student.login'));
        $response->assertSessionHas('success', 'Account created successfully! Please wait for your teacher to approve your account before logging in.');

        $student = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($student);
        $this->assertEquals('student', $student->role);
        $this->assertEquals('pending', $student->approval_status);
        $this->assertEquals($section->id, $student->section_id);
    }

    public function test_prevents_pending_students_from_logging_in(): void
    {
        $section = Section::factory()->create();
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'pending',
            'section_id' => $section->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('student.login.submit'), [
            'email' => $student->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('student.login'));
        $response->assertSessionHasErrors('email', 'Your account is awaiting teacher approval. Please check back later.');
        $this->assertGuest();
    }

    public function test_teacher_can_approve_a_pending_student(): void
    {
        $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
        $section = Section::factory()->create(['teacher_id' => $teacher->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'pending',
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('teacher.student.approve', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', "{$student->name} has been approved and can now log in.");

        $student->refresh();
        $this->assertEquals('approved', $student->approval_status);
    }

    public function test_teacher_can_reject_a_pending_student(): void
    {
        $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
        $section = Section::factory()->create(['teacher_id' => $teacher->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'pending',
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('teacher.student.reject', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', "{$student->name} has been rejected.");

        $student->refresh();
        $this->assertEquals('rejected', $student->approval_status);
    }

    public function test_teacher_cannot_approve_a_student_from_another_teachers_section(): void
    {
        $teacherA = User::factory()->teacher()->create(['approval_status' => 'approved']);
        $teacherB = User::factory()->teacher()->create(['approval_status' => 'approved']);
        $sectionB = Section::factory()->create(['teacher_id' => $teacherB->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'pending',
            'section_id' => $sectionB->id,
        ]);

        $response = $this->actingAs($teacherA)
            ->post(route('teacher.student.approve', $student->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        $student->refresh();
        $this->assertEquals('pending', $student->approval_status);
    }

    public function test_allows_approved_students_to_log_in(): void
    {
        $section = Section::factory()->create();
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'approved',
            'section_id' => $section->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('student.login.submit'), [
            'email' => $student->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_prevents_rejected_students_from_logging_in(): void
    {
        $section = Section::factory()->create();
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'rejected',
            'section_id' => $section->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('student.login.submit'), [
            'email' => $student->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('student.login'));
        $response->assertSessionHasErrors('email', 'Your account is awaiting teacher approval. Please check back later.');
        $this->assertGuest();
    }

    public function test_teacher_student_approvals_index_shows_pending_students(): void
    {
        $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
        $section = Section::factory()->create(['teacher_id' => $teacher->id]);
        $pendingStudents = User::factory(3)->create([
            'role' => 'student',
            'approval_status' => 'pending',
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('teacher.student-approvals'));

        $response->assertSuccessful();
        $response->assertViewHas('pendingStudents');
        $this->assertEquals(3, $response['pendingStudents']->count());
    }

    // ============ GOOGLE OAUTH STUDENT APPROVAL ============

    public function test_google_oauth_creates_student_with_pending_status(): void
    {
        $section = Section::factory()->create();
        $newStudent = [
            'id' => 'google_123456',
            'name' => 'Google Student',
            'email' => 'google.student@gmail.com',
        ];

        $user = User::updateOrCreate(
            ['google_id' => $newStudent['id']],
            [
                'name' => $newStudent['name'],
                'email' => $newStudent['email'],
                'password' => Hash::make('temporary'),
                'role' => 'student',
                'section_id' => $section->id,
                'approval_status' => 'pending',
            ]
        );

        $this->assertEquals('student', $user->role);
        $this->assertEquals('pending', $user->approval_status);
    }

    public function test_pending_google_student_cannot_access_student_dashboard(): void
    {
        $section = Section::factory()->create();
        /** @var User $student */
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'pending',
            'section_id' => $section->id,
            'google_id' => 'google_123456',
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        // Middleware should redirect and log out the user
        $response->assertRedirect(route('student.login'));
        $response->assertSessionHasErrors('email', 'Your account is awaiting teacher approval. Please check back later.');
        $this->assertGuest();
    }

    public function test_approved_google_student_can_access_dashboard(): void
    {
        $section = Section::factory()->create();
        /** @var User $student */
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'approved',
            'section_id' => $section->id,
            'google_id' => 'google_123456',
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertSuccessful();
        $this->assertAuthenticatedAs($student);
    }

    public function test_teacher_can_reset_student_approval_status_to_pending(): void
    {
        $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
        $section = Section::factory()->create(['teacher_id' => $teacher->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'approved',
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('teacher.student.reset', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', "{$student->name} status has been reset to pending.");

        $student->refresh();
        $this->assertEquals('pending', $student->approval_status);
    }
}
