<?php

namespace App\Repositories\Interfaces;

use App\Models\Document;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DocumentRepositoryInterface
{
    public function findById(int $id): ?Document;

    public function findByCode(string $code): ?Document;

    public function getAllPaginated(int $perPage = 20): LengthAwarePaginator;

    public function getByRolePaginated(string $role, ?int $userId = null, int $perPage = 20): LengthAwarePaginator;

    public function create(array $data): Document;

    public function update(Document $document, array $data): bool;

    public function delete(Document $document): bool;

    public function addVersion(Document $document, array $data): void;

    public function getVersions(Document $document): Collection;

    public function getStats(): array;
}
