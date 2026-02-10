<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->decimal('estimated_budget', 12, 2)->nullable();
            $table->decimal('actual_budget', 12, 2)->nullable();
            $table->date('start_date');
            $table->date('planned_end_date');
            $table->date('actual_end_date')->nullable();
            $table->enum('status', ['en_cours', 'en_attente', 'en_danger', 'termine', 'annule'])->default('en_cours');
            $table->integer('progress_percentage')->default(0);
            $table->enum('profitability_indicator', ['green', 'orange', 'red'])->nullable();
            $table->enum('risk_indicator', ['low', 'medium', 'high'])->default('low');
            // Client (interne existant ou externe)
            $table->enum('client_type', ['interne', 'externe'])->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients'); // Client interne existant
            $table->json('external_client_info')->nullable(); // Infos client externe

            // Relations
            $table->foreignId('project_manager_id')->constrained('users');
            $table->string('department');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();

            // Index
            $table->index(['status', 'project_manager_id']);
            $table->index(['start_date', 'planned_end_date']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
