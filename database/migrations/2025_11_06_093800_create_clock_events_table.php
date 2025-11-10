<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clockEvent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->enum('inOrOut', ['IN', 'OUT']);
            $table->datetime('eventTime');
            $table->timestamps();
            
            $table->index('userId');
            $table->index('eventTime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clockEvent');
    }
};
