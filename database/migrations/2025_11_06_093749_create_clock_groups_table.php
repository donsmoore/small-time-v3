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
        Schema::create('clockGroup', function (Blueprint $table) {
            $table->increments('id');
            $table->string('groupName', 50);
            $table->string('weekStartDOW', 20);
            $table->string('weekStartTime', 20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clockGroup');
    }
};
