<?php

use App\Models\ConstituencyAllocationProfile;
use App\Support\ConstituencyAllocationFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->json('allocation_years')->nullable()->after('allocation_2025_2026');
        });

        ConstituencyAllocationProfile::query()->each(function (ConstituencyAllocationProfile $profile) {
            $years = [];

            foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
                if (! preg_match('/^allocation_(\d{4})_(\d{4})$/', $key, $matches)) {
                    continue;
                }

                $years[] = [
                    'year_start' => (int) $matches[1],
                    'year_end' => (int) $matches[2],
                    'amount' => round((float) $profile->{$key}, 2),
                ];
            }

            $profile->forceFill(['allocation_years' => $years])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->dropColumn('allocation_years');
        });
    }
};
