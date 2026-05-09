<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $forms = Form::where('user_id', $request->user()->id)
            ->when($request->filled('status'), function ($query) use ($request) {
                // Filter berdasarkan status ('active' atau 'draft')
                $query->where('status', $request->status);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                // Pencarian berdasarkan judul form
                $query->where('title', 'like', '%'.$request->search.'%');
            })
            // Urutkan dari yang terbaru
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $forms,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $form = Form::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title'].'-'.uniqid()),
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'data' => $form,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $form = Form::with(['fields' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $form,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $form = Form::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $form->update($validated);

        return response()->json([
            'success' => true,
            'data' => $form,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $form = Form::findOrFail($id);
        $form->delete();

        return response()->json([
            'success' => true,
            'message' => 'Form berhasil dihapus',
        ]);
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $form = Form::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:draft,active',
        ]);

        $form->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'data' => $form,
            'message' => 'Status form berhasil diubah',
        ]);
    }

    /**
     * Display the stats for the specified resource.
     */
    public function stats(string $id): JsonResponse
    {
        $form = Form::findOrFail($id);

        // Mock data as per planning.md
        $totalViews = 5000;
        $totalSubmissions = 1248;
        $conversionRate = $totalViews > 0 ? ($totalSubmissions / $totalViews) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_views' => $totalViews,
                'total_submissions' => $totalSubmissions,
                'conversion_rate' => round($conversionRate, 2),
            ],
        ]);
    }
}
