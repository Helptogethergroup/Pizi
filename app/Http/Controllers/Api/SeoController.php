<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeoController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_pages' => DB::table('seo_settings')->count(),
            'active_pages' => DB::table('seo_settings')->where('is_active', 1)->count(),
            'missing_meta' => DB::table('seo_settings')->whereNull('meta_title')->orWhereNull('meta_description')->count(),
        ];

        $recent = DB::table('seo_settings')->orderBy('updated_at', 'desc')->limit(10)->get();

        return $this->ok(['stats' => $stats, 'recent_settings' => $recent]);
    }

    public function settings() { return $this->ok(DB::table('seo_settings')->orderBy('created_at', 'desc')->get()); }
    public function settingShow($id) { return $this->ok(DB::table('seo_settings')->where('id', $id)->first()); }

    public function settingStore(Request $request) { return $this->saveSetting($request, null); }
    public function settingUpdate(Request $request, $id) { return $this->saveSetting($request, $id); }

    private function saveSetting(Request $request, $id)
    {
        $data = $request->only(['page_key', 'page_label', 'meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description']);
        $data['is_active'] = $request->boolean('is_active') ? 1 : 0;
        $data['updated_at'] = now();

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('seo', 'public');
        }

        if ($id) {
            DB::table('seo_settings')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = now();
            $id = DB::table('seo_settings')->insertGetId($data);
        }

        return $this->ok(['id' => $id]);
    }

    public function settingDelete($id) { DB::table('seo_settings')->where('id', $id)->delete(); return $this->ok(['message' => 'Deleted']); }

    public function blogs() { return $this->ok(DB::table('blogs')->orderBy('created_at', 'desc')->get()); }
    public function blogShow($id) { return $this->ok(DB::table('blogs')->where('id', $id)->first()); }
    public function blogStore(Request $request) { return app(AdminController::class)->blogStore($request); }
    public function blogUpdate(Request $request, $id) { return app(AdminController::class)->blogUpdate($request, $id); }
    public function blogDelete($id) { DB::table('blogs')->where('id', $id)->delete(); return $this->ok(['message' => 'Deleted']); }
    public function blogToggle($id) { return app(AdminController::class)->blogToggle($id); }

    private function ok($data) { return response()->json(['success' => true, 'data' => $data]); }
}
