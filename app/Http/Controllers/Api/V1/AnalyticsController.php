<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get summary KPI.
     */
    public function summary(): JsonResponse
    {
        $activeForms = Form::where('status', 'active')->count();
        $totalResponses = Submission::count();
        $averageConversion = 0; // Placeholder until views table is implemented

        return response()->json([
            'success' => true,
            'data' => [
                'total_responses' => $totalResponses,
                'active_forms' => $activeForms,
                'average_conversion' => $averageConversion,
            ],
        ]);
    }

    /**
     * Get submission trend for the last 7 days.
     */
    public function trend(Request $request): JsonResponse
    {
        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today()->endOfDay();

        // Get grouping by date. SQLite uses date()
        $trends = Submission::select(
            DB::raw('date(created_at) as date'),
            DB::raw('count(id) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $formattedTrends = $trends->map(function ($item) use ($hariIndo) {
            $carbonDate = Carbon::parse($item->date);
            $name = substr($hariIndo[$carbonDate->dayOfWeek], 0, 3);

            return [
                'name' => $name,
                'value' => (int) $item->count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedTrends,
        ]);
    }

    /**
     * Get status distribution of submissions.
     */
    public function statusDistribution(): JsonResponse
    {
        $distribution = Submission::select('status', DB::raw('count(id) as count'))
            ->groupBy('status')
            ->orderBy('status', 'asc')
            ->get();

        $formattedDistribution = $distribution->map(fn ($item) => [
            'status' => $item->status,
            'count' => (int) $item->count,
        ]);

        return response()->json([
            'success' => true,
            'data' => $formattedDistribution,
        ]);
    }
}
