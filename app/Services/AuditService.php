<?php

namespace App\Services;

use App\Models\Audit;

class AuditService
{
    public function create(array $data): Audit
    {
        return Audit::create($data);
    }
}