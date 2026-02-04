<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Divisi Model
 * 
 * Represents division types: DGS, DSS, DPS, DES
 * 
 * REVENUE SOURCE LOGIC (DEFAULT/SUGGESTION):
 * - DGS → HO (Revenue Sold)
 * - DSS → BILL (Revenue Bill) 
 * - DPS → BILL (Revenue Bill)
 * - DES → HO (Revenue Sold) - future
 * 
 * NOTE: getRevenueSource() hanya default suggestion untuk frontend.
 *       Final decision tetap dari user input saat import (fleksibel per-periode).
 */
class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisi';

    protected $fillable = [
        'nama',
        'kode',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Segments yang termasuk dalam divisi ini
     */
    public function segments()
    {
        return $this->hasMany(Segment::class);
    }

    /**
     * Teldas yang termasuk dalam divisi ini
     */
    public function teldas()
    {
        return $this->hasMany(Telda::class);
    }

    /**
     * Account Managers yang terkait dengan divisi ini
     * Many-to-many relationship through pivot table
     */
    public function accountManagers()
    {
        return $this->belongsToMany(AccountManager::class, 'account_manager_divisi')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * CC Revenues yang termasuk dalam divisi ini
     */
    public function ccRevenues()
    {
        return $this->hasMany(CcRevenue::class);
    }

    /**
     * AM Revenues yang termasuk dalam divisi ini
     */
    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by divisi kode
     */
    public function scopeByKode($query, $kode)
    {
        return $query->where('kode', $kode);
    }

    /**
     * Only DGS divisi
     */
    public function scopeDgs($query)
    {
        return $query->where('kode', 'DGS');
    }

    /**
     * Only DSS divisi
     */
    public function scopeDss($query)
    {
        return $query->where('kode', 'DSS');
    }

    /**
     * Only DPS divisi
     */
    public function scopeDps($query)
    {
        return $query->where('kode', 'DPS');
    }

    /**
     * Only DES divisi (future)
     */
    public function scopeDes($query)
    {
        return $query->where('kode', 'DES');
    }

    /**
     * Divisi yang menggunakan Revenue Sold (HO) as default
     */
    public function scopeRevenueSold($query)
    {
        return $query->whereIn('kode', ['DGS', 'DES']);
    }

    /**
     * Divisi yang menggunakan Revenue Bill as default
     */
    public function scopeRevenueBill($query)
    {
        return $query->whereIn('kode', ['DSS', 'DPS']);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Check if divisi is DGS
     */
    public function isDgs(): bool
    {
        return $this->kode === 'DGS';
    }

    /**
     * Check if divisi is DSS
     */
    public function isDss(): bool
    {
        return $this->kode === 'DSS';
    }

    /**
     * Check if divisi is DPS
     */
    public function isDps(): bool
    {
        return $this->kode === 'DPS';
    }

    /**
     * Check if divisi is DES
     */
    public function isDes(): bool
    {
        return $this->kode === 'DES';
    }

    /**
     * Get default revenue source (tipe_revenue) untuk divisi ini
     * 
     * FIXED LOGIC:
     * - DGS → HO (Revenue Sold)
     * - DSS → BILL (Revenue Bill) ✅ FIXED (was HO before)
     * - DPS → BILL (Revenue Bill)
     * - DES → HO (Revenue Sold)
     * 
     * NOTE: Ini hanya DEFAULT SUGGESTION untuk frontend dropdown.
     *       User tetap bisa override saat import (fleksibel per-periode).
     * 
     * @return string 'HO' atau 'BILL'
     */
    public function getRevenueSource(): string
    {
        // DGS dan DES → Revenue Sold (HO)
        if ($this->isDgs() || $this->isDes()) {
            return 'HO';
        }
        
        // DSS dan DPS → Revenue Bill (BILL)
        if ($this->isDss() || $this->isDps()) {
            return 'BILL';
        }

        // Fallback default untuk divisi baru
        return 'HO';
    }

    /**
     * Get revenue source label untuk display
     */
    public function getRevenueSourceLabel(): string
    {
        $source = $this->getRevenueSource();
        
        return match($source) {
            'HO' => 'Revenue Sold (HO)',
            'BILL' => 'Revenue Bill',
            default => $source
        };
    }

    /**
     * Check if divisi default menggunakan Revenue Sold (HO)
     */
    public function usesRevenueSold(): bool
    {
        return $this->getRevenueSource() === 'HO';
    }

    /**
     * Check if divisi default menggunakan Revenue Bill
     */
    public function usesRevenueBill(): bool
    {
        return $this->getRevenueSource() === 'BILL';
    }

    /**
     * Get divisi display name dengan kode
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->kode} - {$this->nama}";
    }

    /**
     * Get badge color class untuk divisi
     */
    public function getBadgeColorAttribute(): string
    {
        return match($this->kode) {
            'DGS' => 'badge-dgs',
            'DSS' => 'badge-dss',
            'DPS' => 'badge-dps',
            'DES' => 'badge-des',
            default => 'badge-secondary'
        };
    }

    // ========================================
    // STATIC HELPER METHODS
    // ========================================

    /**
     * Get divisi by kode
     */
    public static function getByKode(string $kode): ?self
    {
        return static::where('kode', $kode)->first();
    }

    /**
     * Get all divisi kodes as array
     */
    public static function getAllKodes(): array
    {
        return static::pluck('kode')->toArray();
    }

    /**
     * Check if kode exists
     */
    public static function kodeExists(string $kode): bool
    {
        return static::where('kode', $kode)->exists();
    }

    /**
     * Get divisi list untuk dropdown
     */
    public static function getDropdownList(): array
    {
        return static::orderBy('kode')->get()->mapWithKeys(function ($divisi) {
            return [$divisi->id => $divisi->full_name];
        })->toArray();
    }
}
