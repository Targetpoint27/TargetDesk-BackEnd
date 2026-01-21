<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // organisateur
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration')->default(60); // minutes
            $table->string('timezone')->default('Europe/Paris');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable(); // lien visio
            $table->enum('type', ['commercial', 'support', 'demo', 'negotiation', 'closing', 'other'])->default('commercial');
            $table->enum('status', ['planned', 'confirmed', 'completed', 'cancelled', 'postponed'])->default('planned');
            $table->unsignedInteger('reminder_minutes')->default(15);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('external_calendar_id')->nullable(); // pour sync calendrier
            $table->text('completion_notes')->nullable(); // notes après RDV
            $table->enum('completion_outcome', ['positive', 'negative', 'neutral', 'follow_up'])->nullable();
            $table->timestamps();

            $table->index(['client_id', 'scheduled_at']);
            $table->index(['user_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index('reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
