<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

final class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {email : Email akun yang akan dijadikan administrator}';

    protected $description = 'Grant the administrator role to an existing user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if ($user === null) {
            $this->error('Pengguna dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }

        $user->update(['role' => UserRole::Admin->value]);
        $this->info("{$user->email} sekarang memiliki peran administrator.");

        return self::SUCCESS;
    }
}
