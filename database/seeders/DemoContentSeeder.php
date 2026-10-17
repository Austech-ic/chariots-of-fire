<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\AvailabilityRule;
use App\Models\CounsellingService;
use App\Models\CounsellorProfile;
use App\Models\Event;
use App\Models\HeroSlide;
use App\Models\Livestream;
use App\Models\Mandate;
use App\Models\Sermon;
use App\Models\SermonCategory;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * SAMPLE content for previewing the design and workflows. Everything is
 * labelled as sample text and must be replaced with ministry-approved
 * content before launch. No biographical facts are invented.
 *
 *   php artisan db:seed --class=DemoContentSeeder
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $published = ['status' => ContentStatus::Published, 'published_at' => now()->subDay()];

        $slides = [
            ['Igniting Faith. Transforming Lives.', 'Discover the ministry, its message and the programmes bringing people together in faith.', 'Watch Live', '/live', 'Explore the Ministry', '/about'],
            ['Messages That Build Faith', 'Sample slide — replace with an approved headline and photograph.', 'Browse sermons', '/sermons', null, null],
            ['Join Us at Our Next Programme', 'Sample slide — replace with an approved headline and photograph.', 'See programmes', '/events', null, null],
        ];
        foreach ($slides as $i => [$headline, $description, $label, $url, $label2, $url2]) {
            $slide = HeroSlide::create(['sort_order' => $i] + $published);
            $slide->translations()->create(['locale' => 'en', 'headline' => $headline, 'description' => $description, 'button_label' => $label, 'button_url' => $url, 'secondary_label' => $label2, 'secondary_url' => $url2]);
        }

        $mandates = ['evangelism' => ['Evangelism', 'flame'], 'discipleship' => ['Discipleship', 'book-open'], 'prayer' => ['Prayer', 'hands'],
            'teaching' => ['Teaching', 'academic-cap'], 'outreach' => ['Community outreach', 'heart'], 'conferences' => ['Conferences', 'users']];
        $i = 0;
        foreach ($mandates as $slug => [$title, $icon]) {
            $mandate = Mandate::create(['slug' => $slug, 'icon' => $icon, 'sort_order' => $i++] + $published);
            $mandate->translations()->create([
                'locale' => 'en', 'title' => $title,
                'summary' => 'Sample summary for the '.strtolower($title).' mandate. Replace with leadership-approved text.',
                'body' => '<p>Sample description. The ministry will supply the approved description of this mandate.</p>',
            ]);
        }

        $speaker = Speaker::firstOrCreate(['slug' => 'guest-speaker'], ['name' => 'Guest Speaker (sample)']);
        $categories = SermonCategory::pluck('id');
        foreach (range(1, 8) as $n) {
            $sermon = Sermon::create([
                'slug' => 'sample-message-'.$n,
                'preached_at' => now()->subWeeks($n)->toDateString(),
                'featured' => $n === 1,
            ] + $published);
            $sermon->translations()->create([
                'locale' => 'en',
                'title' => 'Sample message '.$n,
                'description' => 'Sample sermon record for previewing the library. Attach audio or a YouTube link in the CMS.',
                'scripture_references' => ['John 3:16', 'Romans 12:1-2', 'Acts 1:8', 'Psalm 23'][$n % 4],
            ]);
            $sermon->speakers()->attach($speaker);
            $sermon->categories()->attach($categories->random(min(2, $categories->count())));
        }

        foreach ([['Sunday Celebration Service', 3, 'hybrid'], ['Midweek Prayer Meeting', 6, 'online'], ['Community Outreach Day', 12, 'in_person'], ['Annual Conference', 40, 'hybrid']] as [$title, $days, $type]) {
            $event = Event::create([
                'slug' => Str::slug($title),
                'starts_at' => now()->addDays($days)->setTime(9, 0),
                'ends_at' => now()->addDays($days)->setTime(12, 0),
                'event_type' => $type,
                'category' => $type === 'online' ? 'Prayer' : 'Programme',
                'registration_enabled' => $title === 'Annual Conference',
                'capacity' => $title === 'Annual Conference' ? 500 : null,
                'featured' => $title === 'Annual Conference',
            ] + $published);
            $event->translations()->create([
                'locale' => 'en', 'title' => $title.' (sample)',
                'summary' => 'Sample programme listing. Replace with approved details.',
                'description' => '<p>Sample description of the programme.</p>',
                'venue_name' => $type === 'online' ? null : 'Venue to be confirmed',
            ]);
        }

        Livestream::create(['title' => 'Sunday Celebration Service (sample)', 'provider' => 'youtube', 'status' => 'scheduled', 'scheduled_at' => now()->addDays(3)->setTime(9, 0)]);

        // Sample counsellor without a usable password (staff must set one via password reset).
        $user = User::firstOrCreate(['email' => 'counsellor@example.org'], ['name' => 'Sample Counsellor', 'password' => Str::random(40), 'is_staff' => true]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $profile = CounsellorProfile::firstOrCreate(['user_id' => $user->id], ['display_name' => 'Sample Counsellor']);
        $profile->services()->syncWithoutDetaching(CounsellingService::pluck('id'));
        foreach ([1, 3, 5] as $weekday) {
            AvailabilityRule::firstOrCreate(['counsellor_id' => $profile->id, 'weekday' => $weekday], ['start_time' => '10:00', 'end_time' => '16:00', 'timezone' => 'Africa/Lagos']);
        }
    }
}
