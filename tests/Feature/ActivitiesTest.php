<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitiesTest extends TestCase
{
    use RefreshDatabase;

    private function leadOwnedBy(User $user): Lead
    {
        return Lead::factory()->create(['assigned_to' => $user->id, 'created_by' => $user->id]);
    }

    public function test_activity_can_be_logged_on_a_visible_lead_and_appears_on_its_page(): void
    {
        $exec = User::factory()->create();
        $lead = $this->leadOwnedBy($exec);

        $this->actingAs($exec)->post(route('activities.store'), [
            'activityable_key' => 'lead', 'activityable_id' => $lead->id,
            'type' => 'call', 'subject' => 'Intro call', 'body' => 'Spoke for ten minutes.',
        ])->assertRedirect();

        $activity = Activity::first();
        $this->assertSame($exec->id, $activity->user_id);
        $this->assertTrue($activity->activityable->is($lead));

        $this->actingAs($exec)->get(route('leads.show', $lead))
            ->assertOk()->assertSee('Intro call')->assertSee('Spoke for ten minutes.');
    }

    public function test_activity_cannot_be_created_on_an_inaccessible_parent(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $lead = $this->leadOwnedBy($owner);

        $this->actingAs($intruder)->post(route('activities.store'), [
            'activityable_key' => 'lead', 'activityable_id' => $lead->id,
            'type' => 'note', 'body' => 'Sneaky note',
        ])->assertForbidden();

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_activity_on_inaccessible_company_contact_is_also_blocked(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        foreach (['company' => $company->id, 'contact' => $contact->id] as $key => $id) {
            $this->actingAs($intruder)->post(route('activities.store'), [
                'activityable_key' => $key, 'activityable_id' => $id,
                'type' => 'email', 'body' => 'Should not exist',
            ])->assertForbidden();
        }
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_only_author_manager_or_admin_can_edit_or_delete_an_activity(): void
    {
        $author = User::factory()->create();
        $colleague = User::factory()->create();
        $manager = User::factory()->manager()->create();
        $lead = $this->leadOwnedBy($author);
        // Manager must be able to see the parent too — give manager visibility by making them creator? Managers see all leads.
        $activity = Activity::factory()->create([
            'activityable_type' => $lead->getMorphClass(), 'activityable_id' => $lead->id, 'user_id' => $author->id,
        ]);

        // A colleague who cannot even see the lead: forbidden.
        $this->actingAs($colleague)->get(route('activities.edit', $activity))->assertForbidden();
        $this->actingAs($colleague)->put(route('activities.update', $activity), [
            'type' => 'note', 'body' => 'hacked',
        ])->assertForbidden();

        // The author can edit their own activity.
        $this->actingAs($author)->put(route('activities.update', $activity), [
            'type' => 'call', 'body' => 'Updated by author',
        ])->assertRedirect(route('leads.show', $lead));
        $this->assertSame('Updated by author', $activity->fresh()->body);
        $this->assertSame($author->id, $activity->fresh()->user_id); // authorship unchanged

        // A manager can edit someone else's activity.
        $this->actingAs($manager)->put(route('activities.update', $activity), [
            'type' => 'note', 'body' => 'Manager correction',
        ])->assertRedirect(route('leads.show', $lead));
        $this->assertSame('Manager correction', $activity->fresh()->body);

        // A non-author sales executive WITH parent access still cannot delete it.
        $viewer = User::factory()->create();
        $lead2 = Lead::factory()->create(['assigned_to' => $viewer->id, 'created_by' => $author->id]);
        $activity2 = Activity::factory()->create([
            'activityable_type' => $lead2->getMorphClass(), 'activityable_id' => $lead2->id, 'user_id' => $author->id,
        ]);
        $this->actingAs($viewer)->delete(route('activities.destroy', $activity2))->assertForbidden();
        $this->assertDatabaseHas('activities', ['id' => $activity2->id]);

        // The manager can delete it.
        $this->actingAs($manager)->delete(route('activities.destroy', $activity2))
            ->assertRedirect(route('leads.show', $lead2));
        $this->assertDatabaseMissing('activities', ['id' => $activity2->id]);
    }

    public function test_activity_validation_requires_body_and_known_type(): void
    {
        $exec = User::factory()->create();
        $lead = $this->leadOwnedBy($exec);

        $this->actingAs($exec)->post(route('activities.store'), [
            'activityable_key' => 'lead', 'activityable_id' => $lead->id,
            'type' => 'sms', 'body' => '',
        ])->assertSessionHasErrors(['type', 'body']);

        $this->actingAs($exec)->post(route('activities.store'), [
            'activityable_key' => 'user', 'activityable_id' => $exec->id,
            'type' => 'note', 'body' => 'Arbitrary class attempt',
        ])->assertSessionHasErrors('activityable_key');

        $this->assertDatabaseCount('activities', 0);
    }
}
