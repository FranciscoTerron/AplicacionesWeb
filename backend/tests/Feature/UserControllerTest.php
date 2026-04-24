<?php

namespace Tests\Feature;

use App\Services\FirestoreService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    protected $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable auth middleware for testing
        $this->withoutMiddleware();

        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    public function test_can_display_users_index()
    {
        // Mock Auth for admin user
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['id' => '1', 'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'admin'],
            ],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Usuarios');
    }

    public function test_can_display_create_user_form()
    {
        // Mock Auth for admin user
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);

        $response = $this->get(route('admin.users.create'));

        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_can_store_user_with_valid_data()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'editor',
        ];

        $this->firestoreMock->method('createDocument');

        // Mock Auth for admin user
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);

        $this->firestoreMock->method('query')->willReturn([]); // For email duplicate check

        $response = $this->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Usuario creado correctamente.');
    }

    public function test_cannot_store_user_with_invalid_data()
    {
        $response = $this->postJson(route('admin.users.store'), [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
            'role' => 'invalid-role',
        ]);

        // Check for 422 status (validation failed)
        $response->assertStatus(422);
    }

    public function test_can_display_user_show()
    {
        $user = ['id' => '1', 'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'admin'];

        $this->firestoreMock->method('getDocument')->willReturn($user);

        // Mock Auth for admin user
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);

        $response = $this->get(route('admin.users.show', '1'));

        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_can_display_edit_user_form()
    {
        $user = ['id' => '1', 'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'admin'];

        $this->firestoreMock->method('getDocument')->willReturn($user);

        // Mock Auth for admin user
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);

        $response = $this->get(route('admin.users.edit', '1'));

        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_can_update_user_with_valid_data()
    {
        $user = ['id' => '1', 'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'admin'];

        $this->firestoreMock->method('getDocument')->willReturn($user);
        $this->firestoreMock->method('updateDocument');

        // Mock Auth for admin user
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($authUser);

        $this->firestoreMock->method('query')->willReturn([]); // For email duplicate check

        $updateData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => 'editor',
        ];

        $response = $this->put(route('admin.users.update', '1'), $updateData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Usuario actualizado correctamente.');
    }

    public function test_can_delete_user()
    {
        $user = ['id' => '1', 'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'admin'];

        $this->firestoreMock->method('getDocument')->willReturn($user);
        $this->firestoreMock->method('updateDocument');

        // Mock Auth for admin user (different ID than target)
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('2'); // Different ID than the user being deleted (ID 1)
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($authUser);

        $response = $this->delete(route('admin.users.destroy', '1'));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Usuario bloqueado correctamente.');
    }

    public function test_cannot_delete_self()
    {
        $user = ['id' => '1', 'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'admin', 'active' => true];
        $this->firestoreMock->method('getDocument')->willReturn($user);

        // Mock Auth for same user (ID 1)
        $authUser = \Mockery::mock();
        $authUser->role = 'admin';
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($authUser);

        // Attempt to delete own user - should NOT call updateDocument
        $this->firestoreMock->expects($this->never())
            ->method('updateDocument');

        $response = $this->delete(route('admin.users.destroy', '1'));

        // Should redirect to index with error message
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'No puedes bloquearte a ti mismo.');
    }
}
