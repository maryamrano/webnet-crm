<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8 privacy fix: duplicate matching (warnings + conversion candidates)
 * previously searched the WHOLE database and displayed names of records the
 * acting user cannot access. Matching is now scoped to the actor's
 * visibility. Admins/Managers (organization-wide visibility) are unaffected.
 */
class PrivacyScopingTest extends TestCase
{
    use RefreshDatabase;

    private function twoTeams(): array
    {
        $execA = User::factory()->create(['name' => 'Exec A']);
        $execB = User::factory()->create(['name' => 'Exec B']);
        $companyB = Company::factory()->create(['name' => 'Beta Secret Co', 'owner_id' => $execB->id, 'created_by' => $execB->id]);
        $contactB = Contact::factory()->create([
            'company_id' => $companyB->id, 'name' => 'Zoya Hidden',
            'email' => 'shared@example.com', 'phone' => '0300 1112233',
        ]);

        return [$execA, $execB, $companyB, $contactB];
    }

    public function test_lead_duplicate_warnings_do_not_leak_other_teams_records(): void
    {
        [$execA] = $this->twoTeams();
        $lead = Lead::factory()->create([
            'assigned_to' => $execA->id, 'created_by' => $execA->id,
            'email' => 'shared@example.com', 'phone' => '03001112233', 'company_name' => 'Beta Secret Co',
        ]);

        $response = $this->actingAs($execA)->get(route('leads.show', $lead));
        $response->assertOk();
        // (The lead's OWN company_name legitimately appears on its page; what
        // must not appear is the other team's contact or any warning section.)
        $response->assertDontSee('Zoya Hidden');
        $response->assertDontSee('Possible duplicates');
    }

    public function test_manager_still_sees_organization_wide_duplicate_warnings(): void
    {
        $this->twoTeams();
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create([
            'assigned_to' => $manager->id, 'created_by' => $manager->id,
            'email' => 'shared@example.com', 'company_name' => 'Beta Secret Co',
        ]);

        $response = $this->actingAs($manager)->get(route('leads.show', $lead));
        $response->assertSee('Zoya Hidden');
        $response->assertSee('Beta Secret Co');
    }

    public function test_conversion_candidates_do_not_offer_invisible_records(): void
    {
        [$execA, , $companyB] = $this->twoTeams();
        $lead = Lead::factory()->create([
            'assigned_to' => $execA->id, 'created_by' => $execA->id,
            'email' => 'shared@example.com', 'company_name' => 'Beta Secret Co',
        ]);

        // The other team's company is not offered on the conversion screen.
        $this->actingAs($execA)->get(route('leads.convert.show', $lead))
            ->assertOk()->assertDontSee('Zoya Hidden')->assertDontSee('Link existing');

        // And it cannot be forced through by posting its id.
        $this->actingAs($execA)->post(route('leads.convert.store', $lead), [
            'company_choice' => (string) $companyB->id, 'contact_choice' => 'new',
        ])->assertSessionHasErrors('company_choice');

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => $lead->status]);
        $this->assertNull($lead->fresh()->converted_company_id);
    }

    public function test_contact_duplicate_warning_does_not_leak_invisible_contacts(): void
    {
        [$execA] = $this->twoTeams();
        $ownCompany = Company::factory()->create(['owner_id' => $execA->id, 'created_by' => $execA->id]);

        $response = $this->actingAs($execA)->post(route('contacts.store', $ownCompany), [
            'name' => 'New Person', 'email' => 'shared@example.com',
        ]);
        $response->assertRedirect(route('companies.show', $ownCompany));
        $response->assertSessionHas('status', fn ($m) => ! str_contains($m, 'Zoya Hidden') && ! str_contains($m, 'Beta Secret Co'));
        $this->assertDatabaseHas('contacts', ['company_id' => $ownCompany->id, 'email' => 'shared@example.com']);
    }
}
