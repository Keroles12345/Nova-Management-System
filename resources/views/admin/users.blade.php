@extends('layouts.app')

@section('title', 'User management')
@section('breadcrumb', 'User management')

@section('content')
<div class="page-heading">
    <div><h1>User management</h1><p>Manage workspace roles and access permissions.</p></div>
</div>
<section class="card">
    <div class="card-heading"><div><h2>Team members</h2><p>{{ $users->total() }} accounts</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="table-primary">{{ $user->name }}</td><td>{{ $user->email }}</td>
                        <td><span class="badge {{ $user->role === 'admin' ? 'processing' : ($user->role === 'manager' ? 'completed' : 'pending') }}">{{ ['admin' => 'Administrator', 'manager' => 'Manager', 'user' => 'User'][$user->role] }}</span></td>
                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="table-actions">
                                @if (! $user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}">@csrf @method('PATCH')
                                        <select name="role" aria-label="Role for {{ $user->name }}" onchange="this.form.submit()">
                                            @foreach (['user' => 'User', 'manager' => 'Manager', 'admin' => 'Administrator'] as $value => $label)<option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>@endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="button danger small" type="submit">Delete</button></form>
                                @else
                                    <span class="table-sub">Current account</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:15px 18px">{{ $users->links() }}</div>
</section>
@endsection
