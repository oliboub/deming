<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Disable FK checks during the swap
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        // ── Step 1: Swap main tables ──────────────────────────────────────────
        // measures (security measures) → controls
        // controls (audit instances)   → measures
        Schema::rename('measures', 'controls_swap_tmp');
        Schema::rename('controls', 'measures');
        Schema::rename('controls_swap_tmp', 'controls');

        // ── Step 2: Fix control_measure pivot ─────────────────────────────────
        // Old: control_id → old controls (audit instances) = now in measures
        //      measure_id → old measures (security measures) = now in controls
        // New: control_id → controls (security measures)
        //      measure_id → measures (audit instances)
        // Solution: swap the column VALUES
        DB::statement('ALTER TABLE control_measure ADD COLUMN swap_tmp INTEGER NULL');
        DB::statement('UPDATE control_measure SET swap_tmp = control_id');
        DB::statement('UPDATE control_measure SET control_id = measure_id');
        DB::statement('UPDATE control_measure SET measure_id = swap_tmp');
        Schema::table('control_measure', function (Blueprint $table) {
            $table->dropColumn('swap_tmp');
        });

        // ── Step 3: Fix control_user ──────────────────────────────────────────
        // control_id had audit instance IDs → now audit instances are in measures
        // Rename column: control_id → measure_id
        Schema::table('control_user', function (Blueprint $table) {
            $table->renameColumn('control_id', 'measure_id');
        });

        // ── Step 4: Fix control_user_group ────────────────────────────────────
        Schema::table('control_user_group', function (Blueprint $table) {
            $table->renameColumn('control_id', 'measure_id');
        });

        // ── Step 5: Fix actions.control_id → measure_id ──────────────────────
        // actions.control_id had audit instance IDs → now in measures table
        Schema::table('actions', function (Blueprint $table) {
            $table->renameColumn('control_id', 'measure_id');
        });

        // ── Step 6: Fix documents.control_id → measure_id ────────────────────
        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('control_id', 'measure_id');
        });

        // ── Step 7: Fix action_measure.measure_id → control_id ───────────────
        // action_measure.measure_id pointed to security measures → now in controls
        Schema::table('action_measure', function (Blueprint $table) {
            $table->renameColumn('measure_id', 'control_id');
        });

        // ── Step 8: Fix exceptions.measure_id → control_id ───────────────────
        // exceptions.measure_id pointed to security measures → now in controls
        if (Schema::hasTable('exceptions') && Schema::hasColumn('exceptions', 'measure_id')) {
            Schema::table('exceptions', function (Blueprint $table) {
                $table->renameColumn('measure_id', 'control_id');
            });
        }

        // Re-enable FK checks
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        // Reverse step 8
        if (Schema::hasTable('exceptions') && Schema::hasColumn('exceptions', 'control_id')) {
            Schema::table('exceptions', function (Blueprint $table) {
                $table->renameColumn('control_id', 'measure_id');
            });
        }

        // Reverse step 7
        Schema::table('action_measure', function (Blueprint $table) {
            $table->renameColumn('control_id', 'measure_id');
        });

        // Reverse step 6
        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('measure_id', 'control_id');
        });

        // Reverse step 5
        Schema::table('actions', function (Blueprint $table) {
            $table->renameColumn('measure_id', 'control_id');
        });

        // Reverse step 4
        Schema::table('control_user_group', function (Blueprint $table) {
            $table->renameColumn('measure_id', 'control_id');
        });

        // Reverse step 3
        Schema::table('control_user', function (Blueprint $table) {
            $table->renameColumn('measure_id', 'control_id');
        });

        // Reverse step 2: swap back control_measure column values
        DB::statement('ALTER TABLE control_measure ADD COLUMN swap_tmp INTEGER NULL');
        DB::statement('UPDATE control_measure SET swap_tmp = control_id');
        DB::statement('UPDATE control_measure SET control_id = measure_id');
        DB::statement('UPDATE control_measure SET measure_id = swap_tmp');
        Schema::table('control_measure', function (Blueprint $table) {
            $table->dropColumn('swap_tmp');
        });

        // Reverse step 1: swap tables back
        Schema::rename('controls', 'controls_swap_tmp');
        Schema::rename('measures', 'controls');
        Schema::rename('controls_swap_tmp', 'measures');

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
