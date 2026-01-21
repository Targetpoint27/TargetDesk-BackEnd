<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->longText('content'); // contenu riche HTML
            $table->enum('type', ['important', 'private', 'normal'])->default('normal');
            $table->boolean('is_pinned')->default(false);
            $table->integer('attachments_count')->default(0);
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['user_id', 'type']);
            $table->fullText(['title', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_notes');
    }
}
