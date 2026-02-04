<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamViewSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_view_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique();
            $table->unsignedBigInteger('user_id'); // who activated the session
            $table->json('target_user_ids'); // whose data can be viewed
            $table->text('reason'); // reason for activation
            $table->integer('duration_minutes')->default(60); // session duration
            $table->timestamp('activated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('status')->default('active'); // active, expired, deactivated
            $table->json('accessed_resources')->nullable(); // log of accessed data
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_view_sessions');
    }
}
