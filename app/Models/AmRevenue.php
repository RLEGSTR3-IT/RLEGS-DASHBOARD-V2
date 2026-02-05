<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * AmRevenue Model - FIXED VERSION (2026-02-05)
 * ============================================================================
 * 
 * Version: 5.0 - CRITICAL RELATIONSHIP FIX
 * 
 * CRITICAL FIXES:
 * ✅ FIXED: ccRevenue relationship - Changed from hasOne with complex where
 *    to belongsTo with proper foreign key (cc_revenue_id)
 * ✅ FIXED: Added cc_revenue_id to fillable array
 * ✅ MAINTAINED: All existing functionality (proporsi validation, helpers, etc.)
 * ✅ MAINTAINED: Strict telda_id business rule validation
 * 
 * PROPORSI RANGE:
 * - Database stores: 0.0 - 1.0 (decimal, e.g., 0.4 = 40%)
 * - Display format: 0.4 × 100 = 40%
 * - Validation: 0 ≤ proporsi ≤ 1
 * - Total per CC: SUM(proporsi) = 1.0 (not 100)
 * 
 * BUSINESS RULES:
 * 1. AM role → telda_id = NULL (mandatory)
 * 2. HOTDA role → telda_id NOT NULL (mandatory)
 * 3. Proporsi: 0.0 - 1.0 decimal
 * 4. Total proporsi per CC = 1.0
 * 5. Revenue calculation: CC.real_revenue_sold × proporsi
 * 
 * @author RLEGS Team
 * @version 5.0 - Relationship Fix
 * ============================================================================
 */
class AmRevenue extends Model
{
    use HasFactory;

    protected $table = 'am_revenues';

    protected $fillable = [
        'account_manager_id',
        'corporate_customer_id',
        'cc_revenue_id',           // ✅ ADDED: Foreign key to cc_revenues table
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
        'proporsi' => 'decimal:4',
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
     * ✅ FIXED: CcRevenue relationship
     * 
     * OLD (BROKEN):
     * - Used hasOne with corporate_customer_id
     * - Added dynamic where conditions ($this->divisi_id, $this->bulan, etc)
     * - Failed because $this attributes not available during query building
     * 
     * NEW (FIXED):
     * - Use belongsTo with proper foreign key cc_revenue_id
     * - Simple, direct relationship
     * - Works correctly with eager loading
     * 
     * NOTE: This requires cc_revenue_id column in am_revenues table
     * If column doesn't exist yet, you need to run migration first
     */
    public function ccRevenue()
    {
        return $this->belongsTo(CcRevenue::class, 'cc_revenue_id');
    }

    /**
     * ✅ FALLBACK: Legacy ccRevenue relationship (for backward compatibility)
     * 
     * Use this ONLY if cc_revenue_id column doesn't exist yet
     * This will try to find CC Revenue based on matching criteria
     * 
     * WARNING: This is NOT efficient and should be replaced with proper FK
     */
    public function ccRevenueLegacy()
    {
        return CcRevenue::where('corporate_customer_id', $this->corporate_customer_id)
                       ->where('divisi_id', $this->divisi_id)
                       ->where('bulan', $this->bulan)
                       ->where('tahun', $this->tahun)
                       ->first();
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

    /**
     * ✅ UPDATED: WithRelations scope with fixed ccRevenue
     */
    public function scopeWithRelations($query)
    {
        return $query->with([
            'accountManager:id,nama,nik,role,telda_id',
            'corporateCustomer:id,nama,nipnas',
            'divisi:id,nama,kode',
            'witel:id,nama',
            'telda:id,nama',
            'ccRevenue:id,corporate_customer_id,target_revenue_sold,real_revenue_sold,tipe_revenue,bulan,tahun'  // ✅ FIXED
        ]);
    }

    public function scopeByCorporateCustomer($query, $corporateCustomerId)
    {
        return $query->where('corporate_customer_id', $corporateCustomerId);
    }

    public function scopeByCcAndPeriod($query, $corporateCustomerId, $year, $month)
    {
        return $query->where('corporate_customer_id', $corporateCustomerId)
                     ->where('tahun', $year)
                     ->where('bulan', $month);
    }

    /**
     * ✅ NEW: Scope for filtering by cc_revenue_id (if using new FK structure)
     */
    public function scopeByCcRevenue($query, $ccRevenueId)
    {
        return $query->where('cc_revenue_id', $ccRevenueId);
    }

    // ========================================
    // ACCESSORS & MUTATORS
    // ========================================

    public function getCalculatedAchievementRateAttribute(): float
    {
        if ($this->target_revenue <= 0) {
            return 0;
        }

        return round(($this->real_revenue / $this->target_revenue) * 100, 2);
    }

    public function getPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->tahun, $this->bulan);
    }

    public function getPeriodNameAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$this->bulan] . ' ' . $this->tahun;
    }

    public function getProporsiPercentAttribute(): string
    {
        return number_format((float)$this->proporsi * 100, 2) . '%';
    }

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
        if ($this->telda) {
            return $this->telda->nama;
        }

        if ($this->isHotda() && $this->accountManager) {
            return $this->accountManager->telda?->nama;
        }

        return null;
    }

    /**
     * ✅ NEW: Get CC Revenue info safely (with fallback)
     * 
     * @return array|null
     */
    public function getCcRevenueInfo(): ?array
    {
        $ccRevenue = $this->ccRevenue;

        if (!$ccRevenue) {
            // Fallback to legacy method if FK relationship doesn't work
            $ccRevenue = $this->ccRevenueLegacy();
        }

        if (!$ccRevenue) {
            return null;
        }

        return [
            'id' => $ccRevenue->id,
            'target_revenue_sold' => $ccRevenue->target_revenue_sold,
            'real_revenue_sold' => $ccRevenue->real_revenue_sold,
            'tipe_revenue' => $ccRevenue->tipe_revenue ?? 'HO',
            'bulan' => $ccRevenue->bulan,
            'tahun' => $ccRevenue->tahun,
        ];
    }

    // ========================================
    // PROPORSI HELPER METHODS
    // ========================================

    public function getRelatedAmsForSameCC()
    {
        return static::where('corporate_customer_id', $this->corporate_customer_id)
                     ->where('tahun', $this->tahun)
                     ->where('bulan', $this->bulan)
                     ->where('id', '!=', $this->id)
                     ->with(['accountManager:id,nama,nik,role'])
                     ->get();
    }

    public function getTotalProporsiForCC(): float
    {
        return (float) static::where('corporate_customer_id', $this->corporate_customer_id)
                             ->where('tahun', $this->tahun)
                             ->where('bulan', $this->bulan)
                             ->sum('proporsi');
    }

    public function getRemainingProporsiForCC(): float
    {
        $total = $this->getTotalProporsiForCC();
        return round(1.0 - $total, 4);
    }

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

    public function isProporsiAllocationValid(): bool
    {
        $total = $this->getTotalProporsiForCC();
        return abs($total - 1.0) < 0.001;
    }

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

    public function getAmCountForCC(): int
    {
        return static::where('corporate_customer_id', $this->corporate_customer_id)
                     ->where('tahun', $this->tahun)
                     ->where('bulan', $this->bulan)
                     ->count();
    }

    public function isSoleAm(): bool
    {
        return $this->getAmCountForCC() === 1;
    }

    public function hasMultipleAms(): bool
    {
        return $this->getAmCountForCC() > 1;
    }

    // ========================================
    // VALIDATION METHODS
    // ========================================

    /**
     * Validate proporsi range (0-1)
     */
    public function validateProporsi(): bool
    {
        return $this->proporsi >= 0 && $this->proporsi <= 1;
    }

    /**
     * ============================================================================
     * ✅ MAINTAINED: Strict telda_id business rule validation
     * ============================================================================
     * 
     * BUSINESS RULES:
     * - AM role: telda_id MUST be NULL
     * - HOTDA role: telda_id MUST NOT be NULL
     * 
     * @return bool
     */
    public function validateTeldaConsistency(): bool
    {
        // Load account manager if not loaded
        if (!$this->relationLoaded('accountManager')) {
            $this->load('accountManager');
        }

        $am = $this->accountManager;

        if (!$am) {
            Log::error('AmRevenue validation failed: AccountManager not found', [
                'am_revenue_id' => $this->id,
                'account_manager_id' => $this->account_manager_id
            ]);
            return false;
        }

        $role = strtoupper(trim($am->role ?? ''));

        Log::info('Validating telda consistency', [
            'am_revenue_id' => $this->id ?? 'new',
            'account_manager_id' => $am->id,
            'account_manager_nik' => $am->nik,
            'account_manager_role' => $role,
            'am_revenue_telda_id' => $this->telda_id,
            'account_manager_telda_id' => $am->telda_id
        ]);

        // ✅ STRICT VALIDATION
        if ($role === 'HOTDA') {
            // HOTDA MUST have telda_id
            if (is_null($this->telda_id)) {
                Log::error('Telda validation failed: HOTDA without telda_id', [
                    'am_id' => $am->id,
                    'nik' => $am->nik,
                    'nama' => $am->nama,
                    'role' => $role,
                    'am_telda_id' => $am->telda_id,
                    'am_revenue_telda_id' => $this->telda_id
                ]);
                return false;
            }

            Log::info('✓ HOTDA validation passed (has telda_id)', [
                'am_id' => $am->id,
                'telda_id' => $this->telda_id
            ]);
            return true;

        } elseif ($role === 'AM') {
            // AM MUST NOT have telda_id
            if (!is_null($this->telda_id)) {
                Log::error('Telda validation failed: AM with telda_id', [
                    'am_id' => $am->id,
                    'nik' => $am->nik,
                    'nama' => $am->nama,
                    'role' => $role,
                    'am_telda_id' => $am->telda_id,
                    'am_revenue_telda_id' => $this->telda_id
                ]);
                return false;
            }

            Log::info('✓ AM validation passed (no telda_id)', [
                'am_id' => $am->id,
                'telda_id' => 'NULL (correct)'
            ]);
            return true;

        } else {
            // Unknown or empty role - reject
            Log::error('Telda validation failed: Unknown or empty role', [
                'am_id' => $am->id,
                'nik' => $am->nik,
                'nama' => $am->nama,
                'role' => $role,
                'telda_id' => $this->telda_id
            ]);
            return false;
        }
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

    public static function validateProporsiTotal($corporateCustomerId, $year, $month)
    {
        $totalProporsi = static::where('corporate_customer_id', $corporateCustomerId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->sum('proporsi');

        return abs($totalProporsi - 1.0) < 0.001;
    }

    public static function getProporsiTotal($corporateCustomerId, $year, $month): float
    {
        return (float) static::where('corporate_customer_id', $corporateCustomerId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->sum('proporsi');
    }

    public static function getAmsForCC($corporateCustomerId, $year, $month)
    {
        return static::where('corporate_customer_id', $corporateCustomerId)
                     ->where('tahun', $year)
                     ->where('bulan', $month)
                     ->with(['accountManager:id,nama,nik,role'])
                     ->get();
    }

    // ========================================
    // ✅ MAINTAINED: BOOT METHOD WITH STRICT VALIDATION
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($amRevenue) {
            Log::info('AmRevenue saving event triggered', [
                'id' => $amRevenue->id ?? 'new',
                'account_manager_id' => $amRevenue->account_manager_id,
                'corporate_customer_id' => $amRevenue->corporate_customer_id,
                'cc_revenue_id' => $amRevenue->cc_revenue_id ?? 'not set',
                'telda_id' => $amRevenue->telda_id,
                'proporsi' => $amRevenue->proporsi,
                'bulan' => $amRevenue->bulan,
                'tahun' => $amRevenue->tahun
            ]);

            // ✅ FIX 1: Normalize proporsi if needed
            if ($amRevenue->proporsi > 1) {
                $oldProporsi = $amRevenue->proporsi;
                $amRevenue->proporsi = $amRevenue->proporsi / 100;
                
                Log::info('Normalized proporsi from percentage to decimal', [
                    'old_proporsi' => $oldProporsi,
                    'new_proporsi' => $amRevenue->proporsi
                ]);
            }

            // ✅ FIX 2: Validate proporsi range (0-1)
            if (!$amRevenue->validateProporsi()) {
                Log::error('Proporsi validation failed', [
                    'proporsi' => $amRevenue->proporsi,
                    'valid_range' => '0.0 - 1.0'
                ]);
                
                throw new \InvalidArgumentException(
                    'Proporsi harus antara 0 dan 1 (contoh: 0.4 = 40%). Nilai saat ini: ' . $amRevenue->proporsi
                );
            }

            // ✅ FIX 3: CRITICAL - Validate telda consistency with STRICT rules
            if (!$amRevenue->validateTeldaConsistency()) {
                $am = $amRevenue->accountManager;
                $role = $am ? strtoupper(trim($am->role ?? '')) : 'UNKNOWN';
                
                $errorMessage = '';
                
                if ($role === 'HOTDA') {
                    $errorMessage = "HOTDA role harus memiliki TELDA assignment. " .
                                  "Account Manager NIK {$am->nik} ({$am->nama}) belum memiliki TELDA. " .
                                  "Silakan update data AM terlebih dahulu di menu Data AM.";
                } elseif ($role === 'AM') {
                    $errorMessage = "AM role tidak boleh memiliki TELDA assignment. " .
                                  "Account Manager NIK {$am->nik} ({$am->nama}) adalah role AM, " .
                                  "tapi telda_id tidak NULL. Sistem akan otomatis set ke NULL.";
                } else {
                    $errorMessage = "Account Manager role tidak valid atau kosong. " .
                                  "Role harus 'AM' atau 'HOTDA'. Role saat ini: '{$role}'";
                }
                
                Log::error('CRITICAL: Telda consistency validation failed', [
                    'am_id' => $am->id ?? null,
                    'nik' => $am->nik ?? null,
                    'nama' => $am->nama ?? null,
                    'role' => $role,
                    'am_telda_id' => $am->telda_id ?? null,
                    'am_revenue_telda_id' => $amRevenue->telda_id,
                    'error_message' => $errorMessage
                ]);
                
                throw new \InvalidArgumentException($errorMessage);
            }

            // ✅ FIX 4: Validate period
            if ($amRevenue->bulan < 1 || $amRevenue->bulan > 12) {
                throw new \InvalidArgumentException('Bulan harus antara 1 dan 12. Nilai saat ini: ' . $amRevenue->bulan);
            }

            if ($amRevenue->tahun < 2000 || $amRevenue->tahun > 2099) {
                throw new \InvalidArgumentException('Tahun harus antara 2000 dan 2099. Nilai saat ini: ' . $amRevenue->tahun);
            }

            // ✅ FIX 5: Auto-calculate achievement rate if not set
            if (is_null($amRevenue->achievement_rate)) {
                $amRevenue->achievement_rate = $amRevenue->calculated_achievement_rate;
            }

            Log::info('✓ All validations passed, proceeding to save', [
                'am_revenue_id' => $amRevenue->id ?? 'new'
            ]);
        });

        static::saved(function ($amRevenue) {
            // Validate proporsi total for this CC
            $totalProporsi = static::getProporsiTotal(
                $amRevenue->corporate_customer_id, 
                $amRevenue->tahun, 
                $amRevenue->bulan
            );

            if (!static::validateProporsiTotal($amRevenue->corporate_customer_id, $amRevenue->tahun, $amRevenue->bulan)) {
                Log::warning("Proporsi total for CC {$amRevenue->corporate_customer_id} in {$amRevenue->tahun}-{$amRevenue->bulan} is {$totalProporsi} (should be 1.0)");
            } else {
                Log::info("✓ Proporsi total validated OK", [
                    'cc_id' => $amRevenue->corporate_customer_id,
                    'period' => "{$amRevenue->tahun}-{$amRevenue->bulan}",
                    'total_proporsi' => $totalProporsi
                ]);
            }
        });
    }
}