<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentIdToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('status');
            
            $table->foreign('department_id')
                  ->references('id')
                  ->on('departments')
                  ->onDelete('set null');
            
            $table->index('department_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id']);
            $table->dropColumn('department_id');
        });
    }
}