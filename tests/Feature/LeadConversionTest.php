<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\PipelineStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PipelineStageSeeder::class);
    }

    private function leadFor(User $owner, array $attrs = []): Lead
    {
        return Lead::factory()->create(array_merge([
            'name' => 'Convert Me',
            'email' => 'convert@example.com',
            'phone' => '0300 9998877',
            'company_name' => 'Brand New Co',
            'status' => 'qualified',
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
        ], $attrs));
    }

    public function test_conversion_creates_company_and_contact_atomically(): void
    {
        $exec = User::factory()->create();
        $lead = $this->leadFor($exec);

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
            'company_choice' => 'new', 'contact_choice' => 'new',
        ])->assertRedirect(route('leads.show', $lead));

        $lead->refresh();
        $this->assertTrue($lead->isConverted());
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertSame('Brand New Co', $lead->convertedCompany->name);
        $this->assertSame($lead->convertedCompany->id, $lead->convertedContact->company_id);
    }

    public function test_repeat_conversion_is_rejected_and_creates_nothing(): void
    {
        $exec = User::factory()->create();
        $lead = $this->leadFor($exec);

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), ['company_choice' => 'new', 'contact_choice' => 'new']);
        $counts = [Company::count(), Contact::count(), Deal::count()];

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), ['company_choice' => 'new', 'contact_choice' => 'new'])
            ->assertSessionHas('error');

        $this->assertSame($counts, [Company::count(), Contact::count(), Deal::count()]);
    }

    public function test_conversion_can_link_existing_company_without_duplicating_it(): void
    {
        $exec = User::factory()->create();
        $existing = Company::factory()->create(['name' => 'Brand New Co', 'owner_id' => $exec->id, 'created_by' => $exec->id]);
        $lead = $this->leadFor($exec);

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
            'company_choice' => (string) $existing->id, 'contact_choice' => 'new',
        ])->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseCount('companies', 1); // linked, not duplicated
        $this->assertSame($existing->id, $lead->fresh()->converted_company_id);
        $this->assertDatabaseCount('contacts', 1); // new contact inside the existing company
        $this->assertSame($existing->id, Contact::first()->company_id);
    }

    public function test_exact_email_contact_match_can_be_linked_explicitly(): void
    {
        $exec = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Other Co', 'owner_id' => $exec->id, 'created_by' => $exec->id]);
        $contact = Contact::factory()->create(['company_id' => $company->id, 'email' => 'convert@example.com']);
        $lead = $this->leadFor($exec, ['company_name' => 'Something Else']);

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
            'company_choice' => (string) $company->id, 'contact_choice' => (string) $contact->id,
        ])->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseCount('contacts', 1);
        $this->assertSame($contact->id, $lead->fresh()->converted_contact_id);
    }

    public function test_multiple_company_matches_require_an_explicit_choice(): void
    {
        $exec = User::factory()->create();
        Company::factory()->create(['name' => 'Brand New Co', 'owner_id' => $exec->id, 'created_by' => $exec->id]);
        Company::factory()->create(['name' => 'brand  new   co', 'owner_id' => $exec->id, 'created_by' => $exec->id]); // same after normalization
        $lead = $this->leadFor($exec);

        // The screen offers both candidates:
        $this->actingAs($exec)->get(route('leads.convert.show', $lead))
            ->assertOk()->assertSee('please choose explicitly');

        // Submitting without a choice fails validation and converts nothing:
        $this->actingAs($exec)->post(route('leads.convert.store', $lead), ['contact_choice' => 'new'])
            ->assertSessionHasErrors('company_choice');
        $this->assertFalse($lead->fresh()->isConverted());
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_mismatched_contact_and_company_rolls_back_everything(): void
    {
        $exec = User::factory()->create();
        $companyA = Company::factory()->create(['name' => 'Alpha Co']);
        $companyB = Company::factory()->create(['name' => 'Beta Co', 'owner_id' => $exec->id, 'created_by' => $exec->id]);
        $contactB = Contact::factory()->create(['company_id' => $companyB->id, 'email' => 'convert@example.com']);
        $lead = $this->leadFor($exec, ['company_name' => 'Gamma Co']);

        $companies = Company::count();
        $contacts = Contact::count();

        // Choosing "new company" + an existing contact from Beta forces the service
        // to create a company first and THEN hit the mismatch — proving the created
        // record is rolled back too, not just that nothing started.
        $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
            'company_choice' => 'new',
            'contact_choice' => (string) $contactB->id, // belongs to Beta, not the new company
        ])->assertSessionHas('error');

        // Full rollback: counts unchanged, lead untouched.
        $this->assertSame($companies, Company::count());
        $this->assertSame($contacts, Contact::count());
        $fresh = $lead->fresh();
        $this->assertFalse($fresh->isConverted());
        $this->assertNull($fresh->converted_company_id);
        $this->assertNull($fresh->converted_contact_id);
    }

    public function test_conversion_with_optional_first_deal(): void
    {
        $exec = User::factory()->create();
        $lead = $this->leadFor($exec);

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
            'company_choice' => 'new', 'contact_choice' => 'new',
            'create_deal' => '1', 'deal_title' => 'First order', 'deal_value' => 250000,
        ])->assertRedirect(route('leads.show', $lead));

        $deal = Deal::first();
        $this->assertNotNull($deal);
        $this->assertSame($lead->id, $deal->lead_id);
        $this->assertSame('Prospecting', $deal->stage->name);
        $this->assertEquals(250000, (float) $deal->value);

        // First-deal safeguards: relationships point at the converted records, and
        // ownership/creator follow the server-side rules (owner = lead assignee,
        // creator = the user who performed the conversion).
        $lead->refresh();
        $this->assertSame($lead->converted_company_id, $deal->company_id);
        $this->assertSame($lead->converted_contact_id, $deal->contact_id);
        $this->assertSame($exec->id, $deal->owner_id);
        $this->assertSame($exec->id, $deal->created_by);
    }

    public function test_invalid_deal_value_aborts_conversion_and_creates_nothing(): void
    {
        $exec = User::factory()->create();
        $lead = $this->leadFor($exec);

        $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
            'company_choice' => 'new', 'contact_choice' => 'new',
            'create_deal' => '1', 'deal_value' => -500,
        ])->assertSessionHasErrors('deal_value');

        $this->assertFalse($lead->fresh()->isConverted());
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('deals', 0);
    }

    public function test_sales_executive_cannot_convert_another_executives_lead(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $lead = $this->leadFor($owner);

        $this->actingAs($intruder)->get(route('leads.convert.show', $lead))->assertForbidden();
        $this->actingAs($intruder)->post(route('leads.convert.store', $lead), [
            'company_choice' => 'new', 'contact_choice' => 'new',
        ])->assertForbidden();

        $this->assertFalse($lead->fresh()->isConverted());
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_two_different_leads_can_convert_into_the_same_company(): void
    {
        $exec = User::factory()->create();
        $shared = Company::factory()->create(['name' => 'Shared Co', 'owner_id' => $exec->id, 'created_by' => $exec->id]);

        foreach (['one@example.com', 'two@example.com'] as $email) {
            $lead = $this->leadFor($exec, ['email' => $email, 'company_name' => 'Shared Co']);
            $this->actingAs($exec)->post(route('leads.convert.store', $lead), [
                'company_choice' => (string) $shared->id, 'contact_choice' => 'new',
            ])->assertRedirect(route('leads.show', $lead));
        }

        $this->assertDatabaseCount('companies', 1);
        $this->assertSame(2, Lead::where('converted_company_id', $shared->id)->count());
    }
}
