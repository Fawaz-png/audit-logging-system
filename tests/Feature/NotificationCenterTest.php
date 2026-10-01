<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_notification_center_lists_owned_notifications_and_opening_marks_them_read(): void
    {
        $user = $this->createUser('notification-user');
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\FindingWorkflowNotification',
            'data' => [
                'title' => 'Finding closure request submitted',
                'message' => 'The auditee submitted resolution evidence.',
                'finding_reference' => 'FND-001',
                'finding_id' => 42,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Notifications/Index')
                ->has('notifications', 1)
                ->where('notifications.0.data.title', 'Finding closure request submitted')
            );

        $this->actingAs($user)
            ->post(route('notifications.open', $notification->id))
            ->assertRedirect(route('findings.show', 42));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = $this->createUser('notification-owner');
        $otherUser = $this->createUser('notification-other');
        $notification = $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\FindingWorkflowNotification',
            'data' => [
                'title' => 'Update',
                'message' => 'Message.',
                'finding_reference' => 'FND-002',
                'finding_id' => 43,
            ],
        ]);

        $this->actingAs($otherUser)
            ->post(route('notifications.open', $notification->id))
            ->assertNotFound();
    }

    public function test_live_summary_only_returns_the_authenticated_users_notifications(): void
    {
        $user = $this->createUser('notification-summary-owner');
        $otherUser = $this->createUser('notification-summary-other');

        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\FindingWorkflowNotification',
            'data' => [
                'title' => 'Remediation submitted',
                'message' => 'Evidence is ready for review.',
            ],
        ]);
        $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\FindingWorkflowNotification',
            'data' => ['title' => 'Private update', 'message' => 'Not for this user.'],
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.summary'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('recent.0.id', $notification->id)
            ->assertJsonMissing(['title' => 'Private update']);
    }

    private function createUser(string $slug): User
    {
        $department = Department::create([
            'name' => 'Department '.$slug,
            'code' => Str::upper(Str::random(5)),
        ]);
        $role = Role::create([
            'name' => Str::headline($slug),
            'slug' => $slug,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }
}
