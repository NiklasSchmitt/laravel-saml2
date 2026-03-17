<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('saml2_tenants', function (Blueprint $table): void {
            $table->increments('id');
            $table->uuid('uuid');
            $table->string('key')->nullable();
            $table->string('idp_entity_id');
            $table->string('idp_login_url');
            $table->string('idp_logout_url');
            $table->text('idp_x509_cert');
            $table->json('metadata');
            $table->string('name_id_format')->default('persistent');
            $table->string('relay_state_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('saml2_tenants');
    }
};
