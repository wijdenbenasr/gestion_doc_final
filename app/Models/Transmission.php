<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transmission extends Model
{
    protected $fillable = [
        'document_id', 'from_role', 'to_role',
        'action', 'status', 'comment', 'sent_by',
    ];

    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function sender(): BelongsTo   { return $this->belongsTo(User::class, 'sent_by'); }
}
