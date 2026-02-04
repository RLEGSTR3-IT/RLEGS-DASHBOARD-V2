<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * CcRevenue Model - Corporate Customer Revenue (UPDATED - 2026-02-03)
 *
 * MAJOR CHANGES:
 * ✅ DEFENSIVE CODING: Accessors handle missing columns gracefully
 * ✅ SAFE FALLBACK: If new columns don't exist, use old columns
 * ✅ MIGRATION SAFE: Works before AND after migration #3
 *
 * COLUMN STRUCTURE (5 revenue columns):
 * - real_revenue_sold     → Always stored (semua CC punya)
 * - real_revenue_bill     → Always stored (semua CC punya)
 * - target_revenue_sold   → Always stored (semua divisi pakai sold untuk target)
 * - tipe_revenue          → Determines active revenue: 'HO' (sold) or 'BILL' (bill)
 * - revenue_source        → Data source: 'REGULER' or 'NGTMA'
 *
 * COMPUTED ATTRIBUTES (via accessor with fallback):
 * - real_revenue          → Computed based on tipe_revenue (with fallback to old column)
 * - target_revenue        → Alias to target_revenue_sold (with fallback)
 * - achievement_rate      → Computed percentage
 */
class CcRevenue extends Model
{
    use HasFactory;

    protected $table = 'cc_revenues';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'corporate_customer_id',
        'divisi_id',
        'segment_id',
        'witel_ho_id',
        'witel_bill_id',
        'nama_cc',
        'nipnas',
        // Revenue columns (NEW structure)
        'real_revenue_sold',
        'real_revenue_bill',
        'target_revenue_sold',
        'tipe_revenue',      // HO or BILL
        'revenue_source',    // REGULER or NGTMA
        'bulan',
        'tahun',
        // OLD columns (for backward compatibility during migration)
        'target_revenue',
        'real_revenue',
    ];

    /**
     * Append computed attributes to JSON/Array
     */
    protected $appends = [
        'real_revenue',
        'target_revenue',
        'achievement_rate'
    ];

    /**
     * Cached column existence checks
     * Prevents repeated schema queries
     */
    private static $columnCache = null;

    // ========================================
    // DEFENSIVE COLUMN CHECKING
    // ========================================

    /**
     * Check if new columns exist in database (with caching)
     * 
     * @return array
     */
    private static function checkColumnsExist(): array
    {
        if (self::$columnCache !== null) {
            return self::$columnCache;
        }

        try {
            self::$columnCache = [
                'has_real_revenue_sold' => Schema::hasColumn('cc_revenues', 'real_revenue_sold'),
                'has_real_revenue_bill' => Schema::hasColumn('cc_revenues', 'real_revenue_bill'),
                'has_target_revenue_sold' => Schema::hasColumn('cc_revenues', 'target_revenue_sold'),
                'has_old_real_revenue' => Schema::hasColumn('cc_revenues', 'real_revenue'),
                'has_old_target_revenue' => Schema::hasColumn('cc_revenues', 'target_revenue'),
            ];
        } catch (\Exception $e) {
            // Fallback: assume old structure
            Log::warning('Could not check column existence: ' . $e->getMessage());
            self::$columnCache = [
                'has_real_revenue_sold' => false,
                'has_real_revenue_bill' => false,
                'has_target_revenue_sold' => false,
                'has_old_real_revenue' => true,
                'has_old_target_revenue' => true,
            ];
        }

        return self::$columnCache;
    }

    // ========================================
    // SAFE ACCESSORS WITH FALLBACK
    // ========================================

    /**
     * Get Real Revenue (SAFE with fallback)
     * 
     * Logic:
     * 1. If new columns exist:
     *    - If tipe_revenue = 'HO'   → use real_revenue_sold
     *    - If tipe_revenue = 'BILL' → use real_revenue_bill
     * 2. If new columns DON'T exist (before migration):
     *    - Fallback to old column: real_revenue
     * 
     * @return float
     */
    public function getRealRevenueAttribute(): float
    {
        $columns = self::checkColumnsExist();

        // NEW STRUCTURE (after migration)
        if ($columns['has_real_revenue_sold'] && $columns['has_real_revenue_bill']) {
            $tipeRevenue = $this->attributes['tipe_revenue'] ?? 'HO';
            
            if ($tipeRevenue === 'HO') {
                return (float) ($this->attributes['real_revenue_sold'] ?? 0);
            } else {
                return (float) ($this->attributes['real_revenue_bill'] ?? 0);
            }
        }

        // OLD STRUCTURE (before migration) - FALLBACK
        if ($columns['has_old_real_revenue']) {
            return (float) ($this->attributes['real_revenue'] ?? 0);
        }

        // Ultimate fallback
        return 0.0;
    }

    /**
     * Get Target Revenue (SAFE with fallback)
     * 
     * Logic:
     * 1. If new column exists: use target_revenue_sold
     * 2. If new column DON'T exist: use old column target_revenue
     * 
     * @return float
     */
    public function getTargetRevenueAttribute(): float
    {
        $columns = self::checkColumnsExist();

        // NEW STRUCTURE (after migration)
        if ($columns['has_target_revenue_sold']) {
            return (float) ($this->attributes['target_revenue_sold'] ?? 0);
        }

        // OLD STRUCTURE (before migration) - FALLBACK
        if ($columns['has_old_target_revenue']) {
            return (float) ($this->attributes['target_revenue'] ?? 0);
        }

        // Ultimate fallback
        return 0.0;
    }

    /**
     * Get Achievement Rate (SAFE)
     * 
     * Computed as: (real_revenue / target_revenue) * 100
     * Uses safe accessors above
     * 
     * @return float
     */
    public function getAchievementRateAttribute(): float
    {
        $target = $this->target_revenue;
        
        if ($target <= 0) {
            return 0.0;
        }

        $real = $this->real_revenue;
        
        return round(($real / $target) * 100, 2);
    }

    /**
     * Get real_revenue_sold safely (SAFE direct accessor)
     * 
     * @return float
     */
    public function getRealRevenueSoldSafeAttribute(): float
    {
        $columns = self::checkColumnsExist();
        
        if ($columns['has_real_revenue_sold']) {
            return (float) ($this->attributes['real_revenue_sold'] ?? 0);
        }
        
        // Fallback: if tipe HO, use old real_revenue
        if ($columns['has_old_real_revenue']) {
            $tipeRevenue = $this->attributes['tipe_revenue'] ?? 'HO';
            if ($tipeRevenue === 'HO') {
                return (float) ($this->attributes['real_revenue'] ?? 0);
            }
        }
        
        return 0.0;
    }

    /**
     * Get real_revenue_bill safely (SAFE direct accessor)
     * 
     * @return float
     */
    public function getRealRevenueBillSafeAttribute(): float
    {
        $columns = self::checkColumnsExist();
        
        if ($columns['has_real_revenue_bill']) {
            return (float) ($this->attributes['real_revenue_bill'] ?? 0);
        }
        
        // Fallback: if tipe BILL, use old real_revenue
        if ($columns['has_old_real_revenue']) {
            $tipeRevenue = $this->attributes['tipe_revenue'] ?? 'BILL';
            if ($tipeRevenue === 'BILL') {
                return (float) ($this->attributes['real_revenue'] ?? 0);
            }
        }
        
        return 0.0;
    }

    /**
     * Get target_revenue_sold safely (SAFE direct accessor)
     * 
     * @return float
     */
    public function getTargetRevenueSoldSafeAttribute(): float
    {
        $columns = self::checkColumnsExist();
        
        if ($columns['has_target_revenue_sold']) {
            return (float) ($this->attributes['target_revenue_sold'] ?? 0);
        }
        
        // Fallback to old target_revenue
        if ($columns['has_old_target_revenue']) {
            return (float) ($this->attributes['target_revenue'] ?? 0);
        }
        
        return 0.0;
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Corporate Customer relationship
     */
    public function corporateCustomer()
    {
        return $this->belongsTo(CorporateCustomer::class, 'corporate_customer_id');
    }

    /**
     * Divisi relationship
     */
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    /**
     * Segment relationship
     */
    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    /**
     * Witel HO relationship
     */
    public function witelHo()
    {
        return $this->belongsTo(Witel::class, 'witel_ho_id');
    }

    /**
     * Witel BILL relationship
     */
    public function witelBill()
    {
        return $this->belongsTo(Witel::class, 'witel_bill_id');
    }

    /**
     * AM Revenues relationship (one CC can have multiple AMs)
     */
    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class, 'corporate_customer_id', 'corporate_customer_id')
            ->where('divisi_id', $this->divisi_id)
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get formatted real revenue
     *
     * @return string
     */
    public function getFormattedRealRevenueAttribute(): string
    {
        return 'Rp ' . number_format($this->real_revenue, 0, ',', '.');
    }

    /**
     * Get formatted target revenue
     *
     * @return string
     */
    public function getFormattedTargetRevenueAttribute(): string
    {
        return 'Rp ' . number_format($this->target_revenue, 0, ',', '.');
    }

    /**
     * Get achievement color for UI
     *
     * @return string
     */
    public function getAchievementColorAttribute(): string
    {
        $rate = $this->achievement_rate;

        if ($rate >= 100) {
            return 'success';
        } elseif ($rate >= 80) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Get period display (Month Year)
     *
     * @return string
     */
    public function getPeriodDisplayAttribute(): string
    {
        return \Carbon\Carbon::createFromDate($this->tahun, $this->bulan, 1)
            ->locale('id')
            ->translatedFormat('F Y');
    }

    /**
     * Get tipe revenue label
     * 
     * @return string
     */
    public function getTipeRevenueLabel(): string
    {
        $tipe = $this->attributes['tipe_revenue'] ?? 'HO';
        
        return match($tipe) {
            'HO' => 'Revenue Sold (HO)',
            'BILL' => 'Revenue Bill',
            default => $tipe
        };
    }

    /**
     * Get revenue source label
     * 
     * @return string
     */
    public function getRevenueSourceLabel(): string
    {
        $source = $this->attributes['revenue_source'] ?? 'REGULER';
        
        return match($source) {
            'REGULER' => 'Reguler',
            'NGTMA' => 'NGTMA',
            default => $source
        };
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Filter by divisi
     */
    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    /**
     * Scope: Filter by witel (either HO or BILL)
     */
    public function scopeByWitel($query, $witelId)
    {
        return $query->where(function ($q) use ($witelId) {
            $q->where('witel_ho_id', $witelId)
              ->orWhere('witel_bill_id', $witelId);
        });
    }

    /**
     * Scope: Filter by witel HO
     */
    public function scopeByWitelHo($query, $witelId)
    {
        return $query->where('witel_ho_id', $witelId);
    }

    /**
     * Scope: Filter by witel BILL
     */
    public function scopeByWitelBill($query, $witelId)
    {
        return $query->where('witel_bill_id', $witelId);
    }

    /**
     * Scope: Filter by segment
     */
    public function scopeBySegment($query, $segmentId)
    {
        return $query->where('segment_id', $segmentId);
    }

    /**
     * Scope: Filter by period (month-year)
     */
    public function scopeByPeriod($query, $tahun, $bulan = null)
    {
        $query = $query->where('tahun', $tahun);
        
        if ($bulan) {
            $query->where('bulan', $bulan);
        }
        
        return $query;
    }

    /**
     * Scope: Filter by revenue source
     */
    public function scopeByRevenueSource($query, $revenueSource)
    {
        return $query->where('revenue_source', $revenueSource);
    }

    /**
     * Scope: Filter by tipe revenue
     */
    public function scopeByTipeRevenue($query, $tipeRevenue)
    {
        return $query->where('tipe_revenue', $tipeRevenue);
    }

    /**
     * Scope: Only HO type revenues
     */
    public function scopeHoType($query)
    {
        return $query->where('tipe_revenue', 'HO');
    }

    /**
     * Scope: Only BILL type revenues
     */
    public function scopeBillType($query)
    {
        return $query->where('tipe_revenue', 'BILL');
    }

    /**
     * Scope: Only REGULER source
     */
    public function scopeRegulerSource($query)
    {
        return $query->where('revenue_source', 'REGULER');
    }

    /**
     * Scope: Only NGTMA source
     */
    public function scopeNgtmaSource($query)
    {
        return $query->where('revenue_source', 'NGTMA');
    }

    // ========================================
    // AM REVENUE RELATED METHODS
    // ========================================

    /**
     * Check if has AM revenues
     *
     * @return bool
     */
    public function hasAmRevenues(): bool
    {
        return $this->amRevenues()->exists();
    }

    /**
     * Get total AM revenues for this CC
     *
     * @return float
     */
    public function getTotalAmRevenuesAttribute(): float
    {
        return $this->amRevenues()->sum('real_revenue');
    }

    /**
     * Get count of AMs handling this CC
     * 
     * @return int
     */
    public function getAmCountAttribute(): int
    {
        return $this->amRevenues()->count();
    }

    /**
     * Validate proporsi sum for AM revenues (should = 1.0)
     *
     * @return bool
     */
    public function validateProporsiSum(): bool
    {
        $totalProporsi = $this->amRevenues()->sum('proporsi');
        return abs($totalProporsi - 1.0) < 0.001; // Allow small floating point error
    }

    /**
     * Get proporsi sum for this CC
     * 
     * @return float
     */
    public function getProporsiSumAttribute(): float
    {
        return (float) $this->amRevenues()->sum('proporsi');
    }

    /**
     * Check if proporsi is valid (sum = 1.0)
     * 
     * @return bool
     */
    public function hasValidProporsi(): bool
    {
        if (!$this->hasAmRevenues()) {
            return true; // No AM revenues = valid by default
        }
        
        return $this->validateProporsiSum();
    }

    // ========================================
    // STATIC HELPER METHODS
    // ========================================

    /**
     * Clear column cache (useful after migration)
     * 
     * @return void
     */
    public static function clearColumnCache(): void
    {
        self::$columnCache = null;
    }
}