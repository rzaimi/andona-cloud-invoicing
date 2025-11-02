<?php

namespace App\Modules\Help\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    public function index()
    {
        return Inertia::render('help/index', [
            'user' => $this->contextService->getUserContext(),
            'stats' => $this->contextService->getDashboardStats(),
        ]);
    }

    public function show($category)
    {
        return Inertia::render('help/show', [
            'user' => $this->contextService->getUserContext(),
            'category' => $category,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        // In a real application, you would search through help articles
        $results = [
            // Mock search results
        ];

        return response()->json([
            'results' => $results,
            'query' => $query,
        ]);
    }
}
