<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\User;
use Database\Seeders\PipelineStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealsPipelineTest extends TestCase
{
    use RefreshDatabase;

    private array $stages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PipelineStageSeeder::class);
        $this->stages = PipelineStage::orderBy('position')->get()->keyBy('name')->all();
    }

    private function visibleCompanyFor(User $user): Company
    {
        return Company::factory()->create(['owner_id' => $user->id, 'created_by' => $user->id]);
    }

    public function test_deal_create_sets_creator_and_validates_relationships(): void
    {
        $exec = User::factory()->create();
        $other = User::factory()->create();
        $company = $this->visibleCompanyFor($exec);
        $foreignCompany = $this->visibleCompanyFor($other);
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        // Happy path: creator is the actor even if the request says otherwise.
        $this->actingAs($exec)->post(route('deals.store'), [
            'title' => 'Valid deal', 'company_id' => $company->id, 'contact_id' => $contact->id,
            'stage_id' => $this->stages['Proposal']->id, 'value' => 100000, 'created_by' => $other->id,
            'owner_id' => $exec->id,
        ])->assertRedirect();
        $deal = Deal::first();
        $this->assertSame($exec->id, $deal->created_by);

        // Contact from ANOTHER company is rejected.
        $foreignContact = Contact::factory()->create(['company_id' => $foreignCompany->id]);
        $this->actingAs($exec)->post(route('deals.store'), [
            'title' => 'Mismatch', 'company_id' => $company->id, 'contact_id' => $foreignContact->id,
            'stage_id' => $this->stages['Proposal']->id, 'value' => 5000,
        ])->assertSessionHasErrors('contact_id');

        // A company the executive cannot access is rejected too.
        $this->actingAs($exec)->post(route('deals.store'), [
            'title' => 'No access', 'company_id' => $foreignCompany->id,
            'stage_id' => $this->stages['Proposal']->id, 'value' => 5000,
        ])->assertSessionHasErrors('company_id');

        $this->assertDatabaseCount('deals', 1);
    }

    public function test_deal_scoping_and_direct_url_authorization(): void
    {
        $execA = User::factory()->create();
        $execB = User::factory()->create();
        $dealB = Deal::factory()->create(['owner_id' => $execB->id, 'created_by' => $execB->id, 'title' => 'B Deal']);

        $this->actingAs($execA)->get(route('deals.index'))->assertDontSee('B Deal');
        $this->actingAs($execA)->get(route('deals.show', $dealB))->assertForbidden();
        $this->actingAs($execA)->get(route('pipeline.index'))->assertDontSee('B Deal');
        $this->actingAs($execA)->patch(route('deals.stage', $dealB), ['stage_id' => $this->stages['Won']->id])->assertForbidden();
        $this->actingAs($execA)->delete(route('deals.destroy', $dealB))->assertForbidden();
    }

    public function test_stage_change_updates_effective_probability_and_weighted_value_without_override(): void
    {
        $manager = User::factory()->manager()->create();
        $deal = Deal::factory()->create([
            'stage_id' => $this->stages['Qualification']->id, // 25%
            'value' => 200000, 'probability' => null, 'owner_id' => $manager->id,
        ]);

        $this->assertSame(25, $deal->effectiveProbability());
        $this->assertEquals(50000.0, $deal->weightedValue());

        $this->actingAs($manager)->patch(route('deals.stage', $deal), ['stage_id' => $this->stages['Negotiation']->id])
            ->assertRedirect();

        $deal->refresh()->load('stage');
        $this->assertSame(75, $deal->effectiveProbability());
        $this->assertEquals(150000.0, $deal->weightedValue());
    }

    public function test_probability_override_survives_stage_changes(): void
    {
        $manager = User::factory()->manager()->create();
        $deal = Deal::factory()->create([
            'stage_id' => $this->stages['Qualification']->id,
            'value' => 200000, 'probability' => 40, 'owner_id' => $manager->id,
        ]);

        $this->actingAs($manager)->patch(route('deals.stage', $deal), ['stage_id' => $this->stages['Proposal']->id]);

        $deal->refresh()->load('stage');
        $this->assertSame(40, $deal->effectiveProbability()); // override wins over stage 50%
        $this->assertEquals(80000.0, $deal->weightedValue());
    }

    public function test_won_and_lost_handling_timestamps_and_probabilities(): void
    {
        $manager = User::factory()->manager()->create();
        $deal = Deal::factory()->create([
            'stage_id' => $this->stages['Negotiation']->id, 'value' => 100000,
            'probability' => null, 'owner_id' => $manager->id,
        ]);

        $this->actingAs($manager)->patch(route('deals.stage', $deal), ['stage_id' => $this->stages['Won']->id]);
        $deal->refresh()->load('stage');
        $this->assertSame(100, $deal->effectiveProbability());
        $this->assertEquals(100000.0, $deal->weightedValue());
        $this->assertNotNull($deal->closed_at);

        // Re-opening clears the timestamp and probability falls back to the stage.
        $this->actingAs($manager)->patch(route('deals.stage', $deal), ['stage_id' => $this->stages['Proposal']->id]);
        $deal->refresh()->load('stage');
        $this->assertSame(50, $deal->effectiveProbability());
        $this->assertNull($deal->closed_at);

        $this->actingAs($manager)->patch(route('deals.stage', $deal), ['stage_id' => $this->stages['Lost']->id]);
        $deal->refresh()->load('stage');
        $this->assertSame(0, $deal->effectiveProbability());
        $this->assertEquals(0.0, $deal->weightedValue());
        $this->assertNotNull($deal->closed_at);
    }

    public function test_pipeline_board_shows_consistent_totals(): void
    {
        $manager = User::factory()->manager()->create();
        Deal::factory()->create(['stage_id' => $this->stages['Proposal']->id, 'value' => 200000, 'probability' => null, 'owner_id' => $manager->id, 'title' => 'Board Deal A']);
        Deal::factory()->create(['stage_id' => $this->stages['Proposal']->id, 'value' => 100000, 'probability' => 10, 'owner_id' => $manager->id, 'title' => 'Board Deal B']);

        // Proposal total = 300,000; weighted = 200000*50% + 100000*10%(override) = 110,000.
        $this->actingAs($manager)->get(route('pipeline.index'))
            ->assertOk()
            ->assertSee('Board Deal A')
            ->assertSee('PKR 300,000')
            ->assertSee('Weighted PKR 110,000');
    }

    public function test_activity_on_inaccessible_deal_is_blocked(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $deal = Deal::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);

        $this->actingAs($intruder)->post(route('activities.store'), [
            'activityable_key' => 'deal', 'activityable_id' => $deal->id,
            'type' => 'note', 'body' => 'Should not exist',
        ])->assertForbidden();
        $this->assertDatabaseCount('activities', 0);
    }
}
