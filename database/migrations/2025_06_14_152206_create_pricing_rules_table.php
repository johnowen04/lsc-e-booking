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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('day_of_week')->nullable();
            $table->time('time_start');
            $table->time('time_end');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('price_per_hour', 10, 2);
            $table->enum('type', ['regular', 'peak', 'promo', 'custom'])->default('regular');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
