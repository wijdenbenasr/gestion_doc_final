<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'creator' => 'Createur',
        'validator' => 'Validateur',
        'approver' => 'Approbateur',
        'admin' => 'Administrateur',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'prenom',
        'cin',
        'matricule',
        'profile_image_path',
        'profile_photo',
        'email',
        'password',
        'role',
        'is_admin_approved',
        'admin_approved_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
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

    /**
     * @return array<int, string>
     */
    public static function roleKeys(): array
    {
        return array_keys(self::ROLES);
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

    public function getFullNameAttribute(): string
    {
        $fullName = collect([$this->name, $this->prenom])
            ->filter()
            ->implode(' ');

        return $fullName !== '' ? $fullName : $this->email;
    }

    public function getInitialsAttribute(): string
    {
        $initials = collect([$this->prenom, $this->name])
            ->filter()
            ->map(function (?string $value): string {
                return Str::upper(Str::substr(trim((string) $value), 0, 1));
            })
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    public function getHasProfilePhotoAttribute(): bool
    {
        return (bool) ($this->profile_photo ?: $this->profile_image_path);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        $path = $this->profile_photo ?: $this->profile_image_path;

        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->profile_photo_url;
    }
}
