<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('category');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->timestamp('fetched_at')->nullable(); // Stores FastAPI timestamp!
            $table->timestamps(); // Generates created_at and updated_at automatically
        });
    }
    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};