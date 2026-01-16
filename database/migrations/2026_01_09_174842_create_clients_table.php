<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 20)->unique(); // Identifiant unique généré

            // Champs obligatoires
            $table->string('name'); // Nom/Raison sociale
            $table->enum('type', ['particulier', 'entreprise']); // Type
            $table->string('email')->unique(); // Email principal

            // Champs optionnels
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('siret', 14)->unique()->nullable();
            $table->string('sector')->nullable(); // Secteur d'activité
            $table->string('website')->nullable();
            $table->text('notes')->nullable();

            // Métadonnées
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
