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
        Schema::dropIfExists('users');

        // Create the users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->text('name')->nullable();
            $table->text('email');
            $table->text('username');
            $table->timestamps();

            $table->unique('email');
            $table->unique('username');
        });

        // Insert test user
        DB::table('users')->insert([
            'name' => 'demo user',
            'email' => 'demo@example.com',
            'username' => 'demouser',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
