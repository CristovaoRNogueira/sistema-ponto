<?php
namespace App\Http\Controllers;
use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobTitleController extends Controller {
    public function index() {
        $jobTitles = JobTitle::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        return view('job_titles.index', compact('jobTitles'));
    }
    public function store(Request $request) {
        $request->validate(['name' => 'required|string|max:255', 'cbo' => 'nullable|string']);
        JobTitle::create(['name' => $request->name, 'cbo' => $request->cbo, 'company_id' => Auth::user()->company_id]);
        return back()->with('success', 'Cargo adicionado!');
    }
    public function destroy(JobTitle $jobTitle) {
        if($jobTitle->company_id === Auth::user()->company_id) $jobTitle->delete();
        return back()->with('success', 'Removido com sucesso!');
    }
}