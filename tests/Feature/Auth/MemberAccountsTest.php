<?php

use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Widgets\ClubStats;
use App\Filament\Widgets\MembershipsToChase;
use App\Filament\Widgets\MyMembershipSummary;
use App\Models\Member;
use App\Models\MembershipTier;
use App\Models\User;
use App\Services\Memberships;
use Database\Seeders\ClubSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Livewire\Livewire;

// F-50: member accounts. See docs/members.md.

beforeEach(fn () => $this->seed(ClubSeeder::class));

/**
 * A member's login, linked to a member record with a membership.
 */
function memberLogin(string $tier = 'adult-pay-as-you-paddle'): User
{
    $member = Member::factory()->create();
    app(Memberships::class)->create($member, MembershipTier::where('slug', $tier)->firstOrFail());

    return User::factory()->create(['role' => UserRole::Member, 'member_id' => $member->id]);
}

/**
 * @return array<string, mixed>
 */
function registration(array $overrides = []): array
{
    return [
        'first_name' => 'Alex',
        'last_name' => 'Rivers',
        'email' => 'alex@example.com',
        'date_of_birth' => '1990-05-04',
        'phone' => '07700 900123',
        'address_line_1' => '1 Canal Walk',
        'town' => 'Farnborough',
        'postcode' => 'GU14 7AA',
        'emergency_name' => 'Sam Rivers',
        'emergency_relationship' => 'Partner',
        'emergency_phone' => '07700 900456',
        'medical_notes' => 'Asthma, carries inhaler',
        'photo_consent' => true,
        'membership_tier_id' => MembershipTier::where('slug', 'adult-pay-as-you-paddle')->value('id'),
        'password' => 'a-long-test-password',
        'password_confirmation' => 'a-long-test-password',
        ...$overrides,
    ];
}

it('shows the login page in the site layout, linking to register and forgotten password', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<title>Log in | '.config('site.name').'</title>', false)
        ->assertSee('noindex', false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('site/account/Login')
            ->where('canResetPassword', true));
});

it('shows the register page with the membership tiers on sale', function () {
    MembershipTier::where('slug', 'affiliate')->update(['on_sale' => false]);

    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('site/account/Register')
            ->has('tiers', MembershipTier::where('on_sale', true)->count())
            ->where('tiers.0.name', 'Non-member'));
});

it('creates a member, their login and a pending membership on a paid tier', function () {
    Notification::fake();

    $this->post('/register', registration())->assertRedirect('/dashboard');

    $user = User::where('email', 'alex@example.com')->sole();
    expect($user->role)->toBe(UserRole::Member)
        ->and($user->name)->toBe('Alex Rivers')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $member = $user->member;
    expect($member->getAttribute('postcode'))->toBe('GU14 7AA')
        ->and($member->emergency_contacts)->toBe([['name' => 'Sam Rivers', 'relationship' => 'Partner', 'phone' => '07700 900456']])
        ->and($member->getAttribute('photo_consent'))->toBeTrue();

    $membership = $member->memberships()->sole();
    expect($membership->status)->toBe(MembershipStatus::Pending)
        ->and($membership->price_pence)->toBe(3500);

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('makes a free membership active straight away', function () {
    $this->post('/register', registration([
        'membership_tier_id' => MembershipTier::where('slug', 'non-member')->value('id'),
    ]));

    expect(User::where('email', 'alex@example.com')->sole()->member->memberships()->sole()->status)
        ->toBe(MembershipStatus::Active);
});

it('stores emergency contacts and medical notes encrypted', function () {
    $this->post('/register', registration());

    $raw = DB::table('members')->where('email', 'alex@example.com')->first();
    expect($raw->medical_notes)->not->toContain('Asthma')
        ->and($raw->emergency_contacts)->not->toContain('Sam Rivers');
});

it('checks the sign-up form', function (array $overrides, string $field) {
    User::factory()->create(['email' => 'taken@example.com']);
    MembershipTier::where('slug', 'affiliate')->update(['on_sale' => false]);

    $tier = fn (string $slug) => MembershipTier::where('slug', $slug)->value('id');

    $this->post('/register', registration(array_map(fn ($v) => $v instanceof Closure ? $v($tier) : $v, $overrides)))
        ->assertSessionHasErrors($field);

    expect(Member::where('first_name', 'Alex')->exists())->toBeFalse();
})->with([
    'email already has a login' => [['email' => 'taken@example.com'], 'email'],
    'tier not on sale' => [['membership_tier_id' => fn ($tier) => $tier('affiliate')], 'membership_tier_id'],
    'no tier' => [['membership_tier_id' => null], 'membership_tier_id'],
    'no last name' => [['last_name' => ''], 'last_name'],
    'no emergency contact' => [['emergency_phone' => ''], 'emergency_phone'],
    'date of birth in the future' => [['date_of_birth' => '2999-01-01'], 'date_of_birth'],
    'passwords differ' => [['password_confirmation' => 'something-else-entirely'], 'password'],
]);

it('holds a member at email verification before the admin', function () {
    $user = memberLogin();
    $user->forceFill(['email_verified_at' => null])->save();

    $this->actingAs($user)->get('/admin')->assertRedirectContains('email-verification');
});

it('lets a member see their dashboard and their membership', function () {
    $user = memberLogin();

    $this->actingAs($user)->get('/admin')->assertOk();
    $this->get('/admin/my-membership')
        ->assertOk()
        ->assertSee('Adult: pay as you paddle')
        ->assertSee('Still to pay')
        ->assertSee($user->member->name);
});

it('keeps members out of the staff pages', function (string $path) {
    $this->actingAs(memberLogin())->get($path)->assertForbidden();
})->with([
    '/admin/members', '/admin/memberships', '/admin/payments', '/admin/events', '/admin/bookings',
    '/admin/calendar', '/admin/articles', '/admin/enquiries', '/admin/users',
]);

it('shows members only their own widget', function () {
    $this->actingAs(memberLogin());

    expect(MyMembershipSummary::canView())->toBeTrue()
        ->and(ClubStats::canView())->toBeFalse()
        ->and(MembershipsToChase::canView())->toBeFalse();

    $this->actingAs(staff());

    expect(MyMembershipSummary::canView())->toBeFalse()
        ->and(ClubStats::canView())->toBeTrue();
});

it('leaves member logins out of the staff logins list', function () {
    $member = memberLogin();
    $treasurer = staff(UserRole::Treasurer);

    Livewire::actingAs(staff())->test(ManageUsers::class)
        ->assertCanSeeTableRecords([$treasurer])
        ->assertCanNotSeeTableRecords([$member]);
});

it('emails a member a link to reset a forgotten password', function () {
    Notification::fake();
    $user = memberLogin();

    $this->get('/forgot-password')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('site/account/ForgotPassword'));

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->get("/reset-password/{$notification->token}?email={$user->email}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('site/account/ResetPassword'));

        return true;
    });
});

it('sends the old site\'s account addresses to the new ones', function (string $from, string $to) {
    $this->get($from)->assertRedirect($to);
})->with([
    ['/lostpassword/', '/forgot-password'],
    ['/membership-account/', '/admin'],
    ['/membership-account/membership-checkout/', '/register'],
]);
