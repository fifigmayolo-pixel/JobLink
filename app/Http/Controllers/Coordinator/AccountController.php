<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\CreateCompanyAccountRequest;
use App\Http\Requests\Coordinator\UpdateCompanyAccountRequest;
use App\Models\Company;
use App\Models\FieldChangeLog;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function companyIndex(Request $request)
    {
        Gate::authorize('manageCompanies', User::class);

        $companies = Company::query()
            ->with('user')
            ->whereHas('user')
            ->when($request->search, fn ($q, $search) => $q->where('company_name', 'like', "%{$search}%"))
            ->orderBy('company_name')
            ->paginate(25);

        return view('coordinator.accounts.company-index', compact('companies'));
    }

    public function showCompany(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);

        return view('coordinator.accounts.company-show', compact('company'));
    }

    public function editCompany(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);

        return view('coordinator.accounts.company-edit', compact('company'));
    }

    public function updateCompany(UpdateCompanyAccountRequest $request, Company $company)
    {
        Gate::authorize('manageCompanies', User::class);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($data);

        return redirect()->route('coordinator.accounts.company.show', $company)->with('status', 'Company account updated.');
    }

    public function destroyCompany(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);

        $company->user->delete(); // soft delete — preserves the company profile + job posting history

        return redirect()->route('coordinator.accounts.company.index')->with('status', 'Company account removed.');
    }

    public function regenerateCompanyTempPassword(Company $company)
    {
        Gate::authorize('manageCompanies', User::class);
        abort_unless($company->user, 404);

        $tempPassword = $this->provisioning->regenerateTempPassword($company->user);

        return back()->with(['status' => 'Temp password regenerated.', 'tempPassword' => $tempPassword]);
    }
}