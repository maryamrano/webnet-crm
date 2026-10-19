<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompaniesContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_company_sets_creator_server_side_and_warns_on_duplicate_name(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();
        Company::factory()->create(['name' => 'Acme Textiles']);

        $this->actingAs($exec)->post(route('companies.store'), [
            'name' => 'acme   textiles', 'created_by' => $other->id, 'owner_id' => $exec->id,
        ])->assertRedirect();

        $created = Company::where('name', 'acme   textiles')->first();
        $this->assertSame($exec->id, $created->created_by);
        $this->assertDatabaseCount('companies', 2); // both kept — warning, never a merge
    }

    public function test_company_visibility_scoping_and_direct_url_protection(): void
    {
        $execA = User::factory()->create();
        $execB = User::factory()->create();
        $mine = Company::factory()->create(['owner_id' => $execA->id, 'created_by' => $execA->id, 'name' => 'Mine Co']);
        $theirs = Company::factory()->create(['owner_id' => $execB->id, 'created_by' => $execB->id, 'name' => 'Theirs Co']);

        $this->actingAs($execA)->get(route('companies.index'))
            ->assertSee('Mine Co')->assertDontSee('Theirs Co');
        $this->actingAs($execA)->get(route('companies.show', $theirs))->assertForbidden();
        $this->actingAs($execA)->get(route('companies.show', $mine))->assertOk();
        $this->actingAs($execA)->put(route('companies.update', $theirs), ['name' => 'Hijacked'])->assertForbidden();
    }

    public function test_company_validation_and_exec_owner_rule(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($exec)->post(route('companies.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
        $this->actingAs($exec)->post(route('companies.store'), ['name' => 'Ok Co', 'owner_id' => $other->id])
            ->assertSessionHasErrors('owner_id');
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_contacts_inherit_company_visibility(): void
    {
        $execA = User::factory()->create();
        $execB = User::factory()->create();
        $companyB = Company::factory()->create(['owner_id' => $execB->id, 'created_by' => $execB->id]);
        $contactB = Contact::factory()->create(['company_id' => $companyB->id, 'name' => 'Hidden Person']);

        // Exec A cannot see B's contact directly, nor add contacts to B's company.
        $this->actingAs($execA)->get(route('contacts.show', $contactB))->assertForbidden();
        $this->actingAs($execA)->get(route('contacts.edit', $contactB))->assertForbidden();
        $this->actingAs($execA)->post(route('contacts.store', $companyB), ['name' => 'Nope'])->assertForbidden();
        $this->assertSame(1, Contact::count());
    }

    public function test_contact_create_update_and_primary_switching(): void
    {
        $manager = User::factory()->manager()->create();
        $company = Company::factory()->create(['owner_id' => $manager->id]);

        $this->actingAs($manager)->post(route('contacts.store', $company), [
            'name' => 'First Person', 'email' => 'first@example.com', 'is_primary' => '1',
        ])->assertRedirect(route('companies.show', $company));
        $first = Contact::first();
        $this->assertTrue($first->is_primary);

        $this->actingAs($manager)->post(route('contacts.store', $company), [
            'name' => 'Second Person', 'is_primary' => '1',
        ]);
        $second = Contact::where('name', 'Second Person')->first();
        $this->assertTrue($second->is_primary);
        $this->assertFalse($first->fresh()->is_primary); // only one primary per company

        $this->actingAs($manager)->put(route('contacts.update', $second), [
            'name' => 'Second Person Renamed', 'designation' => 'Director',
        ])->assertRedirect(route('companies.show', $company));
        $this->assertSame('Director', $second->fresh()->designation);
    }

    public function test_contact_duplicate_warning_on_create(): void
    {
        $manager = User::factory()->manager()->create();
        $company = Company::factory()->create(['owner_id' => $manager->id]);
        Contact::factory()->create(['email' => 'taken@example.com', 'name' => 'Original Person']);

        $response = $this->actingAs($manager)->post(route('contacts.store', $company), [
            'name' => 'Copy Person', 'email' => 'TAKEN@example.com',
        ]);
        $response->assertSessionHas('status', fn ($msg) => str_contains($msg, 'Possible duplicate') && str_contains($msg, 'Original Person'));
        $this->assertDatabaseCount('contacts', 2); // warned, not blocked or merged
    }

    public function test_delete_protection_for_companies_and_contacts(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        // Manager cannot delete companies or contacts at all (approved rule A2).
        $this->actingAs($manager)->delete(route('companies.destroy', $company))->assertForbidden();
        $this->actingAs($manager)->delete(route('contacts.destroy', $contact))->assertForbidden();

        // Admin still cannot delete a company that has contacts (history protection).
        $this->actingAs($admin)->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.show', $company));
        $this->assertDatabaseHas('companies', ['id' => $company->id]);

        // A contact referenced by a converted lead cannot be deleted (FK restrict).
        Lead::factory()->create([
            'status' => 'converted', 'converted_company_id' => $company->id,
            'converted_contact_id' => $contact->id, 'converted_at' => now(),
        ]);
        $this->actingAs($admin)->delete(route('contacts.destroy', $contact));
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);

        // An empty company CAN be deleted by an admin.
        $empty = Company::factory()->create();
        $this->actingAs($admin)->delete(route('companies.destroy', $empty))->assertRedirect(route('companies.index'));
        $this->assertDatabaseMissing('companies', ['id' => $empty->id]);
    }
}
