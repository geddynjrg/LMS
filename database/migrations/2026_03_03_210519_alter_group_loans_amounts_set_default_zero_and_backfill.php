<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('group_loans')
            ->whereNull('amount_applied')
            ->update(['amount_applied' => 0]);

        DB::table('group_loans')
            ->whereNull('amount_repaid')
            ->update(['amount_repaid' => 0]);

        DB::table('group_loans')
            ->whereNull('amount_approved')
            ->update(['amount_approved' => 0]);

        DB::table('group_loans')
            ->whereNull('amount_issued')
            ->update(['amount_issued' => 0]);


        // Now alter the columns to have default 0 and make them NOT NULL (if desired)
        Schema::table('group_loans', function (Blueprint $table) {
            // amount_repaid
            $table->integer('amount_applied')
                  ->default(0)
                  ->nullable(false)
                  ->change();

            $table->integer('amount_approved')
                  ->default(0)
                  ->nullable(false)
                  ->change();
            
            $table->integer('amount_issued')
                  ->default(0)
                  ->nullable(false)
                  ->change();

            $table->integer('amount_repaid')
                  ->default(0)
                  ->nullable(false)
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_loans', function (Blueprint $table) {
            $table->integer('amount_applied')->nullable()->default(null)->change();
            $table->integer('amount_approved')->nullable()->default(null)->change();
            $table->integer('amount_issued')->nullable()->default(null)->change();
            $table->integer('amount_repaid')->nullable()->default(null)->change();
        });
    }
};
