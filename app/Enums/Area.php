<?php

namespace App\Enums;

/**
 * Parts of the admin a staff role can be given access to.
 */
enum Area: string
{
    case Members = 'members';
    case Medical = 'medical';
    case Memberships = 'memberships';
    case Events = 'events';
    case Bookings = 'bookings';
    case Payments = 'payments';
    case Content = 'content';
    case Enquiries = 'enquiries';
    case Settings = 'settings';
}
