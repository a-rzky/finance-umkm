<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10);
            // Rupiah tidak mengenal sen, integer menghindari galat pembulatan desimal.
            $table->bigInteger('amount');
            // Tanggal murni tanpa jam, agar rekap harian tidak bergeser karena zona waktu.
            $table->date('occurred_on');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            // Menopang query utama: daftar & rekap milik satu tenant pada rentang tanggal.
            $table->index(['tenant_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
