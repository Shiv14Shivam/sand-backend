<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop old constraint
        DB::statement('ALTER TABLE order_items DROP CONSTRAINT IF EXISTS order_items_status_check');

        // Add new constraint with updated statuses
        DB::statement("
            ALTER TABLE order_items
            ADD CONSTRAINT order_items_status_check
            CHECK (status IN ('pending','accepted','declined','processing','delivered'))
        ");
    }

    public function down(): void
    {
        // Rollback to old constraint (only 3 statuses)
        DB::statement('ALTER TABLE order_items DROP CONSTRAINT IF EXISTS order_items_status_check');

        DB::statement("
            ALTER TABLE order_items
            ADD CONSTRAINT order_items_status_check
            CHECK (status IN ('pending','accepted','declined'))
        ");
    }
};
