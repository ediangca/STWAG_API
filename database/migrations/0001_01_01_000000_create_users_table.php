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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('session_id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->string('member_id')->primary();
            $table->foreignId('user_id')->index();
            $table->string('firstname', 45);
            $table->string('lastname', 45);
            $table->char('mi')->nullable();
            $table->string('type');
            $table->integer('level');
            $table->string('referencecode')->unique();
            $table->string('uplinecode');
            $table->timestamps();
        });

        Schema::create('device', function (Blueprint $table) {
            $table->string('deviceid')->primary();
            $table->string('devicename', 45);
            $table->foreignId('user_id')->index();
            $table->timestamps();
        });
        
        Schema::create('wallets', function (Blueprint $table) {
            $table->string('wallet_id')->primary();
            $table->foreignId('user_id')->index();
            $table->timestamps();
        });

        Schema::create('wallet_item', function (Blueprint $table) {
            $table->string('walletitem_id')->primary();
            $table->foreignId('user_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('device');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('wallet_item');
    }
};
