<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallsTable extends Migration
{
    public function up()
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('call_id', 20)->unique();
            
            $table->enum('type', ['entrant', 'sortant']);
            $table->string('phone_number');
            $table->string('caller_name')->nullable();
            
            $table->unsignedBigInteger('department_id');
            $table->string('object');
            $table->text('summary');
            
            $table->enum('urgency', ['normal', 'urgent', 'critique'])->default('normal');
            $table->enum('status', [
                'a_traiter',
                'en_cours',
                'en_attente',
                'a_rappeler',
                'resolu',
                'cloture',
                'annule'
            ])->default('a_traiter');
            
            $table->enum('outbound_reason', [
                'rappel_client',
                'prospection',
                'suivi_commande',
                'enquete_satisfaction',
                'relance_paiement'
            ])->nullable();
            $table->enum('call_result', [
                'contacte',
                'messagerie',
                'pas_de_reponse',
                'numero_errone',
                'refuse'
            ])->nullable();
            $table->integer('call_duration_seconds')->nullable();
            
            $table->date('scheduled_callback_date')->nullable();
            $table->time('scheduled_callback_time')->nullable();
            $table->text('callback_reason')->nullable();
            $table->text('callback_notes')->nullable();
            $table->integer('callback_attempts')->default(0);
            $table->timestamp('last_callback_at')->nullable();
            
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('related_to_type')->nullable();
            $table->unsignedBigInteger('related_to_id')->nullable();
            
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');
            
            $table->text('resolution_summary')->nullable();
            $table->enum('final_result', [
                'resolu_satisfait',
                'resolu_insatisfait',
                'transfere',
                'non_resolu'
            ])->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->integer('treatment_time_seconds')->nullable();
            
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('call_id');
            $table->index('phone_number');
            $table->index('department_id');
            $table->index('status');
            $table->index('urgency');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index(['type', 'status']);
            
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calls');
    }
}