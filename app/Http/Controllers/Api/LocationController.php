<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wards;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(private readonly LocationService $locationService)
    {
    }
    /**
     * List wards for a constituency (by name).
     */
    public function wards(Request $request): JsonResponse
    {
        $constituencyName = $this->normalizeConstituencyName(
            (string) $request->query('constituency_name', ''),
        );

        if ($constituencyName === '') {
            return response()->json([
                'success' => false,
                'message' => 'constituency_name query parameter is required',
                'data' => [],
            ], 400);
        }

        $constituency = $this->locationService->resolveConstituencyByName($constituencyName);

        if (!$constituency) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => ['count' => 0],
            ]);
        }

        $wards = Wards::query()
            ->where('constituency_code', $constituency->constituency_code)
            ->orderBy('ward_name')
            ->get(['id', 'ward_name']);

        return response()->json([
            'success' => true,
            'data' => $wards->map(fn (Wards $ward) => [
                'id' => $ward->id,
                'ward_name' => $ward->ward_name,
                'name' => $ward->ward_name,
            ])->values(),
            'meta' => ['count' => $wards->count()],
        ]);
    }

    private function normalizeConstituencyName(string $name): string
    {
        $name = trim($name);
        $name = str_replace(
            ["\u{2019}", "\u{2018}", '`'],
            "'",
            $name,
        );
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return $name;
    }
}
