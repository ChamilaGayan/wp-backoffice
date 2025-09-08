<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('wordpress_id')->nullable()->index(); // WP post id
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('status')->default('draft'); // draft/publish etc
            $table->integer('priority')->default(0); // Laravel-only
            $table->timestamp('wp_updated_at')->nullable(); // last updated on WP
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
