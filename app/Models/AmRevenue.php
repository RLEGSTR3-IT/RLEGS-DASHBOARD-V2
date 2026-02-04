<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * AmRevenue Model (UPDATED - 2026-02-03)
 * 
 * NEW FEATURES:
 * - getRelatedAmsForSameCC(): Get list of other AMs handling same CC
 * - getTotalProporsiForCC(): Get sum of proporsi for this CC
 * - Enhanced proporsi validation helpers
 * 
 * PROPORSI RANGE FIX:
 * - Database stores: 0.0 - 1.0 (decimal, e.g., 0.4 = 40%)
 * - Display format: 0.4 × 100 = 40%
 * - Validation: 0 ≤ proporsi ≤ 1
 * - Total per CC: SUM(proporsi) = 1.0 (not 100)
 * 
 * CRITICAL NOTE:
 * - AM Revenue ALWAYS uses real_revenue_sold from CC (not real_revenue_bill)
 * - Tidak peduli divisi DPS/DSS/DGS, semua pakai revenue_sold
 */
class AmRevenue extends Model
{
    use HasFactory;

    protected $table = 'am_revenues';

    protected $fillable = [
        'account_manager_id',
        'corporate_customer_id',
        'divisi_id',
        'witel_id',
        'telda_id',
        'proporsi',
        'target_revenue',
        'real_revenue',
        'achievement_rate',
        'bulan',
        'tahun',
    ];

    protected $casts = [
        'proporsi' => 'decimal:4',        // Support 0.0000 - 1.0000 (4 decimal places)
        'target_revenue' => 'decimal:2',
        'real_revenue' => 'decimal:2',
        'achievement_rate' => 'decimal:2',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function accountManager()
    {
        return $this->belongsTo(AccountManager::class);
    }

    public function corporateCustomer()
    {
        return $this->belongsTo(CorporateCustomer::class);
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function witel()
    {
        return $this->belongsTo(Witel::class);
    }

    public function telda()
    {
        return $this->belongsTo(Telda::class);
    }

    /**
     * Get the CC Revenue record for this AM Revenue
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ccRevenue()
    {
        return $this->hasOne(CcRevenue::class, 'corporate_customer_id', 'corporate_customer_id')
                    ->where('divisi_id', $this->divisi_id)
                    ->where('bulan', $this->bulan)
                    ->where('tahun', $this->tahun);
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeByPeriod($query, $year, $month = null)
    {
        $query = $query->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query;
    }

    public function scopeByAccountManager($query, $accountManagerId)
    {
        return $query->where('account_manager_id', $accountManagerId);
    }

    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    public function scopeByWitel($query, $witelId)
    {
        return $query->where('witel_id', $witelId);
    }

    public function scopeByTelda($query, $teldaId)
    {
        return $query->whereNotNull('telda_id')->where('telda_id', $teldaId);
    }

    public function scopeHotdaOnly($query)
    {
        return $query->whereNotNull('telda_id');
    }

    public function scopeAmOnly($query)
    {
        return $query->whereNull('telda_id');
    }

    public function scopeWithRelations($query)
    {
        return $query->with([
            'accountManager:id,nama,nik,role',
            'corporateCustomer:id,nama,nipnas',
            'divisi:id,nama,kode',
            'witel:id,nama',
            'telda:id,nama'
        ]);
    }

    /**
     * Scope: Filter by corporate customer
     */
    public function scopeByCorporateCustomer($query, $corporateCustomerId)
    {
        return $query->where('corporate_customer_id', $corporateCustomerId);
    }

    /**
     * Scope: Filter by CC and period (for related AMs query)
     */
    public function scopeByCcAndPeriod($query, $corporateCustomerId, $year, $month)
    {
        return $query->where('corporate_customer_id', $corporateCustomerId)
                     ->where('tahun', $year)
                     ->where('bulan', $month);
    }

    // ========================================
    // ACCESSORS & MUTATORS
    // ========================================

    /**
     * Get calculated achievement rate
     */
    public function getCalculatedAchievementRateAttribute(): float
    {
        if ($this->target_revenue <= 0) {
            return 0;
        }

        return round(($this->real_revenue / $this->target_revenue) * 100, 2);
    }

    /**
     * Get period in YYYY-MM format
     */
    public function getPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->tahun, $this->bulan);
    }

    /**
     * Get period name in Indonesian format
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
     * Get proporsi as percentage string
     * FIXED: Properly convert 0.4 → "40.00%"
     * 
     * @return string
     */
    public function getProporsiPercentAttribute(): string
    {
        return number_format((float)$this->proporsi * 100, 2) . '%';
    }

    /**
     * Get proporsi as numeric percentage (for calculations)
     * Returns: 0.4 → 40 (without % symbol)
     */
    public function getProporsiPercentNumericAttribute(): float
    {
        return round((float)$this->proporsi * 100, 2);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    public function isHotda(): bool
    {
        return !is_null($this->telda_id);
    }

    public function isAm(): bool
    {
        return is_null($this->telda_id);
    }

    public function getAccountManagerNama(): ?string
    {
        return $this->accountManager?->nama;
    }

    public function getAccountManagerRole(): ?string
    {
        return $this->accountManager?->role;
    }

    public function getCorporateCustomerNama(): ?string
    {
        return $this->corporateCustomer?->nama;
    }

    public function getDivisiKode(): ?string
    {
        // If divisi_id is null, fallback to account manager's primary divisi
        if ($this->divisi) {
            return $this->divisi->kode;
        }

        if ($this->accountManager) {
            return $this->accountManager->getPrimaryDivisi()?->kode;
        }

        return null;
    }

    public function getWitelNama(): ?string
    {
        // If witel_id is null, fallback to account manager's witel
        if ($this->witel) {
            return $this->witel->nama;
        }

        if ($this->accountManager) {
            return $this->accountManager->witel?->nama;
        }

        return null;
    }

    public function getTeldaNama(): ?string
    {
        // Only for HOTDA
        if ($this->telda) {
            return $this->telda->nama;
        }

        if ($this->isHotda() && $this->accountManager) {
            return $this->accountManager->telda?->nama;
        }

        return null;
    }

    // ========================================
    // NEW HELPER METHODS (2026-02-03)
    // ========================================

    /**
     * Get list of other AMs handling the same CC in the same period
     * 
     * PURPOSE:
     * - Show user which other AMs are handling this CC
     * - Useful for edit forms to display related AMs
     * - Helps with proporsi validation UI
     * 
     * RETURNS:
     * Collection of AmRevenue models (excluding current record)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRelatedAmsForSameCC()
    {
        return static::where('corporate_customer_id', $this->corporate_customer_id)
                     ->where('tahun', $this->tahun)
                     ->where('bulan', $this->bulan)
                     ->where('id', '!=', $this->id) // Exclude current record
                     ->with(['accountManager:id,nama,nik,role'])
                     ->get();
    }

    /**
     * Get total proporsi for this CC in this period
     * 
     * PURPOSE:
     * - Validate that sum of proporsi = 1.0 (100%)
     * - Show remaining proporsi available
     * - Display validation warnings
     * 
     * RETURNS:
     * Float (0.0 - 1.0+), ideally should be exactly 1.0
     * 
     * @return float
     */
    public function getTotalProporsiForCC(): float
    {
        return (float) static::where('corporate_customer_id', $this->corporate_customer_id)
                             ->where('tahun', $this->tahun)
                             ->where('bulan', $this->bulan)
                             ->sum('proporsi');
    }

    /**
     * Get remaining proporsi available for this CC
     * 
     * PURPOSE:
     * - Calculate how much proporsi is still available
     * - Useful for adding new AM to existing CC
     * - Display "X% remaining" in UI
     * 
     * RETURNS:
     * Float (remaining proporsi, can be negative if over-allocated)
     * 
     * @return float
     */
    public function getRemainingProporsiForCC(): float
    {
        $total = $this->getTotalProporsiForCC();
        return round(1.0 - $total, 4);
    }

    /**
     * Get related AMs info with proporsi details
     * 
     * PURPOSE:
     * - Get detailed info about other AMs for display in edit form
     * - Includes AM name, proporsi, and revenue details
     * 
     * RETURNS:
     * Array of arrays with AM details
     * 
     * @return array
     */
    public function getRelatedAmsDetails(): array
    {
        $relatedAms = $this->getRelatedAmsForSameCC();
        
        return $relatedAms->map(function($amRevenue) {
            return [
                'id' => $amRevenue->id,
                'account_manager_id' => $amRevenue->account_manager_id,
                'account_manager_nama' => $amRevenue->accountManager?->nama,
                'account_manager_nik' => $amRevenue->accountManager?->nik,
                'proporsi' => $amRevenue->proporsi,
                'proporsi_percent' => $amRevenue->proporsi_percent,
                'target_revenue' => $amRevenue->target_revenue,
                'real_revenue' => $amRevenue->real_revenue,
                'achievement_rate' => $amRevenue->achievement_rate,
            ];
        })->toArray();
    }

    /**
     * Check if proporsi allocation is valid for this CC
     * 
     * PURPOSE:
     * - Validate total proporsi = 1.0 (with small tolerance)
     * - Used before saving/updating
     * 
     * @return bool
     */
    public function isProporsiAllocationValid(): bool
    {
        $total = $this->getTotalProporsiForCC();
        return abs($total - 1.0) < 0.001; // Allow 0.1% tolerance
    }

    /**
     * Get proporsi validation message
     * 
     * PURPOSE:
     * - Generate user-friendly message about proporsi status
     * - Used in forms and validation
     * 
     * @return string
     */
    public function getProporsiValidationMessage(): string
    {
        $total = $this->getTotalProporsiForCC();
        $totalPercent = round($total * 100, 2);
        
        if (abs($total - 1.0) < 0.001) {
            return "✓ Total proporsi valid: {$totalPercent}%";
        } elseif ($total < 1.0) {
            $remaining = round((1.0 - $total) * 100, 2);
            return "⚠ Total proporsi kurang: {$totalPercent}% (sisa {$remaining}%)";
        } else {
            $excess = round(($total - 1.0) * 100, 2);
            return "❌ Total proporsi berlebih: {$totalPercent}% (kelebihan {$excess}%)";
        }
    }

    /**
     * Get count of AMs handling this CC
     * 
     * @return int
     */
    public function getAmCountForCC(): int
    {
        return static::where('corporate_customer_id', $this->corporate_customer_id)
                     ->where('tahun', $this->tahun)
                     ->where('bulan', $this->bulan)
                     ->count();
    }

    /**
     * Check if this is the only AM for this CC
     * 
     * @return bool
     */
    public function isSoleAm(): bool
    {
        return $this->getAmCountForCC() === 1;
    }

    /**
     * Check if this CC has multiple AMs
     * 
     * @return bool
     */
    public function hasMultipleAms(): bool
    {
        return $this->getAmCountForCC() > 1;
    }

    // ========================================
    // VALIDATION METHODS
    // ========================================

    /**
     * Validate proporsi range
     * FIXED: Range is 0-1 (not 0-100)
     */
    public function validateProporsi(): bool
    {
        return $this->proporsi >= 0 && $this->proporsi <= 1;
    }

    /**
     * Validate telda consistency with AM role
     */
    public function validateTeldaConsistency(): bool
    {
        $amRole = $this->accountManager?->role;

        if ($amRole === 'HOTDA') {
            return !is_null($this->telda_id);
        } elseif ($amRole === 'AM') {
            return is_null($this->telda_id);
        }

        return true; // Allow if AM role is not set yet
    }

    // ========================================
    // STATIC METHODS FOR AGGREGATION
    // ========================================

    public static function getTotalRevenueByAM($year, $month = null)
    {
        $query = static::with('accountManager')->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('account_manager_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target, COUNT(*) as cc_count')
            ->groupBy('account_manager_id')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    public static function getTotalRevenueByDivisi($year, $month = null)
    {
        $query = static::with('divisi')->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('divisi_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target, COUNT(DISTINCT account_manager_id) as am_count')
            ->whereNotNull('divisi_id')
            ->groupBy('divisi_id')
            ->get();
    }

    public static function getTotalRevenueByWitel($year, $month = null)
    {
        $query = static::with('witel')->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('witel_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target, COUNT(DISTINCT account_manager_id) as am_count')
            ->whereNotNull('witel_id')
            ->groupBy('witel_id')
            ->get();
    }

    public static function getTop10AM($year, $month = null)
    {
        return static::getTotalRevenueByAM($year, $month)->take(10);
    }

    /**
     * Validate total proporsi untuk satu CC di periode tertentu
     * FIXED: Total harus = 1.0 (not 100)
     */
    public static function validateProporsiTotal($corporateCustomerId, $year, $month)
    {
        $totalProporsi = static::where('corporate_customer_id', $corporateCustomerId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->sum('proporsi');

        // Allow small floating point differences (0.001 = 0.1%)
        return abs($totalProporsi - 1.0) < 0.001;
    }

    /**
     * Get proporsi total untuk satu CC (for debugging/display)
     */
    public static function getProporsiTotal($corporateCustomerId, $year, $month): float
    {
        return (float) static::where('corporate_customer_id', $corporateCustomerId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->sum('proporsi');
    }

    /**
     * Get all AMs for a CC in a period (static method)
     * 
     * @param int $corporateCustomerId
     * @param int $year
     * @param int $month
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAmsForCC($corporateCustomerId, $year, $month)
    {
        return static::where('corporate_customer_id', $corporateCustomerId)
                     ->where('tahun', $year)
                     ->where('bulan', $month)
                     ->with(['accountManager:id,nama,nik,role'])
                     ->get();
    }

    // ========================================
    // BOOT METHOD
    // ========================================

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate achievement rate on saving
        static::saving(function ($amRevenue) {
            // Normalize proporsi if needed (convert from percentage to decimal)
            // If proporsi > 1, assume it's in percentage format (e.g., 40 = 40%)
            if ($amRevenue->proporsi > 1) {
                $amRevenue->proporsi = $amRevenue->proporsi / 100;
                Log::info("Normalized proporsi from percentage to decimal: {$amRevenue->proporsi}");
            }

            // Validate proporsi range (0-1)
            if (!$amRevenue->validateProporsi()) {
                throw new \InvalidArgumentException('Proporsi must be between 0 and 1 (e.g., 0.4 = 40%)');
            }

            // Validate telda consistency
            if (!$amRevenue->validateTeldaConsistency()) {
                throw new \InvalidArgumentException('Telda assignment inconsistent with Account Manager role');
            }

            // Validate period
            if ($amRevenue->bulan < 1 || $amRevenue->bulan > 12) {
                throw new \InvalidArgumentException('Bulan must be between 1 and 12');
            }

            if ($amRevenue->tahun < 2000 || $amRevenue->tahun > 2099) {
                throw new \InvalidArgumentException('Tahun must be between 2000 and 2099');
            }

            // Auto-calculate achievement rate if not set
            if (is_null($amRevenue->achievement_rate)) {
                $amRevenue->achievement_rate = $amRevenue->calculated_achievement_rate;
            }
        });

        // Validate proporsi total after saving
        static::saved(function ($amRevenue) {
            $totalProporsi = static::getProporsiTotal(
                $amRevenue->corporate_customer_id, 
                $amRevenue->tahun, 
                $amRevenue->bulan
            );

            if (!static::validateProporsiTotal($amRevenue->corporate_customer_id, $amRevenue->tahun, $amRevenue->bulan)) {
                Log::warning("Proporsi total for CC {$amRevenue->corporate_customer_id} in {$amRevenue->tahun}-{$amRevenue->bulan} is {$totalProporsi} (should be 1.0)");
            } else {
                Log::info("Proporsi total validated OK: {$totalProporsi} ≈ 1.0");
            }
        });
    }
}