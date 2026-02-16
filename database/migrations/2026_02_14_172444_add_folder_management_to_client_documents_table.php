<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFolderManagementToClientDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_documents', function (Blueprint $table) {
            // Ajouter gestion des dossiers
            $table->string('folder_path', 500)->nullable()->after('category'); // Chemin du dossier (ex: "KYC/Financier")
            $table->string('folder_name', 200)->nullable()->after('folder_path'); // Nom du dossier parent
            $table->integer('folder_level')->default(0)->after('folder_name'); // Niveau de profondeur (0 = racine)

            // Garder category pour rétrocompatibilité mais le rendre nullable
            $table->string('legacy_category', 50)->nullable()->after('folder_level'); // Ancienne catégorie

            // Index pour optimiser les requêtes de dossiers
            $table->index(['client_id', 'folder_path']);
            $table->index(['client_id', 'folder_name']);
            $table->index(['client_id', 'folder_level']);
        });

        // Migrer les anciennes catégories vers les nouveaux dossiers
        \Illuminate\Support\Facades\DB::statement("UPDATE client_documents SET folder_path = category, folder_name = category, legacy_category = category WHERE category IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_documents', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['client_id', 'folder_path']);
            $table->dropIndex(['client_id', 'folder_name']);
            $table->dropIndex(['client_id', 'folder_level']);

            // Supprimer les colonnes
            $table->dropColumn(['folder_path', 'folder_name', 'folder_level', 'legacy_category']);
        });
    }
}
