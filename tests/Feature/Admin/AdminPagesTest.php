<?php

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Member;
use Database\Seeders\ClubSeeder;
use Database\Seeders\DemoSeeder;

// F-30: the admin panel, its roles and its pages.

beforeEach(function () {
    $this->seed(ClubSeeder::class);
    $this->seed(DemoSeeder::class);
});

it('sends visitors who are not logged in to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('refuses a login that has no staff role', function () {
    $this->actingAs(staff()->forceFill(['role' => null]))->get('/admin')->assertForbidden();
});

it('shows every admin page to an administrator', function (string $path) {
    $this->actingAs(staff())->get($path)->assertOk();
})->with([
    '/admin', '/admin/calendar', '/admin/calendar?mode=year',
    '/admin/members', '/admin/members/create', '/admin/memberships', '/admin/payments',
    '/admin/events', '/admin/events/create', '/admin/bookings', '/admin/venues', '/admin/venues/create', '/admin/event-categories',
    '/admin/articles', '/admin/articles/create', '/admin/disciplines', '/admin/disciplines/create',
    '/admin/pages', '/admin/pages/create', '/admin/documents', '/admin/faqs', '/admin/committee-roles',
    '/admin/article-categories', '/admin/media-items', '/admin/redirects',
    '/admin/enquiries', '/admin/hire-requests',
    '/admin/membership-tiers', '/admin/fee-rates', '/admin/users',
]);

it('shows the edit pages and the register for a record', function () {
    $event = Event::whereHas('bookings')->firstOrFail();
    $member = Member::whereHas('memberships')->firstOrFail();

    $this->actingAs(staff())
        ->get("/admin/events/{$event->id}/edit")->assertOk();
    $this->get("/admin/events/{$event->id}/register")->assertOk();
    $this->get("/admin/members/{$member->id}/edit")->assertOk();
});

it('limits each role to its own areas', function (UserRole $role, array $allowed, array $denied) {
    $this->actingAs(staff($role));

    foreach ($allowed as $path) {
        $this->get($path)->assertOk();
    }

    foreach ($denied as $path) {
        $this->get($path)->assertForbidden();
    }
})->with([
    'membership secretary' => [UserRole::Membership, ['/admin/members', '/admin/memberships', '/admin/payments'], ['/admin/events', '/admin/articles', '/admin/users']],
    'programme' => [UserRole::Events, ['/admin/events', '/admin/bookings', '/admin/calendar'], ['/admin/memberships', '/admin/users', '/admin/pages']],
    'treasurer' => [UserRole::Treasurer, ['/admin/payments', '/admin/bookings', '/admin/memberships'], ['/admin/events', '/admin/users']],
    'welfare' => [UserRole::Welfare, ['/admin/members', '/admin/bookings'], ['/admin/payments', '/admin/users', '/admin/events']],
    'leader' => [UserRole::Leader, ['/admin/events', '/admin/bookings'], ['/admin/members', '/admin/payments', '/admin/users']],
    'editor' => [UserRole::Editor, ['/admin/articles', '/admin/pages', '/admin/enquiries'], ['/admin/members', '/admin/events', '/admin/users']],
]);
