<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Create an administrator account for the Busia 2027 dashboard';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Administrator name'));
        $email = strtolower(trim((string) $this->ask('Administrator email')));
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make(
            compact('name', 'email', 'password') + ['password_confirmation' => $confirmation],
            ['name' => ['required', 'max:100'], 'email' => ['required', 'email', 'max:254', 'unique:users,email'], 'password' => ['required', 'confirmed', Password::min(12)]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }
        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = $password;
        $user->is_admin = true;
        $user->save();
        $this->info('Administrator created. Sign in at '.url('/admin/login'));

        return self::SUCCESS;
    }
}
