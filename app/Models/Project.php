<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Workspace $workspace
 * @property array<Issue> $issues@W
 */
#[Fillable(['workspace_id', 'name', 'key', 'description'])]
class Project extends Model
{
    use HasFactory;

    //
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
