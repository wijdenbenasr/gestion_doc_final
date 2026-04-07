<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Archive extends Model
{
    protected $fillable = ['document_id', 'archived_by', 'archived_at', 'archive_code'];
    protected $casts    = ['archived_at' => 'datetime'];

    public function document(): BelongsTo   { return $this->belongsTo(Document::class); }
    public function archivedBy(): BelongsTo { return $this->belongsTo(User::class, 'archived_by'); }
}
