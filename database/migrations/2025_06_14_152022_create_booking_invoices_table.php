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
        Schema::create('booking_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid');
            $table->dateTime('issued_at');
            $table->dateTime('due_at')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->boolean('is_walk_in')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_invoices');
    }
};
