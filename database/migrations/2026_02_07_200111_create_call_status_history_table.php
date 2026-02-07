<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallStatusHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('call_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->string('old_status');
            $table->string('new_status');
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('changed_by');
            $table->timestamp('created_at');
            
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('call_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_status_history');
    }
}