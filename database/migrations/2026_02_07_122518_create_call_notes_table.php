<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallNotesTable extends Migration
{
    public function up()
    {
        Schema::create('call_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->text('note');
            $table->boolean('is_important')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('call_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_notes');
    }
}