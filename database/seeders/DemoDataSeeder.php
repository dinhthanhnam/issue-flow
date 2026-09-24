<?php

namespace Database\Seeders;

use App\Actions\Projects\CreateProject;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(CreateWorkspace $createWorkspace, CreateProject $createProject): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function () use ($createWorkspace, $createProject): void {
            $user = User::query()->where('email', 'test@example.com')->firstOrFail();
            $workspace = Workspace::query()->where('slug', 'demo-issue-flow')->first();

            if ($workspace === null) {
                $workspace = $createWorkspace->handle($user, 'Demo Workspace', 'demo-issue-flow');
            } elseif (! $workspace->hasRole($user, WorkspaceRole::Owner)) {
                throw new RuntimeException('The demo workspace slug belongs to another owner.');
            }

            $project = $workspace->projects()->where('key', 'DEMO')->first();

            if ($project === null) {
                $project = $createProject->handle(
                    workspace: $workspace,
                    creator: $user,
                    name: 'Issue Flow Demo',
                    key: 'DEMO',
                    description: 'A sample project for exploring the issue board.',
                );
            }

            $labels = [];

            foreach ([
                'Feature' => '#2563EB',
                'Bug' => '#DC2626',
                'Improvement' => '#16A34A',
            ] as $name => $color) {
                $labels[$name] = $workspace->labels()->firstOrCreate(
                    ['name' => $name],
                    ['color' => $color],
                );
            }

            foreach ([
                [1, 'Set up authentication', IssueStatus::Todo, IssuePriority::High, 'Feature'],
                [2, 'Add project board', IssueStatus::Todo, IssuePriority::Medium, 'Feature'],
                [3, 'Review workspace permissions', IssueStatus::InProgress, IssuePriority::High, 'Improvement'],
                [4, 'Improve issue filters', IssueStatus::InProgress, IssuePriority::Medium, 'Improvement'],
                [5, 'Create API resources', IssueStatus::Done, IssuePriority::Low, 'Bug'],
            ] as [$number, $title, $status, $priority, $labelName]) {
                $issue = $project->issues()->firstOrCreate(
                    ['number' => $number],
                    [
                        'reporter_id' => $user->id,
                        'assignee_id' => $user->id,
                        'title' => $title,
                        'description' => 'Sample issue for trying the board and issue details.',
                        'status' => $status,
                        'priority' => $priority,
                    ],
                );

                if ($issue->wasRecentlyCreated) {
                    $issue->labels()->attach($labels[$labelName]);
                }
            }
        });
    }
}
