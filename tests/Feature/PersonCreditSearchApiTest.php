<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonCreditSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_people_search_returns_matching_results(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        Person::query()->create([
            'name' => 'Mustafa Amer',
            'slug' => 'mustafa-amer',
            'position' => 'Director',
            'status' => 'approved',
        ]);

        Person::query()->create([
            'name' => 'Ali Hassan',
            'slug' => 'ali-hassan',
            'position' => 'Editor',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.api.people.search', ['q' => 'm']))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'position', 'slug', 'photo_url']]])
            ->assertJsonFragment(['name' => 'Mustafa Amer']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.people.search', ['q' => 'mustafa']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Mustafa Amer']);
    }
}
