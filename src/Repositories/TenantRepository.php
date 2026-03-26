<?php

declare(strict_types=1);

namespace NiklasSchmitt\Saml2\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use NiklasSchmitt\Saml2\Models\Tenant;

class TenantRepository
{
    public function query(bool $withTrashed = false): Builder
    {
        $class = config('saml2.tenantModel', Tenant::class);
        $query = $class::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }

    public function all(bool $withTrashed = true): Collection
    {
        return $this->query($withTrashed)->get();
    }

    public function findByAnyIdentifier(int|string $key, bool $withTrashed = true): Collection
    {
        $query = $this->query($withTrashed);

        if (is_int($key)) {
            return $query->where('id', $key)->get();
        }

        return $query->where('key', $key)
            ->orWhere('uuid', $key)
            ->get();
    }

    public function findByKey(string $key, bool $withTrashed = true): ?Tenant
    {
        return $this->query($withTrashed)
            ->where('key', $key)
            ->first();
    }

    public function findById(int $id, bool $withTrashed = true): ?Tenant
    {
        return $this->query($withTrashed)
            ->where('id', $id)
            ->first();
    }

    public function findByUUID(string $uuid, bool $withTrashed = true): ?Tenant
    {
        return $this->query($withTrashed)
            ->where('uuid', $uuid)
            ->first();
    }
}
