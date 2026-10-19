<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_create_sets_creator_and_links_parent(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();
        $lead = Lead::factory()->create(['assigned_to' => $exec->id, 'created_by' => $exec->id]);

        $this->actingAs($exec)->post(route('tasks.store'), [
            'title' => 'Call the lead', 'taskable_key' => 'lead', 'taskable_id' => $lead->id,
            'assigned_to' => $exec->id, 'created_by' => $other->id, // tamper attempt
            'priority' => 'high', 'status' => 'pending', 'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $task = Task::first();
        $this->assertSame($exec->id, $task->created_by);
        $this->assertTrue($task->taskable->is($lead));
    }

    public function test_task_parent_whitelist_and_inaccessible_parent_are_rejected(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();
        $foreignLead = Lead::factory()->create(['assigned_to' => $other->id, 'created_by' => $other->id]);

        $this->actingAs($exec)->post(route('tasks.store'), [
            'title' => 'Bad key', 'taskable_key' => 'user', 'taskable_id' => $exec->id,
            'priority' => 'low', 'status' => 'pending',
        ])->assertSessionHasErrors('taskable_key');

        $this->actingAs($exec)->post(route('tasks.store'), [
            'title' => 'No access', 'taskable_key' => 'lead', 'taskable_id' => $foreignLead->id,
            'priority' => 'low', 'status' => 'pending',
        ])->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_exec_can_only_assign_tasks_to_themselves(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($exec)->post(route('tasks.store'), [
            'title' => 'Assign away', 'assigned_to' => $other->id,
            'priority' => 'low', 'status' => 'pending',
        ])->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_complete_and_reopen_set_and_clear_the_timestamp(): void
    {
        $exec = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $exec->id, 'created_by' => $exec->id, 'status' => 'pending']);

        $this->actingAs($exec)->post(route('tasks.complete', $task))->assertRedirect();
        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        $this->actingAs($exec)->post(route('tasks.reopen', $task))->assertRedirect();
        $task->refresh();
        $this->assertSame('pending', $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_overdue_and_due_soon_are_deterministic(): void
    {
        $this->travelTo(now()->setTime(12, 0));

        $exec = User::factory()->create();
        $overdue = Task::factory()->create(['assigned_to' => $exec->id, 'due_at' => now()->subHour(), 'status' => 'pending', 'title' => 'Overdue One']);
        $soon = Task::factory()->create(['assigned_to' => $exec->id, 'due_at' => now()->addHours(47), 'status' => 'pending', 'title' => 'Soon One']);
        $later = Task::factory()->create(['assigned_to' => $exec->id, 'due_at' => now()->addHours(49), 'status' => 'pending', 'title' => 'Later One']);
        $doneLate = Task::factory()->create(['assigned_to' => $exec->id, 'due_at' => now()->subDays(2), 'status' => 'completed', 'completed_at' => now(), 'title' => 'Done Late']);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($soon->isOverdue());
        $this->assertTrue($soon->isDueSoon());
        $this->assertFalse($later->isDueSoon()); // outside the 48h window
        $this->assertFalse($doneLate->isOverdue()); // completed tasks are never overdue

        $this->actingAs($exec)->get(route('tasks.index', ['due' => 'overdue']))
            ->assertSee('Overdue One')->assertDontSee('Soon One')->assertDontSee('Done Late');
        $this->actingAs($exec)->get(route('tasks.index', ['due' => 'soon']))
            ->assertSee('Soon One')->assertDontSee('Overdue One')->assertDontSee('Later One');
    }

    public function test_reminders_page_lists_due_reminders_and_overdue(): void
    {
        $this->travelTo(now()->setTime(12, 0));

        $exec = User::factory()->create();
        Task::factory()->create(['assigned_to' => $exec->id, 'title' => 'Ping Customer', 'reminder_at' => now()->subHour(), 'due_at' => now()->addDay(), 'status' => 'pending']);
        Task::factory()->create(['assigned_to' => $exec->id, 'title' => 'Missed Call Back', 'reminder_at' => null, 'due_at' => now()->subHours(3), 'status' => 'pending']);
        Task::factory()->create(['assigned_to' => $exec->id, 'title' => 'Future Reminder', 'reminder_at' => now()->addDay(), 'due_at' => now()->addDays(10), 'status' => 'pending']);

        $response = $this->actingAs($exec)->get(route('tasks.reminders'));
        $response->assertOk()
            ->assertSee('Ping Customer')
            ->assertSee('Missed Call Back')
            ->assertDontSee('Future Reminder')
            ->assertSee('no background scheduler', false); // honest documentation on the page itself
    }

    public function test_task_scoping_and_direct_url_authorization(): void
    {
        $execA = User::factory()->create();
        $execB = User::factory()->create();
        $taskB = Task::factory()->create(['assigned_to' => $execB->id, 'created_by' => $execB->id, 'title' => 'B Task']);

        $this->actingAs($execA)->get(route('tasks.index'))->assertDontSee('B Task');
        $this->actingAs($execA)->get(route('tasks.show', $taskB))->assertForbidden();
        $this->actingAs($execA)->post(route('tasks.complete', $taskB))->assertForbidden();
        $this->actingAs($execA)->delete(route('tasks.destroy', $taskB))->assertForbidden();
        $this->assertSame('pending', $taskB->fresh()->status);
    }

    public function test_company_parent_task_works_and_task_delete_roles(): void
    {
        $exec = User::factory()->create();
        $manager = User::factory()->manager()->create();
        $company = Company::factory()->create(['owner_id' => $exec->id, 'created_by' => $exec->id]);

        $this->actingAs($exec)->post(route('tasks.store'), [
            'title' => 'Visit office', 'taskable_key' => 'company', 'taskable_id' => $company->id,
            'priority' => 'medium', 'status' => 'pending',
        ])->assertRedirect();
        $task = Task::first();
        $this->assertTrue($task->taskable->is($company));

        // Assignee (not creator — same person here) fine; a different exec cannot delete.
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->delete(route('tasks.destroy', $task))->assertForbidden();

        $this->actingAs($manager)->delete(route('tasks.destroy', $task))->assertRedirect(route('tasks.index'));
        $this->assertDatabaseCount('tasks', 0);
    }
}
