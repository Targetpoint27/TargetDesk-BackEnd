<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeEmailNullableInClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Supprimer la contrainte unique sur email et le rendre nullable
            $table->string('email')->nullable()->change();
        });

        // Supprimer manuellement l'index unique existant
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_email_unique');
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
            // Restaurer l'email comme required et unique
            $table->string('email')->nullable(false)->unique()->change();
        });
    }
}
