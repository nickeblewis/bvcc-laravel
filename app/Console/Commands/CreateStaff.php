<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('bvcc:staff')]
#[Description('Create a staff login for the admin panel (use this for the first administrator)')]
class CreateStaff extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => text('Name', required: true),
            'email' => text('Email', required: true),
            'role' => select('Role', collect(UserRole::cases())->mapWithKeys(fn (UserRole $r) => [$r->value => $r->getLabel()])->all(), UserRole::Admin->value),
            'password' => password('Password', required: true),
        ];

        $validator = Validator::make($data, [
            'email' => ['email', 'unique:users,email'],
            'password' => [Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->error(implode(' ', $validator->errors()->all()));

            return self::FAILURE;
        }

        User::create([...$data, 'email_verified_at' => now()]);

        $this->info('Created. They can sign in at '.url('/admin'));

        return self::SUCCESS;
    }
}
