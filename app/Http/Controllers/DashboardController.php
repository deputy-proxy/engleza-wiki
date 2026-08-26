<?php
namespace App\Http\Controllers;
use App\Models\{Resource, SavedResource, TestAttempt};

class DashboardController extends Controller {
    public function index() {
        $u = auth()->user();
        $attempts = TestAttempt::where('user_id',$u->id)->with('test','result')->latest()->limit(10)->get();
        $completed = $attempts->where('status','completed');
        $avg = $completed->count() ? round($completed->avg('percentage'),1) : 0;
        $latestLevel = $completed->first()?->level_result;
        $saved = SavedResource::where('user_id',$u->id)->with('resource')->latest()->limit(6)->get();
        return view('pages.dashboard.index', compact('attempts','completed','avg','latestLevel','saved'));
    }
    public function tests() {
        $attempts = TestAttempt::where('user_id',auth()->id())->with('test','result')->latest()->paginate(20);
        return view('pages.dashboard.tests', compact('attempts'));
    }
    public function resources() {
        $saved = SavedResource::where('user_id',auth()->id())->with('resource.level')->latest()->paginate(20);
        return view('pages.dashboard.resources', compact('saved'));
    }
    public function writing() {
        $submissions = \App\Models\WritingSubmission::where('user_id',auth()->id())->with('writingResource.resource')->latest()->paginate(20);
        return view('pages.dashboard.writing', compact('submissions'));
    }
    public function toggleSave(Resource $resource) {
        $u = auth()->id();
        $existing = SavedResource::where('user_id',$u)->where('resource_id',$resource->id)->first();
        if ($existing) $existing->delete();
        else SavedResource::create(['user_id'=>$u,'resource_id'=>$resource->id]);
        return back();
    }
}
