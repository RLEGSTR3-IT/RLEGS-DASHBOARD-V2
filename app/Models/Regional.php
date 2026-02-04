<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Regional Model
 * 
 * Represents regional groupings (TREG) in Telkom Regional 3
 * 
 * PURPOSE:
 * - Group Witel by regional/TREG
 * - Support cross-TREG scenarios (witel_bill from different TREG than witel_ho)
 * - Enable regional-level reporting and filtering
 * 
 * TREG LIST:
 * - TREG 1: Sumatera
 * - TREG 2: Jakarta, Banten, Jawa Barat
 * - TREG 3: Jawa Tengah, Jawa Timur, Bali, Nusa Tenggara (THIS SYSTEM)
 * - TREG 4: Kalimantan
 * - TREG 5: Sulawesi, Maluku, Papua
 * 
 * RELATIONSHIPS:
 * - Has many Witel
 * 
 * USAGE EXAMPLES:
 * ```php
 * // Get all witels in TREG 3
 * $treg3 = Regional::treg3()->first();
 * $witels = $treg3->witels;
 * 
 * // Get regional by number
 * $treg = Regional::byTregNumber(3)->first();
 * 
 * // Dropdown list
 * $options = Regional::getDropdownList();
 * ```
 */
class Regional extends Model
{
    use HasFactory;

    protected $table = 'regionals';

    protected $fillable = [
        'nama',
    ];

    // ========================================
    // CONSTANTS
    // ========================================

    public const TREG_1 = 'TREG 1';
    public const TREG_2 = 'TREG 2';
    public const TREG_3 = 'TREG 3';
    public const TREG_4 = 'TREG 4';
    public const TREG_5 = 'TREG 5';

    /**
     * Get all TREG constants as array
     */
    public static function getAllTregs(): array
    {
        return [
            self::TREG_1,
            self::TREG_2,
            self::TREG_3,
            self::TREG_4,
            self::TREG_5,
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Witels that belong to this regional
     */
    public function witels()
    {
        return $this->hasMany(Witel::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by TREG number
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $number - TREG number (1-5)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTregNumber($query, int $number)
    {
        if ($number < 1 || $number > 5) {
            throw new \InvalidArgumentException('TREG number must be between 1 and 5');
        }

        return $query->where('nama', 'TREG ' . $number);
    }

    /**
     * Scope for TREG 1
     */
    public function scopeTreg1($query)
    {
        return $query->where('nama', self::TREG_1);
    }

    /**
     * Scope for TREG 2
     */
    public function scopeTreg2($query)
    {
        return $query->where('nama', self::TREG_2);
    }

    /**
     * Scope for TREG 3 (Default for this system)
     */
    public function scopeTreg3($query)
    {
        return $query->where('nama', self::TREG_3);
    }

    /**
     * Scope for TREG 4
     */
    public function scopeTreg4($query)
    {
        return $query->where('nama', self::TREG_4);
    }

    /**
     * Scope for TREG 5
     */
    public function scopeTreg5($query)
    {
        return $query->where('nama', self::TREG_5);
    }

    /**
     * With witels count
     */
    public function scopeWithWitelsCount($query)
    {
        return $query->withCount('witels');
    }

    /**
     * With active witels (witels that have regional_id set)
     */
    public function scopeWithActiveWitels($query)
    {
        return $query->with(['witels' => function($q) {
            $q->whereNotNull('regional_id');
        }]);
    }

    // ========================================
    // ACCESSORS & HELPER METHODS
    // ========================================

    /**
     * Get TREG number from nama
     * 
     * @return int|null
     */
    public function getTregNumberAttribute(): ?int
    {
        // Extract number from "TREG 1", "TREG 2", etc.
        if (preg_match('/TREG\s+(\d+)/', $this->nama, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Get display name (same as nama for now)
     * 
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nama;
    }

    /**
     * Get badge color class for UI
     * 
     * @return string
     */
    public function getBadgeColorAttribute(): string
    {
        return match($this->nama) {
            self::TREG_1 => 'badge-primary',
            self::TREG_2 => 'badge-success',
            self::TREG_3 => 'badge-info',     // Default for this system
            self::TREG_4 => 'badge-warning',
            self::TREG_5 => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    /**
     * Get total witels count
     * 
     * @return int
     */
    public function getTotalWitelsAttribute(): int
    {
        return $this->witels()->count();
    }

    /**
     * Check if this is TREG 3 (default for this system)
     * 
     * @return bool
     */
    public function isTreg3(): bool
    {
        return $this->nama === self::TREG_3;
    }

    /**
     * Check if this is the default regional
     * 
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isTreg3();
    }

    // ========================================
    // STATIC METHODS
    // ========================================

    /**
     * Get regional by TREG number
     * 
     * @param int $number
     * @return self|null
     */
    public static function getByTregNumber(int $number): ?self
    {
        return static::byTregNumber($number)->first();
    }

    /**
     * Get TREG 3 (default for this system)
     * 
     * @return self|null
     */
    public static function getTreg3(): ?self
    {
        return static::treg3()->first();
    }

    /**
     * Get default regional (TREG 3)
     * 
     * @return self|null
     */
    public static function getDefault(): ?self
    {
        return static::getTreg3();
    }

    /**
     * Get dropdown list for forms
     * 
     * @return array [id => nama]
     */
    public static function getDropdownList(): array
    {
        return static::orderBy('nama')->pluck('nama', 'id')->toArray();
    }

    /**
     * Get dropdown list with witel counts
     * 
     * @return array [id => "TREG 1 (5 witels)"]
     */
    public static function getDropdownListWithCounts(): array
    {
        return static::withCount('witels')
                    ->orderBy('nama')
                    ->get()
                    ->mapWithKeys(function ($regional) {
                        $label = $regional->nama;
                        if ($regional->witels_count > 0) {
                            $label .= " ({$regional->witels_count} witels)";
                        }
                        return [$regional->id => $label];
                    })
                    ->toArray();
    }

    /**
     * Get all TREG names as array
     * 
     * @return array
     */
    public static function getAllTregNames(): array
    {
        return static::pluck('nama')->toArray();
    }

    /**
     * Check if TREG name exists
     * 
     * @param string $nama
     * @return bool
     */
    public static function tregExists(string $nama): bool
    {
        return static::where('nama', $nama)->exists();
    }

    /**
     * Get regional statistics
     * 
     * @return array
     */
    public static function getStatistics(): array
    {
        $stats = [];

        $regionals = static::withCount('witels')->get();

        foreach ($regionals as $regional) {
            $stats[$regional->nama] = [
                'id' => $regional->id,
                'nama' => $regional->nama,
                'treg_number' => $regional->treg_number,
                'total_witels' => $regional->witels_count,
                'is_default' => $regional->isDefault(),
            ];
        }

        return $stats;
    }

    // ========================================
    // BOOT METHOD
    // ========================================

    protected static function boot()
    {
        parent::boot();

        // Validation on saving
        static::saving(function ($regional) {
            // Validate TREG name format
            if (!preg_match('/^TREG\s+[1-5]$/', $regional->nama)) {
                throw new \InvalidArgumentException(
                    'Regional nama must be in format "TREG X" where X is 1-5'
                );
            }

            // Prevent duplicate TREG names
            $exists = static::where('nama', $regional->nama)
                           ->where('id', '!=', $regional->id ?? 0)
                           ->exists();

            if ($exists) {
                throw new \InvalidArgumentException(
                    "Regional {$regional->nama} already exists"
                );
            }
        });

        // Prevent deletion if witels still assigned
        static::deleting(function ($regional) {
            if ($regional->witels()->count() > 0) {
                throw new \Exception(
                    "Cannot delete {$regional->nama}: Still has {$regional->witels()->count()} witels assigned. " .
                    "Please reassign witels to another regional first."
                );
            }
        });
    }
}