<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProtectedPageCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_are_marked_private_and_non_cacheable(): void
    {
        $department = Department::create([
            'name' => 'Internal Audit',
            'code' => Str::upper(Str::random(5)),
        ]);
        $role = Role::create([
            'name' => 'Cache Test User',
            'slug' => 'cache-test-user',
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $response
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        $version = app(HandleInertiaRequests::class)->version(Request::create('/dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'), [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
            ])
            ->assertOk()
            ->assertJsonPath('encryptHistory', true);
    }
}
