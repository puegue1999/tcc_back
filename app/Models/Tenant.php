<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
// use OwenIt\Auditing\Contracts\Auditable;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    // public function transformAudit(array $data): array
    // {
    //     $data = \App\Services\AuditService::addDbName($data);
    //     return $data;
    // }
}
