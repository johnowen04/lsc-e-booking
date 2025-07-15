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
        Schema::create('court_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->decimal('price', 10, 2)->nullable();
            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules')->nullOnDelete();
            $table->enum('status', ['available', 'held', 'confirmed', 'attended', 'no_show'])->default('available');
            $table->timestamps();

            $table->unique(['court_id', 'start_at']);
            $table->index(['court_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_schedule_slots');
    }
};
