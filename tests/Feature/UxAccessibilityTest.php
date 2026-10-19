<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PipelineStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9 regression locks for the responsive/accessibility fixes that were
 * verified by rendering the app in headless Chromium at 1440/1280/768/390/
 * 360/320 px: exactly one h1 per page, labelled filter controls, a skip
 * link, and charts with text alternatives.
 */
class UxAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PipelineStageSeeder::class);
    }

    public function test_pages_have_one_h1_and_a_skip_link(): void
    {
        $user = User::factory()->admin()->create();

        foreach ([route('dashboard'), route('leads.index'), route('pipeline.index'), route('tasks.index'), route('reports.index')] as $url) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '<h1'), "Expected exactly one <h1> at {$url}");
            $this->assertStringContainsString('Skip to main content', $html);
            $this->assertStringContainsString('id="main-content"', $html);
        }
    }

    public function test_filter_controls_have_accessible_names(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('leads.index'))
            ->assertSee('aria-label="Search leads"', false)
            ->assertSee('aria-label="Filter by source"', false)
            ->assertSee('aria-label="Filter by status"', false)
            ->assertSee('aria-label="Filter by assignee"', false);

        $this->actingAs($user)->get(route('tasks.index'))
            ->assertSee('aria-label="Filter by priority"', false)
            ->assertSee('aria-label="Filter by due date"', false);

        $this->actingAs($user)->get(route('admin.audit-logs.index'))
            ->assertSee('aria-label="Filter by user"', false)
            ->assertSee('aria-label="From date"', false);
    }

    public function test_dashboard_charts_have_text_alternatives(): void
    {
        $user = User::factory()->admin()->create();
        // Charts render only when CRM data exists (empty-state branch otherwise).
        \App\Models\Lead::factory()->create(['assigned_to' => $user->id]);
        \App\Models\Deal::factory()->create(['owner_id' => $user->id]);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'role="img"'));
        $this->assertStringContainsString('aria-label="Doughnut chart of leads by status', $html);
        $this->assertStringContainsString('aria-label="Bar chart comparing total and weighted pipeline value by stage', $html);
    }
}
