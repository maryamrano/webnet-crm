<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function store(ContactFormRequest $request, Company $company): RedirectResponse
    {
        // Authorization (ContactFormRequest) already verified the user may update this company.
        $data = $request->validated();
        $data['is_primary'] = $request->boolean('is_primary');

        $warning = $this->duplicateWarning($data, $request->user());
        $contact = $company->contacts()->create($data);
        AuditLogger::record('contact.created', $contact, AuditLogger::createdChanges($contact, 'contact'), ['company_label' => $company->name]);
        if ($contact->is_primary) {
            $company->contacts()->whereKeyNot($contact->id)->update(['is_primary' => false]);
        }

        $message = 'Contact added successfully.'.($warning ? " Warning: {$warning} Nothing was merged automatically." : '');

        return redirect()->route('companies.show', $company)->with('status', $message);
    }

    public function show(Contact $contact): View
    {
        $this->authorize('view', $contact);

        $contact->load(['company', 'deals.stage']);
        $contact->load(['activities' => fn ($q) => $q->with('author')->latest('happened_at')]);

        return view('contacts.show', ['contact' => $contact]);
    }

    public function edit(Contact $contact): View
    {
        $this->authorize('update', $contact);

        return view('contacts.edit', ['contact' => $contact]);
    }

    public function update(ContactFormRequest $request, Contact $contact): RedirectResponse
    {
        $data = $request->validated();
        $data['is_primary'] = $request->boolean('is_primary');
        $before = AuditLogger::snapshot($contact, 'contact');
        $contact->update($data);
        AuditLogger::record('contact.updated', $contact, AuditLogger::diffFrom($before, $contact, 'contact'));

        if ($contact->is_primary) {
            $contact->company->contacts()->whereKeyNot($contact->id)->update(['is_primary' => false]);
        }

        return redirect()->route('companies.show', $contact->company)->with('status', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);
        $company = $contact->company;

        try {
            $changes = AuditLogger::deletedChanges($contact, 'contact');
            $contact->delete();
            AuditLogger::record('contact.deleted', $contact, $changes);
        } catch (QueryException) {
            return redirect()->route('companies.show', $company)
                ->with('error', 'This contact is linked to converted leads or other history and cannot be deleted.');
        }

        return redirect()->route('companies.show', $company)->with('status', 'Contact deleted successfully.');
    }

    /** Duplicate check for the create form: exact normalized email / phone matches elsewhere. */
    private function duplicateWarning(array $data, User $actor): ?string
    {
        $matches = collect();
        // Phase 8: only warn about contacts the actor may actually see —
        // other teams' customer names must not leak through this message.
        $visible = Contact::visibleTo($actor);
        if (! empty($data['email'])) {
            (clone $visible)->where('email_normalized', Contact::normalizeEmail($data['email']))->get()
                ->each(fn ($c) => $matches->push("email matches contact {$c->name} at {$c->company->name}"));
        }
        if (! empty($data['phone'])) {
            (clone $visible)->where('phone_normalized', Contact::normalizePhone($data['phone']))->get()
                ->each(fn ($c) => $matches->push("phone matches contact {$c->name} at {$c->company->name}"));
        }

        return $matches->isEmpty() ? null : 'Possible duplicate — '.$matches->unique()->implode('; ').'.';
    }
}
