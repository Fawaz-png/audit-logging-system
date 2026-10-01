<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_can_update_their_profile_and_persist_preferences(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('profile.email', $user->email)
            );

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Amina Okafor',
                'preferences' => [
                    'timezone' => 'Africa/Lagos',
                    'live_notification_alerts' => false,
                ],
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Amina Okafor', $user->fresh()->name);
        $this->assertSame('Africa/Lagos', $user->fresh()->preferences['timezone']);
        $this->assertFalse($user->fresh()->preferences['live_notification_alerts']);
    }

    public function test_profile_preferences_reject_an_invalid_timezone(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'preferences' => [
                    'timezone' => 'Not/A_Timezone',
                    'live_notification_alerts' => true,
                ],
            ])
            ->assertSessionHasErrors('preferences.timezone');
    }

    private function createUser(): User
    {
        $department = Department::create([
            'name' => 'Retail Banking',
            'code' => Str::upper(Str::random(5)),
        ]);
        $role = Role::create([
            'name' => 'Team Member',
            'slug' => 'profile-test-user',
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }
}
