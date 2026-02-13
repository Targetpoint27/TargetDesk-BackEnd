<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKycFieldsToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Informations entreprise
            $table->string('brand_workshop')->nullable()->comment('Marque/Atelier');
            $table->string('legal_form')->nullable()->comment('Forme juridique');

            // Représentant légal
            $table->string('legal_representative_first_name')->nullable()->comment('Prénom du représentant légal');
            $table->string('legal_representative_last_name')->nullable()->comment('Nom du représentant légal');

            // Bénéficiaire effectif
            $table->string('beneficial_owner_first_name')->nullable()->comment('Prénom du bénéficiaire effectif');
            $table->string('beneficial_owner_last_name')->nullable()->comment('Nom du bénéficiaire effectif');

            // Informations bancaires
            $table->string('bank')->nullable()->comment('Banque');
            $table->string('bank_account_type')->nullable()->comment('Type de compte bancaire');
            $table->string('payment_moment')->nullable()->comment('Moment de paiement');
            $table->boolean('payment_in_foreign_currency')->nullable()->default(false)->comment('Paiement en devise ?');
            $table->boolean('has_bank_identity_statement')->nullable()->default(false)->comment('Relevé d\'identité bancaire ?');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'brand_workshop',
                'legal_form',
                'legal_representative_first_name',
                'legal_representative_last_name',
                'beneficial_owner_first_name',
                'beneficial_owner_last_name',
                'bank',
                'bank_account_type',
                'payment_moment',
                'payment_in_foreign_currency',
                'has_bank_identity_statement'
            ]);
        });
    }
}
