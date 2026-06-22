<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constituency_allocation_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('constituency_code', 10)->unique();
            $table->decimal('allocation_2013_2014', 15, 2)->default(0);
            $table->decimal('allocation_2014_2015', 15, 2)->default(0);
            $table->decimal('allocation_2015_2016', 15, 2)->default(0);
            $table->decimal('allocation_2016_2017', 15, 2)->default(0);
            $table->decimal('allocation_2017_2018', 15, 2)->default(0);
            $table->decimal('allocation_2018_2019', 15, 2)->default(0);
            $table->decimal('allocation_2019_2020', 15, 2)->default(0);
            $table->decimal('allocation_2020_2021', 15, 2)->default(0);
            $table->decimal('allocation_2021_2022', 15, 2)->default(0);
            $table->decimal('allocation_2022_2023', 15, 2)->default(0);
            $table->decimal('allocation_2023_2024', 15, 2)->default(0);
            $table->decimal('allocation_2024_2025', 15, 2)->default(0);
            $table->decimal('allocation_2025_2026', 15, 2)->default(0);
            $table->json('transfer_levels')->nullable();
            $table->json('phase_issued_amounts')->nullable();
            $table->decimal('balance_main_account', 15, 2)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('constituency_code')
                ->references('constituency_code')
                ->on('constituencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constituency_allocation_profiles');
    }
};
