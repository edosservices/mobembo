<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description', 'audience', 'is_premium', 'is_active', 'sort_order', 'config'])]
class ResumeTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    /**
     * @param  Builder<ResumeTemplate>  $query
     * @return Builder<ResumeTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class, 'template_id');
    }
}
