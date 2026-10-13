<?php

use App\Mail\EnquiryMail;
use App\Mail\HireRequestMail;
use App\Models\CommitteeRole;
use App\Models\Enquiry;
use App\Models\HireRequest;
use Database\Seeders\ClubSeeder;
use Illuminate\Support\Facades\Mail;

// F-17 (hire request) and F-19 (contact form).

beforeEach(function () {
    $this->seed(ClubSeeder::class);
    Mail::fake();
});

it('saves an enquiry and emails the role inbox for its topic', function () {
    $polo = CommitteeRole::where('title', 'Canoe polo')->firstOrFail();

    $this->from('/contact')->post('/contact', [
        'name' => 'Alex',
        'email' => 'alex@example.com',
        'committee_role_id' => $polo->id,
        'message' => 'When is polo training this week?',
    ])->assertRedirect('/contact')->assertSessionHas('enquiry_sent');

    expect(Enquiry::sole()->committee_role_id)->toBe($polo->id);
    Mail::assertSent(EnquiryMail::class, fn ($mail) => $mail->hasTo('polo@bvcc.org.uk') && $mail->hasReplyTo('alex@example.com'));
});

it('sends an enquiry with no topic to the general inbox', function () {
    $this->post('/contact', ['name' => 'Alex', 'email' => 'alex@example.com', 'message' => 'Hello there, a question.']);

    Mail::assertSent(EnquiryMail::class, fn ($mail) => $mail->hasTo(config('site.email')));
});

it('rejects an enquiry with a missing name, a bad email or no message', function () {
    $this->post('/contact', ['name' => '', 'email' => 'not-an-email', 'message' => ''])
        ->assertSessionHasErrors(['name', 'email', 'message']);

    expect(Enquiry::count())->toBe(0);
    Mail::assertNothingSent();
});

it('will not route an enquiry to a role that is not offered as a topic', function () {
    $chair = CommitteeRole::where('title', 'Chair')->firstOrFail();

    $this->post('/contact', ['name' => 'Alex', 'email' => 'alex@example.com', 'committee_role_id' => $chair->id, 'message' => 'Hello there, a question.'])
        ->assertSessionHasErrors('committee_role_id');
});

it('quietly drops form posts from bots that fill in the hidden field', function () {
    $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy things now please', 'website' => 'https://spam.example'])
        ->assertSessionHas('enquiry_sent');

    expect(Enquiry::count())->toBe(0);
    Mail::assertNothingSent();
});

it('preselects a contact topic from the address', function () {
    $this->get('/contact?topic=memberships')->assertInertia(fn ($page) => $page
        ->where('selected', CommitteeRole::where('title', 'Memberships')->value('id')));
});

it('saves a hire request and emails the equipment team', function () {
    $this->from('/join/hire')->post('/join/hire', hireRequest())
        ->assertRedirect('/join/hire')->assertSessionHas('hire_sent');

    $hire = HireRequest::sole();

    expect($hire->items)->toBe(['Boat or board', 'Paddle'])->and($hire->status->value)->toBe('new');
    Mail::assertSent(HireRequestMail::class, fn ($mail) => $mail->hasTo(config('site.equipment_email')));
});

it('rejects a hire request that is too long, has no items or skips the terms', function (array $overrides, string $field) {
    $this->post('/join/hire', hireRequest($overrides))->assertSessionHasErrors($field);

    expect(HireRequest::count())->toBe(0);
})->with([
    'more than 4 weeks' => [['return_on' => now()->addDays(40)->toDateString()], 'return_on'],
    'returned before collected' => [['return_on' => now()->addDay()->toDateString()], 'return_on'],
    'no items' => [['items' => []], 'items'],
    'an item we do not have' => [['items' => ['Jet ski']], 'items.0'],
    'terms not accepted' => [['terms' => false], 'terms'],
    'collected in the past' => [['collect_on' => now()->subDay()->toDateString()], 'collect_on'],
]);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function hireRequest(array $overrides = []): array
{
    return [
        'name' => 'Sam',
        'email' => 'sam@example.com',
        'use_type' => 'personal',
        'collect_on' => now()->addDays(3)->toDateString(),
        'return_on' => now()->addDays(10)->toDateString(),
        'participants' => 2,
        'items' => ['Boat or board', 'Paddle'],
        'craft_type' => 'Touring kayak',
        'terms' => true,
        ...$overrides,
    ];
}
