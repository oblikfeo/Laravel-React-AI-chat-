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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * Тариф по умолчанию задан и в базе, и здесь: иначе у только что
     * созданной модели поле остаётся пустым до перечитывания из базы.
     */
    protected $attributes = [
        'plan' => 'free',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'plan',
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
        ];
    }

    /**
     * Чаты пользователя, свежие сверху.
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class)->latest('last_message_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
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
     * Название тарифа для интерфейса.
     */
    public function planName(): string
    {
        return config("plans.list.{$this->plan}.name", 'Free');
    }
}
