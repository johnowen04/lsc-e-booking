<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<SQL
        CREATE UNIQUE INDEX unique_active_booking_slots
        ON booking_slots (court_id, start_at, end_at)
        WHERE status IN ('held', 'confirmed')
    SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_active_booking_slots');
    }
};
