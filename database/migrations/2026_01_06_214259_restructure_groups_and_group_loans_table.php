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
        Schema::table('group_loans', function (Blueprint $table) {
            $table->string('gfs_code')->nullable()->after('group_id');
            $table->string('category')->nullable()->after('gfs_code');
            $table->string('sector')->nullable()->after('category'); // Changed from 'activity'
            $table->string('sector_desc')->nullable()->after('sector'); // Changed from 'activity_desc'

            // Refactoring 'level' to 'loan_type' and adding 'loan_level'
            $table->renameColumn('level', 'loan_type');
            $table->integer('loan_level')->default(1)->after('loan_status'); // e.g., 1st loan, 2nd loan

            $table->decimal('amount_due', 15, 2)->default(0.00)->after('total_arrears');
        });

        DB::statement("
            UPDATE group_loans
            JOIN groups ON group_loans.group_id = groups.id
            SET
                group_loans.gfs_code = groups.gfs_code,
                group_loans.category = groups.category,
                group_loans.sector = groups.activity,
                group_loans.sector_desc = groups.activity_desc
        ");

        // Remove columns from groups table
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['gfs_code', 'category', 'activity', 'activity_desc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('gfs_code')->nullable();
            $table->string('category')->nullable();
            $table->string('activity')->nullable();
            $table->string('activity_desc')->nullable();
        });

        DB::statement("
            UPDATE groups
            JOIN group_loans ON groups.id = group_loans.group_id
            SET
                groups.gfs_code = group_loans.gfs_code,
                groups.category = group_loans.category,
                groups.activity = group_loans.sector,
                groups.activity_desc = group_loans.sector_desc
        ");

        Schema::table('group_loans', function (Blueprint $table) {
            $table->renameColumn('loan_type', 'level');
            $table->dropColumn(['gfs_code', 'category', 'sector', 'sector_desc', 'loan_level', 'amount_due']);
        });
    }
};
