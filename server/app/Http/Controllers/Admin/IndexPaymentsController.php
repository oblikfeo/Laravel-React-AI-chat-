<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PaymentResource;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexPaymentsController extends Controller
{
    private const STATUSES = [
        Payment::STATUS_SUCCEEDED,
        Payment::STATUS_PENDING,
        Payment::STATUS_CANCELLED,
        Payment::STATUS_FAILED,
    ];

    /**
     * История платежей и кто сколько заплатил за всё время.
     */
    public function __invoke(Request $request): Response
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;

        $payments = Payment::query()
            ->with('user')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $top = Payment::query()
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->selectRaw('user_id, currency, SUM(amount) as amount, COUNT(*) as payments')
            ->groupBy('user_id', 'currency')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $users = User::whereIn('id', $top->pluck('user_id'))->get()->keyBy('id');

        return Inertia::render('Admin/Payments', [
            'payments' => PaymentResource::collection($payments),
            'filters' => ['status' => $status],
            'statuses' => self::STATUSES,
            'topPayers' => $top
                ->filter(fn ($line) => $users->has($line->user_id))
                ->map(fn ($line) => [
                    'id' => $line->user_id,
                    'name' => $users[$line->user_id]->name,
                    'email' => $users[$line->user_id]->email,
                    'amount' => (int) $line->amount,
                    'currency' => $line->currency,
                    'payments' => (int) $line->payments,
                ])
                ->values(),
        ]);
    }
}
