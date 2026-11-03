<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Выдаёт или забирает доступ к админке.
 *
 * Только из консоли сервера: из интерфейса стать администратором
 * нельзя.
 */
class GrantAdmin extends Command
{
    protected $signature = 'user:admin
        {email : Почта пользователя}
        {--revoke : Забрать доступ}';

    protected $description = 'Выдаёт доступ к админке';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Нет пользователя с такой почтой.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->info($user->is_admin
            ? "{$user->email} теперь администратор: /admin"
            : "{$user->email} больше не администратор.");

        return self::SUCCESS;
    }
}
