<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_id', 20)->unique()->comment('ID unique généré automatiquement');

            // Champs de base (similaires aux clients)
            $table->string('name')->comment('Nom/Raison sociale du fournisseur');
            $table->enum('type', ['particulier', 'entreprise'])->comment('Type de fournisseur');
            $table->string('email')->unique()->comment('Email principal');
            $table->string('phone', 20)->nullable()->comment('Téléphone principal');
            $table->text('address')->nullable()->comment('Adresse complète');
            $table->string('siret', 14)->nullable()->unique()->comment('Numéro SIRET');
            $table->string('sector', 100)->nullable()->comment('Secteur d\'activité');
            $table->string('website')->nullable()->comment('Site web');
            $table->text('notes')->nullable()->comment('Notes internes');

            // Champs spécifiques aux fournisseurs
            $table->enum('relation_type', ['fournisseur', 'client_et_fournisseur'])->default('fournisseur')
                  ->comment('Type de relation commerciale');
            $table->string('payment_terms', 100)->nullable()->comment('Conditions de paiement');
            $table->integer('delivery_delay')->nullable()->comment('Délai de livraison en jours');
            $table->string('currency', 3)->default('EUR')->comment('Devise (ISO 4217)');

            // Métadonnées
            $table->boolean('is_active')->default(true)->comment('Fournisseur actif');
            $table->unsignedBigInteger('created_by')->comment('Créé par (user_id)');
            $table->timestamps();

            // Index pour les performances
            $table->index('supplier_id');
            $table->index('email');
            $table->index('is_active');
            $table->index('relation_type');
            $table->index('created_by');

            // Contrainte de clé étrangère
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
}
