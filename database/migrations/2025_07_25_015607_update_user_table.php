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
            $table->id(); // auto-incrementing primary key
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->timestamps();
        });

        // Insert test user
        DB::table('users')->insert([
            'name' => 'test',
            'email' => 'tes@abc.com',
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
