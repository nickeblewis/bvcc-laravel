<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\MembershipTier;
use App\Models\User;
use App\Services\Memberships;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * F-50: a member signs up. They get a member record, a login with the Member
 * role, and a membership on the tier they chose (pending until paid, unless
 * the tier is free).
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private Memberships $memberships) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $data = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules(),
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'town' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:20'],
            'paddle_uk_number' => ['nullable', 'string', 'max:50'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'emergency_name' => ['required', 'string', 'max:255'],
            'emergency_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['required', 'string', 'max:50'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'photo_consent' => ['boolean'],
            'membership_tier_id' => ['required', Rule::exists(MembershipTier::class, 'id')->where('on_sale', true)],
            'password' => $this->passwordRules(),
        ], attributes: [
            'emergency_name' => 'emergency contact name',
            'emergency_phone' => 'emergency contact phone',
            'membership_tier_id' => 'membership',
        ])->validate();

        return DB::transaction(function () use ($data): User {
            $member = Member::create([
                ...collect($data)->only([
                    'first_name', 'last_name', 'email', 'date_of_birth', 'phone',
                    'address_line_1', 'address_line_2', 'town', 'postcode',
                    'paddle_uk_number', 'experience', 'medical_notes',
                ])->all(),
                'emergency_contacts' => [array_filter([
                    'name' => $data['emergency_name'],
                    'relationship' => $data['emergency_relationship'] ?? null,
                    'phone' => $data['emergency_phone'],
                ])],
                'photo_consent' => (bool) ($data['photo_consent'] ?? false),
            ]);

            $this->memberships->create($member, MembershipTier::whereKey($data['membership_tier_id'])->firstOrFail());

            return User::create([
                'name' => $member->name,
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Member,
                'member_id' => $member->id,
            ]);
        });
    }
}
