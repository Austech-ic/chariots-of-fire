<?php

namespace Tests;

use App\Enums\ContentStatus;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    protected function staff(string ...$roles): User
    {
        $user = User::factory()->create(['is_staff' => true]);
        $user->roles()->sync(Role::whereIn('slug', $roles)->pluck('id'));

        return $user->fresh();
    }

    protected function member(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function published(): array
    {
        return ['status' => ContentStatus::Published, 'published_at' => now()->subMinute()];
    }
}
