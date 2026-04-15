<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class Organization extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productSafetyEntries(): HasMany
    {
        return $this->hasMany(ProductSafetyEntry::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Active Organization';
    }
}
