<?php

/*
 * The club's identity. Read it with config('site.…'); never hard-code the
 * name, emails or social links in views, mail or components.
 */

return [
    'name' => 'Blackwater Valley Canoe Club',
    'short_name' => 'BVCC',
    'tagline' => 'Aiming to encourage participation and fun in paddlesports',
    'description' => 'A friendly, volunteer-run canoe and kayak club in Aldershot, Hampshire. Weekly sessions, whitewater and touring trips, canoe polo, coaching and club boats for ages 8 and up.',
    'founded' => 1986,
    'minimum_age' => 8,
    'locality' => 'Aldershot',
    'region' => 'Hampshire',
    'country' => 'GB',

    // The home venue's slug; used for the map pin and the club's schema.org address.
    'home_venue' => 'aldershot-lido',

    'email' => env('SITE_EMAIL', 'enquiries@bvcc.org.uk'),
    'bookings_email' => env('SITE_BOOKINGS_EMAIL', 'bookings@bvcc.org.uk'),
    'memberships_email' => env('SITE_MEMBERSHIPS_EMAIL', 'memberships@bvcc.org.uk'),
    'equipment_email' => env('SITE_EQUIPMENT_EMAIL', 'equipment@bvcc.org.uk'),

    'governing_body' => [
        'name' => 'Paddle UK',
        'url' => 'https://paddleuk.org.uk',
    ],

    'social' => [
        'facebook' => 'https://www.facebook.com/BlackwaterValleyCC',
        'facebook_group' => 'https://www.facebook.com/groups/BlackwaterCanoeClub',
        'x' => 'https://twitter.com/BVCanoeClub',
        'vimeo' => 'https://vimeo.com/channels/bvcc',
    ],

    // The WordPress site this one replaces; the importer reads from it.
    'legacy_url' => env('SITE_LEGACY_URL', 'https://bvcc.org.uk'),

    // How to pay, shown in reminder emails. Kept out of the code and the
    // public pages on purpose: set it in the environment.
    'payment_instructions' => env('SITE_PAYMENT_INSTRUCTIONS') ?: 'Reply to this email and we will send you the club bank details, or pay on arrival.',
];
