<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Witel Model (UPDATED - 2026-02-03)
 * 
 * Represents regional offices (Witel) across Indonesia
 * 
 * NEW FEATURES:
 * - Regional/TREG support (belongsTo Regional)
 * - Updated revenue calculation (uses real_revenue_bill explicitly)
 * - Cross-TREG scenario support
 * 
 * RELATIONSHIPS:
 * - Belongs to Regional (NEW)
 * - Has many Account Managers
 * - Has many Teldas
 * - Has many Users (for access control)
 * - Has many CC Revenues (both HO and BILL)
 * - Has many AM Revenues
 * - Has many Witel Target Revenues (for target bill DPS/DSS)
 * 
 * REVENUE LOGIC:
 * - Real revenue witel = SUM(cc_revenues.real_revenue_bill WHERE witel_bill_id = witel.id)
 * - Target revenue witel = SUM(witel_target_revenues.target_revenue_bill)
 * - Total revenue = revenue HO (sold) + revenue BILL
 */
class Witel extends Model
{
    use HasFactory;

    protected $table = 'witel';

    protected $fillable = [
        'nama',
        'regional_id', // NEW: Foreign key to regionals table
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Regional that this witel belongs to (NEW)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function regional()
    {
        return $this->belongsTo(Regional::class)->withDefault();
    }

    /**
     * Account Managers yang berkantor di witel ini
     */
    public function accountManagers()
    {
        return $this->hasMany(AccountManager::class);
    }

    /**
     * Teldas yang berada di bawah witel ini
     */
    public function teldas()
    {
        return $this->hasMany(Telda::class);
    }

    /**
     * Users yang memiliki akses ke witel ini
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * CC Revenues dengan witel sebagai HO (witel_ho_id)
     * Revenue Sold biasanya dari divisi DGS
     */
    public function ccRevenuesHo()
    {
        return $this->hasMany(CcRevenue::class, 'witel_ho_id');
    }

    /**
     * CC Revenues dengan witel sebagai BILL (witel_bill_id)
     * Revenue Bill biasanya dari divisi DPS/DSS
     */
    public function ccRevenuesBill()
    {
        return $this->hasMany(CcRevenue::class, 'witel_bill_id');
    }

    /**
     * Semua CC Revenues (HO + BILL) untuk witel ini
     */
    public function ccRevenuesAll()
    {
        return CcRevenue::where('witel_ho_id', $this->id)
                        ->orWhere('witel_bill_id', $this->id);
    }

    /**
     * AM Revenues yang terkait dengan witel ini
     */
    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    /**
     * Witel Target Revenues
     * Target revenue bill per divisi untuk witel ini
     * Khusus untuk divisi DPS & DSS
     */
    public function witelTargetRevenues()
    {
        return $this->hasMany(WitelTargetRevenue::class);
    }

    /**
     * Witel Target Revenue untuk divisi DPS
     */
    public function targetRevenuesDps()
    {
        return $this->hasMany(WitelTargetRevenue::class)->whereHas('divisi', function($q) {
            $q->where('kode', 'DPS');
        });
    }

    /**
     * Witel Target Revenue untuk divisi DSS
     */
    public function targetRevenuesDss()
    {
        return $this->hasMany(WitelTargetRevenue::class)->whereHas('divisi', function($q) {
            $q->where('kode', 'DSS');
        });
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by regional (NEW)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $regionalId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRegional($query, $regionalId)
    {
        return $query->where('regional_id', $regionalId);
    }

    /**
     * Filter by regional name (NEW)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $regionalName - e.g., "TREG 3"
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRegionalName($query, $regionalName)
    {
        return $query->whereHas('regional', function($q) use ($regionalName) {
            $q->where('nama', $regionalName);
        });
    }

    /**
     * Only witels with regional assigned (NEW)
     */
    public function scopeWithRegional($query)
    {
        return $query->whereNotNull('regional_id');
    }

    /**
     * Only witels without regional assigned (NEW)
     */
    public function scopeWithoutRegional($query)
    {
        return $query->whereNull('regional_id');
    }

    /**
     * With Account Managers count
     */
    public function scopeWithAccountManagersCount($query)
    {
        return $query->withCount('accountManagers');
    }

    /**
     * With Teldas count
     */
    public function scopeWithTeldasCount($query)
    {
        return $query->withCount('teldas');
    }

    /**
     * With all revenue relationships
     */
    public function scopeWithRevenueRelations($query)
    {
        return $query->with([
            'ccRevenuesHo',
            'ccRevenuesBill',
            'amRevenues',
            'witelTargetRevenues'
        ]);
    }

    /**
     * Witel dengan target revenue untuk periode tertentu
     */
    public function scopeWithTargetForPeriod($query, $year, $month = null)
    {
        return $query->with(['witelTargetRevenues' => function($q) use ($year, $month) {
            $q->where('tahun', $year);
            if ($month) {
                $q->where('bulan', $month);
            }
        }]);
    }

    /**
     * With regional relationship (NEW)
     */
    public function scopeWithRegionalInfo($query)
    {
        return $query->with('regional:id,nama');
    }

    // ========================================
    // ACCESSOR & HELPER METHODS
    // ========================================

    /**
     * Get regional name (NEW)
     * 
     * @return string|null
     */
    public function getRegionalNameAttribute(): ?string
    {
        return $this->regional?->nama;
    }

    /**
     * Get TREG number (NEW)
     * 
     * @return int|null
     */
    public function getTregNumberAttribute(): ?int
    {
        return $this->regional?->treg_number;
    }

    /**
     * Check if witel has regional assigned (NEW)
     * 
     * @return bool
     */
    public function hasRegional(): bool
    {
        return !is_null($this->regional_id);
    }

    /**
     * Get total revenue HO untuk periode tertentu
     * Uses real_revenue_sold from CC revenues
     * 
     * @param int $year
     * @param int|null $month
     * @return float
     */
    public function getTotalRevenueHo($year, $month = null)
    {
        $query = $this->ccRevenuesHo()
                      ->where('tahun', $year)
                      ->where('tipe_revenue', 'HO');

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->sum('real_revenue_sold');
    }

    /**
     * Get total revenue BILL untuk periode tertentu (UPDATED)
     * 
     * CRITICAL CHANGE:
     * - Now uses real_revenue_bill explicitly (not real_revenue)
     * - This is the correct column for witel bill revenue
     * 
     * @param int $year
     * @param int|null $month
     * @return float
     */
    public function getTotalRevenueBill($year, $month = null)
    {
        $query = $this->ccRevenuesBill()
                      ->where('tahun', $year)
                      ->where('tipe_revenue', 'BILL');

        if ($month) {
            $query->where('bulan', $month);
        }

        // UPDATED: Use real_revenue_bill explicitly
        return $query->sum('real_revenue_bill');
    }

    /**
     * Get total revenue (HO + BILL) untuk periode tertentu
     * 
     * LOGIC:
     * Total = Revenue Sold (HO dari DGS) + Revenue Bill (BILL dari DPS/DSS)
     * 
     * @param int $year
     * @param int|null $month
     * @return float
     */
    public function getTotalRevenue($year, $month = null)
    {
        $revenueHo = $this->getTotalRevenueHo($year, $month);
        $revenueBill = $this->getTotalRevenueBill($year, $month);

        return $revenueHo + $revenueBill;
    }

    /**
     * Get total target revenue untuk periode tertentu
     * 
     * LOGIC:
     * Total Target = Target Sold (dari cc_revenues) + Target Bill (dari witel_target_revenues)
     * 
     * @param int $year
     * @param int|null $month
     * @return float
     */
    public function getTotalTargetRevenue($year, $month = null)
    {
        // Target sold dari cc_revenues (witel_ho)
        $targetSoldQuery = $this->ccRevenuesHo()->where('tahun', $year);
        if ($month) {
            $targetSoldQuery->where('bulan', $month);
        }
        $targetSold = $targetSoldQuery->sum('target_revenue_sold');

        // Target bill dari witel_target_revenues (DPS + DSS)
        $targetBillQuery = $this->witelTargetRevenues()->where('tahun', $year);
        if ($month) {
            $targetBillQuery->where('bulan', $month);
        }
        $targetBill = $targetBillQuery->sum('target_revenue_bill');

        return $targetSold + $targetBill;
    }

    /**
     * Get achievement rate untuk periode tertentu
     * 
     * @param int $year
     * @param int|null $month
     * @return float Percentage (0-100+)
     */
    public function getAchievementRate($year, $month = null): float
    {
        $target = $this->getTotalTargetRevenue($year, $month);
        
        if ($target <= 0) {
            return 0;
        }

        $revenue = $this->getTotalRevenue($year, $month);
        
        return round(($revenue / $target) * 100, 2);
    }

    /**
     * Get revenue breakdown per divisi untuk periode tertentu
     * 
     * @param int $year
     * @param int|null $month
     * @return array
     */
    public function getRevenueBreakdown($year, $month = null): array
    {
        // DGS (Revenue Sold - HO)
        $dgsQuery = $this->ccRevenuesHo()
                         ->where('tahun', $year)
                         ->where('tipe_revenue', 'HO')
                         ->whereHas('divisi', function($q) {
                             $q->where('kode', 'DGS');
                         });
        if ($month) {
            $dgsQuery->where('bulan', $month);
        }
        $dgsRevenue = $dgsQuery->sum('real_revenue_sold');

        // DPS (Revenue Bill)
        $dpsQuery = $this->ccRevenuesBill()
                         ->where('tahun', $year)
                         ->where('tipe_revenue', 'BILL')
                         ->whereHas('divisi', function($q) {
                             $q->where('kode', 'DPS');
                         });
        if ($month) {
            $dpsQuery->where('bulan', $month);
        }
        $dpsRevenue = $dpsQuery->sum('real_revenue_bill');

        // DSS (Revenue Bill)
        $dssQuery = $this->ccRevenuesBill()
                         ->where('tahun', $year)
                         ->where('tipe_revenue', 'BILL')
                         ->whereHas('divisi', function($q) {
                             $q->where('kode', 'DSS');
                         });
        if ($month) {
            $dssQuery->where('bulan', $month);
        }
        $dssRevenue = $dssQuery->sum('real_revenue_bill');

        return [
            'dgs' => $dgsRevenue,
            'dps' => $dpsRevenue,
            'dss' => $dssRevenue,
            'total' => $dgsRevenue + $dpsRevenue + $dssRevenue
        ];
    }

    /**
     * Get target breakdown per divisi untuk periode tertentu
     * 
     * @param int $year
     * @param int|null $month
     * @return array
     */
    public function getTargetBreakdown($year, $month = null): array
    {
        // Target DGS (dari cc_revenues target_revenue_sold)
        $dgsTargetQuery = $this->ccRevenuesHo()
                               ->where('tahun', $year)
                               ->whereHas('divisi', function($q) {
                                   $q->where('kode', 'DGS');
                               });
        if ($month) {
            $dgsTargetQuery->where('bulan', $month);
        }
        $dgsTarget = $dgsTargetQuery->sum('target_revenue_sold');

        // Target DPS (dari witel_target_revenues)
        $dpsTargetQuery = $this->targetRevenuesDps()->where('tahun', $year);
        if ($month) {
            $dpsTargetQuery->where('bulan', $month);
        }
        $dpsTarget = $dpsTargetQuery->sum('target_revenue_bill');

        // Target DSS (dari witel_target_revenues)
        $dssTargetQuery = $this->targetRevenuesDss()->where('tahun', $year);
        if ($month) {
            $dssTargetQuery->where('bulan', $month);
        }
        $dssTarget = $dssTargetQuery->sum('target_revenue_bill');

        return [
            'dgs' => $dgsTarget,
            'dps' => $dpsTarget,
            'dss' => $dssTarget,
            'total' => $dgsTarget + $dpsTarget + $dssTarget
        ];
    }

    /**
     * Get nama display attribute
     */
    public function getNamaDisplayAttribute(): string
    {
        return "Witel {$this->nama}";
    }

    /**
     * Get full display name with regional (NEW)
     * 
     * @return string
     */
    public function getFullDisplayNameAttribute(): string
    {
        $name = "Witel {$this->nama}";
        
        if ($this->hasRegional()) {
            $name .= " ({$this->regional_name})";
        }
        
        return $name;
    }

    // ========================================
    // STATIC METHODS
    // ========================================

    /**
     * Get witel by nama
     */
    public static function getByNama(string $nama): ?self
    {
        return static::where('nama', $nama)->first();
    }

    /**
     * Get witel list untuk dropdown
     */
    public static function getDropdownList(): array
    {
        return static::orderBy('nama')->pluck('nama', 'id')->toArray();
    }

    /**
     * Get witel list with regional info (NEW)
     * 
     * @return array [id => "Witel Name (TREG 3)"]
     */
    public static function getDropdownListWithRegional(): array
    {
        return static::with('regional')
                    ->orderBy('nama')
                    ->get()
                    ->mapWithKeys(function($witel) {
                        return [$witel->id => $witel->full_display_name];
                    })
                    ->toArray();
    }

    /**
     * Get witels by regional (NEW)
     * 
     * @param int $regionalId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByRegionalId(int $regionalId)
    {
        return static::where('regional_id', $regionalId)
                    ->orderBy('nama')
                    ->get();
    }

    /**
     * Get witels by TREG number (NEW)
     * 
     * @param int $tregNumber (1-5)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByTregNumber(int $tregNumber)
    {
        return static::whereHas('regional', function($q) use ($tregNumber) {
            $q->where('nama', 'TREG ' . $tregNumber);
        })->orderBy('nama')->get();
    }

    /**
     * Get witel dengan revenue terbesar untuk periode tertentu
     */
    public static function getTopPerformers($year, $month = null, $limit = 10)
    {
        $witels = static::all();
        
        $rankings = $witels->map(function($witel) use ($year, $month) {
            return [
                'witel' => $witel,
                'total_revenue' => $witel->getTotalRevenue($year, $month),
                'total_target' => $witel->getTotalTargetRevenue($year, $month),
                'achievement_rate' => $witel->getAchievementRate($year, $month)
            ];
        })->sortByDesc('total_revenue')->take($limit);

        return $rankings;
    }

    /**
     * Get statistics per regional (NEW)
     * 
     * @param int $year
     * @param int|null $month
     * @return array
     */
    public static function getStatisticsByRegional($year, $month = null): array
    {
        $witels = static::with('regional')->get();
        
        $stats = [];
        
        foreach ($witels as $witel) {
            $regionalName = $witel->regional_name ?? 'Unassigned';
            
            if (!isset($stats[$regionalName])) {
                $stats[$regionalName] = [
                    'regional_name' => $regionalName,
                    'witel_count' => 0,
                    'total_revenue' => 0,
                    'total_target' => 0,
                    'achievement_rate' => 0
                ];
            }
            
            $stats[$regionalName]['witel_count']++;
            $stats[$regionalName]['total_revenue'] += $witel->getTotalRevenue($year, $month);
            $stats[$regionalName]['total_target'] += $witel->getTotalTargetRevenue($year, $month);
        }
        
        // Calculate achievement rates
        foreach ($stats as $regionalName => &$data) {
            if ($data['total_target'] > 0) {
                $data['achievement_rate'] = round(
                    ($data['total_revenue'] / $data['total_target']) * 100, 
                    2
                );
            }
        }
        
        return $stats;
    }
}