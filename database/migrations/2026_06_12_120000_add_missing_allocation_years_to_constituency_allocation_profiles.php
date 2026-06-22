<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->decimal('allocation_2015_2016', 15, 2)->default(0)->after('allocation_2014_2015');
            $table->decimal('allocation_2017_2018', 15, 2)->default(0)->after('allocation_2016_2017');
            $table->decimal('allocation_2019_2020', 15, 2)->default(0)->after('allocation_2018_2019');
            $table->decimal('allocation_2021_2022', 15, 2)->default(0)->after('allocation_2020_2021');
            $table->decimal('allocation_2023_2024', 15, 2)->default(0)->after('allocation_2022_2023');
            $table->decimal('allocation_2025_2026', 15, 2)->default(0)->after('allocation_2024_2025');
        });
    }

    public function down(): void
    {
        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'allocation_2015_2016',
                'allocation_2017_2018',
                'allocation_2019_2020',
                'allocation_2021_2022',
                'allocation_2023_2024',
                'allocation_2025_2026',
            ]);
        });
    }
};
