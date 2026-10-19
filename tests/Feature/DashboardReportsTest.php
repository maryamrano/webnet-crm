<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PipelineStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportsTest extends TestCase
{
    use RefreshDatabase;

    private array $stages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PipelineStageSeeder::class);
        $this->stages = PipelineStage::orderBy('position')->get()->keyBy('name')->all();
    }

    private function fixture(User $owner): void
    {
        // 4 leads: 1 converted, 1 lost, 2 open → conversion rate 25%
        Lead::factory()->create(['assigned_to' => $owner->id, 'created_by' => $owner->id, 'status' => 'converted', 'converted_at' => now(), 'source' => 'website']);
        Lead::factory()->create(['assigned_to' => $owner->id, 'created_by' => $owner->id, 'status' => 'lost', 'source' => 'website']);
        Lead::factory()->create(['assigned_to' => $owner->id, 'created_by' => $owner->id, 'status' => 'new', 'source' => 'referral']);
        Lead::factory()->create(['assigned_to' => $owner->id, 'created_by' => $owner->id, 'status' => 'qualified', 'source' => 'referral']);
        $companies = Company::factory()->count(2)->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);
        // Deals: open Proposal 200k @50% = 100k weighted; Won 300k; Lost 50k
        Deal::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id, 'company_id' => $companies[0]->id, 'stage_id' => $this->stages['Proposal']->id, 'value' => 200000, 'probability' => null]);
        Deal::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id, 'company_id' => $companies[0]->id, 'stage_id' => $this->stages['Won']->id, 'value' => 300000, 'probability' => null, 'closed_at' => now()]);
        Deal::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id, 'company_id' => $companies[1]->id, 'stage_id' => $this->stages['Lost']->id, 'value' => 50000, 'probability' => null, 'closed_at' => now()]);
    }

    public function test_dashboard_metrics_are_calculated_from_the_database(): void
    {
        $manager = User::factory()->manager()->create();
        $this->fixture($manager);

        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(4, $stats['total_leads']);
        $this->assertSame(2, $stats['total_companies']);
        $this->assertSame(25.0, $stats['conversion_rate']);
        $this->assertSame(1, $stats['open_deals']);
        $this->assertSame(200000.0, $stats['open_pipeline_value']);
        $this->assertSame(100000.0, $stats['open_weighted_value']);
        $this->assertSame(300000.0, $stats['won_value']);
    }

    public function test_dashboard_month_comparison_uses_current_and_previous_months(): void
    {
        $this->travelTo(now()->setDay(15));
        $manager = User::factory()->manager()->create();

        Lead::factory()->count(3)->create(['assigned_to' => $manager->id, 'created_at' => now()]);
        Lead::factory()->count(1)->create(['assigned_to' => $manager->id, 'created_at' => now()->subMonth()]);

        $comparison = $this->actingAs($manager)->get(route('dashboard'))->viewData('comparison');
        $this->assertSame(3, $comparison['leads']['current']);
        $this->assertSame(1, $comparison['leads']['previous']);
        $this->assertSame(200.0, $comparison['leads']['delta']); // (3-1)/1 = +200%
    }

    public function test_dashboard_empty_state_and_zero_rates(): void
    {
        $exec = User::factory()->create();

        $response = $this->actingAs($exec)->get(route('dashboard'));
        $response->assertOk()->assertSee('No CRM data yet');
        $this->assertSame(0.0, $response->viewData('stats')['conversion_rate']);
        $this->assertFalse($response->viewData('hasAnyData'));
    }

    public function test_dashboard_shows_upcoming_and_overdue_tasks_only(): void
    {
        $exec = User::factory()->create();
        Task::factory()->create(['assigned_to' => $exec->id, 'title' => 'Due Tomorrow Task', 'due_at' => now()->addDay(), 'status' => 'pending']);
        Task::factory()->create(['assigned_to' => $exec->id, 'title' => 'Overdue Dashboard Task', 'due_at' => now()->subDay(), 'status' => 'pending']);
        Task::factory()->create(['assigned_to' => $exec->id, 'title' => 'Far Future Task', 'due_at' => now()->addDays(20), 'status' => 'pending']);

        $this->actingAs($exec)->get(route('dashboard'))
            ->assertSee('Due Tomorrow Task')
            ->assertSee('Overdue Dashboard Task')
            ->assertDontSee('Far Future Task');
    }

    public function test_dashboard_is_scoped_for_sales_executives(): void
    {
        $execA = User::factory()->create();
        $execB = User::factory()->create();
        $this->fixture($execB);
        Lead::factory()->create(['assigned_to' => $execA->id, 'created_by' => $execA->id, 'status' => 'new']);

        $stats = $this->actingAs($execA)->get(route('dashboard'))->viewData('stats');
        $this->assertSame(1, $stats['total_leads']); // only their own lead
        $this->assertSame(0, $stats['open_deals']);
    }

    public function test_conversion_report_groups_and_filters(): void
    {
        $manager = User::factory()->manager()->create();
        $this->fixture($manager);

        $data = $this->actingAs($manager)->get(route('reports.conversion'))->viewData('data');
        $website = collect($data['by_source'])->firstWhere('label', 'Website');
        $this->assertSame(2, $website['total']);
        $this->assertSame(1, $website['converted']);
        $this->assertSame(50.0, $website['rate']);
        $this->assertSame(25.0, $data['totals']['rate']);

        $filtered = $this->actingAs($manager)->get(route('reports.conversion', ['source' => 'referral']))->viewData('data');
        $this->assertSame(2, $filtered['totals']['total']);
        $this->assertSame(0, $filtered['totals']['converted']);
    }

    public function test_pipeline_report_matches_board_calculations(): void
    {
        $manager = User::factory()->manager()->create();
        $this->fixture($manager);

        $data = $this->actingAs($manager)->get(route('reports.pipeline'))->viewData('data');
        $proposal = collect($data['rows'])->firstWhere(fn ($r) => $r['stage']->name === 'Proposal');
        $this->assertSame(1, $proposal['count']);
        $this->assertSame(200000.0, $proposal['total']);
        $this->assertSame(100000.0, $proposal['weighted']); // 50% stage probability
        $this->assertSame(550000.0, $data['totals']['total']);
        // Weighted total: 100k (proposal) + 300k (won @100%) + 0 (lost) = 400k
        $this->assertSame(400000.0, $data['totals']['weighted']);
    }

    public function test_team_report_scoping(): void
    {
        $execA = User::factory()->create(['name' => 'Exec Alpha']);
        $execB = User::factory()->create(['name' => 'Exec Beta']);
        $manager = User::factory()->manager()->create();
        $this->fixture($execB);

        $managerView = $this->actingAs($manager)->get(route('reports.team'));
        $names = collect($managerView->viewData('data')['rows'])->map(fn ($r) => $r['user']->name);
        $this->assertTrue($names->contains('Exec Alpha') && $names->contains('Exec Beta'));

        $execRows = $this->actingAs($execA)->get(route('reports.team'))->viewData('data')['rows'];
        $this->assertCount(1, $execRows);
        $this->assertSame('Exec Alpha', $execRows[0]['user']->name);
    }

    public function test_csv_export_contents_and_content_type(): void
    {
        $manager = User::factory()->manager()->create();
        $this->fixture($manager);

        $response = $this->actingAs($manager)->get(route('reports.conversion.export'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('"By source",Website,2,1,50', $csv);
        $this->assertStringContainsString('By owner', $csv);

        $pipelineCsv = $this->actingAs($manager)->get(route('reports.pipeline.export'))->streamedContent();
        $this->assertStringContainsString('Proposal,50,1,200000,100000', $pipelineCsv);
        $this->assertStringContainsString('TOTAL,,3,550000,400000', $pipelineCsv);
    }

    public function test_csv_export_respects_filters_and_scoping(): void
    {
        $execA = User::factory()->create();
        $execB = User::factory()->create();
        $this->fixture($execB);
        Lead::factory()->create(['assigned_to' => $execA->id, 'created_by' => $execA->id, 'status' => 'new', 'source' => 'website']);

        // Exec A's export contains only their single lead, not Exec B's four.
        $csv = $this->actingAs($execA)->get(route('reports.conversion.export'))->streamedContent();
        $this->assertStringContainsString('"By source",Website,1,0,0', $csv);

        // Team export for an executive contains exactly one data row (their own).
        $teamCsv = $this->actingAs($execA)->get(route('reports.team.export'))->streamedContent();
        $this->assertSame(2, substr_count(trim($teamCsv), "\n") + 1); // header + 1 row
    }

    public function test_csv_formula_injection_is_neutralized(): void
    {
        $manager = User::factory()->manager()->create();
        $evil = User::factory()->create(['name' => '=2+5+cmd|\' /C calc\'!A0']);
        Lead::factory()->create(['assigned_to' => $evil->id, 'created_by' => $evil->id, 'status' => 'new']);

        $csv = $this->actingAs($manager)->get(route('reports.team.export'))->streamedContent();
        $this->assertStringContainsString("'=2+5+cmd", $csv); // prefixed with a quote
        $this->assertStringNotContainsString("\n=2+5", $csv); // never a bare formula cell at line start
    }

    public function test_exports_are_audited_without_logging_contents(): void
    {
        $manager = User::factory()->manager()->create();
        $this->fixture($manager);

        $this->actingAs($manager)->get(route('reports.pipeline.export', ['owner_id' => $manager->id]));

        $log = AuditLog::where('action', 'report.exported')->first();
        $this->assertNotNull($log);
        $this->assertSame('Pipeline', $log->metadata['report']);
        $this->assertContains('owner_id', $log->metadata['filters_applied']);
        $payload = json_encode([$log->changes, $log->metadata]);
        $this->assertStringNotContainsString('200000', $payload); // no report values in the log
        $this->assertSame(1, AuditLog::where('action', 'report.exported')->count());
    }

    public function test_reports_and_exports_require_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect('/login');
        $this->get(route('reports.conversion.export'))->assertRedirect('/login');
        $this->get(route('reports.team'))->assertRedirect('/login');
    }
}
