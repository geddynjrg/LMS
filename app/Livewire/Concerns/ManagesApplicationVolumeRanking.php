<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Computed;

trait ManagesApplicationVolumeRanking
{
    public string $applicationVolumeSearch = '';

    /**
     * @return array{county_code?: string, constituency_code?: string}
     */
    protected function applicationVolumeFilters(): array
    {
        return [];
    }

    #[Computed]
    public function applicationVolumeRows(): array
    {
        $rows = $this->analysisService->getApplicationsPerConstituency($this->applicationVolumeFilters());

        foreach ($rows as $index => $row) {
            $row->rank = $index + 1;
        }

        return $rows;
    }

    #[Computed]
    public function filteredApplicationVolumeRows(): array
    {
        $rows = collect($this->applicationVolumeRows);
        $search = trim(strtolower($this->applicationVolumeSearch));

        if ($search === '') {
            return $rows->all();
        }

        return $rows
            ->filter(function ($row) use ($search) {
                return str_contains(strtolower($row->constituency_name), $search)
                    || str_contains(strtolower($row->county_name ?? ''), $search);
            })
            ->values()
            ->all();
    }

    #[Computed]
    public function applicationVolumeStats(): array
    {
        $rows = collect($this->applicationVolumeRows);
        $top = $rows->first();

        return [
            'total' => (int) $rows->sum('application_count'),
            'constituency_count' => $rows->count(),
            'active_constituencies' => (int) $rows->where('application_count', '>', 0)->count(),
            'top_name' => $top?->constituency_name,
            'top_count' => (int) ($top?->application_count ?? 0),
            'max_count' => max((int) ($top?->application_count ?? 0), 1),
        ];
    }
}
