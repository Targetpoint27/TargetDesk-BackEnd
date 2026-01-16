<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierIdToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Ajouter le support des fournisseurs
            $table->unsignedBigInteger('supplier_id')->nullable()->after('client_id')
                  ->comment('ID du fournisseur (exclusif avec client_id)');

            // Ajouter les contraintes de clé étrangère
            $table->foreign('supplier_id')->references('id')->on('suppliers');

            // Index pour les performances
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Supprimer les contraintes et colonnes ajoutées
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
}
