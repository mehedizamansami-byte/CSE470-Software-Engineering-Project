@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="page-head">
    <div>
        <h1>All users</h1>
        <p class="sub">Admin only. Change roles here; collaborators are managed by their sellers.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.roles') }}"><x-icon name="shield"/> Roles &amp; permissions</a>
</div>

<div class="card">
    <div class="tabs">
        <a class="tab {{ $role ? '' : 'active' }}" href="{{ route('admin.users') }}">All</a>
        @foreach (\App\Models\User::ROLES as $r)
            <a class="tab {{ $role === $r ? 'active' : '' }}" href="{{ route('admin.users', ['role' => $r]) }}">{{ ucfirst($r) }}s</a>
        @endforeach
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Status</th><th>Change role</th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td>{{ $u->email }}</td>
                    <td><span class="badge">{{ ucfirst($u->role) }}</span></td>
                    <td>{{ $u->email_verified_at ? 'Yes' : 'No' }}</td>
                    <td>
                        @if (! $u->is_active) <span class="badge badge-red">Suspended</span>
                        @elseif ($u->deactivated_at) <span class="badge badge-gray">Deactivated</span>
                        @else <span class="badge badge-green">Active</span> @endif
                    </td>
                    <td>
                        @if ($u->id === auth()->id())
                            <span class="muted small">You</span>
                        @elseif ($u->role === 'collaborator')
                            <span class="muted small">Managed by seller</span>
                        @else
                            <form method="POST" action="{{ route('admin.users.role', $u) }}" class="row" style="flex-wrap:nowrap">
                                @csrf @method('PUT')
                                <select name="role" style="width:auto;padding:6px 8px">
                                    @foreach (['seller', 'designer', 'admin'] as $r) <option value="{{ $r }}" @selected($u->role === $r)>{{ ucfirst($r) }}</option> @endforeach
                                </select>
                                <button class="btn btn-secondary btn-sm" type="submit">Save</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt">{{ $users->links('partials.pagination') }}</div>
</div>
@endsection
