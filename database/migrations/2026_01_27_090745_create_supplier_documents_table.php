<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');

            // Informations du document
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['contrat', 'devis', 'facture', 'autre'])->default('autre');

            // Informations fichier
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // en bytes
            $table->string('file_extension');

            // Versioning
            $table->integer('version')->default(1);
            $table->string('document_key'); // Clé pour grouper les versions

            // Métadonnées
            $table->json('metadata')->nullable(); // Pour stocker des infos supplémentaires
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_accessed_at')->nullable();

            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['supplier_id', 'category']);
            $table->index(['supplier_id', 'is_active']);
            $table->index(['document_key', 'version']);
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_documents');
    }
}
