<?php

return [
    'applications' => [
        'electricity' => [
            'name' => 'Kalkulator Listrik',
            'description' => 'Perkirakan pemakaian dan biaya listrik rumah.',
            'icon' => '⚡',
            'route' => 'apps.electricity',
            'features' => [
                'basic' => ['name' => 'Hitung biaya alat', 'description' => 'Hitung estimasi biaya dari daya dan lama pemakaian.'],
                'breakdown' => ['name' => 'Rincian beberapa alat', 'description' => 'Hitung gabungan beberapa alat sekaligus.'],
            ],
        ],
        'discount' => [
            'name' => 'Kalkulator Diskon',
            'description' => 'Hitung harga setelah diskon dan potongan bertingkat.',
            'icon' => '％',
            'route' => 'apps.discount',
            'features' => [
                'basic' => ['name' => 'Diskon tunggal', 'description' => 'Hitung harga setelah satu potongan.'],
                'stacked' => ['name' => 'Diskon bertingkat', 'description' => 'Terapkan dua potongan secara berurutan.'],
            ],
        ],
        'shopping' => [
            'name' => 'Checklist Belanja',
            'description' => 'Catat barang, jumlah, dan status belanja.',
            'icon' => '✓',
            'route' => 'apps.shopping',
            'features' => [
                'checklist' => ['name' => 'Checklist barang', 'description' => 'Tambah, tandai, dan hapus barang belanja.'],
                'multiple_lists' => ['name' => 'Daftar belanja tersimpan', 'description' => 'Buat beberapa daftar belanja terpisah.'],
            ],
        ],
        'fuel' => [
            'name' => 'Kalkulator Bensin',
            'description' => 'Perkirakan kebutuhan dan biaya BBM perjalanan.',
            'icon' => '⛽',
            'route' => 'apps.fuel',
            'features' => [
                'trip_cost' => ['name' => 'Biaya perjalanan', 'description' => 'Hitung liter dan biaya berdasarkan jarak.'],
                'compare_vehicles' => ['name' => 'Bandingkan kendaraan', 'description' => 'Bandingkan biaya dua kendaraan untuk rute yang sama.'],
            ],
        ],
        'hunian' => [
            'name' => 'Kelola Hunian',
            'description' => 'Pantau kondisi setiap kamar, penghuni, dan masa sewa secara visual.',
            'icon' => '⌂',
            'route' => 'apps.hunian',
            'superadmin_only' => true,
            'features' => [],
        ],
    ],
];
