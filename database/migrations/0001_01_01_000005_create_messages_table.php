<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->string('channel');
            $table->string('payload_type')->nullable();
            $table->json('payload')->nullable();
            $table->json('validation')->nullable();
            $table->text('text')->nullable();
            $table->dateTime('received_at');
            $table->timestamps();

            $table->unique(['external_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
