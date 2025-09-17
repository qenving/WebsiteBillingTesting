<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\LoginActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'admin']);
    }

    public function index(): View
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $lastSixMonths = $now->copy()->subMonths(5)->startOfMonth();

        $stats = [
            'total_clients' => Client::count(),
            'new_clients_30_days' => Client::where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'active_services' => Service::where('status', 'active')->count(),
            'pending_services' => Service::where('status', 'pending')->count(),
            'suspended_services' => Service::where('status', 'suspended')->count(),
            'revenue_month' => Invoice::where('status', 'paid')->where('paid_at', '>=', $startOfMonth)->sum('total'),
            'revenue_30_days' => Invoice::where('status', 'paid')->where('paid_at', '>=', $now->copy()->subDays(30))->sum('total'),
            'pending_invoices' => Invoice::whereIn('status', ['unpaid', 'overdue'])->count(),
            'overdue_amount' => Invoice::where('status', 'overdue')->sum('total'),
            'pending_manual_payments' => Payment::where('gateway', 'manual')->where('status', 'pending')->count(),
            'open_tickets' => Ticket::where('status', '!=', 'closed')->count(),
        ];

        $revenueSeries = $this->revenueSeries($lastSixMonths, $now);
        $upcomingRenewals = Service::with(['client.user', 'product'])
            ->where('status', 'active')
            ->whereNotNull('next_due_date')
            ->orderBy('next_due_date')
            ->limit(6)
            ->get();

        $recentInvoices = Invoice::with(['client.user'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $recentPayments = Payment::with(['invoice.client.user'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $recentTickets = Ticket::with(['client.user'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();

        $loginActivities = LoginActivity::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'revenueLabels' => $revenueSeries['labels'],
            'revenueValues' => $revenueSeries['values'],
            'upcomingRenewals' => $upcomingRenewals,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'recentTickets' => $recentTickets,
            'loginActivities' => $loginActivities,
        ]);
    }

    protected function revenueSeries(Carbon $from, Carbon $to): array
    {
        $paidInvoices = Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from->copy(), $to->copy()])
            ->get()
            ->groupBy(function (Invoice $invoice) {
                return $invoice->paid_at->format('Y-m');
            })
            ->map(function (Collection $group) {
                return round($group->sum('total'), 2);
            });

        $labels = [];
        $values = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->translatedFormat('M Y');
            $values[] = $paidInvoices->get($key, 0.0);
            $cursor->addMonth();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
