<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class HunianPayment extends Model
{
    public const PERIODS = [
        'daily' => 'Harian',
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
        'custom' => 'Custom',
    ];

    private const UNITS = ['daily' => 'hari', 'weekly' => 'minggu', 'monthly' => 'bulan', 'yearly' => 'tahun'];

    protected $fillable = [
        'occupancy_id', 'room_id', 'room_number', 'occupant_name', 'period_type', 'period_qty',
        'period_start', 'period_end', 'amount', 'proof_path', 'proof_deleted_at', 'status',
        'matched_at', 'matched_by_name',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'integer',
            'proof_deleted_at' => 'datetime',
            'matched_at' => 'datetime',
        ];
    }

    /** Tanggal akhir periode (terbayar sampai); custom memakai tanggal yang diinput. */
    public static function periodEnd(string $type, ?int $qty, Carbon $start, ?string $customEnd): Carbon
    {
        $start = $start->copy()->startOfDay();

        return match ($type) {
            'daily' => $start->addDays($qty),
            'weekly' => $start->addWeeks($qty),
            'monthly' => $start->addMonthsNoOverflow($qty),
            'yearly' => $start->addYearsNoOverflow($qty),
            default => Carbon::parse($customEnd)->startOfDay(),
        };
    }

    /** Nominal otomatis hanya untuk bulanan/tahunan (dari harga sewa bulanan); selain itu null. */
    public static function defaultAmount(string $type, ?int $qty, int $monthlyRent): ?int
    {
        return match ($type) {
            'monthly' => $monthlyRent * $qty,
            'yearly' => $monthlyRent * 12 * $qty,
            default => null,
        };
    }

    public function periodLabel(): string
    {
        return $this->period_type === 'custom'
            ? 'Custom'
            : self::PERIODS[$this->period_type].' · '.$this->period_qty.' '.self::UNITS[$this->period_type];
    }
}
