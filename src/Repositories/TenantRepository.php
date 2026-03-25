<?php

namespace NiklasSchmitt\Saml2\Repositories;

use NiklasSchmitt\Saml2\Models\Tenant;

class TenantRepository
{
    public function query(bool $withTrashed = false)
    {
        $class = config('saml2.tenantModel', Tenant::class);
        $query = $class::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }

    public function all(bool $withTrashed = true)
    {
        return $this->query($withTrashed)->get();
    }

    public function findByAnyIdentifier($key, bool $withTrashed = true)
    {
        $query = $this->query($withTrashed);

        if (is_int($key)) {
            return $query->where('id', $key)->get();
        }

        return $query->where('key', $key)
            ->orWhere('uuid', $key)
            ->get();
    }

    public function findByKey(string $key, bool $withTrashed = true)
    {
        return $this->query($withTrashed)
            ->where('key', $key)
            ->first();
    }

    public function findById(int $id, bool $withTrashed = true)
    {
        return $this->query($withTrashed)
            ->where('id', $id)
            ->first();
    }

    public function findByUUID(string $uuid, bool $withTrashed = true)
    {
        return $this->query($withTrashed)
            ->where('uuid', $uuid)
            ->first();
    }
}
