<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientInteractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // note, call, appointment, email, modification, opportunity
            $table->unsignedBigInteger('reference_id'); // ID de l'enregistrement spécifique
            $table->string('reference_type'); // nom de la classe (polymorphic)
            $table->string('title');
            $table->text('summary')->nullable();
            $table->enum('importance_level', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->enum('privacy_level', ['public', 'private', 'team'])->default('public');
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable(); // données additionnelles spécifiques
            $table->timestamps();

            $table->index(['client_id', 'occurred_at']);
            $table->index(['type', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_interactions');
    }
}
