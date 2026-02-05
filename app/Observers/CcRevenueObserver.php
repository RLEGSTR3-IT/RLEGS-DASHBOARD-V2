<?php

namespace App\Observers;

use App\Models\CcRevenue;
use App\Models\AmRevenue;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * CcRevenueObserver - Auto-recalculate AM Revenue when CC Revenue updated
 * ============================================================================
 * 
 * TRIGGER: When CC Revenue updated (real_revenue_sold or target_revenue_sold)
 * ACTION: Recalculate all AM revenues for that CC in same period
 * 
 * @author RLEGS Team
 * @version 1.0
 * ============================================================================
 */
class CcRevenueObserver
{
    /**
     * Handle the CcRevenue "updated" event.
     */
    public function updated(CcRevenue $ccRevenue)
    {
        // Check if revenue fields changed
        if ($ccRevenue->isDirty('real_revenue_sold') || $ccRevenue->isDirty('target_revenue_sold')) {
            
            Log::info('🔄 CC Revenue updated, recalculating AM revenues', [
                'cc_revenue_id' => $ccRevenue->id,
                'cc_id' => $ccRevenue->corporate_customer_id,
                'period' => "{$ccRevenue->tahun}-{$ccRevenue->bulan}",
                'old_real_sold' => $ccRevenue->getOriginal('real_revenue_sold'),
                'new_real_sold' => $ccRevenue->real_revenue_sold,
                'old_target_sold' => $ccRevenue->getOriginal('target_revenue_sold'),
                'new_target_sold' => $ccRevenue->target_revenue_sold
            ]);
            
            // Find all AM revenues for this CC in this period
            $amRevenues = AmRevenue::where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                ->where('bulan', $ccRevenue->bulan)
                ->where('tahun', $ccRevenue->tahun)
                ->get();
            
            if ($amRevenues->isEmpty()) {
                Log::info('ℹ️ No AM revenues found to recalculate');
                return;
            }
            
            // Recalculate each AM revenue
            $recalculated = 0;
            foreach ($amRevenues as $amRevenue) {
                $oldTargetRevenue = $amRevenue->target_revenue;
                $oldRealRevenue = $amRevenue->real_revenue;
                
                $newTargetRevenue = $ccRevenue->target_revenue_sold * $amRevenue->proporsi;
                $newRealRevenue = $ccRevenue->real_revenue_sold * $amRevenue->proporsi;
                
                $amRevenue->update([
                    'target_revenue' => $newTargetRevenue,
                    'real_revenue' => $newRealRevenue,
                ]);
                
                $recalculated++;
                
                Log::info('✅ AM Revenue recalculated', [
                    'am_revenue_id' => $amRevenue->id,
                    'am_id' => $amRevenue->account_manager_id,
                    'proporsi' => $amRevenue->proporsi,
                    'old_target' => $oldTargetRevenue,
                    'new_target' => $newTargetRevenue,
                    'old_real' => $oldRealRevenue,
                    'new_real' => $newRealRevenue
                ]);
            }
            
            Log::info('✅ All AM revenues recalculated successfully', [
                'total_recalculated' => $recalculated
            ]);
        }
    }
}