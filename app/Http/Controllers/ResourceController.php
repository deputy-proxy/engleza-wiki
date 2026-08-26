<?php
namespace App\Http\Controllers;

use App\Models\{Resource, Level, Skill, Category};
use Illuminate\Http\Request;

class ResourceController extends Controller {
    public function index(Request $r) {
        $q = Resource::published()->with(['level','skills','categories']);
        if ($r->filled('type')) $q->where('type',$r->type);
        if ($r->filled('level')) $q->whereHas('level', fn($x)=>$x->where('slug',$r->level));
        if ($r->filled('skill')) $q->whereHas('skills', fn($x)=>$x->where('slug',$r->skill));
        if ($r->filled('audience')) $q->whereHas('audiences', fn($x)=>$x->where('slug',$r->audience));
        $resources = $q->latest('published_at')->paginate(12)->withQueryString();
        $levels = Level::orderBy('sort_order')->get();
        $skills = Skill::orderBy('name')->get();
        return view('pages.resources.index', compact('resources','levels','skills'));
    }

    public function show(Resource $resource) {
        abort_unless($resource->status === 'published', 404);
        $resource->load(['level','skills','categories','tags','topics','audiences','media','author']);
        $related = Resource::published()
            ->where('id','!=',$resource->id)
            ->whereHas('topics', fn($q)=>$q->whereIn('topics.id',$resource->topics->pluck('id')))
            ->limit(4)->get();
        return view('pages.resources.show', compact('resource','related'));
    }
}
