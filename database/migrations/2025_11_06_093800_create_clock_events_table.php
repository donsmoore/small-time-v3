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
            $table->increments('id');
            $table->integer('userId');
            $table->string('eventTime', 20);
            $table->string('inOrOut', 5);
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
