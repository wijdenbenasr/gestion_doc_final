<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'creator' => 'Créateur',
        'validator' => 'Validateur',
        'approver' => 'Approbateur',
        'admin' => 'Administrateur',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'prenom',
        'cin',
        'matricule',
        'email',
        'password',
        'role',
        'is_admin_approved',
        'admin_approved_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'admin_approved_at' => 'datetime',
            'is_admin_approved' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function createdDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class);
    }

    public function transmissions(): HasMany
    {
        return $this->hasMany(Transmission::class, 'sent_by');
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? 'Sans role';
    }
}
