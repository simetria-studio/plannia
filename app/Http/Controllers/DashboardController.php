<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\GeneratedDocument;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $stats = [
            'students' => Student::where('school_id', $schoolId)->count(),
            'documents' => GeneratedDocument::where('school_id', $schoolId)->count(),
            'pending' => GeneratedDocument::where('school_id', $schoolId)
                ->where('status', DocumentStatus::Pendente)->count(),
            'approved' => GeneratedDocument::where('school_id', $schoolId)
                ->where('status', DocumentStatus::Aprovado)->count(),
        ];

        $recentDocuments = GeneratedDocument::with(['student', 'creator'])
            ->where('school_id', $schoolId)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'recentDocuments'));
    }
}
