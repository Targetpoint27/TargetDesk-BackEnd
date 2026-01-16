<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('civility', ['M.', 'Mme', 'Dr.', 'Prof.', 'Maître'])->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('function')->nullable(); // Poste/fonction
            $table->string('department')->nullable(); // Service
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Index pour performance
            $table->index(['client_id', 'is_primary']);
            $table->index(['client_id', 'is_active']);
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}
