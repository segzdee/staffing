<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\BulkShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Controller for bulk shift posting via CSV upload.
 * BIZ-005: Bulk Shift Posting
 */
class BulkShiftController extends Controller
{
    protected $bulkShiftService;

    public function __construct(BulkShiftService $bulkShiftService)
    {
        $this->middleware('auth');
        $this->middleware('user_type:business');
        $this->bulkShiftService = $bulkShiftService;
    }

    /**
     * Show bulk upload form.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('business.shifts.bulk-upload');
    }

    /**
     * Download CSV template.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadTemplate()
    {
        $csv = $this->bulkShiftService->generateTemplate();

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_shift_template.csv"',
        ]);
    }

    /**
     * Validate uploaded CSV file.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateUpload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('csv_file');
            $filePath = $file->getRealPath();

            $validation = $this->bulkShiftService->validateCsvFile($filePath);

            return response()->json([
                'success' => $validation['valid'],
                'validation' => $validation,
            ]);
        } catch (\Exception $e) {
            Log::error('CSV validation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate CSV file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process bulk shift upload.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $business = auth()->user();
            $file = $request->file('csv_file');
            $filePath = $file->getRealPath();

            // Validate CSV
            $validation = $this->bulkShiftService->validateCsvFile($filePath);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV validation failed',
                    'errors' => $validation['errors'],
                ], 422);
            }

            // Generate batch ID
            $batchId = $this->bulkShiftService->generateBatchId();

            // Process upload
            $results = $this->bulkShiftService->processBulkUpload(
                $business,
                $validation['data'],
                $batchId
            );

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk upload failed', [
                'business_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload status and results.
     *
     * @param string $batchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus($batchId)
    {
        $status = Cache::get("bulk_upload_{$batchId}_status", 'not_found');
        $progress = Cache::get("bulk_upload_{$batchId}_progress", 0);
        $results = Cache::get("bulk_upload_{$batchId}_results");
        $error = Cache::get("bulk_upload_{$batchId}_error");

        return response()->json([
            'status' => $status,
            'progress' => $progress,
            'results' => $results,
            'error' => $error,
        ]);
    }

    /**
     * Show results page for completed upload.
     *
     * @param string $batchId
     * @return \Illuminate\View\View
     */
    public function showResults($batchId)
    {
        $status = Cache::get("bulk_upload_{$batchId}_status", 'not_found');
        $results = Cache::get("bulk_upload_{$batchId}_results");
        $error = Cache::get("bulk_upload_{$batchId}_error");

        if ($status === 'not_found') {
            abort(404, 'Upload batch not found or expired');
        }

        return view('business.shifts.bulk-results', [
            'batchId' => $batchId,
            'status' => $status,
            'results' => $results,
            'error' => $error,
        ]);
    }
}
