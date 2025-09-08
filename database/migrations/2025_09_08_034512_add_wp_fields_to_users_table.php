<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wordpress_id')->nullable()->unique();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
        });
    }
    public function down() {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wordpress_id','access_token','refresh_token','token_expires_at']);
        });
    }
};
