<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\JwtTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_update_and_delete_their_tasks(): void
    {
        $user = User::factory()->create();
        $token = app(JwtTokenService::class)->issue($user)['token'];

        $created = $this->withToken($token)->postJson('/api/tasks', [
            'title' => 'Prepare weekly report',
            'description' => 'Collect sales figures',
            'due_date' => today()->addDays(3)->toDateString(),
        ]);
        $created->assertCreated()->assertJsonPath('data.status', 'pending');

        $taskId = $created->json('data.id');
        $this->withToken($token)->patchJson("/api/tasks/{$taskId}", [
            'status' => 'in_progress',
        ])->assertOk()->assertJsonPath('data.status', 'in_progress');

        $this->withToken($token)->deleteJson("/api/tasks/{$taskId}")->assertOk();
        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    public function test_users_cannot_read_or_change_another_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::create([
            'user_id' => $owner->id,
            'title' => 'Private task',
            'status' => 'pending',
        ]);
        $token = app(JwtTokenService::class)->issue($otherUser)['token'];

        $this->withToken($token)->getJson("/api/tasks/{$task->id}")->assertForbidden();
        $this->withToken($token)->patchJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ])->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'pending']);
    }

    public function test_web_dashboard_requires_authentication_and_shows_user_tasks(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $user = User::factory()->create();
        $user->tasks()->create(['title' => 'My private work', 'status' => 'pending']);

        $this->actingAs($user)->get('/dashboard?tab=tasks')
            ->assertOk()
            ->assertSee('My private work')
            ->assertSee('action="'.url('/tasks').'"', false);

        $task = $user->tasks()->first();
        $this->actingAs($user)->patch("/tasks/{$task->id}", [
            'title' => 'Updated task',
            'description' => 'Added details',
            'due_date' => today()->addDays(5)->toDateString(),
            'status' => 'completed',
        ])->assertRedirect('/dashboard?tab=tasks');
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated task',
            'status' => 'completed',
        ]);
    }
}
