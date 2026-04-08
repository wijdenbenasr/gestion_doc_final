<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class DocumentData
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $aio,
        public readonly string $ligne,
        public readonly string $phase,
        public readonly ?string $nom_phase = null,   // Nom de la phase (si phase=projet)
        public readonly ?string $nom_serie = null,   // Numéro/nom de série (si phase=serie)
        public readonly ?string $deadline = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->all());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            aio: $data['aio'],
            ligne: $data['ligne'],
            phase: $data['phase'],
            nom_phase: $data['nom_phase'] ?? null,
            nom_serie: $data['nom_serie'] ?? null,
            deadline: $data['deadline'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'aio' => $this->aio,
            'ligne' => $this->ligne,
            'phase' => $this->phase,
            'nom_phase' => $this->nom_phase,
            'nom_serie' => $this->nom_serie,
            'deadline' => $this->deadline,
        ], fn ($v) => $v !== null);
    }
}
