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
        CREATE UNIQUE INDEX unique_active_bookings 
        ON bookings (court_id, starts_at, ends_at)
        WHERE status IN ('held', 'confirmed')
    SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_active_bookings');
    }
};
