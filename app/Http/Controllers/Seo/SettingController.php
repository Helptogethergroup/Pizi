<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SeoSetting::orderBy('page_label')->paginate(20);
        return view('seo.settings.index', compact('settings'));
    }

    public function create()
    {
        return view('seo.settings.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('seo', 'public');
        }

        SeoSetting::create($data);
        return redirect()->route('seo.settings.index')->with('success', '✓ SEO page created.');
    }

    public function edit(SeoSetting $setting)
    {
        return view('seo.settings.edit', compact('setting'));
    }

    public function update(Request $request, SeoSetting $setting)
    {
        $data = $this->validateData($request, $setting->id);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('seo', 'public');
        }

        $setting->update($data);
        return redirect()->route('seo.settings.index')->with('success', '✓ SEO page updated.');
    }

    public function destroy(SeoSetting $setting)
    {
        $setting->delete();
        return back()->with('success', '✓ SEO page deleted.');
    }

    private function validateData(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'page_key' => 'required|string|max:100|unique:seo_settings,page_key' . ($ignoreId ? ",$ignoreId" : ''),
            'page_label' => 'required|string|max:200',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|image|max:2048',
        ]);
    }
}