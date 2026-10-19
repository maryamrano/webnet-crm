<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityFormRequest;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Services\AuditLogger;
use App\Support\CrmParentTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Activities are always handled THROUGH their parent record. The parent key is
 * a fixed whitelist (never a user-supplied class name), the parent must exist,
 * and the user must be authorized to VIEW the parent before any activity is
 * created, edited or deleted through it.
 */
class ActivityController extends Controller
{
    public function store(ActivityFormRequest $request): RedirectResponse
    {
        $validated = $request->validate([
            'activityable_key' => ['required', Rule::in(CrmParentTypes::keys())],
            'activityable_id' => ['required', 'integer'],
        ]);

        $parent = $this->resolveParent($validated['activityable_key'], $validated['activityable_id']);
        $this->authorizeParentView($request, $parent);

        $activity = new Activity($request->validated());
        $activity->activityable_type = $parent->getMorphClass();
        $activity->activityable_id = $parent->id;
        $activity->user_id = $request->user()->id; // author is always the authenticated user
        $activity->happened_at = $activity->happened_at ?? now();
        $activity->save();
        AuditLogger::record('activity.created', $activity, AuditLogger::createdChanges($activity, 'activity'),
            ['parent_label' => AuditLogger::label($parent), 'parent_type' => class_basename($parent)]);

        return back()->with('status', 'Activity logged successfully.');
    }

    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('activities.edit', ['activity' => $activity]);
    }

    public function update(ActivityFormRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $data = $request->validated();
        unset($data['user_id']); // authorship never changes hands
        $before = AuditLogger::snapshot($activity, 'activity');
        $activity->update($data);
        AuditLogger::record('activity.updated', $activity, AuditLogger::diffFrom($before, $activity, 'activity'));

        return $this->redirectToParent($activity)->with('status', 'Activity updated successfully.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $response = $this->redirectToParent($activity);
        $changes = AuditLogger::deletedChanges($activity, 'activity');
        $parent = $activity->activityable;
        $activity->delete();
        AuditLogger::record('activity.deleted', $activity, $changes,
            $parent ? ['parent_label' => AuditLogger::label($parent), 'parent_type' => class_basename($parent)] : []);

        return $response->with('status', 'Activity deleted successfully.');
    }

    private function resolveParent(string $key, int $id): Model
    {
        $class = CrmParentTypes::classFor($key);

        return $class::findOrFail($id);
    }

    private function authorizeParentView(Request $request, Model $parent): void
    {
        abort_unless($request->user()->can('view', $parent), 403);
    }

    private function redirectToParent(Activity $activity): RedirectResponse
    {
        $parent = $activity->activityable;

        return match (true) {
            $parent instanceof Lead => redirect()->route('leads.show', $parent),
            $parent instanceof Company => redirect()->route('companies.show', $parent),
            $parent instanceof Contact => redirect()->route('contacts.show', $parent),
            default => redirect()->route('dashboard'),
        };
    }
}
