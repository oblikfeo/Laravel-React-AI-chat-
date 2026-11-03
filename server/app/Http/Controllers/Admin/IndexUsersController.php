<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\Usage\UsageMeter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexUsersController extends Controller
{
    /**
     * Пользователи: тариф, сколько заплатили и сколько бюджета
     * израсходовали. Поиск по имени и почте.
     */
    public function __invoke(Request $request, UsageMeter $meter): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('email', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->withSum(['payments as paid' => fn ($query) => $query->where('status', Payment::STATUS_SUCCEEDED)], 'amount')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->planName(),
                'isAdmin' => $user->isAdmin(),
                'joinedAt' => $user->created_at?->format('j M Y'),
                'paid' => (int) $user->paid,
                'usage' => $meter->snapshot($user)->toAdminArray(),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => ['q' => $search],
            'currency' => config('billing.currency'),
        ]);
    }
}
