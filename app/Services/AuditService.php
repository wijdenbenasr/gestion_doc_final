<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function log(?int $userId, string $action, Model $auditable, array $payload = [], ?Request $request = null): void
    {
        $attributes = [
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'payload' => $payload,
        ];

        if ($request) {
            $attributes['ip_address'] = $request->ip();
            $attributes['user_agent'] = $request->userAgent();
        }

        AuditLog::create($attributes);
    }
}
