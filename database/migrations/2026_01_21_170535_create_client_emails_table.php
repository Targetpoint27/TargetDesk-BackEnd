<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->string('subject');
            $table->text('content');
            $table->string('from_email');
            $table->string('to_email');
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->enum('status', ['draft', 'sent', 'delivered', 'read', 'replied', 'bounced'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'sent_at']);
            $table->index(['direction', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_emails');
    }
}
