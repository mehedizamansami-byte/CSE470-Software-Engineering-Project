@extends('layouts.app')
@section('title', 'Users | BrandBondhu')

@section('content')
<div class="container">
    <div class="card wide">
        <h1>All users</h1>
        <p class="sub">Admin only.</p>
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td><span class="badge">{{ $u->role }}</span></td>
                    <td>{{ $u->is_active ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
