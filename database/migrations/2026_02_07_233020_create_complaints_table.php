<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintsTable extends Migration
{
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            // ID unique (US-CC-023: REC-2024-0001)
            $table->string('complaint_id', 20)->unique();
            
            // Link to the original call
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('client_id')->nullable();
            
            // Categorization (US-CC-023)
            $table->enum('category', [
                'produit_defectueux', 
                'service_insatisfaisant', 
                'livraison_retard', 
                'facturation_erronee', 
                'comportement_personnel', 
                'autre'
            ]);
            $table->enum('severity', ['faible', 'moyen', 'eleve', 'critique']);
            
            // Workflow Status (US-CC-024)
            $table->enum('status', [
                'ouverte', 
                'en_analyse', 
                'en_attente_client', 
                'en_attente_interne', 
                'resolue', 
                'cloture'
            ])->default('ouverte');
            
            // SLA Management
            $table->timestamp('sla_deadline')->nullable();
            
            // Processing Details (US-CC-025)
            $table->text('description'); // Initial description
            $table->text('actions_taken')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('proposed_solution')->nullable();
            $table->text('compensation_details')->nullable();
            $table->text('prevention_measures')->nullable();
            
            // Resolution (US-CC-026)
            $table->text('resolution_summary')->nullable();
            $table->enum('client_satisfaction', [
                'satisfait', 
                'partiellement_satisfait', 
                'non_satisfait'
            ])->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            
            // Closure (US-CC-027)
            $table->text('closing_comment')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            
            // Ownership
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign Keys
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for Dashboard performance
            $table->index('status');
            $table->index('severity');
            $table->index('category');
            $table->index('assigned_to');
        });
    }

    public function down()
    {
        Schema::dropIfExists('complaints');
    }
}