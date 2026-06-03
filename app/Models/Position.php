<?php

namespace App\Models;

use App\Enums\PositionCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Position extends Model
{
    protected static ?bool $hasCategoryColumn = null;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Position $position) {
            if (empty($position->slug)) {
                $position->slug = static::generateUniqueSlug($position->name);
            }

            if (static::hasCategoryColumn() && empty($position->category)) {
                $position->category = PositionCategory::Other->value;
            }
        });
    }

    public static function hasCategoryColumn(): bool
    {
        if (static::$hasCategoryColumn === null) {
            static::$hasCategoryColumn = Schema::hasTable('positions')
                && Schema::hasColumn('positions', 'category');
        }

        return static::$hasCategoryColumn;
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function categoryEnum(): PositionCategory
    {
        return PositionCategory::tryFromMixed($this->category);
    }

    public function categoryLabel(): string
    {
        return $this->categoryEnum()->label();
    }

    public function peopleCount(): int
    {
        return $this->people()->count();
    }

    public function campaignCreditsCount(): int
    {
        return (int) DB::table('campaign_person')
            ->where('role', $this->name)
            ->count();
    }

    public function isInUse(): bool
    {
        return $this->peopleCount() > 0 || $this->campaignCreditsCount() > 0;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        if (static::hasCategoryColumn()) {
            return $query
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name');
        }

        if (Schema::hasColumn('positions', 'sort_order')) {
            return $query->orderBy('sort_order')->orderBy('name');
        }

        return $query->orderBy('name');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder->where('name', 'like', '%'.$term.'%')
                ->orWhere('slug', 'like', '%'.$term.'%');

            if (static::hasCategoryColumn()) {
                $builder->orWhere('category', 'like', '%'.$term.'%');
            }
        });
    }
}
