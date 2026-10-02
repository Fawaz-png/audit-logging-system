<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_idle_and_absolute_timeouts_end_authenticated_sessions(): void
    {
        $user = $this->createUser();
        $now = now()->timestamp;

        $this->assertTrue($user->active);
        $this->assertSame(1, (int) $user->session_version);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->withSession([
            'auth_session_started_at' => $now - (13 * 60 * 60),
            'auth_session_last_activity_at' => $now,
            'auth_session_version' => 1,
        ])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->actingAs($user)->withSession([
            'auth_session_started_at' => $now,
            'auth_session_last_activity_at' => $now - ((config('auth.session.idle_timeout') + 1) * 60),
            'auth_session_version' => 1,
        ])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_revoking_other_sessions_keeps_current_session_and_rejects_old_versions(): void
    {
        $user = $this->createUser();
        $now = now()->timestamp;

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->post(route('profile.sessions.revoke-others'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success')
            ->assertSessionHas('auth_session_version', 2);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'session_version' => 2,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'OTHER_SESSIONS_REVOKED',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user->fresh())
            ->withSession([
                'auth_session_started_at' => $now,
                'auth_session_last_activity_at' => $now,
                'auth_session_version' => 1,
            ])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    private function createUser(): User
    {
        $department = Department::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Session test unit',
            'code' => Str::upper(Str::random(5)),
        ]);

        return User::factory()
            ->forRole('session-security-test')
            ->inDepartment($department)
            ->active()
            ->create();
    }
}
