<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->json('transfer_levels')->nullable()->after('allocation_2025_2026');
            $table->json('phase_issued_amounts')->nullable()->after('transfer_levels');
        });

        DB::table('constituency_allocation_profiles')->orderBy('id')->each(function ($profile) {
            $transferLevels = [];

            for ($level = 1; $level <= 5; $level++) {
                $column = "transfer_{$level}";
                $transferLevels[] = [
                    'level' => $level,
                    'amount' => (float) ($profile->{$column} ?? 0),
                ];
            }

            DB::table('constituency_allocation_profiles')
                ->where('id', $profile->id)
                ->update([
                    'transfer_levels' => json_encode($transferLevels),
                    'phase_issued_amounts' => json_encode([]),
                ]);
        });

        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'transfer_1',
                'transfer_2',
                'transfer_3',
                'transfer_4',
                'transfer_5',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->decimal('transfer_1', 15, 2)->default(0);
            $table->decimal('transfer_2', 15, 2)->default(0);
            $table->decimal('transfer_3', 15, 2)->default(0);
            $table->decimal('transfer_4', 15, 2)->default(0);
            $table->decimal('transfer_5', 15, 2)->default(0);
        });

        DB::table('constituency_allocation_profiles')->orderBy('id')->each(function ($profile) {
            $levels = json_decode($profile->transfer_levels ?? '[]', true) ?: [];
            $payload = [];

            foreach ($levels as $level) {
                $index = (int) ($level['level'] ?? 0);

                if ($index >= 1 && $index <= 5) {
                    $payload["transfer_{$index}"] = (float) ($level['amount'] ?? 0);
                }
            }

            DB::table('constituency_allocation_profiles')
                ->where('id', $profile->id)
                ->update($payload);
        });

        Schema::table('constituency_allocation_profiles', function (Blueprint $table) {
            $table->dropColumn(['transfer_levels', 'phase_issued_amounts']);
        });
    }
};
