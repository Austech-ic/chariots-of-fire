<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editorial content: pages, homepage, mandates, sermons, events, streams, galleries.
 */
return new class extends Migration
{
    private function publishable(Blueprint $table): void
    {
        $table->string('status', 20)->default('draft')->index();
        $table->timestamp('published_at')->nullable()->index();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    }

    private function translationKeys(Blueprint $table, string $parent, string $column): void
    {
        $table->id();
        $table->foreignId($column)->constrained($parent)->cascadeOnDelete();
        $table->string('locale', 10)->index();
        $table->unique([$column, 'locale']);
    }

    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type', 30)->default('standard'); // standard, about, founder, legal
            $table->foreignId('image_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('show_in_footer')->default(false);
            $this->publishable($table);
            $table->timestamps();
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'pages', 'page_id');
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->default(7);
            $this->publishable($table);
            $table->timestamps();
        });

        Schema::create('hero_slide_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'hero_slides', 'hero_slide_id');
            $table->string('headline');
            $table->string('description', 500)->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->string('secondary_label')->nullable();
            $table->string('secondary_url')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique(); // intro, watch, programmes, gallery, connect
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('home_section_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'home_sections', 'home_section_id');
            $table->string('eyebrow')->nullable();
            $table->string('heading');
            $table->text('body')->nullable();
            $table->string('link_label')->nullable();
            $table->string('link_url')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('mandates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('icon', 60)->nullable();
            $table->foreignId('image_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('banner_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publishable($table);
            $table->timestamps();
        });

        Schema::create('mandate_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'mandates', 'mandate_id');
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('biography')->nullable();
            $table->foreignId('portrait_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('sermon_series', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('image_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $this->publishable($table);
            $table->timestamps();
        });

        Schema::create('sermon_series_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'sermon_series', 'sermon_series_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('sermon_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('sermon_category_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'sermon_categories', 'category_id');
            $table->string('name');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('sermons', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('series_id')->nullable()->constrained('sermon_series')->nullOnDelete();
            $table->foreignId('thumbnail_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->date('preached_at')->nullable()->index();
            $table->string('language', 10)->default('en')->index();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('members_only')->default(false);
            $this->publishable($table);
            $table->timestamps();
        });

        Schema::create('sermon_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'sermons', 'sermon_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('scripture_references')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('sermon_speaker', function (Blueprint $table) {
            $table->foreignId('sermon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->primary(['sermon_id', 'speaker_id']);
        });

        Schema::create('sermon_category', function (Blueprint $table) {
            $table->foreignId('sermon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('sermon_categories')->cascadeOnDelete();
            $table->primary(['sermon_id', 'category_id']);
        });

        Schema::create('sermon_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sermon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('media_type', 20); // audio, video, document
            $table->boolean('download_allowed')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sermon_external_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sermon_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30); // youtube, vimeo
            $table->string('external_id');
            $table->string('embed_url')->nullable();
            $table->string('watch_url')->nullable();
            $table->boolean('is_embeddable')->default(true);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('parent_event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->foreignId('banner_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->string('event_type', 30)->default('in_person'); // in_person, online, hybrid
            $table->string('category', 60)->nullable()->index();
            $table->string('online_url')->nullable();
            $table->string('map_url')->nullable();
            $table->boolean('registration_enabled')->default(false);
            $table->unsignedInteger('capacity')->nullable();
            $table->string('external_registration_url')->nullable();
            $table->string('lifecycle', 20)->default('scheduled'); // scheduled, postponed, cancelled
            $table->jsonb('recurrence')->nullable();
            $table->boolean('featured')->default(false);
            $this->publishable($table);
            $table->timestamps();
        });

        Schema::create('event_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'events', 'event_id');
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('description')->nullable();
            $table->text('agenda')->nullable();
            $table->string('speakers')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->text('registration_instructions')->nullable();
            $table->string('notice')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('registration_reference')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->unsignedSmallInteger('guests')->default(0);
            $table->string('status', 20)->default('confirmed');
            $table->timestamps();
            $table->index(['event_id', 'email']);
        });

        Schema::create('livestreams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('provider', 30)->default('youtube');
            $table->string('external_id')->nullable();
            $table->string('embed_url')->nullable();
            $table->string('watch_url')->nullable();
            $table->string('chat_url')->nullable();
            $table->string('status', 20)->default('scheduled'); // scheduled, live, ended, cancelled
            $table->string('status_source', 20)->default('manual'); // manual, provider
            $table->timestamp('status_checked_at')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('recording_sermon_id')->nullable()->constrained('sermons')->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('cover_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 60)->nullable()->index();
            $table->date('taken_at')->nullable();
            $table->jsonb('video_urls')->nullable();
            $this->publishable($table);
            $table->timestamps();
        });

        Schema::create('gallery_album_translations', function (Blueprint $table) {
            $this->translationKeys($table, 'gallery_albums', 'album_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('gallery_albums')->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'gallery_images', 'gallery_album_translations', 'gallery_albums', 'livestreams',
            'event_registrations', 'event_translations', 'events', 'sermon_external_sources',
            'sermon_media', 'sermon_category', 'sermon_speaker', 'sermon_translations', 'sermons',
            'sermon_category_translations', 'sermon_categories', 'sermon_series_translations',
            'sermon_series', 'speakers', 'mandate_translations', 'mandates',
            'home_section_translations', 'home_sections', 'hero_slide_translations', 'hero_slides',
            'page_translations', 'pages',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
