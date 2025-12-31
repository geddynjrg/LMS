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
        Schema::table('group_loans', function (Blueprint $table) {
            $table->boolean('is_defaulted')->default(false)->index();
            $table->integer('months_defaulted')->nullable();
            $table->decimal('total_arrears', 15, 2)->default(0);
            $table->timestamp('default_status_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_loans', function (Blueprint $table) {
            $table->dropColumn(['is_defaulted', 'months_defaulted', 'total_arrears', 'default_status_updated_at']);
        });
    }
};
