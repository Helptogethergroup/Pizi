<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdLeadFormType;
use Illuminate\Http\Request;

class AdLeadFormController extends Controller
{
    public function index()
    {
        $mappings = AdLeadFormType::orderBy('platform')->orderBy('label')->get();
        return view('admin.ad-lead-forms', compact('mappings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'platform' => 'required|in:meta,google',
            'form_id' => 'required|string|max:100',
            'label' => 'nullable|string|max:150',
            'inquiry_type' => 'required|in:tenant,owner',
        ]);

        AdLeadFormType::updateOrCreate(
            ['platform' => $data['platform'], 'form_id' => $data['form_id']],
            ['label' => $data['label'], 'inquiry_type' => $data['inquiry_type']]
        );

        return back()->with('success', '✓ Form mapping saved.');
    }

    public function destroy(AdLeadFormType $adLeadForm)
    {
        $adLeadForm->delete();
        return back()->with('success', '✓ Mapping removed.');
    }
}
