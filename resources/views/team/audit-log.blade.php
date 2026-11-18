@extends('layouts.app')
@section('title', 'Audit log')

@section('content')
<div class="page-head">
    <div>
        <h1>Audit log</h1>
        <p class="sub">Every important action in your workspace, by you and your collaborators.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('team.index') }}">Back to team</a>
</div>

<div class="card">
    <div class="tabs">
        <a class="tab {{ $area ? '' : 'active' }}" href="{{ route('team.audit') }}">All</a>
        @foreach ($areas as $key => $label)
            <a class="tab {{ $area === $key ? 'active' : '' }}" href="{{ route('team.audit', ['area' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP address</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="nowrap">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                    <td class="nowrap"><strong>{{ $log->actor_name }}</strong></td>
                    <td><span class="badge badge-gray">{{ $log->action }}</span></td>
                    <td>{{ $log->description }}</td>
                    <td class="small muted">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Nothing logged yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt">{{ $logs->links('partials.pagination') }}</div>
</div>
@endsection
