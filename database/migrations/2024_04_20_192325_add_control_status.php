<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Control;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('controls', function (Blueprint $table) {
            $table->integer('status')->default(0);
            });

        // Status :
        // O - Todo => relisation date null
        // 1 - Proposed
        // 2 - Done => relisation date not null
        DB::table('controls')->whereNotNull('realisation_date')->update(['status' => 2]);
        DB::table('controls')->whereNull('realisation_date')->update(['status' => 0]);    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("controls", function(Blueprint $table) {
            $table->dropColumn("status");
        });
    }
};
