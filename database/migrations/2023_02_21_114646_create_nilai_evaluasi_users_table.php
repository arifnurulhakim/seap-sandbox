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
        Schema::create('nilai_evaluasi_users', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('evalusi_id');
            $table->double('nilai');
            $table->integer('periode');
            $table->string('label');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_evaluasi_users');
    }
};
