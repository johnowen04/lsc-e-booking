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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('amount', 10, 2);
            $table->string('method')->nullable();
            $table->string('reference_code')->nullable()->unique();
            $table->string('provider_name')->nullable();
            $table->string('notes')->nullable();
            $table->enum('status', ['draft', 'pending', 'paid', 'failed'])->default('draft');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
