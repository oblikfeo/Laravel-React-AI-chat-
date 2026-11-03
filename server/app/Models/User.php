<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Тариф по умолчанию задан и в базе, и здесь: иначе у только что
     * созданной модели поле остаётся пустым до перечитывания из базы.
     */
    protected $attributes = [
        'plan' => 'free',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'plan',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Чаты пользователя, свежие сверху.
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class)->latest('last_message_at');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class)->latest('id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Действующая подписка, если есть.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()->active()->first();
    }

    /**
     * Показывать ли предложение перейти на платный тариф.
     */
    public function isPromotedPlan(): bool
    {
        return in_array($this->plan, config('plans.promoted'), true);
    }

    /**
     * Платный ли тариф.
     */
    public function hasPaidPlan(): bool
    {
        return ! $this->isPromotedPlan();
    }

    /**
     * Название тарифа для интерфейса.
     */
    public function planName(): string
    {
        return config("plans.list.{$this->plan}.name", 'Free');
    }

    /**
     * Сколько покрывает тариф, см. usage в config/plans.php.
     *
     * @return array{allowance: float, daily_share: float|null, rollover: int}
     */
    public function planUsage(): array
    {
        $usage = config("plans.list.{$this->plan}.usage")
            ?? config('plans.list.'.config('plans.default').'.usage');

        return [
            'allowance' => (float) $usage['allowance'],
            'daily_share' => isset($usage['daily_share']) ? (float) $usage['daily_share'] : null,
            'rollover' => max(1, (int) ($usage['rollover'] ?? 1)),
        ];
    }

    /** Есть ли на тарифе видео. */
    public function hasVideo(): bool
    {
        return (bool) config("plans.list.{$this->plan}.video", false);
    }
}
