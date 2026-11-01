<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $canViewAnalytics = in_array($user->role, ['admin', 'manager'], true);
        $tab = $request->query('tab', 'overview');
        abort_unless(in_array($tab, ['overview', 'tasks'], true), 404);

        $tasks = Task::query()
            ->when(! in_array($user->role, ['admin', 'manager'], true), fn (Builder $query) => $query->where('user_id', $user->id))
            ->with('user:id,name')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->latest('updated_at')
            ->paginate(8, ['*'], 'tasks_page');

        $analytics = null;

        if ($canViewAnalytics) {
            $filters = $request->validate([
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date', 'after_or_equal:from'],
                'status' => ['nullable', 'in:pending,processing,completed,cancelled'],
            ]);
            $from = Carbon::parse($filters['from'] ?? now()->subDays(6)->toDateString())->startOfDay();
            $to = Carbon::parse($filters['to'] ?? today()->toDateString())->endOfDay();
            $status = $filters['status'] ?? null;

            $ordersQuery = Order::query()
                ->with(['customer:id,name', 'items.product:id,name'])
                ->whereBetween('ordered_at', [$from, $to])
                ->when($status, fn (Builder $query) => $query->where('status', $status));

            $orders = (clone $ordersQuery)->latest('ordered_at')->limit(8)->get();
            $stats = (clone $ordersQuery)
                ->selectRaw('COUNT(*) as orders_count')
                ->selectRaw('COUNT(DISTINCT customer_id) as customers_count')
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as sales_total")
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
                ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
                ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                ->selectRaw("SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count")
                ->first();

            $dailySales = (clone $ordersQuery)
                ->selectRaw('DATE(ordered_at) as sales_day, SUM(CASE WHEN status = \'completed\' THEN total ELSE 0 END) as sales_total')
                ->groupBy('sales_day')
                ->pluck('sales_total', 'sales_day');

            $dailyOrders = (clone $ordersQuery)
                ->selectRaw('DATE(ordered_at) as orders_day, COUNT(*) as orders_count')
                ->groupBy('orders_day')
                ->pluck('orders_count', 'orders_day');

            $chart = [];
            $ordersChart = [];
            $maximumDailyOrders = max(1, (int) $dailyOrders->max());
            for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
                $sales = (float) ($dailySales[$day->toDateString()] ?? 0);
                $ordersCount = (int) ($dailyOrders[$day->toDateString()] ?? 0);
                $x = 35 + (count($chart) * (690 / max(1, $from->diffInDays($to))));
                $chart[] = [
                    'label' => $day->format('d/m'),
                    'sales' => $sales,
                    'x' => $x,
                    'y' => 195 - ($sales / max(1, (float) $dailySales->max()) * 155),
                ];
                $ordersChart[] = [
                    'label' => $day->format('d/m'),
                    'count' => $ordersCount,
                    'x' => $x,
                    'y' => 195 - ($ordersCount / $maximumDailyOrders * 155),
                ];
            }

            $comparisonPeriodDays = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
            $previousPeriodTo = $from->copy()->startOfDay()->subSecond();
            $previousPeriodFrom = $previousPeriodTo->copy()->subDays($comparisonPeriodDays - 1)->startOfDay();
            $currentPeriodSales = (float) Order::query()
                ->where('status', 'completed')
                ->whereBetween('ordered_at', [$from, $to])
                ->sum('total');
            $previousPeriodSales = (float) Order::query()
                ->where('status', 'completed')
                ->whereBetween('ordered_at', [$previousPeriodFrom, $previousPeriodTo])
                ->sum('total');

            $currentMonth = now()->startOfMonth();
            $firstRevenueMonth = $currentMonth->copy()->subMonths(5);
            $monthlyDailySales = Order::query()
                ->where('status', 'completed')
                ->whereBetween('ordered_at', [$firstRevenueMonth, $currentMonth->copy()->endOfMonth()])
                ->selectRaw('DATE(ordered_at) as sales_day, SUM(total) as sales_total')
                ->groupBy('sales_day')
                ->get();
            $monthlySalesByMonth = [];
            foreach ($monthlyDailySales as $dailySale) {
                $month = Carbon::parse($dailySale->sales_day)->format('Y-m');
                $monthlySalesByMonth[$month] = ($monthlySalesByMonth[$month] ?? 0) + (float) $dailySale->sales_total;
            }

            $monthlyRevenue = [];
            $maximumMonthlyRevenue = max(1, max($monthlySalesByMonth ?: [0]));
            for ($monthOffset = 0; $monthOffset < 6; $monthOffset++) {
                $month = $firstRevenueMonth->copy()->addMonths($monthOffset);
                $revenue = (float) ($monthlySalesByMonth[$month->format('Y-m')] ?? 0);
                $monthlyRevenue[] = [
                    'label' => $month->format('M'),
                    'revenue' => $revenue,
                    'x' => 35 + ($monthOffset * 138),
                    'y' => 195 - ($revenue / $maximumMonthlyRevenue * 155),
                ];
            }

            $products = \Illuminate\Support\Facades\DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.status', 'completed')
                ->whereBetween('orders.ordered_at', [$from, $to])
                ->select('products.id', 'products.name', 'products.category')
                ->selectRaw('SUM(order_items.quantity) as units_sold')
                ->selectRaw('SUM(order_items.quantity * order_items.unit_price) as revenue')
                ->groupBy('products.id', 'products.name', 'products.category')
                ->orderByDesc('units_sold')
                ->limit(5)
                ->get();

            $analytics = [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'status' => $status,
                'orders' => $orders,
                'stats' => $stats,
                'chart' => $chart,
                'orders_chart' => $ordersChart,
                'monthly_revenue' => $monthlyRevenue,
                'comparison' => [
                    'current' => $currentPeriodSales,
                    'previous' => $previousPeriodSales,
                    'period_days' => $comparisonPeriodDays,
                    'change_percent' => $previousPeriodSales > 0
                        ? (($currentPeriodSales - $previousPeriodSales) / $previousPeriodSales) * 100
                        : null,
                    'previous_from' => $previousPeriodFrom->toDateString(),
                    'previous_to' => $previousPeriodTo->toDateString(),
                ],
                'products' => $products,
            ];
        }

        return view('dashboard.index', compact('analytics', 'canViewAnalytics', 'tab', 'tasks'));
    }
}
