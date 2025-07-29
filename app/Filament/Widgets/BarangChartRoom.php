<?php

namespace App\Filament\Widgets;

use App\Models\Barang;
use Filament\Widgets\ChartWidget;

class BarangChartRoom extends ChartWidget
{
    protected static ?string $heading = 'Kondisi Barang Inventaris Area Room';

    protected function getData(): array
    {
        $areaName = 'Room'; // Ganti ini sesuai dengan nama area 'Room' di database kamu

        // Ambil semua item_name unik di area Room
        $itemNames = Barang::whereHas('room.area', function ($query) use ($areaName) {
            $query->where('area_name', $areaName);
        })
            ->select('item_name')
            ->distinct()
            ->pluck('item_name');

        $baikData = [];
        $rusakData = [];

        foreach ($itemNames as $itemName) {
            $baikCount = Barang::where('item_name', $itemName)
                ->where('condition', 'baik')
                ->whereHas('room.area', function ($query) use ($areaName) {
                    $query->where('area_name', $areaName);
                })
                ->count();

            $rusakCount = Barang::where('item_name', $itemName)
                ->where('condition', 'rusak')
                ->whereHas('room.area', function ($query) use ($areaName) {
                    $query->where('area_name', $areaName);
                })
                ->count();

            $baikData[] = $baikCount;
            $rusakData[] = $rusakCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Baik',
                    'data' => $baikData,
                    'backgroundColor' => '#4ade80',
                ],
                [
                    'label' => 'Rusak',
                    'data' => $rusakData,
                    'backgroundColor' => '#f87171',
                ],
            ],
            'labels' => $itemNames->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'labels' => [
                        'color' => '#333',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'title' => ['display' => true, 'text' => 'Nama Barang'],
                    'ticks' => ['color' => '#000'],
                ],
                'y' => [
                    'title' => ['display' => true, 'text' => 'Jumlah Barang'],
                    'ticks' => ['beginAtZero' => true, 'color' => '#000'],
                ],
            ],
            'aspectRatio' => 2,
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'chief_engineering';
    }

    public static ?int $sort = 2;
}
