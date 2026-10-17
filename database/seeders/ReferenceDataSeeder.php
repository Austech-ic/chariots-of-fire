<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\CounsellingService;
use App\Models\DonationCampaign;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\SermonCategory;
use App\Models\SiteSetting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Settings::DEFAULTS as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $sections = [
            'intro' => ['Our Mandate', 'Our Mandate', 'A concise, leadership-approved explanation of the ministry\'s vision, mission and areas of service.', 'Discover our ministry', '/about'],
            'watch' => ['Watch & Listen', 'Messages That Build Faith', null, 'Browse all sermons', '/sermons'],
            'programmes' => ['Programmes', 'Upcoming Programmes', null, 'See all programmes', '/events'],
            'gallery' => ['Ministry Life', 'The Work in Pictures', null, 'Open the gallery', '/gallery'],
            'connect' => ['Connect & Give', 'Take Your Next Step', null, null, null],
        ];
        $order = 0;
        foreach ($sections as $key => [$eyebrow, $heading, $body, $label, $url]) {
            $section = HomeSection::firstOrCreate(['key' => $key], ['sort_order' => $order++, 'is_visible' => true]);
            if ($section->wasRecentlyCreated) {
                $section->translations()->create(['locale' => 'en', 'eyebrow' => $eyebrow, 'heading' => $heading, 'body' => $body, 'link_label' => $label, 'link_url' => $url]);
            }
        }

        foreach (['faith' => 'Faith', 'prayer' => 'Prayer', 'evangelism' => 'Evangelism', 'discipleship' => 'Discipleship', 'family' => 'Family', 'holy-spirit' => 'The Holy Spirit', 'leadership' => 'Leadership'] as $slug => $name) {
            $category = SermonCategory::firstOrCreate(['slug' => $slug]);
            if ($category->wasRecentlyCreated) {
                $category->translations()->create(['locale' => 'en', 'name' => $name]);
            }
        }

        $services = [
            ['spiritual-guidance', 'Spiritual guidance', 'Prayerful, confidential conversation about faith and life.', 60, ['in_person', 'phone', 'online']],
            ['marriage-family', 'Marriage and family', 'Support for couples and families.', 60, ['in_person', 'online']],
            ['prayer-support', 'Prayer support', 'A shorter session to pray together.', 30, ['phone', 'online']],
        ];
        foreach ($services as $i => [$slug, $name, $description, $duration, $methods]) {
            CounsellingService::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'description' => $description, 'duration_minutes' => $duration, 'buffer_minutes' => 15,
                'booking_mode' => 'both', 'approval_required' => true, 'appointment_methods' => $methods, 'sort_order' => $i,
            ]);
        }

        DonationCampaign::firstOrCreate(['slug' => 'general'], ['name' => 'General giving', 'currency' => 'NGN', 'status' => 'active', 'sort_order' => 0]);
        DonationCampaign::firstOrCreate(['slug' => 'outreach'], ['name' => 'Outreach and missions', 'currency' => 'NGN', 'status' => 'active', 'sort_order' => 1]);

        // Placeholder pages are drafts: the ministry must supply and approve real content before publishing.
        $placeholder = '<p><strong>Content to be supplied and approved by the ministry.</strong></p>';
        foreach ([
            ['about', 'about', 'About Flaming Chariot Outreach'],
            ['founder', 'founder', 'Our Founder'],
            ['privacy', 'legal', 'Privacy notice'],
            ['statement-of-faith', 'standard', 'Statement of faith'],
        ] as [$slug, $type, $title]) {
            $page = Page::firstOrCreate(['slug' => $slug], ['type' => $type, 'status' => ContentStatus::Draft, 'show_in_footer' => $type === 'legal']);
            if ($page->wasRecentlyCreated) {
                $page->translations()->create(['locale' => 'en', 'title' => $title, 'body' => $placeholder]);
            }
        }
    }
}
