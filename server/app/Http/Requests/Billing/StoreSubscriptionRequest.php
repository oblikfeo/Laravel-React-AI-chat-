<?php

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        // Бесплатный тариф оплатить нельзя, поэтому он исключён.
        $paid = array_keys(array_diff_key(
            config('plans.list'),
            [config('plans.default') => true],
        ));

        return [
            'plan' => ['required', Rule::in($paid)],
            'period' => ['required', Rule::in([
                Subscription::PERIOD_MONTHLY,
                Subscription::PERIOD_YEARLY,
            ])],
        ];
    }
}
