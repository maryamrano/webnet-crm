<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PipelineStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private function leadPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Audited Lead', 'email' => 'audit@example.com', 'phone' => '03001112223',
            'company_name' => 'Audit Co', 'source' => 'website', 'status' => 'new', 'notes' => '',
        ], $overrides);
    }

    public function test_create_and_update_events_capture_actor_and_before_after(): void
    {
        $exec = User::factory()->create(['name' => 'Actor One']);

        $this->actingAs($exec)->post(route('leads.store'), $this->leadPayload(['assigned_to' => $exec->id]));
        $lead = Lead::first();

        $created = AuditLog::where('action', 'lead.created')->first();
        $this->assertNotNull($created);
        $this->assertSame($exec->id, $created->user_id);
        $this->assertSame('Actor One', $created->actor_name);
        $this->assertSame($lead->id, $created->auditable_id);
        $this->assertSame('Audited Lead', $created->changes['name']['after']);

        $this->actingAs($exec)->put(route('leads.update', $lead), $this->leadPayload(['status' => 'contacted', 'assigned_to' => $exec->id]));
        $updated = AuditLog::where('action', 'lead.updated')->first();
        $this->assertNotNull($updated);
        $this->assertSame('new', $updated->changes['status']['before']);
        $this->assertSame('contacted', $updated->changes['status']['after']);
        // Exactly one entry per logical action:
        $this->assertSame(1, AuditLog::where('action', 'lead.created')->count());
        $this->assertSame(1, AuditLog::where('action', 'lead.updated')->count());
    }

    public function test_delete_event_keeps_a_snapshot_after_the_record_is_gone(): void
    {
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create(['name' => 'Doomed Lead', 'assigned_to' => $manager->id]);

        $this->actingAs($manager)->delete(route('leads.destroy', $lead));

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
        $log = AuditLog::where('action', 'lead.deleted')->first();
        $this->assertNotNull($log);
        $this->assertSame('Doomed Lead', $log->changes['name']['before']);
        $this->assertSame('Doomed Lead', $log->entityLabel());
    }

    public function test_conversion_writes_exactly_one_event_inside_the_transaction(): void
    {
        $this->seed(PipelineStageSeeder::class);
        $exec = User::factory()->create();
        $lead = Lead::factory()->create([
            'name' => 'Convert Audit', 'email' => 'ca@example.com', 'company_name' => 'Fresh Co',
            'status' => 'qualified', 'assigned_to' => $exec->id, 'created_by' => $exec->id,
        ]);

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), ['company_choice' => 'new', 'contact_choice' => 'new']);

        $events = AuditLog::where('action', 'lead.converted')->where('auditable_id', $lead->id)->get();
        $this->assertCount(1, $events);
        $this->assertTrue($events->first()->metadata['company_created']);
        $this->assertSame('converted', $events->first()->changes['status']['after']);
    }

    public function test_rolled_back_conversion_leaves_no_audit_event(): void
    {
        $exec = User::factory()->create();
        $companyB = Company::factory()->create(['name' => 'Beta Co', 'owner_id' => $exec->id, 'created_by' => $exec->id]);
        $contactB = Contact::factory()->create(['company_id' => $companyB->id, 'email' => 'rb@example.com']);
        $lead = Lead::factory()->create([
            'name' => 'Rollback Lead', 'email' => 'rb@example.com', 'company_name' => 'Gamma Co',
            'status' => 'qualified', 'assigned_to' => $exec->id, 'created_by' => $exec->id,
        ]);

        // New company + Beta's contact => mismatch inside the transaction => rollback.
        $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
            'company_choice' => 'new', 'contact_choice' => (string) $contactB->id,
        ])->assertSessionHas('error');

        $this->assertSame(0, AuditLog::where('action', 'lead.converted')->count());
        $this->assertFalse($lead->fresh()->isConverted());
    }

    public function test_deal_stage_change_and_task_completion_events(): void
    {
        $this->seed(PipelineStageSeeder::class);
        $stages = PipelineStage::orderBy('position')->get()->keyBy('name');
        $manager = User::factory()->manager()->create();
        $deal = Deal::factory()->create(['stage_id' => $stages['Qualification']->id, 'owner_id' => $manager->id]);

        $this->actingAs($manager)->patch(route('deals.stage', $deal), ['stage_id' => $stages['Negotiation']->id]);
        $log = AuditLog::where('action', 'deal.stage_changed')->first();
        $this->assertNotNull($log);
        $this->assertSame('Qualification', $log->metadata['stage_before']);
        $this->assertSame('Negotiation', $log->metadata['stage_after']);
        $this->assertSame(1, AuditLog::where('action', 'deal.stage_changed')->count());
        $this->assertSame(0, AuditLog::where('action', 'deal.updated')->count()); // no duplicate generic entry

        $task = Task::factory()->create(['assigned_to' => $manager->id, 'created_by' => $manager->id, 'status' => 'pending']);
        $this->actingAs($manager)->post(route('tasks.complete', $task));
        $this->actingAs($manager)->post(route('tasks.reopen', $task));
        $this->assertSame(1, AuditLog::where('action', 'task.completed')->count());
        $this->assertSame(1, AuditLog::where('action', 'task.reopened')->count());
        $completed = AuditLog::where('action', 'task.completed')->first();
        $this->assertSame('completed', $completed->changes['status']['after']);
    }

    public function test_user_management_events_never_contain_secrets(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Person', 'email' => 'newperson@webnetcrm.test',
            'role' => 'sales_executive', 'password' => 'super-secret-123', 'password_confirmation' => 'super-secret-123',
        ])->assertRedirect(route('admin.users.index'));

        $created = AuditLog::where('action', 'user.created')->first();
        $this->assertNotNull($created);
        $this->assertSame('sales_executive', $created->changes['role']['after']);

        $newUser = User::where('email', 'newperson@webnetcrm.test')->first();
        $this->actingAs($admin)->put(route('admin.users.update', $newUser), [
            'name' => 'New Person', 'email' => 'newperson@webnetcrm.test', 'role' => 'manager',
            'password' => 'another-secret-456', 'password_confirmation' => 'another-secret-456',
        ]);

        $updated = AuditLog::where('action', 'user.updated')->first();
        $this->assertSame('sales_executive', $updated->changes['role']['before']);
        $this->assertSame('manager', $updated->changes['role']['after']);
        $this->assertTrue($updated->metadata['password_changed']);

        // No secret material anywhere in any audit payload:
        $everything = AuditLog::all()->map(fn ($l) => json_encode([$l->changes, $l->metadata]))->implode(' ');
        $this->assertStringNotContainsString('super-secret-123', $everything);
        $this->assertStringNotContainsString('another-secret-456', $everything);
        $this->assertStringNotContainsString($newUser->password, $everything); // the stored hash
        $this->assertStringNotContainsString('password', json_encode(AuditLog::where('action', 'user.created')->first()->changes));
    }

    public function test_audit_viewer_is_admin_only(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $exec = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($exec)->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_the_audit_viewer(): void
    {
        $this->get(route('admin.audit-logs.index'))->assertRedirect('/login');
    }

    public function test_audit_viewer_filters_and_pagination(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Boss Admin']);
        $exec = User::factory()->create(['name' => 'Field Exec']);

        $this->actingAs($exec)->post(route('leads.store'), $this->leadPayload(['assigned_to' => $exec->id]));
        for ($i = 0; $i < 22; $i++) {
            $this->actingAs($admin)->post(route('companies.store'), ['name' => "Filter Co {$i}", 'owner_id' => $admin->id]);
        }

        $all = $this->actingAs($admin)->get(route('admin.audit-logs.index'));
        $all->assertOk();
        $this->assertSame(23, $all->viewData('logs')->total());
        $this->assertCount(20, $all->viewData('logs')->items()); // 20 per page

        $byActor = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['actor_id' => $exec->id]));
        $this->assertSame(1, $byActor->viewData('logs')->total());

        $byAction = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['action' => 'company.created']));
        $this->assertSame(22, $byAction->viewData('logs')->total());

        $byEntity = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['entity' => Lead::class]));
        $this->assertSame(1, $byEntity->viewData('logs')->total());
    }

    public function test_deleted_actor_still_displays_via_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        $exec = User::factory()->create(['name' => 'Soon Deleted']);
        $this->actingAs($exec)->post(route('leads.store'), $this->leadPayload(['assigned_to' => $exec->id]));

        // Removing the actor's account must not break or blank the history.
        $exec->delete();

        $log = AuditLog::where('action', 'lead.created')->first();
        $this->assertNull($log->user_id); // FK nullOnDelete
        $this->assertSame('Soon Deleted', $log->actorDisplayName());

        $this->actingAs($admin)->get(route('admin.audit-logs.index'))
            ->assertOk()->assertSee('Soon Deleted')->assertSee('account deleted');
    }

    public function test_audit_entries_cannot_be_modified_through_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('leads.store'), $this->leadPayload(['assigned_to' => $admin->id]));
        $log = AuditLog::first();

        // No update/delete endpoints exist for audit entries at all:
        $this->actingAs($admin)->put("/admin/audit-logs/{$log->id}", [])->assertNotFound();
        $this->actingAs($admin)->delete("/admin/audit-logs/{$log->id}")->assertNotFound();

        // And the model itself refuses mutation at the application layer:
        $log->action = 'tampered';
        $this->assertFalse($log->save());
        $this->assertSame(1, AuditLog::count());
    }
}
