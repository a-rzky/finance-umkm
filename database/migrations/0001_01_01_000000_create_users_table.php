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
        // Pendaftaran hanya meminta nama toko, username, dan kata sandi.
        // Nama toko disimpan di tabel tenants, jadi user tidak punya kolom nama
        // sendiri. Tidak ada kolom email: belum ada jalur pemulihan lewat email,
        // dan menyimpan kolom yang tak pernah diisi hanya menyesatkan.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('username', 30)->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->index('tenant_id');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
