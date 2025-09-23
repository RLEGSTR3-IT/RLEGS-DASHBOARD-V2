<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateCustomer extends Model
{
    use HasFactory;

    protected $table = 'corporate_customers';

    protected $fillable = [
        'nama',
        'nipnas',
    ];

    // Relationships
    public function ccRevenues()
    {
        return $this->hasMany(CcRevenue::class);
    }

    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', '%' . $search . '%')
              ->orWhere('nipnas', 'like', '%' . $search . '%');
        });
    }

    public function scopeByNipnas($query, $nipnas)
    {
        return $query->where('nipnas', $nipnas);
    }

    // Helper methods
    public function getDisplayNameAttribute(): string
    {
        return $this->nama . ' (' . $this->nipnas . ')';
    }

    // Get total revenue for specific period
    public function getTotalRevenue($year, $month = null)
    {
        $query = $this->ccRevenues()->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->sum('real_revenue');
    }

    // Get revenue by divisi for specific period
    public function getRevenueByDivisi($year, $month = null)
    {
        $query = $this->ccRevenues()
                      ->with('divisi')
                      ->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->get()->groupBy('divisi.kode')->map(function ($revenues) {
            return [
                'divisi_nama' => $revenues->first()->divisi->nama,
                'divisi_kode' => $revenues->first()->divisi->kode,
                'total_revenue' => $revenues->sum('real_revenue'),
                'total_target' => $revenues->sum('target_revenue'),
                'achievement_rate' => $revenues->sum('target_revenue') > 0 ?
                    ($revenues->sum('real_revenue') / $revenues->sum('target_revenue')) * 100 : 0
            ];
        });
    }

    // Get account managers handling this CC in specific period
    public function getAccountManagers($year, $month)
    {
        return $this->amRevenues()
                    ->with('accountManager')
                    ->where('tahun', $year)
                    ->where('bulan', $month)
                    ->get()
                    ->pluck('accountManager')
                    ->unique('id');
    }

    // Get latest revenue record
    public function getLatestRevenue()
    {
        return $this->ccRevenues()
                    ->orderBy('tahun', 'desc')
                    ->orderBy('bulan', 'desc')
                    ->first();
    }

    // Get current divisi and segment (from latest revenue)
    public function getCurrentDivisi()
    {
        $latestRevenue = $this->getLatestRevenue();
        return $latestRevenue?->divisi;
    }

    public function getCurrentSegment()
    {
        $latestRevenue = $this->getLatestRevenue();
        return $latestRevenue?->segment;
    }

    // Get historical revenue trend
    public function getRevenueTrend($months = 12)
    {
        return $this->ccRevenues()
                    ->selectRaw('tahun, bulan, SUM(real_revenue) as total_revenue')
                    ->groupBy('tahun', 'bulan')
                    ->orderBy('tahun', 'desc')
                    ->orderBy('bulan', 'desc')
                    ->limit($months)
                    ->get()
                    ->reverse()
                    ->values();
    }

    // Calculate average monthly revenue
    public function getAverageMonthlyRevenue($year = null)
    {
        $query = $this->ccRevenues();

        if ($year) {
            $query->where('tahun', $year);
        }

        $revenues = $query->selectRaw('AVG(real_revenue) as avg_revenue')->first();
        return $revenues->avg_revenue ?? 0;
    }
}