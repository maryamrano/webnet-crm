<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase7HardeningTest extends TestCase
{
    use RefreshDatabase;

    /** Phase 7 security fix: the conversion endpoints used to reveal the
     *  conversion state/date of a lead BEFORE checking authorization. An
     *  unauthorized user must now get a plain 403 and learn nothing. */
    public function test_converted_lead_state_is_not_disclosed_to_unauthorized_users(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $lead = Lead::factory()->create([
            'assigned_to' => $owner->id, 'created_by' => $owner->id,
            'status' => 'converted', 'converted_at' => now()->setDate(2026, 9, 3),
            'converted_company_id' => null, 'converted_contact_id' => null,
        ]);

        $response = $this->actingAs($intruder)->get(route('leads.convert.show', $lead));
        $response->assertForbidden();
        $response->assertDontSee('03 Sep 2026');

        $response = $this->actingAs($intruder)->post(route('leads.convert.store', $lead), [
            'company_choice' => 'new', 'contact_choice' => 'new',
        ]);
        $response->assertForbidden();
    }

    /** The authorized owner still gets the friendly already-converted message. */
    public function test_converted_lead_message_still_works_for_authorized_users(): void
    {
        $owner = User::factory()->create();
        $lead = Lead::factory()->create([
            'assigned_to' => $owner->id, 'created_by' => $owner->id,
            'status' => 'converted', 'converted_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('leads.convert.show', $lead))
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHas('error');
    }

    /** Phase 7 performance fix: ownership/assignment and task-scan indexes exist. */
    public function test_performance_indexes_exist(): void
    {
        $leadIndexes = collect(Schema::getIndexes('leads'))->flatMap(fn ($i) => $i['columns']);
        $this->assertTrue($leadIndexes->contains('assigned_to'));
        $this->assertTrue($leadIndexes->contains('created_by'));

        $dealIndexes = collect(Schema::getIndexes('deals'))->flatMap(fn ($i) => $i['columns']);
        $this->assertTrue($dealIndexes->contains('owner_id'));

        $taskIndexes = collect(Schema::getIndexes('tasks'));
        $this->assertTrue($taskIndexes->flatMap(fn ($i) => $i['columns'])->contains('assigned_to'));
        $this->assertTrue($taskIndexes->contains(fn ($i) => $i['columns'] === ['status', 'due_at']));
        $this->assertTrue($taskIndexes->flatMap(fn ($i) => $i['columns'])->contains('reminder_at'));
    }
}
