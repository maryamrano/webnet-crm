<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'phone' => '0300 1112223',
            'company_name' => 'Example Traders',
            'source' => 'website',
            'status' => 'new',
            'notes' => 'note',
        ], $overrides);
    }

    public function test_sales_executive_can_create_lead_and_creator_is_set_server_side(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($exec)->post(route('leads.store'), $this->payload([
            'assigned_to' => $exec->id,
            'created_by' => $other->id, // tampering attempt — must be ignored
        ]))->assertRedirect();

        $lead = Lead::first();
        $this->assertSame($exec->id, $lead->created_by);
        $this->assertSame($exec->id, $lead->assigned_to);
    }

    public function test_sales_executive_cannot_assign_lead_to_someone_else(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($exec)->post(route('leads.store'), $this->payload(['assigned_to' => $other->id]))
            ->assertSessionHasErrors('assigned_to');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_validation_rejects_bad_source_email_and_converted_status(): void
    {
        $exec = User::factory()->create();

        $this->actingAs($exec)->post(route('leads.store'), $this->payload(['source' => 'tiktok']))
            ->assertSessionHasErrors('source');
        $this->actingAs($exec)->post(route('leads.store'), $this->payload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
        $this->actingAs($exec)->post(route('leads.store'), $this->payload(['status' => 'converted']))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_list_is_scoped_for_sales_executive_but_open_for_manager(): void
    {
        $execA = User::factory()->create(['name' => 'Exec A']);
        $execB = User::factory()->create(['name' => 'Exec B']);
        $manager = User::factory()->manager()->create();

        $mine = Lead::factory()->create(['assigned_to' => $execA->id, 'created_by' => $execA->id, 'name' => 'Alpha Lead']);
        $theirs = Lead::factory()->create(['assigned_to' => $execB->id, 'created_by' => $execB->id, 'name' => 'Beta Lead']);

        $this->actingAs($execA)->get(route('leads.index'))
            ->assertOk()->assertSee('Alpha Lead')->assertDontSee('Beta Lead');

        $this->actingAs($manager)->get(route('leads.index'))
            ->assertOk()->assertSee('Alpha Lead')->assertSee('Beta Lead');

        // Direct URL access is enforced too, not just list scoping.
        $this->actingAs($execA)->get(route('leads.show', $theirs))->assertForbidden();
        $this->actingAs($execA)->get(route('leads.edit', $theirs))->assertForbidden();
        $this->actingAs($execA)->put(route('leads.update', $theirs), $this->payload())->assertForbidden();
        $this->actingAs($execA)->get(route('leads.show', $mine))->assertOk();
    }

    public function test_sales_executive_cannot_delete_but_manager_can(): void
    {
        $exec = User::factory()->create();
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create(['assigned_to' => $exec->id, 'created_by' => $exec->id]);

        $this->actingAs($exec)->delete(route('leads.destroy', $lead))->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);

        $this->actingAs($manager)->delete(route('leads.destroy', $lead))->assertRedirect(route('leads.index'));
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_search_and_filters(): void
    {
        $manager = User::factory()->manager()->create();
        Lead::factory()->create(['name' => 'Karachi Prospect', 'source' => 'website', 'status' => 'new']);
        Lead::factory()->create(['name' => 'Lahore Prospect', 'source' => 'referral', 'status' => 'qualified']);

        $this->actingAs($manager)->get(route('leads.index', ['q' => 'Karachi']))
            ->assertSee('Karachi Prospect')->assertDontSee('Lahore Prospect');
        $this->actingAs($manager)->get(route('leads.index', ['source' => 'referral']))
            ->assertDontSee('Karachi Prospect')->assertSee('Lahore Prospect');
        $this->actingAs($manager)->get(route('leads.index', ['status' => 'qualified']))
            ->assertDontSee('Karachi Prospect')->assertSee('Lahore Prospect');
    }

    public function test_pagination_shows_twelve_per_page(): void
    {
        $manager = User::factory()->manager()->create();
        Lead::factory()->count(15)->create(['assigned_to' => $manager->id]);

        $response = $this->actingAs($manager)->get(route('leads.index'));
        $response->assertOk();
        $this->assertSame(15, $response->viewData('leads')->total());
        $this->assertCount(12, $response->viewData('leads')->items());
    }

    public function test_duplicate_warning_appears_for_matching_contact_email(): void
    {
        $exec = User::factory()->create();
        $ownCompany = \App\Models\Company::factory()->create(['owner_id' => $exec->id, 'created_by' => $exec->id]);
        Contact::factory()->create(['company_id' => $ownCompany->id, 'email' => 'dup@example.com', 'name' => 'Existing Person']);

        $this->actingAs($exec)->post(route('leads.store'), $this->payload([
            'email' => 'DUP@example.com', 'assigned_to' => $exec->id,
        ]));

        $lead = Lead::first();
        $this->actingAs($exec)->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Possible duplicates')
            ->assertSee('Existing Person');
        // The warning never merged anything: the contact still stands alone.
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_guest_cannot_access_leads(): void
    {
        $this->get(route('leads.index'))->assertRedirect('/login');
    }
}
