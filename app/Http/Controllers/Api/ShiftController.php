<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Shift::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $shifts = $query->paginate(20);

        return response()->json($shifts);
    }

    public function show(int $id): JsonResponse
    {
        $shift = Shift::with(['business', 'skills', 'assignments'])->findOrFail($id);

        return response()->json($shift);
    }
}
