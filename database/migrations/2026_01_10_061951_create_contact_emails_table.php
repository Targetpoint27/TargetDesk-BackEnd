<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->enum('type', ['professionnel', 'personnel'])->default('professionnel');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Index pour performance et unicité
            $table->index(['contact_id', 'is_primary']);
            $table->index('email');

            // Contrainte : email unique par client (via contact)
            // Cette contrainte sera gérée dans le code métier
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_emails');
    }
}
