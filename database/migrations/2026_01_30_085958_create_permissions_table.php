<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // ex: clients.create, clients.read, etc.
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module'); // ex: clients, contacts, documents, etc.
            $table->string('action'); // ex: create, read, update, delete, export, etc.
            $table->string('scope')->default('global'); // global, own, team
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module', 'action', 'scope']);
            $table->index(['module', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissions');
    }
}
