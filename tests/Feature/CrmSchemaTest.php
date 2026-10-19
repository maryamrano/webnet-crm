<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PipelineStageSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_stages_seed_in_order_with_probabilities(): void
    {
        $this->seed(PipelineStageSeeder::class);

        $stages = PipelineStage::orderBy('position')->get();
        $this->assertCount(6, $stages);
        $this->assertSame(['Prospecting', 'Qualification', 'Proposal', 'Negotiation', 'Won', 'Lost'], $stages->pluck('name')->all());
        $this->assertSame([10, 25, 50, 75, 100, 0], $stages->pluck('probability')->all());
        $this->assertTrue($stages[4]->is_won);
        $this->assertTrue($stages[5]->is_lost);
    }

    public function test_company_contact_and_user_relationships(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id, 'name' => '  Acme   Textiles ']);
        $contact = Contact::factory()->create(['company_id' => $company->id, 'is_primary' => true, 'email' => '  Person@Example.com ']);

        $this->assertTrue($company->owner->is($owner));
        $this->assertTrue($company->contacts->first()->is($contact));
        $this->assertTrue($company->primaryContact->is($contact));
        $this->assertTrue($contact->company->is($company));
        $this->assertTrue($owner->ownedCompanies->first()->is($company));
        // Normalization is applied on save:
        $this->assertSame('acme textiles', $company->name_normalized);
        $this->assertSame('person@example.com', $contact->email_normalized);
    }

    public function test_phone_normalization_unifies_pakistani_formats(): void
    {
        $this->assertSame('03331234567', Contact::normalizePhone('+92 333 1234567'));
        $this->assertSame('03331234567', Contact::normalizePhone('0333-1234567'));
    }

    public function test_company_with_contacts_cannot_be_deleted(): void
    {
        $company = Company::factory()->create();
        Contact::factory()->create(['company_id' => $company->id]);

        $this->expectException(QueryException::class);
        $company->delete();
    }

    public function test_deleting_a_user_unassigns_records_instead_of_deleting_them(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $user->id]);
        $lead = Lead::factory()->create(['assigned_to' => $user->id]);

        $user->delete();

        $this->assertNull($company->fresh()->owner_id);
        $this->assertNull($lead->fresh()->assigned_to);
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_converted_lead_links_and_multiple_leads_may_share_a_company(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        $first = Lead::factory()->create([
            'status' => 'converted', 'converted_company_id' => $company->id,
            'converted_contact_id' => $contact->id, 'converted_at' => now(),
        ]);
        $second = Lead::factory()->create([
            'status' => 'converted', 'converted_company_id' => $company->id,
            'converted_contact_id' => $contact->id, 'converted_at' => now(),
        ]);

        $this->assertTrue($first->isConverted());
        $this->assertTrue($first->convertedCompany->is($company));
        $this->assertTrue($second->convertedCompany->is($company));
        $this->assertSame(2, $company->convertedLeads->count());
    }

    public function test_deal_relationships_and_weighted_value(): void
    {
        $stage = PipelineStage::factory()->create(['probability' => 50]);
        $deal = Deal::factory()->create(['stage_id' => $stage->id, 'value' => 200000, 'probability' => null]);

        $this->assertTrue($deal->stage->is($stage));
        $this->assertTrue($deal->company->deals->first()->is($deal));
        $this->assertSame(50, $deal->effectiveProbability());
        $this->assertEquals(100000.0, $deal->weightedValue());

        $deal->probability = 25;
        $this->assertSame(25, $deal->effectiveProbability());
    }

    public function test_stage_in_use_by_a_deal_cannot_be_deleted(): void
    {
        $deal = Deal::factory()->create();

        $this->expectException(QueryException::class);
        $deal->stage->delete();
    }

    public function test_polymorphic_tasks_and_activities_attach_to_records(): void
    {
        $lead = Lead::factory()->create();
        $author = User::factory()->create();

        $task = Task::factory()->create(['taskable_type' => $lead->getMorphClass(), 'taskable_id' => $lead->id]);
        $activity = Activity::factory()->create([
            'activityable_type' => $lead->getMorphClass(), 'activityable_id' => $lead->id, 'user_id' => $author->id,
        ]);

        $this->assertTrue($task->taskable->is($lead));
        $this->assertTrue($lead->tasks->first()->is($task));
        $this->assertTrue($lead->activities->first()->is($activity));
        $this->assertTrue($activity->author->is($author));
    }

    public function test_task_overdue_logic(): void
    {
        $overdue = Task::factory()->create(['due_at' => now()->subDay(), 'status' => 'pending']);
        $doneLate = Task::factory()->create(['due_at' => now()->subDay(), 'status' => 'completed', 'completed_at' => now()]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($doneLate->isOverdue());
    }
}
