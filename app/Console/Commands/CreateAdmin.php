<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[AsCommand(name: 'fco:create-admin', description: 'Create or promote a staff account securely (password is prompted, never seeded)')]
class CreateAdmin extends Command
{
    protected $signature = 'fco:create-admin {email} {--name=} {--role=super-admin}';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $role = Role::where('slug', $this->option('role'))->first();
        if (! $role) {
            $this->error('Unknown role. Run php artisan db:seed first.');

            return self::FAILURE;
        }

        $password = $this->secret('Password (min 12 chars, mixed case and numbers)');
        $validator = Validator::make(['password' => $password], ['password' => ['required', Password::min(12)->mixedCase()->numbers()]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $this->option('name') ?: $email,
            'password' => $password,
            'is_staff' => true,
            'status' => User::STATUS_ACTIVE,
        ]);
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->info("Staff account {$email} now has role {$role->name}. Enable two-factor authentication from the admin profile page.");

        return self::SUCCESS;
    }
}
