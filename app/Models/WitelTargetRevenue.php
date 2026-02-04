<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * WitelTargetRevenue Model
 * 
 * PURPOSE:
 * - Menyimpan target_revenue_bill per witel-divisi
 * - Khusus untuk divisi DPS & DSS
 * - Data diinput via import terpisah (bukan dari CC)
 * 
 * USAGE:
 * - Total target revenue witel = 
 *   SUM(target_revenue_sold dari cc_revenues) + 
 *   target_revenue_bill dari table ini
 */
class WitelTargetRevenue extends Model
{
    use HasFactory;

    protected $table = 'witel_target_revenues';

    protected $fillable = [
        'witel_id',
        'divisi_id',
        'target_revenue_bill',
        'bulan',
        'tahun',
    ];

    protected $casts = [
        'target_revenue_bill' => 'decimal:2',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Witel yang memiliki target ini
     */
    public function witel()
    {
        return $this->belongsTo(Witel::class);
    }

    /**
     * Divisi yang memiliki target ini (DPS/DSS only)
     */
    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by period (year + month)
     */
    public function scopeByPeriod($query, $year, $month = null)
    {
        $query = $query->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query;
    }

    /**
     * Filter by witel
     */
    public function scopeByWitel($query, $witelId)
    {
        return $query->where('witel_id', $witelId);
    }

    /**
     * Filter by divisi
     */
    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    /**
     * Filter by divisi kode
     */
    public function scopeByDivisiKode($query, $kode)
    {
        return $query->whereHas('divisi', function($q) use ($kode) {
            $q->where('kode', $kode);
        });
    }

    /**
     * Only DPS divisi
     */
    public function scopeDps($query)
    {
        return $query->whereHas('divisi', function($q) {
            $q->where('kode', 'DPS');
        });
    }

    /**
     * Only DSS divisi
     */
    public function scopeDss($query)
    {
        return $query->whereHas('divisi', function($q) {
            $q->where('kode', 'DSS');
        });
    }

    /**
     * With relationships (for eager loading)
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['witel:id,nama', 'divisi:id,nama,kode']);
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get period string (YYYY-MM)
     */
    public function getPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->tahun, $this->bulan);
    }

    /**
     * Get period name (Indonesian format)
     */
    public function getPeriodNameAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$this->bulan] . ' ' . $this->tahun;
    }

    /**
     * Get witel nama
     */
    public function getWitelNamaAttribute(): ?string
    {
        return $this->witel?->nama;
    }

    /**
     * Get divisi kode
     */
    public function getDivisiKodeAttribute(): ?string
    {
        return $this->divisi?->kode;
    }

    /**
     * Get divisi nama
     */
    public function getDivisiNamaAttribute(): ?string
    {
        return $this->divisi?->nama;
    }

    /**
     * Get formatted target revenue
     */
    public function getTargetRevenueFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->target_revenue_bill, 0, ',', '.');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Check if divisi is DPS
     */
    public function isDps(): bool
    {
        return $this->divisi?->kode === 'DPS';
    }

    /**
     * Check if divisi is DSS
     */
    public function isDss(): bool
    {
        return $this->divisi?->kode === 'DSS';
    }

    // ========================================
    // STATIC METHODS FOR AGGREGATION
    // ========================================

    /**
     * Get total target bill by witel for specific period
     */
    public static function getTotalByWitel($witelId, $year, $month = null)
    {
        $query = static::where('witel_id', $witelId)
                       ->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->sum('target_revenue_bill');
    }

    /**
     * Get total target bill by divisi for specific period
     */
    public static function getTotalByDivisi($divisiId, $year, $month = null)
    {
        $query = static::where('divisi_id', $divisiId)
                       ->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->sum('target_revenue_bill');
    }

    /**
     * Get all witel targets for specific period (grouped by witel)
     */
    public static function getByPeriodGrouped($year, $month = null)
    {
        $query = static::with(['witel', 'divisi'])
                       ->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('
                witel_id,
                divisi_id,
                SUM(target_revenue_bill) as total_target
            ')
            ->groupBy('witel_id', 'divisi_id')
            ->get();
    }

    /**
     * Get breakdown target bill per witel (DPS + DSS)
     */
    public static function getWitelBreakdown($witelId, $year, $month = null)
    {
        $query = static::with('divisi')
                       ->where('witel_id', $witelId)
                       ->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        $results = $query->get();

        return [
            'witel_id' => $witelId,
            'year' => $year,
            'month' => $month,
            'dps_target' => $results->where('divisi.kode', 'DPS')->sum('target_revenue_bill'),
            'dss_target' => $results->where('divisi.kode', 'DSS')->sum('target_revenue_bill'),
            'total_target' => $results->sum('target_revenue_bill'),
        ];
    }

    // ========================================
    // BOOT METHOD
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($witelTarget) {
            // Validasi periode
            if ($witelTarget->bulan < 1 || $witelTarget->bulan > 12) {
                throw new \InvalidArgumentException('Bulan must be between 1 and 12');
            }

            if ($witelTarget->tahun < 2000 || $witelTarget->tahun > 2099) {
                throw new \InvalidArgumentException('Tahun must be between 2000 and 2099');
            }

            // Validasi divisi harus DPS atau DSS
            $divisi = \App\Models\Divisi::find($witelTarget->divisi_id);
            if ($divisi && !in_array($divisi->kode, ['DPS', 'DSS'])) {
                throw new \InvalidArgumentException('Target bill hanya untuk divisi DPS dan DSS');
            }

            // Validasi target revenue tidak boleh negatif
            if ($witelTarget->target_revenue_bill < 0) {
                throw new \InvalidArgumentException('Target revenue bill tidak boleh negatif');
            }
        });
    }
}