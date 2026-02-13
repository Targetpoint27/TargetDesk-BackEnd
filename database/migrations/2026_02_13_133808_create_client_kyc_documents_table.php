<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientKycDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->enum('document_type', [
                'kbis',
                'dlabe',
                'legal_representative_id_recto_verso',
                'accommodation_certificate',
                'beneficial_owner_id_recto_verso',
                'address_proof',
                'beneficial_accommodation_certificate',
                'bank_identity_statement'
            ])->comment('Type de document KYC');
            $table->string('original_name')->comment('Nom original du fichier');
            $table->string('file_path')->comment('Chemin de stockage du fichier');
            $table->string('mime_type')->comment('Type MIME du fichier');
            $table->unsignedBigInteger('file_size')->comment('Taille du fichier en octets');
            $table->boolean('can_preview')->default(false)->comment('Peut être prévisualisé');
            $table->timestamp('uploaded_at')->nullable()->comment('Date de téléchargement');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->comment('Utilisateur qui a téléchargé');
            $table->timestamps();

            $table->index(['client_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_kyc_documents');
    }
}
