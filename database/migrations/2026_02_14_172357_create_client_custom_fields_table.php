<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientCustomFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_custom_fields', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Champ personnalisé
            $table->string('field_key', 100); // Clé du champ (ex: "marque", "budget")
            $table->text('field_value')->nullable(); // Valeur du champ
            $table->enum('field_type', ['text', 'number', 'email', 'phone', 'url', 'textarea', 'select', 'date', 'boolean'])->default('text');

            // Métadonnées du champ
            $table->string('field_label', 200)->nullable(); // Libellé affiché à l'utilisateur
            $table->text('field_description')->nullable(); // Description du champ
            $table->json('field_options')->nullable(); // Options pour select, validation rules, etc.
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);

            // Ordre d'affichage
            $table->integer('display_order')->default(0);

            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['client_id', 'field_key']);
            $table->index(['client_id', 'is_active']);
            $table->index(['field_key', 'is_active']);
            $table->index('created_by');

            // Contrainte unique : un client ne peut avoir qu'une seule valeur par clé de champ
            $table->unique(['client_id', 'field_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_custom_fields');
    }
}
