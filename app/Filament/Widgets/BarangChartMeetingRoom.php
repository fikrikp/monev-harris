<?php

namespace App\Filament\Widgets;

use App\Models\Barang;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js; // Pastikan ini diimpor!

class BarangChartMeetingRoom extends ChartWidget
{
    protected static ?string $heading = 'Kondisi Barang Inventaris Area Meeting Room';

    protected function getData(): array
    {
        $areaName = "Meeting Room";

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
                    'backgroundColor' => '#4ade80', // hijau
                ],
                [
                    'label' => 'Rusak',
                    'data' => $rusakData,
                    'backgroundColor' => '#f87171', // merah
                ],
            ],
            'labels' => $itemNames->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getPlugins(): array
    {
        return [
            'annotation',
        ];
    }

    protected function getOptions(): ?array
    {
        $areaName = "Meeting Room";

        $totalMeetingRoomItems = Barang::whereHas('room.area', function ($query) use ($areaName) {
            $query->where('area_name', $areaName);
        })->count();

        $thresholdValue = max(1, round($totalMeetingRoomItems * 0.20));

        Log::info('Chart Options Generated:', ['thresholdValue' => $thresholdValue, 'totalItems' => $totalMeetingRoomItems]);

        $options = [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'labels' => [
                        'color' => 'rgba(0,0,0,0.7)',
                    ],
                ],
                'annotation' => [
                    'annotations' => [
                        'thresholdLine' => [
                            'type' => 'line',
                            'yMin' => $thresholdValue,
                            'yMax' => $thresholdValue,
                            'borderColor' => 'rgba(0,0,0,0.7)',
                            'borderWidth' => 2,
                            'borderDash' => [5, 5],
                            'label' => [
                                'content' => 'Batas Kerusakan (20% dari Barang Meeting Room: ' . $thresholdValue . ')',
                                'enabled' => true,
                                'position' => 'end',
                                'backgroundColor' => '#FFC107',
                                'font' => [
                                    'size' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
                // ✅ PERBAIKAN: Ubah menjadi \Illuminate\Support\Js::from() dan HAPUS 'new'
                'afterDraw' => \Illuminate\Support\Js::from("
                    function(chart) {
                        console.log('Chart rendered! Options:', chart.options);
                        console.log('Chart plugins:', chart.options.plugins);
                        if (chart.options.plugins && chart.options.plugins.annotation) {
                            console.log('Annotation plugin detected in options:', chart.options.plugins.annotation);
                        } else {
                            console.log('Annotation plugin NOT found in options!');
                        }
                        console.log('Threshold Value passed:', " . $thresholdValue . ");
                    }
                "),
            ],
            'scales' => [
                'x' => [
                    'title' => ['display' => true, 'text' => 'Nama Barang'],
                    'ticks' => ['color' => '#000'],
                ],
                'y' => [
                    'title' => ['display' => true, 'text' => 'Jumlah Barang'],
                    'ticks' => ['beginAtZero' => true, 'color' => '#000'],
                    'min' => 0,
                    'max' => 5,
                ],
            ],
            'aspectRatio' => 2,
        ];
        return $options;
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'chief_engineering';
    }

    public static ?int $sort = 2;
}
