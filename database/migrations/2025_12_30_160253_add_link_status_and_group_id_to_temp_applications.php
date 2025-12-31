<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up()
    {
        Schema::table('temp_applications', function (Blueprint $table) {
            $table->enum('link_status', ['not_attempted', 'attempted_no_match', 'linked'])->default('not_attempted');
            // Add the foreign key column

            $table->foreignId('group_id')
                ->nullable() // Must be nullable to use nullOnDelete
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('temp_applications', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn(['link_status', 'group_id']);
        });
    }
};
