<?php

namespace App\Filament\Widgets;

use App\Models\Barang;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js; // Ini tidak diperlukan di sini!

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

        // Logging PHP untuk debugging
        Log::info('Chart Options Generated:', ['thresholdValue' => $thresholdValue, 'totalItems' => $totalMeetingRoomItems]);

        // Ambil data untuk menentukan max Y-axis
        $data = $this->getData();
        $allDataValues = array_merge($data['datasets'][0]['data'], $data['datasets'][1]['data']);
        $maxYValue = !empty($allDataValues) ? max($allDataValues) : 0; // Pastikan tidak kosong

        // Tentukan max Y-axis agar garis threshold terlihat
        // Ambil nilai terbesar antara data tertinggi dan threshold, lalu tambahkan sedikit margin
        $chartMaxY = max($maxYValue, $thresholdValue) + 2; // +2 untuk sedikit ruang di atas garis

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
                        'thresholdLine' => [ // Menggunakan nama yang lebih deskriptif
                            'type' => 'line',
                            'scaleID' => 'y',
                            'value' => $thresholdValue, // Menggunakan nilai yang dihitung
                            'borderColor' => '#FFC107',
                            'borderWidth' => 2,
                            'borderDash' => [6, 6], // Garis putus-putus
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
                // HAPUS BLOK afterDraw INI DARI SINI
                // 'afterDraw' => \Illuminate\Support\Js::from(" ... "),
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
                    'max' => $chartMaxY, // Menggunakan nilai max Y yang dihitung
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
