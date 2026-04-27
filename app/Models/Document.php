<?php

namespace App\Models;

use App\Traits\OptimisticLocking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, OptimisticLocking, SoftDeletes;

    public const TYPES = [
        'fmea_process' => 'FMEA Process',
        'sop' => 'SOP - Standard Operating Process / Work Instruction',
        'defect_catalogue' => 'Defect Catalogue',
        'control_plan' => 'Control Plan',
        'process_flow_chart' => 'Process Flow Chart',
        'process_parameters_sheet' => 'Process Parameters Sheet',
        'control_sheet' => 'Control Sheet',
        'rework_instruction' => 'Rework Instruction',
        'quality_wall_instruction' => 'Quality Wall Instruction',
        'checklist_cleaning_tracking' => 'Checklist & Cleaning Tracking',
        'safety_sheet' => 'Safety Sheet at the Workstation',
    ];

    public const AIOS = [
        'aio1' => 'AIO 1',
        'aio2' => 'AIO 2',
        'aio3' => 'AIO 3',
        'aio4' => 'AIO 4',
        'aio5' => 'AIO 5',
    ];

    public const STATUSES = [
        'draft' => 'Brouillon',
        'pending_codification' => 'En attente codification',
        'in_validation' => 'En validation',
        'approbation' => 'En approbation',
        'validation_admin' => 'Validation finale admin',
        'ready_for_pdf' => 'Prêt pour PDF',
        'pdf_converti' => 'PDF converti',
        'signing_validator' => 'Signature validateur',
        'signing_approver' => 'Signature approbateur',
        'signing_admin' => 'Signature admin finale',
        'finalized' => 'Archivé',
        'rejected' => 'Rejeté',
    ];

    protected $fillable = [
        'name', 'code', 'type', 'aio', 'ligne',
        'phase', 'nom_phase', 'nom_serie',
        'file_path', 'file_original_name', 'hash',
        'created_by', 'current_owner_id',
        'version', 'revision', 'status', 'current_role',
        'deadline', 'is_pdf', 'is_fully_signed', 'lock_version',
        'fichier_signe_path', 'pdf_converti',
        'validated_by', 'validated_at',
        'approved_by', 'approved_at',
        'admin_validated_by', 'admin_validated_at',
        'pdf_signe_createur', 'pdf_signe_validateur',
        'pdf_signe_approbateur', 'pdf_signe_final',
        'commentaire_rejet', 'deadline_correction', 'archived_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'version' => 'integer',
        'is_pdf' => 'boolean',
        'is_fully_signed' => 'boolean',
        'lock_version' => 'integer',
        'pdf_converti' => 'boolean',
        'validated_at' => 'datetime',
        'approved_at' => 'datetime',
        'admin_validated_at' => 'datetime',
        'deadline_correction' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_owner_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function adminValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_validated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class)->orderBy('order');
    }

    public function transmissions(): HasMany
    {
        return $this->hasMany(Transmission::class)->latest();
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', self::class)
            ->latest();
    }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getAioLibelleAttribute(): string
    {
        return self::AIOS[$this->aio] ?? strtoupper($this->aio);
    }

    public function getStatusLibelleAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPhaseLibelleAttribute(): string
    {
        if ($this->phase === 'projet') {
            return 'Projet'.($this->nom_phase ? ' - '.$this->nom_phase : '');
        }

        return 'Serie'.($this->nom_serie ? ' - '.$this->nom_serie : '');
    }
}
