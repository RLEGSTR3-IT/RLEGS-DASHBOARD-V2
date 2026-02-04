<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AccountManager Model
 * 
 * ✅ FIXED VERSION - 2026-02-04
 * 
 * CRITICAL FIX:
 * - Removed belongsTo divisi() method (column divisi_id doesn't exist in account_managers table)
 * - Use divisis() (belongsToMany via pivot) for all divisi relationships
 * - Fixed getPrimaryDivisi() to use pivot table only
 * 
 * @property int $id
 * @property string $nama
 * @property string $nik
 * @property string $role (AM|HOTDA)
 * @property int $witel_id
 * @property int|null $telda_id
 */
class AccountManager extends Model
{
    use HasFactory;

    protected $table = 'account_managers';

    public const ROLE_AM    = 'AM';
    public const ROLE_HOTDA = 'HOTDA';

    protected $fillable = [
        'nama',
        'nik',
        'role',       // AM | HOTDA
        'witel_id',   // required
        'telda_id',   // nullable, wajib jika HOTDA
    ];

    protected $casts = [
        'role' => 'string',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Witel relationship
     */
    public function witel()
    {
        return $this->belongsTo(Witel::class);
    }

    /**
     * Telda relationship (for HOTDA only)
     */
    public function telda()
    {
        return $this->belongsTo(Telda::class);
    }

    /**
     * User account relationship (for login)
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * AM Revenues relationship
     */
    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    /**
     * ✅ FIXED: Multi-divisi via pivot table
     * 
     * This is the CORRECT way to get divisi for AccountManager
     * Use this instead of divisi() (singular)
     */
    public function divisis()
    {
        return $this->belongsToMany(Divisi::class, 'account_manager_divisi')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * ✅ NEW: Alias for backwards compatibility
     * Returns collection of divisi (not single)
     */
    public function divisi()
    {
        return $this->divisis();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Filter by role AM
     */
    public function scopeAm($query)
    {
        return $query->where('role', self::ROLE_AM);
    }

    /**
     * Scope: Filter by role HOTDA
     */
    public function scopeHotda($query)
    {
        return $query->where('role', self::ROLE_HOTDA);
    }

    /**
     * Scope: Filter by witel
     */
    public function scopeByWitel($query, $witelId)
    {
        return $query->where('witel_id', $witelId);
    }

    /**
     * Scope: Filter by telda
     */
    public function scopeByTelda($query, $teldaId)
    {
        return $query->where('telda_id', $teldaId);
    }

    /**
     * Scope: Filter by divisi (via pivot)
     */
    public function scopeByDivisi($query, $divisiId)
    {
        return $query->whereHas('divisis', function($q) use ($divisiId) {
            $q->where('divisi.id', $divisiId);
        });
    }

    /**
     * Scope: Search by nama or nik
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
              ->orWhere('nik', 'like', "%{$search}%");
        });
    }

    // ========================================
    // ROLE CHECKS
    // ========================================

    /**
     * Check if AM role
     */
    public function isAm(): bool
    {
        return $this->role === self::ROLE_AM;
    }

    /**
     * Check if HOTDA role
     */
    public function isHotda(): bool
    {
        return $this->role === self::ROLE_HOTDA;
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * ✅ FIXED: Get primary divisi (from pivot table)
     */
    public function getPrimaryDivisi()
    {
        return $this->divisis()->wherePivot('is_primary', 1)->first();
    }

    /**
     * Get primary divisi name
     */
    public function getPrimaryDivisiNama(): ?string
    {
        $divisi = $this->getPrimaryDivisi();
        return $divisi?->nama;
    }

    /**
     * Get primary divisi code
     */
    public function getPrimaryDivisiKode(): ?string
    {
        $divisi = $this->getPrimaryDivisi();
        return $divisi?->kode;
    }

    /**
     * Get all divisi codes for this AM
     */
    public function getDivisiKodes(): array
    {
        return $this->divisis()->pluck('kode')->toArray();
    }

    /**
     * Get all divisi names for this AM
     */
    public function getDivisiNames(): array
    {
        return $this->divisis()->pluck('nama')->toArray();
    }

    /**
     * Check if AM has specific divisi
     */
    public function hasDivisi($divisiId): bool
    {
        return $this->divisis()->where('divisi.id', $divisiId)->exists();
    }

    /**
     * Get display name with role and location
     */
    public function getDisplayNameAttribute(): string
    {
        $role = $this->isHotda() ? self::ROLE_HOTDA : self::ROLE_AM;
        $loc  = $this->isHotda() ? $this->telda?->nama : $this->witel?->nama;
        return "{$this->nama} ({$role}" . ($loc ? " - {$loc}" : '') . ')';
    }

    /**
     * Get short display name (nama + divisi codes)
     */
    public function getShortDisplayNameAttribute(): string
    {
        $divisiCodes = $this->getDivisiKodes();
        $divisiText = !empty($divisiCodes) ? ' [' . implode(', ', $divisiCodes) . ']' : '';
        return $this->nama . $divisiText;
    }

    // ========================================
    // REVENUE AGGREGATES
    // ========================================

    /**
     * Get monthly revenue
     */
    public function getMonthlyRevenue(int $year, int $month)
    {
        return $this->amRevenues()
                    ->where('tahun', $year)
                    ->where('bulan', $month)
                    ->sum('real_revenue');
    }

    /**
     * Get monthly target
     */
    public function getMonthlyTarget(int $year, int $month)
    {
        return $this->amRevenues()
                    ->where('tahun', $year)
                    ->where('bulan', $month)
                    ->sum('target_revenue');
    }

    /**
     * Get yearly revenue
     */
    public function getYearlyRevenue(int $year)
    {
        return $this->amRevenues()
                    ->where('tahun', $year)
                    ->sum('real_revenue');
    }

    /**
     * Get yearly target
     */
    public function getYearlyTarget(int $year)
    {
        return $this->amRevenues()
                    ->where('tahun', $year)
                    ->sum('target_revenue');
    }

    /**
     * Get achievement rate for period
     */
    public function getAchievementRate(int $year, int $month = null): float
    {
        $query = $this->amRevenues()->where('tahun', $year);
        
        if ($month) {
            $query->where('bulan', $month);
        }

        $target = $query->sum('target_revenue');
        $revenue = $query->sum('real_revenue');

        if ($target <= 0) {
            return 0;
        }

        return round(($revenue / $target) * 100, 2);
    }

    // ========================================
    // BUSINESS RULES & VALIDATION
    // ========================================

    /**
     * Boot method - Add model event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Validate before saving
        static::saving(function (self $am) {
            // Validate role
            if (!in_array($am->role, [self::ROLE_AM, self::ROLE_HOTDA], true)) {
                throw new \InvalidArgumentException('Role tidak valid: gunakan AM atau HOTDA.');
            }

            // HOTDA must have telda_id
            if ($am->isHotda() && is_null($am->telda_id)) {
                throw new \InvalidArgumentException('HOTDA wajib memiliki telda_id.');
            }

            // AM should not have telda_id
            if ($am->isAm() && !is_null($am->telda_id)) {
                $am->telda_id = null; // Auto-clear instead of throwing error
            }
        });
    }

    // ========================================
    // STATIC HELPERS
    // ========================================

    /**
     * Find AM by NIK
     */
    public static function findByNik(string $nik)
    {
        return static::where('nik', $nik)->first();
    }

    /**
     * Get all AMs for a witel
     */
    public static function getByWitel(int $witelId)
    {
        return static::where('witel_id', $witelId)
                    ->orderBy('nama')
                    ->get();
    }

    /**
     * Get all AMs for a divisi
     */
    public static function getByDivisi(int $divisiId)
    {
        return static::whereHas('divisis', function($q) use ($divisiId) {
                    $q->where('divisi.id', $divisiId);
                })
                ->orderBy('nama')
                ->get();
    }

    /**
     * Get dropdown list (id => display name)
     */
    public static function getDropdownList()
    {
        return static::orderBy('nama')
                    ->get()
                    ->mapWithKeys(function($am) {
                        return [$am->id => $am->display_name];
                    })
                    ->toArray();
    }
}