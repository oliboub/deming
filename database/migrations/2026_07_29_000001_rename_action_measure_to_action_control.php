<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('action_measure', 'action_control');
    }

    public function down(): void
    {
        Schema::rename('action_control', 'action_measure');
    }
};
