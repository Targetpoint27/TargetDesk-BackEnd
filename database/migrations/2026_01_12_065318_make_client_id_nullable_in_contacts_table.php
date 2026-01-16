<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeClientIdNullableInContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modifier la colonne client_id pour qu'elle soit nullable
        DB::statement('ALTER TABLE contacts MODIFY client_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remettre la colonne client_id comme non-nullable
        DB::statement('ALTER TABLE contacts MODIFY client_id BIGINT UNSIGNED NOT NULL');
    }
}
