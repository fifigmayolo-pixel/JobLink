<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\CreateCompanyAccountRequest;
use App\Http\Requests\Coordinator\UpdateCompanyAccountRequest;
use App\Http\Requests\Coordinator\CreateCoordinatorAccountRequest;
use App\Mail\CoordinatorAccountCreatedMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Company;
use App\Models\FieldChangeLog;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Mail\CompanyTempPasswordMail;


class AccountController extends Controller
{
    public function __construct(private AccountProvisioningService $provisioning) {}

    public function index()
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        return view('coordinator.accounts.index');
    }

    public function showCompanyForm()
    {
        Gate::authorize('createCompany', User::class);

        return view('coordinator.accounts.create-company');
    }

    public function storeCompany(CreateCompanyAccountRequest $request)
    {
        Gate::authorize('createCompany', User::class);

        $result = $this->provisioning->createCompanyWithTempPassword($request->validated(), auth()->id());

        FieldChangeLog::create([
            'action' => 'company_account_created',
            'user_id' => $result['user']->id,
            'changed_by' => auth()->id(),
            'details' => 'Company: '.$result['company']->company_name,

            
        ]);

        return back()->with(['status' => 'Company account created.', 'tempPassword' => $result['temp_password']]);
    }

        public function showCoordinatorForm()
    {
        Gate::authorize('createCoordinator', User::class);

        return view('coordinator.accounts.create-coordinator');
    }

    public function storeCoordinator(CreateCoordinatorAccountRequest $request)
    {
        Gate::authorize('createCoordinator', User::class);

        $result = $this->provisioning->createCoordinatorWithTempPassword(
            $request->validated('full_name'),
            $request->validated('email'),
        );

        $user = $result['user'];
        $emailed = false;

        try {
            // Sent right away, not queued, so the password never sits in the jobs table.
            Mail::to($user->email)->send(new CoordinatorAccountCreatedMail(
                fullName: $user->full_name,
                loginEmail: $user->email,
                tempPassword: $result['temp_password'],
                loginUrl: route('login'),
                createdBy: auth()->user()->full_name ?? auth()->user()->email,
            ));
            $emailed = true;
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with([
            'status' => $emailed
                ? "Coordinator account created. A temporary password was emailed to {$user->email}."
                : 'Coordinator account created, but the email could not be sent. Share the temporary password below with them directly.',
            'tempPassword' => $result['temp_password'],
        ]);
    }

    public function companyIndex(Request $request)
    {
        Gate::authorize('manageCompanies', User::class);

        $tab = $request->query('tab') === 'archived' ? 'archived' : 'active';

        $companies = Company::query()
            ->when(
                $tab === 'archived',
                // Archived = the company's login (User) is soft-deleted.
                fn ($q) => $q->with(['user' => fn ($u) => $u->withTrashed()])
                    ->whereHas('user', fn ($u) => $u->onlyTrashed()),
                fn ($q) => $q->with('user')->whereHas('user')
            )
            ->when($request->search, fn ($q, $search) => $q->where('company_name', 'like', "%{$search}%"))
            ->orderBy('company_name')
            ->paginate(25)
            ->withQueryString();

        $archivedCount = Company::whereHas('user', fn ($u) => $u->onlyTrashed())->count();

        return view('coordinator.accounts.company-index', compact('companies', 'tab', 'archivedCount'));
    }

    public function showCompany(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);

        return view('coordinator.accounts.company-show', compact('company'));
    }

        public function editCompany(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);
        abort_unless($company->user, 404); // archived companies have no active login to edit

        return view('coordinator.accounts.company-edit', compact('company'));
    }

    public function updateCompany(UpdateCompanyAccountRequest $request, Company $company)
    {
        Gate::authorize('manageCompanies', User::class);
        abort_unless($company->user, 404);

        $data = $request->validated();

        // The login email lives on the users table, so it's validated here.
        // Rule::unique ignores this company's own row but still blocks any
        // other account (including archived ones) that already owns the address.
        $email = strtolower(trim((string) $request->input('email')));

        validator(['email' => $email], [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($company->user_id)],
        ], [
            'email.unique' => 'That email is already used by another account.',
        ])->validate();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        $emailChanged = $company->user->email !== $email;

        DB::transaction(function () use ($company, $data, $email) {
            $company->update($data);
            $company->user->update(['email' => $email]);
        });

        return redirect()->route('coordinator.accounts.company.show', $company)
            ->with('status', $emailChanged
                ? "Company account updated. Their login email is now {$email}."
                : 'Company account updated.');
    }

    public function destroyCompany(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);

        $company->user->delete(); // soft delete — preserves the company profile + job posting history

        return redirect()->route('coordinator.accounts.company.index')
            ->with('status', 'Company account archived. You can restore it from the Archived tab.');
    }

    public function restoreCompany(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);

                $user = User::onlyTrashed()->findOrFail($company->user_id);

        $user->restore();

        return redirect()->route('coordinator.accounts.company.index')
            ->with('status', "{$company->company_name} restored. They can log in again with their existing password.");
    }

       public function regenerateCompanyTempPassword(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);
        abort_unless($company->user, 404);

        $tempPassword = $this->provisioning->regenerateTempPassword($company->user);

        $email = $company->user->email;
        $emailed = false;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                // Sent right away, not queued: keeps the password out of the
                // jobs table and shows immediately if delivery failed.
                Mail::to($email)->send(new CompanyTempPasswordMail(
                    companyName: $company->company_name,
                    loginEmail: $email,
                    tempPassword: $tempPassword,
                    loginUrl: route('login'),
                ));
                $emailed = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with([
            'status' => $emailed
                ? "Temp password regenerated and emailed to {$email}."
                : 'Temp password regenerated, but the email could not be sent. Share the password below with them directly.',
            'tempPassword' => $tempPassword,
        ]);
    }
}