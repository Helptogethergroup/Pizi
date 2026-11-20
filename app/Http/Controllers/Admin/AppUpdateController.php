<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OwnerAppUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Admin control of the daily "please update the Pizi Owner app" WhatsApp reminders. */
class AppUpdateController extends Controller
{
    public function __construct(private OwnerAppUpdateService $updates)
    {
    }

    public function index()
    {
        $latest = $this->updates->latestVersion();

        return view('admin.app-updates', [
            'enabled' => $this->updates->enabled(),
            'latest' => $latest,
            'max' => $this->updates->maxReminders(),
            'counts' => $this->updates->counts(),
            'outdated' => $latest ? $this->updates->outdated($latest)->take(25) : collect(),
            'recent' => DB::table('owner_app_update_reminders as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->where('r.latest_version', '!=', OwnerAppUpdateService::INSTALL_KIND)
                ->orderByDesc('r.id')->limit(20)
                ->get(['u.name', 'u.phone', 'r.version_seen', 'r.latest_version', 'r.sent_at']),
            'installOn' => $this->updates->installEnabled(),
            'installMax' => $this->updates->installMax(),
            'noApp' => $this->updates->installTargets()->take(25),
            'installRecent' => DB::table('owner_app_update_reminders as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->where('r.latest_version', OwnerAppUpdateService::INSTALL_KIND)
                ->orderByDesc('r.id')->limit(20)
                ->get(['u.name', 'u.phone', 'r.sent_at']),
        ]);
    }

    public function saveInstall(Request $request)
    {
        $data = $request->validate(['install_max' => 'required|integer|min:1|max:365']);
        $on = $request->boolean('install_enabled');

        $this->updates->setSetting('owner_install_max_reminders', $data['install_max']);
        $this->updates->setSetting('owner_install_reminders', $on ? '1' : '0');

        return back()->with('success', $on
            ? 'Saved. Owners who have not installed the app get a WhatsApp message every day until they install and sign in.'
            : 'Saved. The download reminders are OFF: nobody is messaged.');
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'latest_version' => ['nullable', 'regex:/^\d+(\.\d+){0,3}(\+\d+)?$/', 'max:20'],
            'max_reminders' => 'required|integer|min:1|max:365',
        ], ['latest_version.regex' => 'Write the version like 1.2.2+24 (version, then + and the build number).']);

        $on = $request->boolean('enabled');
        if ($on && empty($data['latest_version'])) {
            return back()->with('error', 'Set the latest version first, then switch the reminders on.')->withInput();
        }

        $this->updates->setSetting('owner_app_latest_version', $data['latest_version'] ?? '');
        $this->updates->setSetting('owner_update_max_reminders', $data['max_reminders']);
        $this->updates->setSetting('owner_update_reminders', $on ? '1' : '0');

        return back()->with('success', $on
            ? 'Saved. Owners whose app is older than ' . $data['latest_version'] . ' get a WhatsApp message every day until they update.'
            : 'Saved. The reminders are OFF: nobody is messaged.');
    }
}
