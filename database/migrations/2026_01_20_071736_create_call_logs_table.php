<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('called_at');
            $table->unsignedInteger('duration')->nullable(); // minutes
            $table->enum('type', ['outgoing', 'incoming', 'missed'])->default('outgoing');
            $table->string('phone_number')->nullable();
            $table->string('subject');
            $table->text('summary');
            $table->enum('outcome', ['positive', 'negative', 'neutral', 'appointment', 'callback'])->default('neutral');
            $table->boolean('follow_up_required')->default(false);
            $table->timestamp('follow_up_date')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'called_at']);
            $table->index(['user_id', 'called_at']);
            $table->index('outcome');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_logs');
    }
}
