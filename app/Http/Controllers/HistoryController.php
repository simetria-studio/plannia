<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $search = $request->get('search');
        $tab = $request->get('tab', 'all');

        $query = GeneratedDocument::with(['student', 'creator', 'approver'])
            ->where('school_id', $schoolId);

        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        if ($tab === 'approved') {
            $query->where('status', DocumentStatus::Aprovado);
        } elseif ($tab === 'pending') {
            $query->where('status', DocumentStatus::Pendente);
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'all' => GeneratedDocument::where('school_id', $schoolId)->count(),
            'approved' => GeneratedDocument::where('school_id', $schoolId)
                ->where('status', DocumentStatus::Aprovado)->count(),
            'pending' => GeneratedDocument::where('school_id', $schoolId)
                ->where('status', DocumentStatus::Pendente)->count(),
        ];

        return view('history.index', compact('documents', 'search', 'tab', 'counts'));
    }
}
