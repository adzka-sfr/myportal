<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeSuperadmin extends Command
{
    protected $signature = 'portal:make-superadmin {name?} {email?}';
    protected $description = 'Create or promote a portal Superadmin account';

    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('Nama Superadmin');
        $email = $this->argument('email') ?: $this->ask('Email Superadmin');
        if (! is_string($name) || trim($name) === '' || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Nama wajib diisi dan email harus valid.');
            return self::FAILURE;
        }

        $password = $this->secret('Kata sandi (minimal 12 karakter)');
        $confirmation = $this->secret('Ulangi kata sandi');
        if (! is_string($password) || strlen($password) < 12 || ! hash_equals($password, (string) $confirmation)) {
            $this->error('Kata sandi tidak cocok atau kurang dari 12 karakter.');
            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => Str::lower(trim($email))],
            ['name' => trim($name), 'password' => $password, 'is_superadmin' => true, 'is_active' => true],
        );
        $this->info("Superadmin siap: {$user->email}");

        return self::SUCCESS;
    }
}
