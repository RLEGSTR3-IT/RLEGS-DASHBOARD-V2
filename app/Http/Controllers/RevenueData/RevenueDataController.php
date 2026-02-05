<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\CcRevenue;
use App\Models\AmRevenue;
use App\Models\AccountManager;
use App\Models\CorporateCustomer;
use App\Models\Witel;
use App\Models\Divisi;
use App\Models\Segment;
use App\Models\Telda;
use App\Models\User;
use Carbon\Carbon;

/**
 * RevenueDataController
 * 
 * Handles all CRUD operations for Revenue Data Management
 * 
 * FIXED ISSUES:
 * 1. ✅ showDataAM() - Fixed 'username' column issue (use account_manager_id)
 * 2. ✅ deleteDataAM() - Fixed 'username' column issue (use account_manager_id)
 * 3. ✅ getDataAM() - Added eager loading for divisi badges
 * 4. ✅ getRevenueCC() - Fixed empty table issue with proper query
 * 5. ✅ All GET methods - Added comprehensive search functionality
 * 6. ✅ All GET methods - Added recordsFiltered for accurate badge counter
 * 7. ✅ DELETE METHODS - Fixed FK column name (cc_revenue_id → corporate_customer_id)
 * 8. ✅ DELETE DATA CC - Added warning + log option for related data
 * 9. ✅ DELETE REVENUE CC - Improved with warning (no log option)
 * 
 * @author RLEGS Team
 * @version 3.0 - Fixed Delete Methods (2026-02-05)
 */
class RevenueDataController extends Controller
{
    // ========================================
    // MAIN INDEX PAGE
    // ========================================

    /**
     * Display main revenue data management page
     */
    public function index()
    {
        return view('revenue.revenueData');
    }

    // ========================================
    // FILTER OPTIONS API
    // ========================================

    /**
     * Get all filter options for dropdowns
     * 
     * Returns: witels, divisions, segments, teldas
     */
    public function getFilterOptions()
    {
        try {
            $witels = Witel::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            $divisions = Divisi::select('id', 'nama', 'kode')
                ->orderBy('nama')
                ->get();

            $segments = Segment::select('id', 'lsegment_ho', 'ssegment_ho', 'divisi_id')
                ->with('divisi:id,kode')
                ->orderBy('lsegment_ho')
                ->get()
                ->map(function($segment) {
                    return [
                        'id' => $segment->id,
                        'lsegment_ho' => $segment->lsegment_ho,
                        'ssegment_ho' => $segment->ssegment_ho,
                        'divisi_id' => $segment->divisi_id,
                        'divisi_kode' => $segment->divisi ? $segment->divisi->kode : null,
                        'divisi' => $segment->divisi ? $segment->divisi->kode : null
                    ];
                });

            $teldas = Telda::select('id', 'nama', 'witel_id')
                ->with('witel:id,nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'witels' => $witels,
                'divisions' => $divisions,
                'segments' => $segments,
                'teldas' => $teldas
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading filter options: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat filter options: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // REVENUE CC - CRUD + MAPPING AM
    // ========================================

    /**
     * ✅ FIXED: Get Revenue CC with comprehensive search
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRevenueCC(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 25);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $witelId = $request->input('witel_id');
            $divisiId = $request->input('divisi_id');
            $segmentId = $request->input('segment_id');
            $periode = $request->input('periode'); // Format: YYYY-MM
            $tipeRevenue = $request->input('tipe_revenue', 'REGULER'); // REGULER, NGTMA, KOMBINASI
            $displayMode = $request->input('display_mode', 'default'); // default or all

            // Base query with eager loading
            $query = CcRevenue::query()
                ->with([
                    'corporateCustomer:id,nama,nipnas',
                    'divisi:id,nama,kode',
                    'segment:id,lsegment_ho,ssegment_ho',
                    'witelHo:id,nama',
                    'witelBill:id,nama'
                ])
                ->select('cc_revenues.*');

            // ✅ FIX: Join with corporate_customers for search functionality
            $query->leftJoin('corporate_customers', 'cc_revenues.corporate_customer_id', '=', 'corporate_customers.id');
            $query->leftJoin('divisi', 'cc_revenues.divisi_id', '=', 'divisi.id');

            // ✅ SEARCH FUNCTIONALITY - Search across multiple columns
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('corporate_customers.nama', 'LIKE', "%{$search}%")
                      ->orWhere('corporate_customers.nipnas', 'LIKE', "%{$search}%")
                      ->orWhere('divisi.nama', 'LIKE', "%{$search}%")
                      ->orWhere('divisi.kode', 'LIKE', "%{$search}%");
                });
            }

            // Filter by witel (either HO or BILL)
            if ($witelId && $witelId !== 'all') {
                $query->where(function($q) use ($witelId) {
                    $q->where('cc_revenues.witel_ho_id', $witelId)
                      ->orWhere('cc_revenues.witel_bill_id', $witelId);
                });
            }

            // Filter by divisi
            if ($divisiId && $divisiId !== 'all') {
                $query->where('cc_revenues.divisi_id', $divisiId);
            }

            // Filter by segment
            if ($segmentId && $segmentId !== 'all') {
                $query->where('cc_revenues.segment_id', $segmentId);
            }

            // Filter by periode (YYYY-MM)
            if ($periode) {
                list($year, $month) = explode('-', $periode);
                $query->where('cc_revenues.tahun', $year)
                      ->where('cc_revenues.bulan', $month);
            }

            // Filter by tipe revenue source
            if ($tipeRevenue && $tipeRevenue !== 'all') {
                if ($tipeRevenue === 'REGULER') {
                    $query->where('cc_revenues.revenue_source', 'REGULER');
                } elseif ($tipeRevenue === 'NGTMA') {
                    $query->where('cc_revenues.revenue_source', 'NGTMA');
                } elseif ($tipeRevenue === 'KOMBINASI') {
                    $query->whereIn('cc_revenues.revenue_source', ['REGULER', 'NGTMA']);
                }
            }

            // Order by latest
            $query->orderBy('cc_revenues.tahun', 'desc')
                  ->orderBy('cc_revenues.bulan', 'desc')
                  ->orderBy('corporate_customers.nama', 'asc');

            // ✅ FIX: Get total before pagination for recordsFiltered
            $recordsFiltered = $query->count(DB::raw('DISTINCT cc_revenues.id'));
            $recordsTotal = CcRevenue::count();

            // Paginate
            $data = $query->distinct()
                ->select('cc_revenues.*')
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform data
            $transformedData = $data->map(function($item) use ($displayMode) {
                $cc = $item->corporateCustomer;
                $divisi = $item->divisi;
                $segment = $item->segment;

                return [
                    'id' => $item->id,
                    'nama_cc' => $cc ? $cc->nama : '-',
                    'nipnas' => $cc ? $cc->nipnas : '-',
                    'divisi' => $divisi ? $divisi->nama : '-',
                    'divisi_kode' => $divisi ? $divisi->kode : '-',
                    'segment' => $segment ? $segment->lsegment_ho : '-',
                    'target_revenue_sold' => $item->target_revenue_sold,
                    'real_revenue_sold' => $item->real_revenue_sold,
                    'real_revenue_bill' => $item->real_revenue_bill,
                    'tipe_revenue' => $item->tipe_revenue,
                    'revenue_source' => $item->revenue_source,
                    'bulan' => $item->bulan,
                    'tahun' => $item->tahun,
                    'bulan_display' => Carbon::create($item->tahun, $item->bulan, 1)->translatedFormat('F Y')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered // ✅ Added for accurate badge counter
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading Revenue CC: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Revenue CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show single Revenue CC
     */
    public function showRevenueCC($id)
    {
        try {
            $revenue = CcRevenue::with([
                'corporateCustomer:id,nama,nipnas',
                'divisi:id,nama,kode',
                'segment:id,lsegment_ho',
                'witelHo:id,nama',
                'witelBill:id,nama'
            ])->findOrFail($id);

            $cc = $revenue->corporateCustomer;
            $divisi = $revenue->divisi;
            $segment = $revenue->segment;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $revenue->id,
                    'corporate_customer_id' => $revenue->corporate_customer_id,
                    'nama_cc' => $cc ? $cc->nama : '-',
                    'nipnas' => $cc ? $cc->nipnas : '-',
                    'divisi' => $divisi ? $divisi->nama : '-',
                    'divisi_kode' => $divisi ? $divisi->kode : '-',
                    'segment' => $segment ? $segment->lsegment_ho : '-',
                    'target_revenue' => $revenue->target_revenue_sold,
                    'real_revenue' => $revenue->tipe_revenue === 'HO' ? $revenue->real_revenue_sold : $revenue->real_revenue_bill,
                    'target_revenue_sold' => $revenue->target_revenue_sold,
                    'real_revenue_sold' => $revenue->real_revenue_sold,
                    'real_revenue_bill' => $revenue->real_revenue_bill,
                    'tipe_revenue' => $revenue->tipe_revenue,
                    'revenue_source' => $revenue->revenue_source,
                    'bulan' => $revenue->bulan,
                    'tahun' => $revenue->tahun
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing Revenue CC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail Revenue CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Revenue CC
     */
    public function updateRevenueCC(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'target_revenue' => 'required|numeric|min:0',
                'real_revenue' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $revenue = CcRevenue::findOrFail($id);
            
            // Update based on tipe_revenue
            if ($revenue->tipe_revenue === 'HO') {
                $revenue->target_revenue_sold = $request->target_revenue;
                $revenue->real_revenue_sold = $request->real_revenue;
            } else {
                $revenue->target_revenue_sold = $request->target_revenue;
                $revenue->real_revenue_bill = $request->real_revenue;
            }

            $revenue->save();

            // ✅ CASCADE UPDATE: Update related AM revenues
            $this->cascadeUpdateAmRevenues($revenue);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revenue CC berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Revenue CC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate Revenue CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Delete single Revenue CC - NO LOG OPTION
     * 
     * Improved with proper FK handling and warning
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRevenueCC($id)
    {
        try {
            DB::beginTransaction();

            $ccRevenue = CcRevenue::findOrFail($id);

            // ✅ FIX: Count related AM revenues dengan FK yang BENAR
            // am_revenues memiliki corporate_customer_id yang reference ke master CC
            // BUKAN cc_revenue_id!
            $relatedAmRevenues = AmRevenue::where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                ->where('bulan', $ccRevenue->bulan)
                ->where('tahun', $ccRevenue->tahun)
                ->count();

            Log::info('Delete Revenue CC:', [
                'cc_revenue_id' => $id,
                'cc_name' => $ccRevenue->nama_cc,
                'periode' => $ccRevenue->bulan . '/' . $ccRevenue->tahun,
                'related_am_revenues' => $relatedAmRevenues
            ]);

            // Delete related AM revenues first (same corporate_customer + periode)
            if ($relatedAmRevenues > 0) {
                AmRevenue::where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                    ->where('bulan', $ccRevenue->bulan)
                    ->where('tahun', $ccRevenue->tahun)
                    ->delete();
            }

            // Delete CC Revenue
            $ccRevenue->delete();

            DB::commit();

            $message = $relatedAmRevenues > 0
                ? "Revenue CC '{$ccRevenue->nama_cc}' dan {$relatedAmRevenues} Revenue AM terkait berhasil dihapus"
                : "Revenue CC '{$ccRevenue->nama_cc}' berhasil dihapus";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_data' => [
                    'cc_revenues' => 1,
                    'am_revenues' => $relatedAmRevenues
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Revenue CC not found: ' . $id);
            
            return response()->json([
                'success' => false,
                'message' => 'Revenue CC tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Revenue CC: ' . $e->getMessage(), [
                'cc_revenue_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Bulk delete Revenue CC - NO LOG OPTION
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDeleteRevenueCC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:cc_revenues,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ids = $request->input('ids');

            // Get CC revenues to find related AM revenues
            $ccRevenues = CcRevenue::whereIn('id', $ids)->get();
            
            // Collect conditions for AM revenues deletion
            $amRevenueDeleteCount = 0;
            foreach ($ccRevenues as $ccRev) {
                $count = AmRevenue::where('corporate_customer_id', $ccRev->corporate_customer_id)
                    ->where('bulan', $ccRev->bulan)
                    ->where('tahun', $ccRev->tahun)
                    ->delete();
                $amRevenueDeleteCount += $count;
            }

            // Delete CC revenues
            $deletedCount = CcRevenue::whereIn('id', $ids)->delete();

            DB::commit();

            Log::info('Bulk deleted Revenue CC:', [
                'deleted_cc_revenues' => $deletedCount,
                'deleted_am_revenues' => $amRevenueDeleteCount
            ]);

            $message = $amRevenueDeleteCount > 0
                ? "{$deletedCount} Revenue CC dan {$amRevenueDeleteCount} Revenue AM terkait berhasil dihapus"
                : "{$deletedCount} Revenue CC berhasil dihapus";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_data' => [
                    'cc_revenues' => $deletedCount,
                    'am_revenues' => $amRevenueDeleteCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk deleting Revenue CC: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete ALL Revenue CC
     */
    public function bulkDeleteAllRevenueCC()
    {
        try {
            DB::beginTransaction();

            // Delete all AM revenues first
            AmRevenue::truncate();

            // Delete all CC revenues
            $deleted = CcRevenue::count();
            CcRevenue::truncate();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} Revenue CC berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk deleting all Revenue CC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus semua Revenue CC: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // MAPPING AM TAB - NEW FEATURE
    // ========================================

    /**
     * Get CC Revenue AM Mapping (for display in tab)
     */
    public function getCcRevenueAmMapping($id)
    {
        try {
            $ccRevenue = CcRevenue::with('corporateCustomer')->findOrFail($id);

            // ✅ FIXED: am_revenues table doesn't have cc_revenue_id column
            $amMappings = AmRevenue::where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                ->where('bulan', $ccRevenue->bulan)
                ->where('tahun', $ccRevenue->tahun)
                ->where('divisi_id', $ccRevenue->divisi_id)
                ->with(['accountManager:id,nama,nik'])
                ->get()
                ->map(function($am) {
                    return [
                        'id' => $am->id,
                        'account_manager_id' => $am->account_manager_id,
                        'nama' => $am->accountManager ? $am->accountManager->nama : '-',
                        'nik' => $am->accountManager ? $am->accountManager->nik : '-',
                        'proporsi' => $am->proporsi,
                        'real_revenue' => $am->real_revenue,
                        'target_revenue' => $am->target_revenue
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'cc_revenue' => [
                        'id' => $ccRevenue->id,
                        'nama' => $ccRevenue->corporateCustomer ? $ccRevenue->corporateCustomer->nama : '-',
                        'real_revenue_sold' => $ccRevenue->real_revenue_sold,
                        'target_revenue_sold' => $ccRevenue->target_revenue_sold
                    ],
                    'am_mappings' => $amMappings
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting CC Revenue AM mapping: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat mapping AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CC Revenue AM Mappings for Edit (with display formats)
     */
    public function getCcRevenueAmMappingsForEdit($id)
    {
        try {
            $ccRevenue = CcRevenue::with('corporateCustomer')->findOrFail($id);

            // ✅ FIXED: am_revenues table doesn't have cc_revenue_id column
            // Instead, filter by corporate_customer_id, bulan, and tahun
            $amMappings = AmRevenue::where('corporate_customer_id', $ccRevenue->corporate_customer_id)
                ->where('bulan', $ccRevenue->bulan)
                ->where('tahun', $ccRevenue->tahun)
                ->where('divisi_id', $ccRevenue->divisi_id)
                ->with(['accountManager:id,nama,nik'])
                ->get()
                ->map(function($am) {
                    return [
                        'am_revenue_id' => $am->id,
                        'account_manager_id' => $am->account_manager_id,
                        'nama' => $am->accountManager ? $am->accountManager->nama : '-',
                        'nik' => $am->accountManager ? $am->accountManager->nik : '-',
                        'proporsi_decimal' => $am->proporsi,
                        'proporsi_percent_display' => ($am->proporsi * 100),
                        'real_revenue_display' => $am->real_revenue,
                        'target_revenue_display' => $am->target_revenue
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'cc_revenue' => [
                        'id' => $ccRevenue->id,
                        'nama' => $ccRevenue->corporateCustomer ? $ccRevenue->corporateCustomer->nama : '-',
                        'nipnas' => $ccRevenue->corporateCustomer ? $ccRevenue->corporateCustomer->nipnas : '-',
                        'real_revenue_sold' => $ccRevenue->real_revenue_sold,
                        'target_revenue_sold' => $ccRevenue->target_revenue_sold
                    ],
                    'am_mappings' => $amMappings
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting CC Revenue AM mappings for edit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update CC Revenue AM Mappings (proportions)
     */
    public function updateCcRevenueAmMappings(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'am_mappings' => 'required|array|min:1',
                'am_mappings.*.am_revenue_id' => 'required|integer|exists:am_revenues,id',
                'am_mappings.*.proporsi' => 'required|numeric|min:0|max:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate total proporsi = 1 (100%)
            $totalProporsi = array_sum(array_column($request->am_mappings, 'proporsi'));
            if (abs($totalProporsi - 1) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total proporsi harus 100%'
                ], 422);
            }

            DB::beginTransaction();

            $ccRevenue = CcRevenue::findOrFail($id);

            foreach ($request->am_mappings as $mapping) {
                $amRevenue = AmRevenue::findOrFail($mapping['am_revenue_id']);
                $amRevenue->proporsi = $mapping['proporsi'];
                
                // Recalculate revenues based on new proporsi
                $amRevenue->real_revenue = $ccRevenue->real_revenue_sold * $mapping['proporsi'];
                $amRevenue->target_revenue = $ccRevenue->target_revenue_sold * $mapping['proporsi'];
                
                $amRevenue->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mapping AM berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating CC Revenue AM mappings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate mapping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate proporsi for a CC
     */
    public function validateProporsiForCc(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cc_revenue_id' => 'required|integer|exists:cc_revenues,id',
                'am_mappings' => 'required|array|min:1',
                'am_mappings.*.proporsi' => 'required|numeric|min:0|max:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $totalProporsi = array_sum(array_column($request->am_mappings, 'proporsi'));
            $isValid = abs($totalProporsi - 1) < 0.01;

            return response()->json([
                'success' => true,
                'is_valid' => $isValid,
                'total_proporsi' => $totalProporsi,
                'message' => $isValid ? 'Proporsi valid (100%)' : 'Total proporsi harus 100%'
            ]);

        } catch (\Exception $e) {
            Log::error('Error validating proporsi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal validasi proporsi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Cascade update AM revenues when CC revenue changes
     */
    private function cascadeUpdateAmRevenues($ccRevenue)
    {
        $amRevenues = AmRevenue::where('corporate_customer_id', $ccRevenue->corporate_customer_id)
            ->where('bulan', $ccRevenue->bulan)
            ->where('tahun', $ccRevenue->tahun)
            ->get();

        foreach ($amRevenues as $amRevenue) {
            $amRevenue->real_revenue = $ccRevenue->real_revenue_sold * $amRevenue->proporsi;
            $amRevenue->target_revenue = $ccRevenue->target_revenue_sold * $amRevenue->proporsi;
            $amRevenue->save();
        }

        Log::info('Cascaded AM revenue updates', [
            'cc_revenue_id' => $ccRevenue->id,
            'am_revenues_updated' => $amRevenues->count()
        ]);
    }

    // ========================================
    // REVENUE AM - CRUD + RELATED AMS
    // ========================================

    /**
     * ✅ FIXED: Get Revenue AM with comprehensive search
     */
    public function getRevenueAM(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 25);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $witelId = $request->input('witel_id');
            $divisiId = $request->input('divisi_id');
            $periode = $request->input('periode');
            $role = $request->input('role', 'all'); // all, AM, HOTDA

            $query = AmRevenue::query()
                ->with([
                    'accountManager.witel:id,nama',
                    'accountManager.telda:id,nama',
                    'accountManager.divisi:id,nama,kode',
                    'corporateCustomer:id,nama,nipnas',
                    'ccRevenue:id,corporate_customer_id,divisi_id',
                    'ccRevenue.divisi:id,nama,kode'
                ])
                ->select('am_revenues.*');

            // Join for search
            $query->leftJoin('account_managers', 'am_revenues.account_manager_id', '=', 'account_managers.id');
            $query->leftJoin('corporate_customers', 'am_revenues.corporate_customer_id', '=', 'corporate_customers.id');

            // ✅ SEARCH FUNCTIONALITY
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('account_managers.nama', 'LIKE', "%{$search}%")
                      ->orWhere('account_managers.nik', 'LIKE', "%{$search}%")
                      ->orWhere('corporate_customers.nama', 'LIKE', "%{$search}%")
                      ->orWhere('corporate_customers.nipnas', 'LIKE', "%{$search}%");
                });
            }

            // Filter by witel
            if ($witelId && $witelId !== 'all') {
                $query->where('account_managers.witel_id', $witelId);
            }

            // Filter by divisi
            if ($divisiId && $divisiId !== 'all') {
                $query->whereHas('accountManager.divisi', function($q) use ($divisiId) {
                    $q->where('divisi.id', $divisiId);
                });
            }

            // Filter by periode
            if ($periode) {
                list($year, $month) = explode('-', $periode);
                $query->where('am_revenues.tahun', $year)
                      ->where('am_revenues.bulan', $month);
            }

            // Filter by role
            if ($role && $role !== 'all') {
                $query->where('account_managers.role', $role);
            }

            $query->orderBy('am_revenues.tahun', 'desc')
                  ->orderBy('am_revenues.bulan', 'desc')
                  ->orderBy('account_managers.nama', 'asc');

            // ✅ Get counts
            $recordsFiltered = $query->count(DB::raw('DISTINCT am_revenues.id'));
            $recordsTotal = AmRevenue::count();

            $data = $query->distinct()
                ->select('am_revenues.*')
                ->paginate($perPage, ['*'], 'page', $page);

            $transformedData = $data->map(function($item) {
                $am = $item->accountManager;
                $cc = $item->corporateCustomer;
                $telda = $am ? $am->telda : null;
                $divisi = $am && $am->divisi->isNotEmpty() ? $am->divisi->first() : null;

                $achievement = 0;
                if ($item->target_revenue > 0) {
                    $achievement = ($item->real_revenue / $item->target_revenue) * 100;
                }

                return [
                    'id' => $item->id,
                    'nama_am' => $am ? $am->nama : '-',
                    'nik' => $am ? $am->nik : '-',
                    'role' => $am ? $am->role : '-',
                    'divisi' => $divisi ? $divisi->nama : '-',
                    'divisi_kode' => $divisi ? $divisi->kode : '-',
                    'nama_cc' => $cc ? $cc->nama : '-',
                    'target_revenue' => $item->target_revenue,
                    'real_revenue' => $item->real_revenue,
                    'achievement' => $achievement,
                    'proporsi' => $item->proporsi * 100,
                    'telda_nama' => $telda ? $telda->nama : '-',
                    'bulan' => $item->bulan,
                    'tahun' => $item->tahun,
                    'bulan_display' => Carbon::create($item->tahun, $item->bulan, 1)->translatedFormat('F Y')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading Revenue AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show single Revenue AM
     */
    public function showRevenueAM($id)
    {
        try {
            $revenue = AmRevenue::with([
                'accountManager:id,nama,nik',
                'corporateCustomer:id,nama,nipnas'
            ])->findOrFail($id);

            $am = $revenue->accountManager;
            $cc = $revenue->corporateCustomer;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $revenue->id,
                    'account_manager_id' => $revenue->account_manager_id,
                    'nama_am' => $am ? $am->nama : '-',
                    'corporate_customer_id' => $revenue->corporate_customer_id,
                    'nama_cc' => $cc ? $cc->nama : '-',
                    'proporsi' => $revenue->proporsi * 100,
                    'target_revenue' => $revenue->target_revenue,
                    'real_revenue' => $revenue->real_revenue,
                    'bulan' => $revenue->bulan,
                    'tahun' => $revenue->tahun
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing Revenue AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Revenue AM
     */
    public function updateRevenueAM(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'proporsi' => 'sometimes|numeric|min:0|max:100',
                'target_revenue' => 'sometimes|numeric|min:0',
                'real_revenue' => 'sometimes|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $revenue = AmRevenue::findOrFail($id);

            if ($request->has('proporsi')) {
                $revenue->proporsi = $request->proporsi / 100;
                
                // Recalculate based on CC revenue
                $ccRevenue = $revenue->ccRevenue;
                if ($ccRevenue) {
                    $revenue->real_revenue = $ccRevenue->real_revenue_sold * $revenue->proporsi;
                    $revenue->target_revenue = $ccRevenue->target_revenue_sold * $revenue->proporsi;
                }
            }

            if ($request->has('target_revenue')) {
                $revenue->target_revenue = $request->target_revenue;
            }

            if ($request->has('real_revenue')) {
                $revenue->real_revenue = $request->real_revenue;
            }

            $revenue->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revenue AM berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Revenue AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Revenue AM
     */
    public function deleteRevenueAM($id)
    {
        try {
            $revenue = AmRevenue::findOrFail($id);
            $revenue->delete();

            return response()->json([
                'success' => true,
                'message' => 'Revenue AM berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting Revenue AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete Revenue AM
     */
    public function bulkDeleteRevenueAM(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:am_revenues,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deleted = AmRevenue::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} Revenue AM berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting Revenue AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete ALL Revenue AM
     */
    public function bulkDeleteAllRevenueAM()
    {
        try {
            $deleted = AmRevenue::count();
            AmRevenue::truncate();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} Revenue AM berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting all Revenue AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus semua Revenue AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Related AMs for AM Revenue (for edit form info)
     */
    public function getRelatedAmsForAmRevenue($id)
    {
        try {
            $amRevenue = AmRevenue::with(['corporateCustomer', 'ccRevenue'])->findOrFail($id);

            // Get all other AMs handling the same CC in the same period
            $relatedAms = AmRevenue::where('corporate_customer_id', $amRevenue->corporate_customer_id)
                ->where('bulan', $amRevenue->bulan)
                ->where('tahun', $amRevenue->tahun)
                ->where('id', '!=', $id)
                ->with(['accountManager:id,nama,nik'])
                ->get()
                ->map(function($am) {
                    return [
                        'id' => $am->id,
                        'account_manager_id' => $am->account_manager_id,
                        'nama' => $am->accountManager ? $am->accountManager->nama : '-',
                        'nik' => $am->accountManager ? $am->accountManager->nik : '-',
                        'proporsi' => $am->proporsi * 100,
                        'real_revenue' => $am->real_revenue,
                        'target_revenue' => $am->target_revenue
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'am_revenue' => [
                        'id' => $amRevenue->id,
                        'nama_cc' => $amRevenue->corporateCustomer ? $amRevenue->corporateCustomer->nama : '-',
                        'proporsi' => $amRevenue->proporsi * 100
                    ],
                    'related_ams' => $relatedAms,
                    'total_count' => $relatedAms->count() + 1
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting related AMs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat related AMs: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // DATA AM - CRUD
    // ========================================

    /**
     * ✅ FIXED: Get Data AM with divisi badges and comprehensive search
     */
    public function getDataAM(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 25);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $witelId = $request->input('witel_id');
            $role = $request->input('role', 'all');

            $query = AccountManager::query()
                ->with([
                    'witel:id,nama',
                    'telda:id,nama',
                    'divisis:id,nama,kode' // ✅ FIXED: Use divisis (plural) via pivot table
                ])
                ->select(
                    'account_managers.*',
                    'witel.nama as witel_nama',
                    'teldas.nama as telda_nama',
                    DB::raw('CASE WHEN users.id IS NOT NULL THEN 1 ELSE 0 END as is_registered')
                )
                ->leftJoin('witel', 'account_managers.witel_id', '=', 'witel.id')
                ->leftJoin('teldas', 'account_managers.telda_id', '=', 'teldas.id')
                ->leftJoin('users', 'account_managers.id', '=', 'users.account_manager_id'); // ✅ FIXED: Use account_manager_id

            // ✅ SEARCH FUNCTIONALITY
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('account_managers.nama', 'LIKE', "%{$search}%")
                      ->orWhere('account_managers.nik', 'LIKE', "%{$search}%")
                      ->orWhere('witel.nama', 'LIKE', "%{$search}%")
                      ->orWhere('teldas.nama', 'LIKE', "%{$search}%");
                });
            }

            // Filter by witel
            if ($witelId && $witelId !== 'all') {
                $query->where('account_managers.witel_id', $witelId);
            }

            // Filter by role
            if ($role && $role !== 'all') {
                $query->where('account_managers.role', $role);
            }

            $query->orderBy('account_managers.nama', 'asc');

            // ✅ Get counts
            $recordsFiltered = $query->count(DB::raw('DISTINCT account_managers.id'));
            $recordsTotal = AccountManager::count();

            $data = $query->distinct()
                ->select('account_managers.*', 'witel.nama as witel_nama', 'teldas.nama as telda_nama', DB::raw('CASE WHEN users.id IS NOT NULL THEN 1 ELSE 0 END as is_registered'))
                ->paginate($perPage, ['*'], 'page', $page);

            $transformedData = $data->map(function($item) {
                // ✅ FIX: Transform divisis to array format with kode (handle null safely)
                $divisiArray = [];
                if ($item->divisis && $item->divisis->isNotEmpty()) {
                    $divisiArray = $item->divisis->map(function($div) {
                        return [
                            'id' => $div->id,
                            'nama' => $div->nama,
                            'kode' => $div->kode
                        ];
                    })->toArray();
                }

                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'nik' => $item->nik,
                    'role' => $item->role,
                    'witel_id' => $item->witel_id,
                    'witel_nama' => $item->witel_nama,
                    'telda_id' => $item->telda_id,
                    'telda_nama' => $item->telda_nama,
                    'is_registered' => (bool) $item->is_registered,
                    'divisi' => $divisiArray // ✅ FIX: Now includes divisi array
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading Data AM: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Show single Data AM - Fixed 'username' column issue
     */
    public function showDataAM($id)
    {
        try {
            $am = AccountManager::query()
                ->with([
                    'witel:id,nama',
                    'telda:id,nama',
                    'divisis:id,nama,kode' // ✅ FIXED: Use divisis (plural)
                ])
                ->select(
                    'account_managers.*',
                    'witel.nama as witel_nama',
                    'teldas.nama as telda_nama',
                    DB::raw('CASE WHEN users.id IS NOT NULL THEN 1 ELSE 0 END as is_registered')
                )
                ->leftJoin('witel', 'account_managers.witel_id', '=', 'witel.id')
                ->leftJoin('teldas', 'account_managers.telda_id', '=', 'teldas.id')
                ->leftJoin('users', 'account_managers.id', '=', 'users.account_manager_id') // ✅ FIXED: Use account_manager_id instead of username
                ->where('account_managers.id', $id)
                ->first();

            if (!$am) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data AM tidak ditemukan'
                ], 404);
            }

            $divisiArray = [];
            if ($am->divisis && $am->divisis->isNotEmpty()) {
                $divisiArray = $am->divisis->map(function($div) {
                    return [
                        'id' => $div->id,
                        'nama' => $div->nama,
                        'kode' => $div->kode
                    ];
                })->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $am->id,
                    'nama' => $am->nama,
                    'nik' => $am->nik,
                    'role' => $am->role,
                    'witel_id' => $am->witel_id,
                    'witel_nama' => $am->witel_nama,
                    'telda_id' => $am->telda_id,
                    'telda_nama' => $am->telda_nama,
                    'is_registered' => (bool) $am->is_registered,
                    'divisi' => $divisiArray
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Show Data AM Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Data AM
     */
    public function updateDataAM(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'nik' => 'required|string|max:50',
                'role' => 'required|in:AM,HOTDA',
                'witel_id' => 'required|integer|exists:witel,id',
                'telda_id' => 'nullable|integer|exists:teldas,id',
                'divisi_ids' => 'required|array|min:1',
                'divisi_ids.*' => 'required|integer|exists:divisi,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate TELDA requirement for HOTDA
            if ($request->role === 'HOTDA' && !$request->telda_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'TELDA wajib diisi untuk role HOTDA'
                ], 422);
            }

            DB::beginTransaction();

            $am = AccountManager::findOrFail($id);
            
            $am->nama = $request->nama;
            $am->nik = $request->nik;
            $am->role = $request->role;
            $am->witel_id = $request->witel_id;
            $am->telda_id = $request->role === 'HOTDA' ? $request->telda_id : null;
            $am->save();

            // Update divisi pivot
            $am->divisi()->sync($request->divisi_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data AM berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Data AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Delete Data AM - Fixed 'username' column issue
     */
    public function deleteDataAM($id)
    {
        try {
            DB::beginTransaction();

            $am = AccountManager::findOrFail($id);

            // Delete related user account if exists
            // ✅ FIXED: Use account_manager_id instead of username
            User::where('account_manager_id', $id)->delete();

            // Delete divisi pivot
            $am->divisi()->detach();

            // Delete related revenues
            AmRevenue::where('account_manager_id', $id)->delete();

            $am->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data AM berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Data AM Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Data AM (for future use)
     */
    public function createDataAM(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'nik' => 'required|string|max:50|unique:account_managers,nik',
                'role' => 'required|in:AM,HOTDA',
                'witel_id' => 'required|integer|exists:witel,id',
                'telda_id' => 'nullable|integer|exists:teldas,id',
                'divisi_ids' => 'required|array|min:1',
                'divisi_ids.*' => 'required|integer|exists:divisi,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->role === 'HOTDA' && !$request->telda_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'TELDA wajib diisi untuk role HOTDA'
                ], 422);
            }

            DB::beginTransaction();

            $am = new AccountManager();
            $am->nama = $request->nama;
            $am->nik = $request->nik;
            $am->role = $request->role;
            $am->witel_id = $request->witel_id;
            $am->telda_id = $request->role === 'HOTDA' ? $request->telda_id : null;
            $am->save();

            $am->divisi()->attach($request->divisi_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data AM berhasil ditambahkan',
                'data' => ['id' => $am->id]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating Data AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change Password AM (for registered users)
     */
    public function changePasswordAM(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $am = AccountManager::findOrFail($id);
            
            $user = User::where('account_manager_id', $am->id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User belum terdaftar'
                ], 404);
            }

            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah'
            ]);

        } catch (\Exception $e) {
            Log::error('Error changing password AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete Data AM
     */
    public function bulkDeleteDataAM(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:account_managers,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $ids = $request->ids;

            // Delete related users
            User::whereIn('account_manager_id', $ids)->delete();

            // Delete divisi pivot
            DB::table('account_manager_divisi')->whereIn('account_manager_id', $ids)->delete();

            // Delete related revenues
            AmRevenue::whereIn('account_manager_id', $ids)->delete();

            // Delete AMs
            $deleted = AccountManager::whereIn('id', $ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} Data AM berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk deleting Data AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data AM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete ALL Data AM
     */
    public function bulkDeleteAllDataAM()
    {
        try {
            DB::beginTransaction();

            $deleted = AccountManager::count();

            // Delete all related data
            User::whereNotNull('account_manager_id')->delete();
            DB::table('account_manager_divisi')->truncate();
            AmRevenue::truncate();
            AccountManager::truncate();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} Data AM berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk deleting all Data AM: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus semua data AM: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // DATA CC - CRUD WITH LOG OPTIONS
    // ========================================

    /**
     * ✅ FIXED: Get Data CC with comprehensive search
     */
    public function getDataCC(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 25);
            $page = $request->input('page', 1);
            $search = $request->input('search');

            $query = CorporateCustomer::query();

            // ✅ SEARCH FUNCTIONALITY
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama', 'LIKE', "%{$search}%")
                      ->orWhere('nipnas', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('nama', 'asc');

            // ✅ Get counts
            $recordsFiltered = $query->count();
            $recordsTotal = CorporateCustomer::count();

            $data = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $data->items(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading Data CC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show single Data CC
     */
    public function showDataCC($id)
    {
        try {
            $cc = CorporateCustomer::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cc->id,
                    'nama' => $cc->nama,
                    'nipnas' => $cc->nipnas
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing Data CC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Data CC
     */
    public function updateDataCC(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'nipnas' => 'required|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cc = CorporateCustomer::findOrFail($id);
            
            $cc->nama = $request->nama;
            $cc->nipnas = $request->nipnas;
            $cc->save();

            return response()->json([
                'success' => true,
                'message' => 'Data CC berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating Data CC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Delete single Data CC (Master) - WITH LOG OPTION
     * 
     * Check related cc_revenues and am_revenues count
     * Option to save deletion log or permanent delete
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDataCC($id)
    {
        try {
            DB::beginTransaction();

            // Find CC
            $cc = CorporateCustomer::findOrFail($id);

            // ✅ FIX #1: Count related data dengan FK yang BENAR
            $relatedCcRevenues = CcRevenue::where('corporate_customer_id', $id)->count();
            $relatedAmRevenues = AmRevenue::where('corporate_customer_id', $id)->count();

            Log::info('Delete Data CC request:', [
                'cc_id' => $id,
                'cc_name' => $cc->nama,
                'nipnas' => $cc->nipnas,
                'related_cc_revenues' => $relatedCcRevenues,
                'related_am_revenues' => $relatedAmRevenues
            ]);

            // ✅ FIX #2: Check if has related data
            if ($relatedCcRevenues > 0 || $relatedAmRevenues > 0) {
                // Return warning info (frontend will show confirmation modal)
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'requires_confirmation' => true,
                    'message' => 'Data CC memiliki data terkait yang akan ikut terhapus!',
                    'warning_data' => [
                        'cc_name' => $cc->nama,
                        'nipnas' => $cc->nipnas,
                        'cc_revenues_count' => $relatedCcRevenues,
                        'am_revenues_count' => $relatedAmRevenues,
                        'total_related' => $relatedCcRevenues + $relatedAmRevenues
                    ]
                ], 200);
            }

            // ✅ No related data, safe to delete
            $cc->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Data CC '{$cc->nama}' berhasil dihapus"
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Data CC not found: ' . $id);
            
            return response()->json([
                'success' => false,
                'message' => 'Data CC tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting Data CC: ' . $e->getMessage(), [
                'cc_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW METHOD: Confirm delete Data CC with option
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmDeleteDataCC(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'delete_type' => 'required|in:permanent,with_log'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $cc = CorporateCustomer::findOrFail($id);
            $deleteType = $request->input('delete_type');

            // Get related data BEFORE deletion
            $relatedCcRevenues = CcRevenue::where('corporate_customer_id', $id)->get();
            $relatedAmRevenues = AmRevenue::where('corporate_customer_id', $id)->get();

            Log::info('Confirm delete Data CC:', [
                'cc_id' => $id,
                'cc_name' => $cc->nama,
                'delete_type' => $deleteType,
                'cc_revenues' => $relatedCcRevenues->count(),
                'am_revenues' => $relatedAmRevenues->count()
            ]);

            // ✅ OPTION 1: Save log before deleting
            if ($deleteType === 'with_log') {
                $logData = [
                    'deleted_at' => now()->toDateTimeString(),
                    'cc' => $cc->toArray(),
                    'cc_revenues' => $relatedCcRevenues->toArray(),
                    'am_revenues' => $relatedAmRevenues->toArray()
                ];

                // Save log to file
                $logFileName = 'deleted_cc_' . $cc->id . '_' . now()->format('Y-m-d_His') . '.json';
                $logPath = storage_path('logs/deleted_data/' . $logFileName);
                
                // Ensure directory exists
                if (!file_exists(dirname($logPath))) {
                    mkdir(dirname($logPath), 0755, true);
                }
                
                file_put_contents($logPath, json_encode($logData, JSON_PRETTY_PRINT));

                Log::info('Deletion log saved:', ['path' => $logPath]);
            }

            // Delete related AM revenues first (FK constraint)
            AmRevenue::where('corporate_customer_id', $id)->delete();
            
            // Delete related CC revenues
            CcRevenue::where('corporate_customer_id', $id)->delete();
            
            // Finally delete the CC itself
            $cc->delete();

            DB::commit();

            $message = $deleteType === 'with_log' 
                ? "Data CC '{$cc->nama}' dan {$relatedCcRevenues->count()} Revenue CC serta {$relatedAmRevenues->count()} Revenue AM berhasil dihapus (log tersimpan)"
                : "Data CC '{$cc->nama}' dan semua data terkait berhasil dihapus permanen";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_data' => [
                    'cc' => 1,
                    'cc_revenues' => $relatedCcRevenues->count(),
                    'am_revenues' => $relatedAmRevenues->count()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Data CC not found: ' . $id);
            
            return response()->json([
                'success' => false,
                'message' => 'Data CC tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirming delete Data CC: ' . $e->getMessage(), [
                'cc_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Data CC (for future use)
     */
    public function createDataCC(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'nipnas' => 'required|string|max:50|unique:corporate_customers,nipnas'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cc = new CorporateCustomer();
            $cc->nama = $request->nama;
            $cc->nipnas = $request->nipnas;
            $cc->save();

            return response()->json([
                'success' => true,
                'message' => 'Data CC berhasil ditambahkan',
                'data' => ['id' => $cc->id]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating Data CC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data CC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Bulk delete Data CC (Master) - WITH LOG OPTION
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDeleteDataCC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:corporate_customers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ids = $request->input('ids');

            // ✅ Count related data for ALL selected CCs
            $totalCcRevenues = CcRevenue::whereIn('corporate_customer_id', $ids)->count();
            $totalAmRevenues = AmRevenue::whereIn('corporate_customer_id', $ids)->count();

            Log::info('Bulk delete Data CC request:', [
                'cc_ids' => $ids,
                'count' => count($ids),
                'related_cc_revenues' => $totalCcRevenues,
                'related_am_revenues' => $totalAmRevenues
            ]);

            // ✅ Check if has related data
            if ($totalCcRevenues > 0 || $totalAmRevenues > 0) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'requires_confirmation' => true,
                    'message' => count($ids) . ' Data CC memiliki data terkait yang akan ikut terhapus!',
                    'warning_data' => [
                        'selected_cc' => count($ids),
                        'cc_revenues_count' => $totalCcRevenues,
                        'am_revenues_count' => $totalAmRevenues,
                        'total_related' => $totalCcRevenues + $totalAmRevenues
                    ]
                ], 200);
            }

            // Safe to delete (no related data)
            $deleted = CorporateCustomer::whereIn('id', $ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} Data CC berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk deleting Data CC: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW METHOD: Confirm bulk delete Data CC
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmBulkDeleteDataCC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:corporate_customers,id',
            'delete_type' => 'required|in:permanent,with_log'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ids = $request->input('ids');
            $deleteType = $request->input('delete_type');

            // Get all CCs and related data BEFORE deletion
            $ccs = CorporateCustomer::whereIn('id', $ids)->get();
            $relatedCcRevenues = CcRevenue::whereIn('corporate_customer_id', $ids)->get();
            $relatedAmRevenues = AmRevenue::whereIn('corporate_customer_id', $ids)->get();

            Log::info('Confirm bulk delete Data CC:', [
                'cc_ids' => $ids,
                'delete_type' => $deleteType,
                'cc_count' => $ccs->count(),
                'cc_revenues' => $relatedCcRevenues->count(),
                'am_revenues' => $relatedAmRevenues->count()
            ]);

            // ✅ OPTION: Save log before deleting
            if ($deleteType === 'with_log') {
                $logData = [
                    'deleted_at' => now()->toDateTimeString(),
                    'cc_count' => $ccs->count(),
                    'corporate_customers' => $ccs->toArray(),
                    'cc_revenues' => $relatedCcRevenues->toArray(),
                    'am_revenues' => $relatedAmRevenues->toArray()
                ];

                $logFileName = 'deleted_cc_bulk_' . now()->format('Y-m-d_His') . '.json';
                $logPath = storage_path('logs/deleted_data/' . $logFileName);
                
                if (!file_exists(dirname($logPath))) {
                    mkdir(dirname($logPath), 0755, true);
                }
                
                file_put_contents($logPath, json_encode($logData, JSON_PRETTY_PRINT));

                Log::info('Bulk deletion log saved:', ['path' => $logPath]);
            }

            // Delete in correct order (FK constraints)
            AmRevenue::whereIn('corporate_customer_id', $ids)->delete();
            CcRevenue::whereIn('corporate_customer_id', $ids)->delete();
            $deletedCount = CorporateCustomer::whereIn('id', $ids)->delete();

            DB::commit();

            $message = $deleteType === 'with_log'
                ? "{$deletedCount} Data CC, {$relatedCcRevenues->count()} Revenue CC, dan {$relatedAmRevenues->count()} Revenue AM berhasil dihapus (log tersimpan)"
                : "{$deletedCount} Data CC dan semua data terkait berhasil dihapus permanen";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_data' => [
                    'cc' => $deletedCount,
                    'cc_revenues' => $relatedCcRevenues->count(),
                    'am_revenues' => $relatedAmRevenues->count()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirming bulk delete Data CC: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Bulk delete ALL Data CC (Master) - WITH LOG OPTION
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDeleteAllDataCC()
    {
        try {
            DB::beginTransaction();

            // Count all
            $totalCc = CorporateCustomer::count();
            $totalCcRevenues = CcRevenue::count();
            $totalAmRevenues = AmRevenue::count();

            Log::warning('Bulk delete ALL Data CC requested:', [
                'total_cc' => $totalCc,
                'total_cc_revenues' => $totalCcRevenues,
                'total_am_revenues' => $totalAmRevenues
            ]);

            if ($totalCc === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada Data CC untuk dihapus'
                ], 404);
            }

            // Require confirmation
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'message' => 'Anda akan menghapus SEMUA Data CC!',
                'warning_data' => [
                    'total_cc' => $totalCc,
                    'cc_revenues_count' => $totalCcRevenues,
                    'am_revenues_count' => $totalAmRevenues,
                    'total_all' => $totalCc + $totalCcRevenues + $totalAmRevenues
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk delete all Data CC: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW METHOD: Confirm bulk delete ALL Data CC
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmBulkDeleteAllDataCC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delete_type' => 'required|in:permanent,with_log',
            'confirmation_text' => 'required|string|in:DELETE ALL'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Ketik "DELETE ALL" untuk konfirmasi.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $deleteType = $request->input('delete_type');

            // Get ALL data before deletion
            $allCc = CorporateCustomer::all();
            $allCcRevenues = CcRevenue::all();
            $allAmRevenues = AmRevenue::all();

            Log::warning('Confirming delete ALL Data CC:', [
                'delete_type' => $deleteType,
                'cc_count' => $allCc->count(),
                'cc_revenues_count' => $allCcRevenues->count(),
                'am_revenues_count' => $allAmRevenues->count()
            ]);

            // Save log if requested
            if ($deleteType === 'with_log') {
                $logData = [
                    'deleted_at' => now()->toDateTimeString(),
                    'type' => 'BULK_DELETE_ALL',
                    'corporate_customers' => $allCc->toArray(),
                    'cc_revenues' => $allCcRevenues->toArray(),
                    'am_revenues' => $allAmRevenues->toArray()
                ];

                $logFileName = 'deleted_ALL_cc_' . now()->format('Y-m-d_His') . '.json';
                $logPath = storage_path('logs/deleted_data/' . $logFileName);
                
                if (!file_exists(dirname($logPath))) {
                    mkdir(dirname($logPath), 0755, true);
                }
                
                file_put_contents($logPath, json_encode($logData, JSON_PRETTY_PRINT));

                Log::warning('ALL CC deletion log saved:', ['path' => $logPath]);
            }

            // Delete in correct order
            AmRevenue::truncate();
            CcRevenue::truncate();
            CorporateCustomer::truncate();

            DB::commit();

            $message = $deleteType === 'with_log'
                ? "SEMUA Data CC ({$allCc->count()}), Revenue CC ({$allCcRevenues->count()}), dan Revenue AM ({$allAmRevenues->count()}) berhasil dihapus (log tersimpan)"
                : "SEMUA Data CC dan data terkait berhasil dihapus permanen";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_data' => [
                    'cc' => $allCc->count(),
                    'cc_revenues' => $allCcRevenues->count(),
                    'am_revenues' => $allAmRevenues->count()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirming delete ALL Data CC: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}