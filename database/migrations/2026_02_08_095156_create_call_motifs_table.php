<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('call_motifs', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->enum('category', ['info', 'reclamation', 'support', 'commercial', 'autre']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->integer('sla_hours')->nullable();
            $table->text('suggested_script')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->foreign('parent_id')->references('id')->on('call_motifs')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            
            $table->index('parent_id');
            $table->index('department_id');
            $table->index('category');
            $table->index('is_active');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_motifs');
    }
};