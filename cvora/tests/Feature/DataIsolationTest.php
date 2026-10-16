<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Resumes\ResumeWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_cannot_open_another_members_resume(): void
    {
        $owner = $this->member();
        $other = User::factory()->create();
        $resume = app(ResumeWriter::class)->create($owner);

        $this->actingAs($other)
            ->get(route('resumes.edit', $resume))
            ->assertNotFound();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $resume));
        $this->assertFalse(Gate::forUser($other)->allows('view', $resume));
    }

    public function test_an_administrator_does_not_receive_the_resume_editor(): void
    {
        $owner = $this->member();
        $admin = User::factory()->admin()->create();
        $resume = app(ResumeWriter::class)->create($owner);

        $this->actingAs($admin)
            ->get(route('resumes.edit', $resume))
            ->assertNotFound();

        $this->assertFalse(Gate::forUser($admin)->allows('view', $resume));
    }
}
