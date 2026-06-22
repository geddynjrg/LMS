<?php

use App\Models\ConstituencyAllocationProfile;
use App\Support\ConstituencyAllocationFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('constituency_allocation_profiles')->orderBy('id')->each(function ($profile) {
            $allocations = 0.0;

            foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
                $allocations += (float) ($profile->{$key} ?? 0);
            }

            $transfers = collect(json_decode($profile->transfer_levels ?? '[]', true) ?: [])
                ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

            $approvals = collect(json_decode($profile->phase_issued_amounts ?? '[]', true) ?: [])
                ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

            DB::table('constituency_allocation_profiles')
                ->where('id', $profile->id)
                ->update([
                    'balance_main_account' => round(($allocations + $transfers) - $approvals, 2),
                ]);
        });
    }

    public function down(): void
    {
        // Previous balances were manually entered; cannot be restored automatically.
    }
};
