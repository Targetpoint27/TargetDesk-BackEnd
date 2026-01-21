<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointment_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email'); // email du participant
            $table->string('name')->nullable();
            $table->enum('type', ['contact', 'internal', 'external'])->default('contact');
            $table->enum('status', ['invited', 'accepted', 'declined', 'tentative'])->default('invited');
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamps();

            $table->index('appointment_id');
            $table->unique(['appointment_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointment_participants');
    }
}
