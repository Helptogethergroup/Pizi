<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeoSettingController extends Controller
{
    public function index(Request $request)
    {
        $q = SeoSetting::with('updatedBy');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(fn($x) => $x->where('page_label', 'like', $term)->orWhere('page_key', 'like', $term));
        }

        if ($request->filled('status')) {
            $q->where('is_active', $request->status === 'active');
        }

        $pages = $q->latest('updated_at')->paginate(20)->withQueryString();
        return view('seo.settings.index', compact('pages'));
    }

    public function create()
    {
        return view('seo.settings.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['updated_by'] = auth()->id();
        $data['page_key'] = Str::slug($data['page_key']);

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('seo/og', 'public');
        }

        SeoSetting::create($data);

        return redirect()->route('seo.settings.index')->with('success', '✓ SEO settings added.');
    }

    public function edit(SeoSetting $setting)
    {
        return view('seo.settings.edit', compact('setting'));
    }

    public function update(Request $request, SeoSetting $setting)
    {
        $data = $this->validateData($request, $setting->id);
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('og_image')) {
            if ($setting->og_image && !str_starts_with($setting->og_image, 'http')) {
                Storage::disk('public')->delete($setting->og_image);
            }
            $data['og_image'] = $request->file('og_image')->store('seo/og', 'public');
        }

        $setting->update($data);

        return redirect()->route('seo.settings.index')->with('success', '✓ SEO settings updated.');
    }

    public function toggle(SeoSetting $setting)
    {
        $setting->is_active = !$setting->is_active;
        $setting->save();
        return back()->with('success', $setting->is_active ? '✓ Activated' : '⏸ Deactivated');
    }

    public function destroy(SeoSetting $setting)
    {
        if ($setting->og_image && !str_starts_with($setting->og_image, 'http')) {
            Storage::disk('public')->delete($setting->og_image);
        }
        $setting->delete();
        return back()->with('success', '✓ SEO setting deleted.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'page_key' => 'required|string|max:120|unique:seo_settings,page_key' . ($ignoreId ? ",$ignoreId" : ''),
            'page_label' => 'required|string|max:160',
            'meta_title' => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:500',
            'keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:160',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|image|max:4096',
            'schema_json' => 'nullable|string',
            'custom_head_html' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];

        return $request->validate($rules);
    }
}