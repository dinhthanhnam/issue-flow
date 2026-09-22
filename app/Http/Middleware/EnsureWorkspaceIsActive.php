<?php

namespace App\Http\Middleware;

use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $this->resolveWorkspace($request);

        if ($workspace->archived_at !== null) {
            abort(403, 'Workspace is archived.');
        }

        return $next($request);
    }

    private function resolveWorkspace(Request $request): Workspace
    {
        $workspace = $request->route('workspace');

        if ($workspace instanceof Workspace) {
            return $workspace;
        }

        $project = $request->route('project');

        if ($project instanceof Project) {
            return $project->workspace;
        }

        $issue = $request->route('issue');

        if ($issue instanceof Issue) {
            return $issue->project->workspace;
        }

        $label = $request->route('label');

        if ($label instanceof Label) {
            return $label->workspace;
        }

        abort(500, 'Unable to resolve workspace for active workspace middleware.');
    }
}
