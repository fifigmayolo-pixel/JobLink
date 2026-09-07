<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\CreateCompanyAccountRequest;
use App\Http\Requests\Coordinator\CreatePesoAccountRequest;
use App\Http\Requests\Coordinator\UpdateCompanyAccountRequest;
use App\Http\Requests\Coordinator\UpdatePesoAccountRequest;
use App\Models\Company;
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

    public function showPesoForm()
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        return view('coordinator.accounts.create-peso');
    }

    public function storePeso(CreatePesoAccountRequest $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $result = $this->provisioning->createPesoAccount($request->validated('email'));

        return back()->with(['status' => 'PESO account created.', 'tempPassword' => $result['temp_password']]);
    }

    public function pesoIndex(Request $request)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $pesoUsers = User::query()
            ->where('role', 'peso')
            ->when($request->search, fn ($q, $search) => $q->where('full_name', 'like', "%{$search}%"))
            ->orderBy('full_name')
            ->paginate(25);

        return view('coordinator.accounts.peso-index', compact('pesoUsers'));
    }

    public function showPeso(User $user)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);
        abort_unless($user->role === 'peso', 404);

        return view('coordinator.accounts.peso-show', compact('user'));
    }

    public function editPeso(User $user)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);
        abort_unless($user->role === 'peso', 404);

        return view('coordinator.accounts.peso-edit', compact('user'));
    }

    public function updatePeso(UpdatePesoAccountRequest $request, User $user)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);
        abort_unless($user->role === 'peso', 404);

        $user->update($request->validated());

        return redirect()->route('coordinator.accounts.peso.show', $user)->with('status', 'PESO account updated.');
    }

    public function destroyPeso(User $user)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);
        abort_unless($user->role === 'peso', 404);

        $user->delete();

        return redirect()->route('coordinator.accounts.peso.index')->with('status', 'PESO account removed.');
    }

    public function regenerateTempPassword(User $user)
    {
        Gate::authorize('manageStudentsAndAlumni', User::class);

        $tempPassword = $this->provisioning->regenerateTempPassword($user);

        return back()->with(['status' => 'Temp password regenerated.', 'tempPassword' => $tempPassword]);
    }
}