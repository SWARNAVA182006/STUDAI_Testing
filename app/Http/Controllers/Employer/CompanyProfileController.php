<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'employer']);
    }

    public function show()
    {
        $company = auth()->user()->company;
        return view('employer.profile.show', compact('company'));
    }

    public function edit()
    {
        $company = auth()->user()->company;
        return view('employer.profile.edit', compact('company'));
    }

    public function update(Request $request)
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'company_size' => ['nullable', 'string', 'in:1-10,11-50,51-200,201-500,501-1000,1000+'],
            'website' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'headquarters' => ['nullable', 'string', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:255'],
            'culture' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo_url) {
                Storage::disk('public')->delete($company->logo_url);
            }

            $logoPath = $request->file('logo')->store('company-logos', 'public');
            $validated['logo_url'] = $logoPath;
        }

        // Benefits are cast to array in model, no need to json_encode

        $company->update($validated);

        return redirect()
            ->route('employer.profile.show')
            ->with('success', 'Company profile updated successfully!');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $company = auth()->user()->company;

        // Delete old logo
        if ($company->logo_url) {
            Storage::disk('public')->delete($company->logo_url);
        }

        $logoPath = $request->file('logo')->store('company-logos', 'public');
        $company->update(['logo_url' => $logoPath]);

        return response()->json([
            'success' => true,
            'logo_url' => Storage::url($logoPath),
            'message' => 'Logo updated successfully!'
        ]);
    }

    public function deleteLogo()
    {
        $company = auth()->user()->company;

        if ($company->logo_url) {
            Storage::disk('public')->delete($company->logo_url);
            $company->update(['logo_url' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logo removed successfully!'
        ]);
    }
}
