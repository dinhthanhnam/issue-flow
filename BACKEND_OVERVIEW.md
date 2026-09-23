# Backend overview

This document is a reading map for the backend implemented through phase 5. The central rule is that workspace membership establishes the tenant boundary, while project membership controls day-to-day project access.

## Recommended reading order

1. Start at [`routes/api.php`](routes/api.php). It shows the complete HTTP surface and which write routes use the active-workspace middleware.
2. Read the role and issue enums in [`app/Enums`](app/Enums): `WorkspaceRole`, `ProjectRole`, `IssueStatus`, and `IssuePriority`.
3. Read [`app/Models/Workspace.php`](app/Models/Workspace.php), then [`app/Models/Project.php`](app/Models/Project.php). The helper methods on these two models define the effective access rules used by policies.
4. Read the policies in [`app/Policies`](app/Policies), especially `ProjectPolicy` and `IssuePolicy`. Policies answer whether an operation is allowed; controllers do not duplicate that decision.
5. Read [`app/Actions`](app/Actions) for transactional commands and [`app/Queries`](app/Queries) for complex list composition.
6. Follow one request through `FormRequest -> Controller -> Action/Query -> Resource`:
   - Issue creation: `StoreIssueRequest -> IssueController::store -> CreateIssue -> IssueResource`.
   - Issue update: `UpdateIssueRequest -> IssueController::update -> IssueObserver -> IssueResource`.
   - Issue listing: `IndexIssueRequest -> IssueController::index -> IssueIndexQuery -> IssueResource`.
7. Read [`app/Http/Middleware/EnsureWorkspaceIsActive.php`](app/Http/Middleware/EnsureWorkspaceIsActive.php). It resolves a workspace from a bound workspace, project, issue, or label and prevents mutations after archival.
8. Finish with the feature tests in [`tests/Feature`](tests/Feature). They are the most direct executable description of the business rules.

## Data and ownership model

```text
Workspace
├── user_workspace ── User       (owner | admin | member)
├── Project
│   ├── project_user ── User     (lead | member | viewer)
│   └── Issue
│       ├── Comment
│       └── issue_label ── Label
└── Label
```

`user_workspace` and `project_user` are current-state many-to-many pivots. A user has at most one workspace role and at most one role in a particular project. Multiple users may hold the same role, including Owner and Lead.

Labels belong to a workspace. An issue can only receive labels from its project's workspace.

## Permission rules

| Actor | Project visibility | Project update | Membership management | Issue write/comment | Label definition |
|---|---|---|---|---|---|
| Workspace Owner | Every project | Yes | All project roles | Yes | Yes |
| Workspace Admin | Every project | Yes | All project roles | Yes | Yes |
| Project Lead | Assigned projects | Yes | Member and Viewer only | Yes | No |
| Project Member | Assigned projects | No | No | Yes | No |
| Project Viewer | Assigned projects | No | No | Read only | No |
| Unassigned workspace Member | No access | No | No | No | No |

Important implementation points:

- A project creator is attached as its first Lead in the same transaction.
- Workspace managers can access all projects without a `project_user` row, but assignment requires explicit Lead or Member participation.
- Removing a workspace member also removes that user's project memberships inside that workspace.
- Archived workspaces are readable. Update, create, attach, detach, comment, and archive mutations are rejected; restore remains available to the Owner.

## Issue behavior

[`app/Actions/Issues/CreateIssue.php`](app/Actions/Issues/CreateIssue.php) locks the project while allocating the next project-local issue number. This prevents two concurrent creates from receiving the same number.

Simple issue updates remain in the controller. [`app/Observers/IssueObserver.php`](app/Observers/IssueObserver.php) reacts only when `assignee_id` changes and sends a queued database notification after commit. The observer does not own authorization or core issue invariants.

Issue listing supports status, priority, assignee, reporter, workspace label, title/description search, allowlisted sorting, and bounded pagination. The nested project route is the project filter and tenant boundary.

## Application-layer convention

All JSON domain controllers live directly under `App\Http\Controllers` and return `JsonResponse`. A FormRequest owns authorization when an endpoint has validated input; endpoints without one authorize through Gate in the controller.

Actions are command operations with transactions, locking, multiple writes, or meaningful orchestration. Simple one-model CRUD and pivot changes remain in controllers. Complex reads use query objects instead of Actions. Observers are reserved for secondary lifecycle reactions and must not hide core business rules.

The current Actions are `CreateWorkspace`, `CreateProject`, `CreateIssue`, and `RemoveWorkspaceMember`. The current query objects are `ProjectIndexQuery` and `IssueIndexQuery`.

## Where behavior is proven

- Workspace and archival: `WorkspaceTest`, `WorkspaceMemberTest`, `WorkspaceActiveMiddlewareTest`.
- Project access and roles: `ProjectTest`, `ProjectMemberTest`.
- Issues: `StoreIssueTest`, `UpdateIssueTest`, `IndexIssueTest`.
- Comments and labels: `StoreCommentTest`, `LabelTest`, `IssueLabelTest`.

Run one area with `php artisan test --compact tests/Feature/IndexIssueTest.php`, or run the complete backend suite with `php artisan test --compact`.

## Intentional stopping point

The backend is complete through phase 5. The database notification writer already exists, but notification listing/read APIs are not implemented. Audit/activity history is intentionally deferred until concrete retention and query requirements exist. React SPA, chat UI, chat API integration, and tool/function calling are also intentionally outside this phase and should be designed together next.
