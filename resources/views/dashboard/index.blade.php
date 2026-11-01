@extends('layouts.app')

@section('title', $tab === 'tasks' ? 'Task management' : 'Overview')
@section('breadcrumb', $tab === 'tasks' ? 'Tasks' : 'Overview')

@section('content')
<div class="page-heading">
    <div>
        <h1>{{ $tab === 'tasks' ? 'Task management' : 'Good morning, '.auth()->user()->name }}</h1>
        <p>{{ $tab === 'tasks' ? 'Organize priorities and track your team’s progress.' : 'Here is an overview of your store and workspace.' }}</p>
    </div>
    @if ($tab === 'overview')
        <a class="button soft" href="{{ route('dashboard', ['tab' => 'tasks']) }}">＋ &nbsp; Add a task</a>
    @endif
</div>

@if ($canViewAnalytics && $tab === 'overview')
    <form class="card filter-bar" method="GET" action="{{ route('dashboard') }}" style="padding:13px 16px;margin-bottom:15px">
        <input type="hidden" name="tab" value="overview">
        <label class="filter-field">From <input type="date" name="from" value="{{ $analytics['from'] }}"></label>
        <label class="filter-field">To <input type="date" name="to" value="{{ $analytics['to'] }}"></label>
        <label class="filter-field">Status
            <select name="status">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected($analytics['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="button small" type="submit">Apply filters</button>
        <a class="button soft small" href="{{ route('dashboard') }}">Reset</a>
    </form>
    @php($stats = $analytics['stats'])
    <section class="stats-grid">
        <article class="card stat-card"><div class="stat-label">Total sales <span class="stat-icon">$</span></div><div class="stat-value">{{ number_format((float) ($stats->sales_total ?? 0), 0) }} <small style="font-size:11px;color:#8a91a4">EGP</small></div><div class="stat-foot">Completed orders in this period</div></article>
        <article class="card stat-card"><div class="stat-label">Total orders <span class="stat-icon">▤</span></div><div class="stat-value">{{ number_format((int) ($stats->orders_count ?? 0)) }}</div><div class="stat-foot">Orders matching the selected filters</div></article>
        <article class="card stat-card"><div class="stat-label">Total customers <span class="stat-icon">♙</span></div><div class="stat-value">{{ number_format((int) ($stats->customers_count ?? 0)) }}</div><div class="stat-foot">Customers with orders in this period</div></article>
        <article class="card stat-card"><div class="stat-label">Completed orders <span class="stat-icon">✓</span></div><div class="stat-value">{{ number_format((int) ($stats->completed_count ?? 0)) }}</div><div class="stat-foot">Orders completed in this period</div></article>
        <article class="card stat-card"><div class="stat-label">Cancelled orders <span class="stat-icon">×</span></div><div class="stat-value">{{ number_format((int) ($stats->cancelled_count ?? 0)) }}</div><div class="stat-foot">Orders cancelled in this period</div></article>
    </section>
    <section class="content-grid">
        <article class="card">
            <div class="card-heading"><div><h2>Sales overview</h2><p>Completed sales in the selected period</p></div><span class="badge completed">● Live</span></div>
            <div class="chart-wrap">
                @php($chartMax = max(1, max(array_column($analytics['chart'], 'sales'))))
                <svg class="chart" viewBox="0 0 760 220" role="img" aria-label="Daily sales chart">
                    <defs><linearGradient id="chartFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#6c60e3" stop-opacity=".19"/><stop offset="100%" stop-color="#6c60e3" stop-opacity="0"/></linearGradient></defs>
                    <line class="chart-grid" x1="25" y1="35" x2="745" y2="35"/><line class="chart-grid" x1="25" y1="90" x2="745" y2="90"/><line class="chart-grid" x1="25" y1="145" x2="745" y2="145"/><line class="chart-grid" x1="25" y1="200" x2="745" y2="200"/>
                    <polygon class="chart-area" points="25,200 @foreach ($analytics['chart'] as $point){{ $point['x'] }},{{ $point['y'] }} @endforeach 745,200"/>
                    <polyline class="chart-line" points="@foreach ($analytics['chart'] as $point){{ $point['x'] }},{{ $point['y'] }} @endforeach"/>
                    @foreach ($analytics['chart'] as $point)<circle class="chart-dot" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4"><title>{{ $point['label'] }} · {{ number_format($point['sales'], 0) }} EGP</title></circle>@endforeach
                </svg>
                <div class="chart-labels">@foreach ($analytics['chart'] as $point)<span>{{ $point['label'] }}</span>@endforeach</div>
            </div>
        </article>
        <article class="card">
            <div class="card-heading"><div><h2>Order status</h2><p>Orders matching the current filters</p></div></div>
            <div class="status-list">
                @foreach ([
                    ['label' => 'Completed', 'count' => (int) ($stats->completed_count ?? 0), 'dot' => 'green'],
                    ['label' => 'Pending', 'count' => (int) ($stats->pending_count ?? 0), 'dot' => 'amber'],
                    ['label' => 'Processing', 'count' => (int) ($stats->processing_count ?? 0), 'dot' => 'blue'],
                    ['label' => 'Cancelled', 'count' => (int) ($stats->cancelled_count ?? 0), 'dot' => 'red'],
                ] as $statusRow)
                    <div><div class="status-row"><span class="status-name"><i class="dot {{ $statusRow['dot'] }}"></i>{{ $statusRow['label'] }}</span><span class="status-count">{{ $statusRow['count'] }}</span></div><div class="progress"><span style="width:{{ $stats->orders_count ? min(100, $statusRow['count'] / $stats->orders_count * 100) : 0 }}%"></span></div></div>
                @endforeach
            </div>
        </article>
    </section>
    <article class="card comparison-card section-gap">
        <div class="card-heading"><div><h2>Sales comparison</h2><p>Completed sales compared with the previous {{ $analytics['comparison']['period_days'] }}-day period</p></div></div>
        <div class="comparison-values">
            <div><span>Selected period</span><strong>{{ number_format($analytics['comparison']['current'], 0) }} EGP</strong></div>
            <div><span>Previous period ({{ $analytics['comparison']['previous_from'] }} – {{ $analytics['comparison']['previous_to'] }})</span><strong>{{ number_format($analytics['comparison']['previous'], 0) }} EGP</strong></div>
            <div><span>Change</span><strong class="{{ ($analytics['comparison']['change_percent'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $analytics['comparison']['change_percent'] === null ? 'No previous sales' : number_format($analytics['comparison']['change_percent'], 1).'% '.($analytics['comparison']['change_percent'] >= 0 ? 'increase' : 'decrease') }}</strong></div>
        </div>
    </article>
    <section class="content-grid section-gap">
        <article class="card">
            <div class="card-heading"><div><h2>Orders chart</h2><p>Order count per day for the selected filters</p></div></div>
            <div class="chart-wrap">
                <svg class="chart" viewBox="0 0 760 220" role="img" aria-label="Daily order count chart">
                    <line class="chart-grid" x1="25" y1="35" x2="745" y2="35"/><line class="chart-grid" x1="25" y1="90" x2="745" y2="90"/><line class="chart-grid" x1="25" y1="145" x2="745" y2="145"/><line class="chart-grid" x1="25" y1="200" x2="745" y2="200"/>
                    <polyline class="chart-line orders-chart-line" points="@foreach ($analytics['orders_chart'] as $point){{ $point['x'] }},{{ $point['y'] }} @endforeach"/>
                    @foreach ($analytics['orders_chart'] as $point)<circle class="chart-dot" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4"><title>{{ $point['label'] }} · {{ $point['count'] }} orders</title></circle>@endforeach
                </svg>
                <div class="chart-labels">@foreach ($analytics['orders_chart'] as $point)<span>{{ $point['label'] }}</span>@endforeach</div>
            </div>
        </article>
        <article class="card">
            <div class="card-heading"><div><h2>Monthly revenue</h2><p>Completed sales for the last six months</p></div></div>
            <div class="chart-wrap">
                <svg class="chart" viewBox="0 0 760 220" role="img" aria-label="Monthly revenue chart">
                    <line class="chart-grid" x1="25" y1="35" x2="745" y2="35"/><line class="chart-grid" x1="25" y1="90" x2="745" y2="90"/><line class="chart-grid" x1="25" y1="145" x2="745" y2="145"/><line class="chart-grid" x1="25" y1="200" x2="745" y2="200"/>
                    <polyline class="chart-line monthly-chart-line" points="@foreach ($analytics['monthly_revenue'] as $month){{ $month['x'] }},{{ $month['y'] }} @endforeach"/>
                    @foreach ($analytics['monthly_revenue'] as $month)<circle class="chart-dot monthly-chart-dot" cx="{{ $month['x'] }}" cy="{{ $month['y'] }}" r="4"><title>{{ $month['label'] }} · {{ number_format($month['revenue'], 0) }} EGP</title></circle>@endforeach
                </svg>
                <div class="chart-labels">@foreach ($analytics['monthly_revenue'] as $month)<span>{{ $month['label'] }}</span>@endforeach</div>
            </div>
        </article>
    </section>
    <section class="lower-grid">
        <article class="card">
            <div class="card-heading"><div><h2>Top-selling products</h2><p>Best performers from completed orders</p></div></div>
            @forelse ($analytics['products'] as $product)
                @php($topUnits = max(1, (int) $analytics['products']->max('units_sold')))
                <div class="product-row">
                    <div class="product-art">✦</div>
                    <div class="product-name">{{ $product->name }}<small>{{ $product->category }}</small><div class="progress"><span style="width:{{ min(100, $product->units_sold / $topUnits * 100) }}%"></span></div></div>
                    <div class="product-sales"><strong>{{ number_format($product->revenue, 0) }} EGP</strong>{{ $product->units_sold }} units</div>
                </div>
            @empty
                <div class="empty">No completed sales in this period.</div>
            @endforelse
        </article>
        <article class="card">
            <div class="card-heading"><div><h2>New task</h2><p>Add a task for yourself or your team</p></div></div>
            <form class="task-form" method="POST" action="{{ route('tasks.store') }}">
                @csrf
                <input name="title" placeholder="Task title" maxlength="180" required>
                <textarea name="description" placeholder="Short description (optional)" maxlength="2000"></textarea>
                <div class="task-form-row"><input type="date" name="due_date" aria-label="Task deadline"><button class="button" type="submit">Add task</button></div>
            </form>
            <div class="card-heading"><div><h2>Upcoming tasks</h2><p>What is next in your workspace</p></div><a class="button soft small" href="{{ route('dashboard', ['tab' => 'tasks']) }}">View all</a></div>
            @forelse ($tasks->take(3) as $task)
                <div class="task-item"><div class="task-detail"><div class="task-title">{{ $task->title }}</div><div class="task-meta">{{ $task->due_date ? 'Due: '.$task->due_date->format('m/d/Y') : 'No deadline' }}</div></div><span class="badge {{ $task->status }}">{{ ['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'][$task->status] }}</span></div>
            @empty
                <div class="empty">No tasks yet.</div>
            @endforelse
        </article>
    </section>
    <article class="card section-gap">
        <div class="card-heading"><div><h2>Recent orders</h2><p>Latest orders matching the selected filters</p></div><a class="button soft small" href="{{ route('orders.index', array_filter(['from' => $analytics['from'], 'to' => $analytics['to'], 'status' => $analytics['status']])) }}">Manage orders</a></div>
        <div class="table-wrap">
            <table><thead><tr><th>Order</th><th>Customer</th><th>Products</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>
                @forelse ($analytics['orders'] as $order)
                    <tr><td class="table-primary">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</td><td>{{ $order->customer?->name ?? 'Customer' }}</td><td>{{ $order->items->pluck('product.name')->filter()->join(', ') ?: '—' }}</td><td>{{ $order->ordered_at->format('m/d/Y') }}</td><td class="table-primary">{{ number_format((float) $order->total, 0) }} EGP</td><td><span class="badge {{ $order->status }}">● {{ ['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'][$order->status] ?? $order->status }}</span></td></tr>
                @empty
                    <tr><td colspan="6" class="empty">No orders match these filters.</td></tr>
                @endforelse
            </tbody></table>
        </div>
    </article>
@elseif ($tab === 'tasks')
    <section class="content-grid">
        <article class="card">
            <div class="card-heading"><div><h2>Task list</h2><p>Your tasks and team tasks available to your role</p></div><span class="badge processing">{{ $tasks->total() }} tasks</span></div>
            @forelse ($tasks as $task)
                <div class="task-item">
                    <div class="task-detail"><div class="task-title">{{ $task->title }}</div><div class="task-description">{{ $task->description ?: 'No task description.' }}</div><div class="task-meta">{{ $task->due_date ? 'Due: '.$task->due_date->format('m/d/Y') : 'No deadline' }} · {{ $task->user?->name }}</div></div>
                    <div class="task-actions">
                        <details class="task-edit">
                            <summary>Edit</summary>
                            <form class="task-edit-form" method="POST" action="{{ route('tasks.update', $task) }}">
                                @csrf @method('PATCH')
                                <input name="title" value="{{ $task->title }}" maxlength="180" required aria-label="Task title">
                                <textarea name="description" maxlength="2000" aria-label="Task description">{{ $task->description }}</textarea>
                                <input type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}" aria-label="Deadline">
                                <select name="status" aria-label="Task status">
                                    @foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $value => $label)<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endforeach
                                </select>
                                <button class="button small" type="submit">Save changes</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('tasks.update', $task) }}">@csrf @method('PATCH')
                            <select name="status" aria-label="Task status" onchange="this.form.submit()">
                                @foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $value => $label)<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">@csrf @method('DELETE')<button class="button danger small" type="submit">Delete</button></form>
                    </div>
                </div>
            @empty
                <div class="empty">No tasks yet. Add your first task using the form.</div>
            @endforelse
            <div style="padding:15px 18px">{{ $tasks->links() }}</div>
        </article>
        <article class="card">
            <div class="card-heading"><div><h2>Create a task</h2><p>Add a clear description and deadline</p></div></div>
            <form class="task-form" method="POST" action="{{ route('tasks.store') }}">
                @csrf
                <input name="title" placeholder="Task title" maxlength="180" required>
                <textarea name="description" placeholder="Short description (optional)" maxlength="2000"></textarea>
                <label style="font-size:10px;color:#8790a3">Deadline</label><input type="date" name="due_date">
                <button class="button" type="submit">Add task</button>
            </form>
        </article>
    </section>
@else
    <section class="card" style="padding:24px">
        <h2 style="margin:0 0 8px;font-size:16px">Your workspace is ready</h2>
        <p style="font-size:12px;color:#8a91a4">Store analytics are available to administrators and managers. You can manage your tasks from the tasks page.</p>
        <a class="button" href="{{ route('dashboard', ['tab' => 'tasks']) }}">Go to tasks</a>
    </section>
@endif
@endsection
