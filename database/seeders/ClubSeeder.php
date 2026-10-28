<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\FeeBand;
use App\Enums\FeeType;
use App\Enums\PageSection;
use App\Models\ArticleCategory;
use App\Models\CommitteeRole;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Faq;
use App\Models\FeeRate;
use App\Models\MembershipTier;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The club's real reference data, as published on bvcc.org.uk on 2026-10-13.
 * Safe to run again: rows are matched on their slug or natural key, and
 * anything an admin has since edited is left alone.
 */
class ClubSeeder extends Seeder
{
    public function run(): void
    {
        $this->tiers();
        $this->feeRates();
        $this->venues();
        $this->eventCategories();
        $this->disciplines();
        $this->committee();
        $this->pages();
        $this->faqs();
        $this->articleCategories();
        $this->events();
        $this->redirects();
    }

    private function tiers(): void
    {
        $adult = '18 or over and not in full-time education';
        $youth = 'Under 18, or in full-time education (16 or more hours a week)';

        $tiers = [
            ['Non-member', FeeBand::NonMember, 0, 0, 'Anyone', 'For new starters, or if you are not ready to buy a membership yet. It lets you paddle at club sessions and gives us your emergency contact details.', false, false],
            ['Youth or student: pay as you paddle', FeeBand::PayAsYouPaddle, 1500, 0, $youth, 'Reduced session and event fees, paid each time you come.', false, false],
            ['Youth or student: monthly subscriber', FeeBand::Subscriber, 3000, 1500, $youth, 'One monthly payment and no session or event club fees.', false, true],
            ['Adult: pay as you paddle', FeeBand::PayAsYouPaddle, 3500, 0, $adult, 'Reduced session and event fees, paid each time you come.', false, false],
            ['Adult: monthly subscriber', FeeBand::Subscriber, 5000, 1500, $adult, 'One monthly payment and no session or event club fees.', false, true],
            ['Family: pay as you paddle', FeeBand::PayAsYouPaddle, 5000, 0, 'Up to 2 adults and any number of youth or student members living at the same address', 'One membership for the whole household, with reduced session and event fees.', true, false],
            ['Adult: pay as you paddle with polo shed storage', FeeBand::PayAsYouPaddle, 6000, 0, $adult, 'Adult pay as you paddle membership plus storage for your boat in the polo shed.', false, false],
            ['Affiliate', FeeBand::Affiliate, 0, 0, 'Members of B3C or USCC', 'For members of our affiliate clubs.', false, false],
        ];

        foreach ($tiers as $sort => [$name, $band, $initial, $monthly, $eligibility, $description, $household, $toConfirm]) {
            MembershipTier::firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'fee_band' => $band,
                'initial_pence' => $initial,
                'monthly_pence' => $monthly,
                'eligibility' => $eligibility,
                'description' => $description,
                'covers_household' => $household,
                'max_adults' => $household ? 2 : null,
                // The old site gives two different subscriber prices.
                'to_confirm' => $toConfirm,
                'sort' => $sort,
            ]);
        }
    }

    private function feeRates(): void
    {
        $rates = [
            FeeBand::NonMember->value => [1000, 1000, 1500, 500],
            FeeBand::PayAsYouPaddle->value => [600, 600, 1000, null],
            FeeBand::Subscriber->value => [0, 0, 0, null],
            FeeBand::Affiliate->value => [600, 600, 1000, 500],
        ];

        foreach ($rates as $band => $amounts) {
            foreach (FeeType::standard() as $i => $type) {
                FeeRate::firstOrCreate(
                    ['fee_band' => $band, 'fee_type' => $type],
                    ['amount_pence' => $amounts[$i]],
                );
            }
        }
    }

    private function venues(): void
    {
        $venues = [
            ['Aldershot Lido', 'Our home, boat store and summer venue.', 'Guildford Road', 'Aldershot', 'GU12 4BP', "The club's home since 1986. A 3,600 m² outdoor pool filled with clean water, with changing rooms on site. It is a friendly place to learn: falling in is far more pleasant here than in a river.\n\nSummer sessions run here on Wednesday evenings from late May to early September. Club boats, paddles and buoyancy aids are stored on site.", true],
            ['Yateley Manor School pool', 'Warm indoor pool for winter sessions.', '51 Reading Road', 'Yateley', 'GU46 7UQ', 'An indoor pool we use on Wednesday evenings through the winter. Space is limited to about 10 paddlers on the water at a time, so please book.', true],
            ['Basingstoke Canal Centre', 'Flat-water paddling in spring and autumn.', 'Mytchett Place Road', 'Mytchett', 'GU16 6DD', 'Sheltered flat water on the Basingstoke Canal. We paddle the canal on Wednesday evenings in spring and autumn, launching here or from Farnborough Wharf, Ash Lock or Runways End. Check the event for the launch point.', true],
            ['Farnborough Wharf', 'Canal launch point for evening sessions.', 'Clubhouse Road', 'Aldershot', null, 'A launch point on the Basingstoke Canal used for evening canal sessions. Our affiliate club B3C kindly lends us its canal boat store.', true],
            ['Hawley Lake', 'Home of our canoe polo section.', 'Gibraltar Barracks', 'Camberley', 'GU17 9LP', 'Where the canoe polo section trains: up to four times a week in summer and at weekends in winter.', true],
            ['Whitehill & Bordon Leisure Centre', 'Winter canoe polo training.', null, 'Bordon', null, 'Indoor pool used for canoe polo training in winter.', true],
            ['Coxes Lock', 'Gentle moving water for taster sessions.', null, 'Addlestone', null, 'A gentle, safe piece of moving water on the River Wey Navigation. We run moving-water taster sessions here for paddlers taking their first steps towards whitewater.', true],
        ];

        foreach ($venues as $sort => [$name, $summary, $address, $town, $postcode, $description]) {
            Venue::firstOrCreate(['slug' => Str::slug($name)], compact('name', 'summary', 'address', 'town', 'postcode', 'description', 'sort'));
        }
    }

    private function eventCategories(): void
    {
        $categories = [
            ['Sessions', 'teal', 'Regular weekly paddling at the Lido, the pool or the canal. All levels welcome.'],
            ['Trips', 'blue', 'Days and weekends away: whitewater, touring, sea and surf.'],
            ['Courses', 'green', 'Coaching, safety and rescue training, and first aid.'],
            ['Competitions', 'coral', 'Canoe polo leagues and tournaments, and other events.'],
            ['Socials', 'gold', 'Barbecues, the AGM and get-togethers off the water.'],
            ['Volunteering', 'violet', 'Boat store days and other ways to help the club run.'],
            ['Meetings', 'slate', 'Committee meetings and workshops.'],
        ];

        foreach ($categories as $sort => [$name, $colour, $description]) {
            EventCategory::firstOrCreate(['slug' => Str::slug($name)], compact('name', 'colour', 'description', 'sort'));
        }
    }

    private function disciplines(): void
    {
        $disciplines = [
            [
                'name' => 'Whitewater',
                'summary' => 'River and whitewater-course trips through the year, from first moving-water tasters to graded rivers.',
                'video_url' => 'https://vimeo.com/406835621',
                'facts' => [
                    ['label' => 'Good for', 'value' => 'Paddlers who are confident capsizing with a spray deck'],
                    ['label' => 'First step', 'value' => 'A moving-water taster at Coxes Lock'],
                    ['label' => 'Where', 'value' => 'Rivers and man-made courses across the UK, and a trip abroad most years'],
                    ['label' => 'Kit', 'value' => 'Club whitewater boats, tandem kayaks and sit-on-tops'],
                ],
                'body' => "A large part of the club paddles whitewater. We run several whitewater trips each year at different levels of difficulty, to rivers and to man-made courses across the UK, and members arrange plenty of peer trips of their own between them.\n\nMost years we also try to arrange a whitewater trip abroad, most recently to the Soča Valley in Slovenia.\n\n## How do I start whitewater kayaking?\n\nOur coaches are experienced at introducing people to whitewater. We run moving-water taster sessions at places like Coxes Lock, where you can get a feel for moving water somewhere gentle and safe. From there we run trips to easier rivers and man-made courses where you can start to learn.\n\nThe club has tandem kayaks and whitewater sit-on-tops, which help a lot with building confidence.\n\n## What do the river grades mean?\n\nEach whitewater trip in our calendar shows a river grade, from 1 (easy moving water) to 5 (for experts only). The event page says who the trip is suitable for; if you are not sure, ask before you book.",
            ],
            [
                'name' => 'Canoe polo',
                'summary' => 'A friendly polo section that trains year-round at Hawley Lake and plays in national and regional leagues.',
                'video_url' => 'https://vimeo.com/150488682',
                'contact_email' => 'polo@bvcc.org.uk',
                'facts' => [
                    ['label' => 'Where', 'value' => 'Hawley Lake, and Whitehill & Bordon Leisure Centre in winter'],
                    ['label' => 'When', 'value' => 'Up to four times a week in summer; weekends in winter'],
                    ['label' => 'Teams', 'value' => 'Open team in Open National Division 2; women’s team in the South East regional league'],
                    ['label' => 'You need', 'value' => 'To be competent in a kayak (about Paddle Explore award level)'],
                ],
                'body' => "We are a welcoming canoe polo club: a friendly place to learn, and a route to playing at a high level.\n\nThrough the summer we train up to four times a week at Hawley Lake. In winter, once the evenings are dark, we train once a week at weekends.\n\nWe have an open team in Open National Division 2 and a women's team in the Women's South East regional league. We also go to weekend tournaments around the UK and in Europe at a wide range of levels, so everyone gets the chance to compete at a big event.\n\n## Can I join without any polo experience?\n\nYes. You do need to be competent in a kayak first, at about Paddle Explore award level (the old 2 star). If you are not there yet, our weekly sessions will get you there.\n\n## Is there kit I can borrow?\n\nWe have polo boats, paddles, helmets and buoyancy aids for training sessions. For tournaments, members who are not monthly subscribers pay £10 a day or £15 a weekend for club kit with a boat, and £5 a day or £7.50 a weekend for kit without a boat.\n\nPolo training is not in the public calendar. Email the polo team for this week's times.",
            ],
            [
                'name' => 'Touring',
                'summary' => 'Relaxed flat-water journeys on local waterways and further afield. Great for families and any craft.',
                'facts' => [
                    ['label' => 'Good for', 'value' => 'Individuals and families of any experience'],
                    ['label' => 'Craft', 'value' => 'Kayaks, open canoes and paddleboards'],
                    ['label' => 'How often', 'value' => 'Several trips a year'],
                ],
                'body' => "We run several flat-water touring trips through the year, on local waterways and further afield.\n\nThey suit individuals and families of any experience, as long as you are confident you can paddle the distance. They are a chance to paddle further than you can at a weekly session, and a good way to get the family out exploring.\n\nAny craft is welcome: kayak, open canoe or paddleboard.",
            ],
            [
                'name' => 'Freestyle',
                'summary' => 'A growing group of playboaters who practise together at Thames weirs and whitewater courses.',
                'facts' => [
                    ['label' => 'Local spots', 'value' => 'Hurley Weir, Chertsey Weir and Lee Valley White Water Centre'],
                    ['label' => 'How it works', 'value' => 'Peer trips arranged between members, plus help at weekly sessions'],
                ],
                'body' => "There is a growing group of freestyle paddlers in the club who help each other practise and arrange regular peer trips to local play spots and whitewater courses. If you are interested, they will be glad to help you get started.\n\nBeing close to the Thames Valley gives us easy access to some great freestyle spots, such as Hurley Weir and Chertsey Weir, and to Lee Valley White Water Centre.",
            ],
            [
                'name' => 'Sea kayaking',
                'summary' => 'One or two sea trips a year, from multi-day Dorset tours to a day at the seaside.',
                'video_url' => 'https://vimeo.com/265321429',
                'facts' => [
                    ['label' => 'How often', 'value' => 'Usually one or two trips a year'],
                    ['label' => 'Where', 'value' => 'The Dorset coast and the tidal Thames, among others'],
                ],
                'body' => "Sea kayaking is not the club's main focus, but plenty of members enjoy it and we usually run one or two sea trips a year.\n\nThese range from multi-day touring trips in Dorset, to paddles on the tidal Thames, to a bit of fun in our boats at the seaside. We are always open to suggestions for places to paddle.",
            ],
            [
                'name' => 'Stand-up paddleboarding',
                'summary' => 'Hard and inflatable club boards to try at Lido sessions, with coaches to help you find your feet.',
                'facts' => [
                    ['label' => 'Best place to learn', 'value' => 'Summer sessions at Aldershot Lido'],
                    ['label' => 'Kit', 'value' => 'Club hard and inflatable boards'],
                ],
                'body' => "The club has a number of stand-up paddleboards, both hard and inflatable, for members to use at sessions and on trips.\n\nIf you want to learn, build confidence or just try it for fun, the Lido sessions are ideal. There is plenty of space, the water is clean and there are changing rooms on site, so falling in is no hardship. Several of our coaches paddleboard and can help you improve.\n\nWe have taken boards on some of our gentler whitewater trips too, and members have competed in the SUP X events at the Hurley Classic.",
            ],
        ];

        foreach ($disciplines as $sort => $discipline) {
            Discipline::firstOrCreate(['slug' => Str::slug($discipline['name'])], [...$discipline, 'sort' => $sort]);
        }
    }

    private function committee(): void
    {
        // Office holders' names are added in the admin, not kept in code.
        $roles = [
            ['General enquiries', 'enquiries@bvcc.org.uk', 'Anything about the club, or if you are not sure who to ask.', false, true],
            ['Bookings', 'bookings@bvcc.org.uk', 'Booking onto sessions, trips and courses.', false, true],
            ['Memberships', 'memberships@bvcc.org.uk', 'Joining, renewing and membership tiers.', false, true],
            ['Chair', 'chair@bvcc.org.uk', 'Leads the committee and represents the club.', true, false],
            ['Vice chair', 'vicechair@bvcc.org.uk', 'Supports the chair and stands in when needed.', true, false],
            ['Welfare officer', 'welfare@bvcc.org.uk', 'Safeguarding and wellbeing of everyone at the club, especially children and adults at risk.', true, true],
            ['Treasurer', 'treasurer@bvcc.org.uk', 'Looks after the club accounts.', true, false],
            ['Secretary', 'secretary@bvcc.org.uk', 'Meetings, minutes and club correspondence.', true, false],
            ['Safety officer', 'healthandsafety@bvcc.org.uk', 'Risk assessments and safe operating procedures.', true, false],
            ['Programme coordinator', 'programme@bvcc.org.uk', 'Plans the calendar of sessions, trips and courses.', true, true],
            ['Equipment officer', 'equipment@bvcc.org.uk', 'Club boats and kit, and equipment hire.', true, true],
            ['Canoe polo', 'polo@bvcc.org.uk', 'Polo training, teams and tournaments.', true, true],
            ['Volunteer coordinator', 'volunteering@bvcc.org.uk', 'Helps members find ways to get involved.', true, false],
            ['Publicity officer', 'media@bvcc.org.uk', 'News, photos and social media.', true, false],
            ['Website', 'webmaster@bvcc.org.uk', 'Problems with this website.', true, true],
        ];

        foreach ($roles as $sort => [$title, $email, $description, $listed, $enquiries]) {
            CommitteeRole::firstOrCreate(['title' => $title], [
                'email' => $email,
                'description' => $description,
                'is_listed' => $listed,
                'takes_enquiries' => $enquiries,
                'sort' => $sort,
            ]);
        }
    }

    private function pages(): void
    {
        $legacy = rtrim((string) config('site.legacy_url'), '/');

        $pages = [
            [PageSection::Paddle, 'weekly-sessions', 'Weekly sessions', 'We paddle every Wednesday evening, all year round. The venue changes with the season.', [
                ['label' => 'When', 'value' => 'Every Wednesday evening'],
                ['label' => 'Who', 'value' => 'All levels, age 8 and up'],
                ['label' => 'Cost', 'value' => '£10 for non-members, £6 for pay as you paddle members, free for monthly subscribers'],
                ['label' => 'Kit', 'value' => 'Club boats, paddles and buoyancy aids are provided'],
            ], "Our weekly sessions are open to individuals and families of any skill level. If you are new to paddlesport they are the place to start: our qualified coaches will teach you to paddle, and the clean water of the Lido and the pool makes it easy to build confidence.\n\nThey are just as good for experienced paddlers. Coaches can help with specific skills such as rolling or freestyle, or you can simply practise and paddle with friends.\n\nThe club has a large fleet of boats and boards covering nearly every discipline, and members are welcome to use them at sessions.\n\n## When and where are the sessions?\n\n| Season | Roughly | Venue | Time |\n| --- | --- | --- | --- |\n| Summer | Late May to early September | Aldershot Lido | Wednesdays 7pm to 8:30pm |\n| Autumn | Mid September to early October | Basingstoke Canal | Wednesdays 6pm to 7:30pm |\n| Winter | October to March | Yateley Manor School pool | Wednesdays 8pm to 9:45pm |\n| Spring | End of March to late May | Basingstoke Canal | Wednesdays 7pm to 8:30pm |\n\nTimes and launch points can change, so always check the [events calendar](/events) for this week's session.\n\n## Do I need to book?\n\nPlease book, especially for pool sessions, where space on the water is limited. Email the bookings team or see the event page for how to book.", 'sessions', true],
            [PageSection::Paddle, 'beginners', 'Beginners and coaching', 'Never paddled before? Come and try it. Our volunteer coaches will get you started in clean, calm water.', [
                ['label' => 'Minimum age', 'value' => '8 years'],
                ['label' => 'Experience needed', 'value' => 'None'],
                ['label' => 'What to bring', 'value' => 'Clothes you can get wet, a towel and a change of clothes'],
                ['label' => 'First step', 'value' => 'Come to any weekly session'],
            ], "One of the club's aims is to encourage participation and fun in paddlesports, and we welcome new members whatever their experience.\n\nWe have many qualified coaches with lots of experience of introducing people to paddling. Because we are based in a pool or the Lido for most of the year, we are an ideal place to learn. Fear of falling in is the big worry for most beginners, and people feel much more confident starting out in clean, clear water than in a canal or river.\n\nOur volunteers can help you learn in a kayak, an open canoe or on a paddleboard. They can also help with individual skills, such as rolling a kayak, or with a new discipline such as whitewater or freestyle.\n\n## Can I work towards awards?\n\nYes. Our coaches can help members work towards Paddle UK awards. When there is enough interest the club arranges formal courses, such as Foundation Safety and Rescue Training, Whitewater Safety and Rescue, and first aid, and brings in outside coaches for more advanced skills.", 'getting-started', true],
            [PageSection::Paddle, 'learning-and-development', 'Learning and development', 'Ideas for building your skills, and for planning your own paddling safely.', null, "Learning is a big part of paddlesport, and our volunteers are always happy to help with skills or suggest what to do next. When there is enough interest we also arrange courses with our own or outside coaches.\n\n## How can I improve my paddling?\n\n- Look at the awards and courses for skills, coaching, leadership and safety on the Paddle UK website.\n- Talk to other members, who can show you or talk you through a skill.\n- Look at local centres and whitewater courses that offer progression courses or coaching.\n- Come on club trips. If what you want is not in the programme, talk to the programme team.\n- Use the club Facebook group to arrange peer paddles with like-minded members.\n- Watch videos of the skill you want to learn, then get on the water and try it.\n\n## How do I plan my own paddling?\n\nThe club aims to help members become safe, independent paddlers. Build up gradually: start with short distances in sheltered places before trying anything longer or more adventurous.", null, false],
            [PageSection::Join, 'hire', 'Equipment hire', 'Club kit is free to use at club sessions and events. Paid-up members can ask to borrow kit for their own paddling.', [
                ['label' => 'At club sessions and events', 'value' => 'Included for everyone'],
                ['label' => 'Personal use', 'value' => 'Paid memberships only, at the committee’s discretion'],
                ['label' => 'How long', 'value' => '1 to 4 weeks'],
            ], "Equipment is available to everyone at all official club activities.\n\nMembers with a paid membership can also ask to borrow club equipment for their own paddling. Requests are approved by the committee.\n\nNot every venue has kit stored on site. If kit needs to be transported to an event, or you want to borrow it for personal use, fill in the hire request form below and the equipment team will get back to you.", 'kit', false],
            [PageSection::Join, 'hire-terms', 'Hire terms', 'The terms for borrowing club equipment.', null, null, null, true, "{$legacy}/hire/hire-terms/"],
            [PageSection::Join, 'membership-terms', 'Membership terms', 'The code of conduct and declarations every member agrees to.', null, null, null, false, "{$legacy}/membership/membership-terms/"],
            [PageSection::Club, 'booking-terms', 'Event booking terms', 'What you agree to when you book onto a club session, trip or course.', null, null, null, true, "{$legacy}/events/event-booking-terms/"],
            [PageSection::Club, 'constitution', 'Constitution', 'The rules the club is run by.', null, null, null, false, "{$legacy}/documents/constitution/"],
            [PageSection::Club, 'governance-and-roles', 'Governance and roles', 'How the committee is organised and what each role does.', null, null, null, false, "{$legacy}/documents/governance-and-roles/"],
            [PageSection::Club, 'safeguarding', 'Health and safety, and safeguarding', 'How we keep everyone safe, and who to talk to if you have a concern.', null, "The club has a health and safety policy and a safeguarding policy, which you can download from the [documents page](/club/documents).\n\nIf you are worried about the welfare of a child or an adult at risk at the club, contact our welfare officer. If someone is in immediate danger, call 999.", null, false, "{$legacy}/documents/policies-health-and-safety-safeguarding/"],
            [PageSection::Club, 'privacy-policy', 'Privacy policy', 'What personal information we hold, why, and your rights.', null, null, null, true, "{$legacy}/documents/privacy-policy/"],
            [PageSection::Club, 'photography-and-filming-policy', 'Photography and filming policy', 'How we take and use photos and video at club activities.', null, null, null, false, "{$legacy}/documents/photography-and-filming-policy/"],
        ];

        foreach ($pages as $sort => $page) {
            [$section, $slug, $title, $summary, $facts, $body, $faqCategory, $toConfirm] = $page;

            Page::firstOrCreate(['section' => $section, 'slug' => $slug], [
                'title' => $title,
                'summary' => $summary,
                'facts' => $facts,
                'body' => $body,
                'faq_category' => $faqCategory,
                'to_confirm' => $toConfirm,
                'source_url' => $page[8] ?? null,
                'sort' => $sort,
            ]);
        }
    }

    private function faqs(): void
    {
        $faqs = [
            ['getting-started', 'How do I try kayaking or canoeing with the club?', 'Come to any of our weekly Wednesday sessions. A volunteer will welcome you, sort out a boat and kit, and get you paddling. Pool and Lido sessions are the easiest place to start. It helps if you book and fill in your details beforehand, but you can do it when you arrive.', false],
            ['getting-started', 'Do I need any experience or my own kit?', 'No. Beginners are welcome and the club provides boats, paddles and buoyancy aids at sessions. Just bring clothes you can get wet, a towel and a change of clothes.', true],
            ['getting-started', 'How old do you have to be?', 'We welcome individuals and families aged 8 and over. Under 18s need a parent or guardian to complete their details and consent.', false],
            ['getting-started', 'Do I need to be able to swim?', 'You should be happy in the water. Everyone wears a buoyancy aid, and beginners start in a pool or the Lido where the water is clean and calm. Tell the coach at the start if you are not a confident swimmer.', true],
            ['sessions', 'When and where does the club meet?', 'Every Wednesday evening. In summer we are at Aldershot Lido (7pm to 8:30pm), in winter at Yateley Manor School pool (8pm to 9:45pm), and in spring and autumn on the Basingstoke Canal. Check the events calendar for this week.', true],
            ['sessions', 'How much does a session cost?', 'A weekly session is £10 for non-members and £6 for pay as you paddle members. Monthly subscribers pay nothing extra. Kit is included.', false],
            ['membership', 'How much is membership?', 'Annual pay as you paddle membership is £15 for a youth or student, £35 for an adult and £50 for a family. Monthly subscriber membership has an initial fee and a monthly payment, and covers all your session and event club fees. Non-member registration is free.', true],
            ['membership', 'What is the difference between pay as you paddle and monthly subscriber?', 'Pay as you paddle members pay a small annual fee and then a reduced fee each time they come. Monthly subscribers pay monthly and no session or event club fees. If you paddle most weeks, subscribing usually works out cheaper.', false],
            ['membership', 'What does membership include?', 'Club communications, affiliation to the national governing body through the club, support from trained volunteers, help with your own development, use of club kit at club activities, access to waterways and public liability insurance for club activities, and reduced fees. Trips, courses and competitions can have extra costs such as accommodation.', false],
            ['membership', 'Can I come along without joining?', 'Yes. Register as a non-member for free so we have your emergency contact and medical details, then pay the non-member fee for each session.', false],
            ['booking', 'How do I book onto a session or trip?', 'Each event page says how to book. Tell us who is coming and we will hold your places. Your booking is confirmed once payment is received, though for most sessions you can also pay on arrival.', false],
            ['booking', 'How do I pay?', 'By bank transfer where possible, or in cash on the day. We send the payment details with your booking.', false],
            ['booking', 'What if I need to cancel?', 'Tell the bookings team as soon as you can so we can offer your place to someone on the waiting list.', true],
            ['kit', 'Can I borrow club equipment?', 'Kit is free to use at all official club activities. Members with a paid membership can also ask to borrow kit for their own paddling for 1 to 4 weeks, at the committee’s discretion, using the hire request form.', false],
        ];

        foreach ($faqs as $sort => [$category, $question, $answer, $toConfirm]) {
            Faq::firstOrCreate(['question' => $question], [
                'category' => $category,
                'answer' => $answer,
                'to_confirm' => $toConfirm,
                'sort' => $sort,
            ]);
        }
    }

    private function articleCategories(): void
    {
        foreach (['Club news', 'Club sessions', 'Club trips', 'Volunteering'] as $name) {
            ArticleCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }

    /**
     * The two trips that were open for booking on the old site.
     */
    private function events(): void
    {
        $trips = EventCategory::where('slug', 'trips')->firstOrFail();
        $whitewater = Discipline::where('slug', 'whitewater')->first();

        Event::firstOrCreate(['slug' => 'barle-whitewater-day-trip-2026-10-24'], [
            'title' => 'River Barle whitewater day trip',
            'summary' => 'A day on the River Barle: grade 2 to 3 whitewater for beginner and intermediate paddlers.',
            'description' => "A whitewater day trip to the River Barle.\n\n**Type:** Beginner and intermediate whitewater (grade 2 to 3).\n\nClub kit is available: say what you need when you book.",
            'starts_at' => '2026-10-24 08:00',
            'ends_at' => '2026-10-24 18:00',
            'event_category_id' => $trips->id,
            'discipline_id' => $whitewater?->id,
            'grade' => 3,
            'suitability' => 'Confident capsizing with a spray deck',
            'fee_type' => FeeType::FullDay,
            'status' => EventStatus::Published,
        ]);

        Event::firstOrCreate(['slug' => 'dartmoor-whitewater-weekend-2026-11-21'], [
            'title' => 'Dartmoor whitewater weekend',
            'summary' => 'A whitewater weekend on Dartmoor.',
            'description' => "A weekend of whitewater paddling on Dartmoor.\n\nClub kit is available: say what you need when you book.",
            'starts_at' => '2026-11-21 09:00',
            'ends_at' => '2026-11-22 17:00',
            'all_day' => true,
            'event_category_id' => $trips->id,
            'discipline_id' => $whitewater?->id,
            'grade' => 3,
            'suitability' => 'Confident capsizing with a spray deck',
            'fee_type' => FeeType::Custom,
            'custom_prices' => [
                FeeBand::NonMember->value => 3000,
                FeeBand::PayAsYouPaddle->value => 2000,
                FeeBand::Subscriber->value => 0,
                FeeBand::Affiliate->value => 2000,
            ],
            'extra_costs' => 'Accommodation is charged separately once confirmed.',
            'status' => EventStatus::Published,
        ]);
    }

    /**
     * Old WordPress paths and where they live now. Dated news posts and old
     * event pages are handled by pattern in App\Http\Controllers\RedirectController.
     */
    private function redirects(): void
    {
        $redirects = [
            '/about-us' => '/paddle',
            '/about-us/weekly-sessions' => '/paddle/weekly-sessions',
            '/about-us/beginners-and-coaching' => '/paddle/beginners',
            '/about-us/canoe-polo' => '/paddle/canoe-polo',
            '/about-us/touring' => '/paddle/touring',
            '/about-us/whitewater' => '/paddle/whitewater',
            '/about-us/freestyle' => '/paddle/freestyle',
            '/about-us/sea-kayaking' => '/paddle/sea-kayaking',
            '/about-us/stand-up-paddleboarding' => '/paddle/stand-up-paddleboarding',
            '/about-us/learning-and-development' => '/paddle/learning-and-development',
            '/disciplines' => '/paddle',
            '/club-clothing' => '/club/documents',
            '/contact-us' => '/contact',
            '/events/event-search' => '/events',
            '/events/events-today' => '/events',
            '/events/event-tags' => '/events',
            '/events/event-categories' => '/events',
            '/events/event-locations' => '/venues',
            '/events/event-booking-terms' => '/club/booking-terms',
            '/events/event-proposal-submission' => '/contact',
            '/hire' => '/join/hire',
            '/hire-request' => '/join/hire',
            '/hire/hire-request' => '/join/hire',
            '/hire/hire-terms' => '/join/hire-terms',
            '/membership' => '/join',
            '/membership/club-fees' => '/join',
            '/club-fees' => '/join',
            '/standard-fees' => '/join',
            '/membership/membership-terms' => '/join/membership-terms',
            '/membership/membership-checkout' => '/register',
            '/documents' => '/club/documents',
            '/documents/constitution' => '/club/constitution',
            '/documents/meetings' => '/club/documents',
            '/documents/authenticity-statement' => '/club/privacy-policy',
            '/documents/policies-health-and-safety-safeguarding' => '/club/safeguarding',
            '/documents/governance-and-roles' => '/club/governance-and-roles',
            '/documents/privacy-policy' => '/club/privacy-policy',
            '/documents/photography-and-filming-policy' => '/club/photography-and-filming-policy',
            '/help' => '/faqs',
            '/help/how-to-create-an-account' => '/faqs',
            '/help/how-to-apply-for-a-membership' => '/join',
            '/help/how-to-renew-your-membership' => '/join',
            '/help/how-to-book-events' => '/faqs',
            // Theme My Login and Paid Memberships Pro account pages (F-50).
            '/lostpassword' => '/forgot-password',
            '/resetpass' => '/forgot-password',
            '/my-account' => '/admin',
            '/membership-account' => '/admin',
            '/membership-account/membership-checkout' => '/register',
            '/membership-account/membership-levels' => '/join',
            '/feed' => '/news/feed.xml',
            '/news/category/all' => '/news',
        ];

        foreach ($redirects as $from => $to) {
            Redirect::firstOrCreate(['from_path' => $from], ['to_path' => $to]);
        }
    }
}
