<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Profiles
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->unique();
            $table->string('name')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->boolean('is_active')->default(true);
        });

        DB::table('categories')->insert([
            ['name' => 'IT/CS', 'key' => 'itcs'],
            ['name' => 'Marketing', 'key' => 'marketing'],
            ['name' => 'Design', 'key' => 'design'],
            ['name' => 'Finance', 'key' => 'finance'],
            ['name' => 'Healthcare', 'key' => 'healthcare'],
            ['name' => 'Education', 'key' => 'education'],
            ['name' => 'Engineering', 'key' => 'engineering'],
            ['name' => 'Product Management', 'key' => 'product_mgmt'],
        ]);

        // Status
        Schema::create('status', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->boolean('is_active')->default(true);
        });

        DB::table('status')->insert([
            ['name' => 'Finished', 'key' => 'finished'],
            ['name' => 'In Progress', 'key' => 'inprogress'],
            ['name' => 'Planning', 'key' => 'planning'],
            ['name' => 'On Hold', 'key' => 'onhold'],
            ['name' => 'Cancelled', 'key' => 'cancelled'],
            ['name' => 'Not Started', 'key' => 'notstarted'],
        ]);

        // Projects
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('status_id')->constrained('status');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Link Types
        Schema::create('link_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
        });

        DB::table('link_types')->insert([
            ['key' => 'github', 'name' => 'Github'],
            ['key' => 'linkedin', 'name' => 'LinkedIn'],
            ['key' => 'portfolio', 'name' => 'Personal Website'],
            ['key' => 'liveurl', 'name' => 'Project Link'],
        ]);

        // Asset Types
        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
        });

        DB::table('asset_types')->insert([
            ['key' => 'image', 'name' => 'Image'],
            ['key' => 'video', 'name' => 'Video'],
            ['key' => 'resume', 'name' => 'Resume'],
            ['key' => 'readme', 'name' => 'Read Me'],
        ]);

        // Links (Morphs)
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->string('url');
            $table->foreignId('link_type_id')->constrained('link_types');
            $table->unsignedBigInteger('linkable_id');
            $table->string('linkable_type');
            $table->timestamps();
            $table->softDeletes();
        });

        // Assets (Morphs)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->string('filename')->nullable();
            $table->foreignId('asset_type_id')->constrained('asset_types');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('assetable_id');
            $table->string('assetable_type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
        Schema::dropIfExists('links');
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('link_types');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('status');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('users');
    }
};
