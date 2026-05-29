<?php

namespace Arielcerda\Beacon\Tests\Feature;

use Arielcerda\Beacon\Tests\TestCase;
use Mockery;
use Statamic\Auth\User;
use Statamic\Facades\User as UserFacade;

class PermissionsTest extends TestCase
{
    public function test_user_with_view_permission_can_access_dashboard(): void
    {
        $user = $this->actingAsUserWithPermissions(['view beacon']);

        $response = $this->get(route('beacon.dashboard'));

        $response->assertStatus(200);
    }

    public function test_user_with_view_permission_cannot_post_to_notifications_endpoint(): void
    {
        $this->actingAsUserWithPermissions(['view beacon']);

        $response = $this->postJson(route('beacon.notifications.store'), [
            'type' => 'announcement',
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'frequency' => 'session',
            'payload' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_user_with_edit_permission_can_post_to_notifications_endpoint(): void
    {
        $this->actingAsUserWithPermissions(['edit beacon']);

        $response = $this->postJson(route('beacon.notifications.store'), [
            'type' => 'announcement',
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'frequency' => 'session',
            'payload' => ['message' => 'Test'],
        ]);

        $response->assertStatus(201);
    }

    public function test_user_without_any_permission_cannot_access_dashboard(): void
    {
        $this->actingAsUserWithPermissions([]);

        $response = $this->get(route('beacon.dashboard'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_all_endpoints(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('beacon.dashboard'));
        $response->assertStatus(200);

        $response = $this->get(route('beacon.settings'));
        $response->assertStatus(200);
    }

    private function actingAsUserWithPermissions(array $permissions): mixed
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($perm) => in_array($perm, $permissions));
        $user->shouldReceive('isSuper')->andReturn(false);

        UserFacade::shouldReceive('current')->andReturn($user);

        $this->actingAs($user);

        return $user;
    }

    private function actingAsSuperAdmin(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isSuper')->andReturn(true);
        $user->shouldReceive('hasPermission')->andReturn(true);

        UserFacade::shouldReceive('current')->andReturn($user);

        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
