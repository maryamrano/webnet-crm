<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Realistic FICTIONAL demo data for the WebNet CRM assignment.
 * Every company, person, email and phone number here is invented for the demo.
 * Emails use the reserved .test / example domains; phones are illustrative only.
 */
class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@webnetcrm.test')->firstOrFail();
        $manager = User::where('email', 'manager@webnetcrm.test')->firstOrFail();
        $sales = User::where('email', 'sales@webnetcrm.test')->firstOrFail();
        $team = [$sales, $manager, $sales, $admin];

        $stages = PipelineStage::orderBy('position')->get()->keyBy('name');

        // ---- Companies + contacts (8 companies, 12 contacts) ----
        $companyData = [
            ['NorthPeak Apparel', 'Textiles', 'Karachi'],
            ['IndusTech Solutions', 'IT Services', 'Lahore'],
            ['Saffron Foods', 'Food & Beverage', 'Islamabad'],
            ['Crescent Pharma Labs', 'Pharmaceuticals', 'Karachi'],
            ['RapidHaul Logistics', 'Logistics', 'Hyderabad'],
            ['PearlMart Retail', 'Retail', 'Faisalabad'],
            ['Skyline Builders', 'Construction', 'Lahore'],
            ['GreenGrid Solar', 'Energy', 'Islamabad'],
        ];

        $companies = collect();
        foreach ($companyData as $i => [$name, $industry, $city]) {
            $companies->push(Company::create([
                'name' => $name,
                'industry' => $industry,
                'website' => 'https://'.str(strtolower($name))->replace(' ', '').'.example',
                'email' => 'info@'.str(strtolower($name))->replace(' ', '').'.example',
                'phone' => '0300'.str_pad((string) (1000000 + $i * 137), 7, '0', STR_PAD_LEFT),
                'address' => ($i + 11).' Industrial Avenue',
                'city' => $city,
                'owner_id' => $team[$i % count($team)]->id,
                'created_by' => $manager->id,
            ]));
        }

        $contacts = collect();
        $contactNames = [
            ['Hamza Qureshi', 'Owner'], ['Mehwish Tariq', 'Procurement Manager'],
            ['Usman Khalid', 'IT Head'], ['Fatima Zahra', 'Operations Manager'],
            ['Ali Raza', 'Director'], ['Hira Shahid', 'Purchase Officer'],
            ['Omar Farooq', 'Project Manager'], ['Zainab Iqbal', 'Admin Manager'],
            ['Hassan Javed', 'Owner'], ['Mahnoor Aslam', 'Supply Chain Lead'],
            ['Bilal Chaudhry', 'Finance Manager'], ['Areeba Khan', 'HR Manager'],
        ];
        foreach ($contactNames as $i => [$cname, $designation]) {
            $company = $companies[$i % $companies->count()];
            $contacts->push(Contact::create([
                'company_id' => $company->id,
                'name' => $cname,
                'designation' => $designation,
                'email' => str(strtolower($cname))->replace(' ', '.').'@'.str(strtolower($company->name))->replace(' ', '').'.example',
                'phone' => '0321'.str_pad((string) (2000000 + $i * 211), 7, '0', STR_PAD_LEFT),
                'is_primary' => $i < $companies->count(),
            ]));
        }

        // ---- Leads (20 across sources/statuses; 3 already converted coherently) ----
        $leadRows = [
            ['Daniyal Sheikh', 'website', 'new'], ['Sana Malik', 'referral', 'contacted'],
            ['Ahmed Nadeem', 'cold_call', 'qualified'], ['Iqra Batool', 'social_media', 'new'],
            ['Talha Mehmood', 'walk_in', 'contacted'], ['Nimra Asif', 'website', 'qualified'],
            ['Shahzaib Alam', 'referral', 'lost'], ['Maryam Siddiqui', 'website', 'new'],
            ['Farhan Akhtar', 'cold_call', 'contacted'], ['Laiba Noor', 'social_media', 'qualified'],
            ['Waqas Ahmed', 'website', 'new'], ['Eman Tariq', 'referral', 'contacted'],
            ['Haris Sohail', 'walk_in', 'new'], ['Dua Fatima', 'website', 'lost'],
            ['Abdullah Tariq', 'cold_call', 'qualified'], ['Komal Razzaq', 'social_media', 'new'],
            ['Usama Bin Zaid', 'website', 'contacted'],
        ];
        foreach ($leadRows as $i => [$lname, $source, $status]) {
            Lead::create([
                'name' => $lname,
                'email' => str(strtolower($lname))->replace(' ', '.').'@example.com',
                'phone' => '0333'.str_pad((string) (3000000 + $i * 173), 7, '0', STR_PAD_LEFT),
                'company_name' => fake()->company(),
                'source' => $source,
                'status' => $status,
                'notes' => 'Demo lead seeded for the assignment walkthrough.',
                'assigned_to' => $team[$i % count($team)]->id,
                'created_by' => $sales->id,
            ]);
        }
        // Three converted leads, each coherently linked to a seeded company + its primary contact.
        foreach ([0, 2, 4] as $idx) {
            $company = $companies[$idx];
            $primary = $contacts->firstWhere('company_id', $company->id);
            Lead::create([
                'name' => $primary->name,
                'email' => $primary->email,
                'phone' => $primary->phone,
                'company_name' => $company->name,
                'source' => 'referral',
                'status' => 'converted',
                'notes' => 'Converted during an earlier sales cycle (seeded history).',
                'assigned_to' => $sales->id,
                'created_by' => $manager->id,
                'converted_company_id' => $company->id,
                'converted_contact_id' => $primary->id,
                'converted_at' => now()->subDays(20 - $idx),
            ]);
        }

        // ---- Deals (12 across all six stages) ----
        $dealPlan = [
            ['Website revamp package', 'Prospecting', 450000], ['Annual support contract', 'Prospecting', 300000],
            ['E-commerce store build', 'Qualification', 850000], ['Inventory system rollout', 'Qualification', 1200000],
            ['Corporate portal project', 'Proposal', 2400000], ['Mobile app phase 1', 'Proposal', 1800000],
            ['Warehouse automation', 'Negotiation', 3200000], ['Loyalty platform', 'Negotiation', 950000],
            ['ERP onboarding', 'Won', 2750000], ['Annual maintenance renewal', 'Won', 600000],
            ['POS integration', 'Won', 780000], ['Legacy migration bid', 'Lost', 1500000],
        ];
        foreach ($dealPlan as $i => [$title, $stageName, $value]) {
            $company = $companies[$i % $companies->count()];
            $stage = $stages[$stageName];
            Deal::create([
                'title' => $title,
                'company_id' => $company->id,
                'contact_id' => $contacts->firstWhere('company_id', $company->id)?->id,
                'lead_id' => null,
                'stage_id' => $stage->id,
                'owner_id' => $team[$i % count($team)]->id,
                'created_by' => $manager->id,
                'value' => $value,
                'probability' => null,
                'expected_close_date' => now()->addDays(5 + $i * 4)->toDateString(),
                'closed_at' => $stage->is_won || $stage->is_lost ? now()->subDays(3 + $i) : null,
                'description' => 'Seeded demo deal.',
            ]);
        }

        // ---- Tasks (15: overdue, due this week, in progress, completed) ----
        $taskPlan = [
            ['Call NorthPeak about fabric order', -2, 'pending', 'high'],
            ['Send proposal PDF to IndusTech', -1, 'pending', 'high'],
            ['Follow up on quotation #1042', 0, 'in_progress', 'medium'],
            ['Schedule demo for Saffron Foods', 1, 'pending', 'medium'],
            ['Prepare negotiation notes', 2, 'pending', 'high'],
            ['Collect requirements from PearlMart', 3, 'in_progress', 'medium'],
            ['Renewal reminder call — Crescent Pharma', 4, 'pending', 'low'],
            ['Site visit: RapidHaul warehouse', 5, 'pending', 'medium'],
            ['Share revised pricing sheet', -3, 'completed', 'medium'],
            ['Intro call with GreenGrid Solar', -4, 'completed', 'low'],
            ['Send contract draft to Skyline', 6, 'pending', 'high'],
            ['Quarterly review meeting prep', 7, 'pending', 'medium'],
            ['Update CRM records for Lahore visit', -1, 'completed', 'low'],
            ['Chase pending invoice confirmation', 1, 'in_progress', 'high'],
            ['Prepare sample kit for walk-in lead', 2, 'pending', 'low'],
        ];
        $taskTargets = [
            $companies[0], $companies[1], null, $companies[2], null,
            $companies[5], $companies[3], $companies[4], null, $companies[7],
            $companies[6], null, null, $companies[1], null,
        ];
        foreach ($taskPlan as $i => [$title, $offsetDays, $status, $priority]) {
            $target = $taskTargets[$i];
            Task::create([
                'title' => $title,
                'description' => 'Seeded demo task.',
                'taskable_type' => $target?->getMorphClass(),
                'taskable_id' => $target?->id,
                'assigned_to' => $team[$i % count($team)]->id,
                'created_by' => $manager->id,
                'due_at' => now()->addDays($offsetDays)->setTime(11, 0),
                'reminder_at' => now()->addDays($offsetDays)->setTime(9, 0),
                'priority' => $priority,
                'status' => $status,
                'completed_at' => $status === 'completed' ? now()->subDays(abs($offsetDays)) : null,
            ]);
        }

        // ---- Activities (communication history across companies + leads) ----
        $activityPlan = [
            ['call', 'Intro call completed', 'Discussed current requirements and budget range.'],
            ['email', 'Proposal sent', 'Sent the detailed proposal with pricing breakdown.'],
            ['meeting', 'On-site meeting', 'Met the procurement team; they requested a revised timeline.'],
            ['note', 'Internal note', 'Decision maker is the owner; follow up next week.'],
            ['call', 'Follow-up call', 'Customer asked for references from a similar business.'],
            ['email', 'Quotation follow-up', 'Shared quotation #1042 and payment terms.'],
            ['meeting', 'Product demo', 'Demo went well; team liked the reporting features.'],
            ['note', 'Requirement note', 'They need Urdu-language invoices — flagged for the deal.'],
            ['call', 'Negotiation call', 'Discussed discount ceiling approved by the manager.'],
            ['email', 'Contract shared', 'Sent the draft contract for legal review.'],
        ];
        foreach ($activityPlan as $i => [$type, $subject, $body]) {
            $parent = $i % 2 === 0 ? $companies[$i % $companies->count()] : Lead::inRandomOrder()->first();
            Activity::create([
                'activityable_type' => $parent->getMorphClass(),
                'activityable_id' => $parent->id,
                'user_id' => $team[$i % count($team)]->id,
                'type' => $type,
                'subject' => $subject,
                'body' => $body,
                'happened_at' => now()->subDays($i + 1)->setTime(10, 30),
            ]);
        }
    }
}
