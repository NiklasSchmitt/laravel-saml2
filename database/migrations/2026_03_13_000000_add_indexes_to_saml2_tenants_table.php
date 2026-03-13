<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saml2_tenants', function (Blueprint $table): void {
            $table->unique('uuid', 'saml2_tenants_uuid_unique');
            $table->index('key', 'saml2_tenants_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('saml2_tenants', function (Blueprint $table): void {
            $table->dropUnique('saml2_tenants_uuid_unique');
            $table->dropIndex('saml2_tenants_key_index');
        });
    }
};