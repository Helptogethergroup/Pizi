@extends('layouts.dashboard')
@section('title', 'Login Activity — Admin')
@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-display font-black text-3xl">Login Activity</h1>
            <p class="text-ink-900/60 mt-1">See who's logging in, how often, and when they last logged in.</p>
        </div>
    </div>

    <form class="flex gap-2 mb-6">
        <input name="search" value="{{ request('search') }}" placeholder="Search name / email"
            class="px-3 py-2 rounded-lg border border-ink-900/15 flex-1 max-w-sm">
        <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.users.activity') }}" class="px-4 py-2 border border-ink-900/15 rounded-lg text-ink-900/60">Clear</a>
        @endif
    </form>

    <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-center">Login Count</th>
                    <th>Last Login</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($activity as $row)
                    <tr class="border-t border-ink-900/5">
                        <td class="px-4 py-3 font-semibold">{{ $row->name }}</td>
                        <td>{{ $row->email }}</td>
                        <td>
                            <span class="text-xs px-2 py-1 rounded-full bg-ink-900/5">{{ ucfirst(str_replace('_', ' ', $row->role)) }}</span>
                        </td>
                        <td class="text-center">
                            <span class="font-bold {{ $row->login_count >= 20 ? 'text-rose-600' : ($row->login_count >= 10 ? 'text-amber-600' : 'text-ink-900') }}">
                                {{ $row->login_count }}
                            </span>
                            @if($row->login_count >= 20)
                                <span class="text-xs text-rose-500 block"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Frequent</span>
                            @endif
                        </td>
                        <td class="text-ink-900/70">{{ \Carbon\Carbon::parse($row->last_login)->diffForHumans() }}</td>
                        <td class="px-4">
                            <a href="{{ route('admin.users.show', $row->user_id) }}" class="text-xs px-3 py-1.5 rounded bg-ink-900 text-cream font-semibold">View User</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-ink-900/50">No login activity recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $activity->links() }}
    </div>
@endsection