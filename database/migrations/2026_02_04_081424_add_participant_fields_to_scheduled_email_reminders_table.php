<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParticipantFieldsToScheduledEmailRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_email_reminders', function (Blueprint $table) {
            $table->unsignedBigInteger('participant_id')->nullable()->after('user_id');
            $table->enum('recipient_type', ['organizer', 'participant'])->default('organizer')->after('participant_id');

            $table->foreign('participant_id')->references('id')->on('appointment_participants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scheduled_email_reminders', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
            $table->dropColumn(['participant_id', 'recipient_type']);
        });
    }
}
