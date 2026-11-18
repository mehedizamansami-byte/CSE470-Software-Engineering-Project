@extends('layouts.app')
@section('title', 'Team & collaborators')

@section('content')
<div class="page-head">
    <div>
        <h1>Team and collaborators</h1>
        <p class="sub">Invite staff, choose what each person can use, and see everything they do in the audit log.</p>
    </div>
    <span class="badge badge-blue">{{ $plan->name }} plan: {{ $plan->team_limit === null ? 'unlimited members' : $plan->team_limit.' member(s)' }}</span>
</div>

<div class="grid grid-main">
    <div class="card">
        <div class="card-head"><h2>Members ({{ $members->count() }})</h2></div>
        @if ($members->isEmpty())
            <div class="empty"><h3>No collaborators yet</h3><p>Invite your staff with the form on the right.</p></div>
        @else
            <div class="stack">
                @foreach ($members as $member)
                    <div class="card" style="padding:16px;box-shadow:none">
                        <div class="row between">
                            <div class="row">
                                <span class="avatar">{{ mb_strtoupper(mb_substr($member->name ?: $member->email, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $member->name ?: 'Invited staff' }}</strong>
                                    <div class="small muted">{{ $member->email }}</div>
                                </div>
                            </div>
                            <div class="row">
                                @if ($member->isPending())
                                    <span class="badge badge-amber">Invitation pending</span>
                                @else
                                    <span class="badge badge-green">Active since {{ $member->accepted_at?->format('d M Y') }}</span>
                                @endif
                                <form method="POST" action="{{ route('team.destroy', $member) }}" data-confirm="Remove {{ $member->email }} from your team? Their collaborator account will be deleted.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm" type="submit">{{ $member->isPending() ? 'Cancel invite' : 'Remove' }}</button>
                                </form>
                            </div>
                        </div>

                        @if ($member->isPending())
                            <div class="alert alert-info mt" style="margin-bottom:0">
                                Send this invite link to {{ $member->email }}:
                                <div class="row mt" style="margin-top:6px">
                                    <code style="word-break:break-all;flex:1">{{ $member->inviteUrl() }}</code>
                                    <button type="button" class="btn btn-secondary btn-sm" data-copy="{{ $member->inviteUrl() }}">Copy link</button>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('team.update', $member) }}" class="mt">
                            @csrf @method('PUT')
                            <div class="small muted" style="font-weight:600">Permissions</div>
                            <div class="checks">
                                @foreach ($permissions as $key => $label)
                                    <label><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $member->permissions ?? [], true))> {{ $label }}</label>
                                @endforeach
                            </div>
                            <button class="btn btn-secondary btn-sm" type="submit">Save permissions</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="stack">
        <div class="card">
            <h2>Invite a staff member</h2>
            <form method="POST" action="{{ route('team.invite') }}">
                @csrf
                <div class="field">
                    <label for="email">Staff email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="staff@example.com">
                </div>
                <label>What can they use?</label>
                <div class="checks" style="flex-direction:column">
                    @foreach ($permissions as $key => $label)
                        <label><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, old('permissions', ['content']), true))> {{ $label }}</label>
                    @endforeach
                </div>
                <p class="help">Team, billing and payments always stay with you, the owner.</p>
                <div class="actions"><button class="btn" type="submit"><x-icon name="plus"/> Send invitation</button></div>
            </form>
        </div>

        <div class="card">
            <div class="card-head"><h2>Recent activity</h2><a href="{{ route('team.audit') }}">Full audit log</a></div>
            <ul class="list">
                @forelse ($logs as $log)
                    <li><span><strong>{{ $log->actor_name }}</strong> <span class="muted">{{ $log->description }}</span></span><span class="small muted nowrap">{{ $log->created_at->diffForHumans(short: true) }}</span></li>
                @empty
                    <li class="muted">No activity yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
