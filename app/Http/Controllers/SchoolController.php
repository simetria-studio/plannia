<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRequest;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function edit(): View
    {
        $school = School::findOrFail(auth()->user()->school_id);

        return view('schools.edit', compact('school'));
    }

    public function update(StoreSchoolRequest $request): RedirectResponse
    {
        $school = School::findOrFail($request->user()->school_id);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($school->logo_path) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($data['logo']);
        $school->update($data);

        return redirect()->route('schools.edit')
            ->with('success', 'Escola atualizada com sucesso.');
    }
}
