<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Services\Resumes\ResumeContent;
use App\Services\Resumes\ResumeWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_create_update_and_delete_a_resume(): void
    {
        $user = $this->member();
        $writer = app(ResumeWriter::class);
        $resume = $writer->create($user);

        $payload = [
            'title' => 'CV Kinshasa',
            'template_slug' => 'moderne',
            'accent_color' => '#2563EB',
            'content' => ResumeContent::fromProfile($user),
        ];
        $payload['content']['personal']['first_name'] = 'Amina';
        $payload['content']['summary'] = 'Analyste à Kinshasa.';

        $this->actingAs($user)
            ->put(route('resumes.update', $resume), $payload)
            ->assertRedirect(route('resumes.edit', $resume));

        $resume->refresh();
        $this->assertSame('CV Kinshasa', $resume->title);
        $this->assertSame('Amina', $resume->content['personal']['first_name']);

        $this->actingAs($user)
            ->delete(route('resumes.destroy', $resume))
            ->assertRedirect(route('resumes.index'));

        $this->assertSoftDeleted($resume);
    }

    public function test_the_free_offer_blocks_a_second_resume_and_a_premium_template(): void
    {
        $user = $this->member();
        app(ResumeWriter::class)->create($user);

        $this->actingAs($user)
            ->from(route('resumes.index'))
            ->post(route('resumes.store'))
            ->assertSessionHasErrors('title');

        $this->assertSame(1, Resume::query()->count());

        $resume = Resume::query()->first();
        $payload = [
            'title' => 'CV',
            'template_slug' => 'technique',
            'accent_color' => '#2563EB',
            'content' => ResumeContent::blank(),
        ];

        $this->actingAs($user)
            ->put(route('resumes.update', $resume), $payload)
            ->assertSessionHasErrors('template_slug');
    }
}
