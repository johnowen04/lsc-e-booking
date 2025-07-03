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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('booking_number')->unique()->nullable();
            $table->foreignId('booking_invoice_id')->nullable()->constrained('booking_invoices')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->enum('status', ['held', 'confirmed', 'cancelled', 'expired'])->default('held');
            $table->enum('attendance_status', ['pending', 'attended', 'no_show'])->default('pending');
            $table->dateTime('must_check_in_before');
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('rescheduled_from_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
