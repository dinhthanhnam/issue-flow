Continue implementing the Laravel 13 backend for the "IssueFlow" project.

IssueFlow is a Jira/Linear-like issue tracking application.

==================================================
CURRENT STATE
==================================================

Authentication:
- Laravel Fortify already handles:
    - login/logout
    - registration
    - password reset
    - email verification
    - password confirmation
    - 2FA
    - passkeys
- Laravel Sanctum is used for SPA/API authentication.
- DO NOT reimplement authentication.
- Authentication work is currently considered done.

Existing domain:
- User
- Workspace
- Project
- Issue
- Comment
- IssueActivity
- Notifications

Existing relationships:
- Workspace belongsToMany User through `user_workspace`
- Pivot contains `role`
- Workspace hasMany Project
- Project belongsTo Workspace
- Project hasMany Issue
- Issue belongsTo Project
- Issue belongsTo reporter User
- Issue optionally belongsTo assignee User
- Issue hasMany Comment
- Issue hasMany IssueActivity

Existing issue functionality:
- Create issue
- Update issue
- Issue status / priority enums
- Reporter / assignee
- Per-project issue number
- Project row locking when generating issue number
- Comments
- Activity timeline
- IssueUpdated event
- Activity listener
- Redis queue
- Queued assignee notification
- Database notifications

WorkspaceRole enum:
- owner
- admin
- member

==================================================
PROJECT CONVENTIONS
==================================================

1. Routes must be explicit.

DO NOT replace routes with:
Route::apiResource(...)

Prefer:

    Route::get(...)
    Route::post(...)
    Route::patch(...)
    Route::delete(...)

The API surface should be directly readable from routes/api.php.

2. Authorization

For controller methods without FormRequest:
Gate::authorize(...)

Example:
Gate::authorize('view', $workspace);

For endpoints using FormRequest:
authorization belongs in FormRequest::authorize()

DO NOT authorize the same operation again in the controller.

Use semantic Policy abilities.

Good:
view
update
manageMembers
createIssue
archive
restore

Do not abuse generic `update` for unrelated operations such as member
management.

3. API Resources

All domain API responses should use Laravel API Resources.

Existing / expected resources:
- WorkspaceResource
- WorkspaceMemberResource
- ProjectResource
- IssueResource
- CommentResource
- etc.

Do NOT return raw Eloquent models/collections from domain endpoints.

Resources should represent API/use-case context.

Do NOT make:
WorkspaceMemberResource extends UserResource

WorkspaceMemberResource and UserResource are different representations
of User for different contexts.

4. Tests

Use Pest.

DO NOT generate PHPUnit class-style tests.

5. Architecture

Do not create Service/Action classes for trivial CRUD.

Use Actions/Services when there is actual domain logic, transactions,
concurrency, orchestration, etc.

Do not over-engineer abstractions prematurely.

6. Laravel version

Laravel 13 / modern Laravel structure.

Do not assume Laravel 8-era Kernel/provider structure.

7. Route model binding

Implicit route model binding is allowed and expected.

Example:
/workspaces/{workspace}/members/{user}

maps to:
Workspace $workspace
User $user

Authenticated actor is:
$request->user()

Route-bound User is the target user.

==================================================
BACKLOG
==================================================

PENDING APPROVAL — BACKEND ARCHITECTURE NORMALIZATION

Normalize controller and application-layer conventions before adding
the Notification API or another backend domain.

Controller convention:
- keep all JSON domain controllers directly under
  `App\Http\Controllers`; do not use a separate `Api` namespace
- controllers coordinate HTTP input, an application operation, relation
  loading, and an API Resource response
- FormRequest handles validation and authorization for endpoints with
  input; methods without a FormRequest use `Gate::authorize()`
- return `JsonResponse` consistently from domain controllers
- keep simple one-model CRUD and simple pivot attach/detach in controllers
- move complex read composition to query scopes/query objects, not Actions

Action convention:
- place commands under `App\Actions\{Domain}` and expose `handle()`
- create an Action when an operation has a transaction, concurrency lock,
  multiple writes, reusable domain orchestration, or meaningful side effects
- do not create Actions for trivial create/update/delete calls
- immediate candidates: `CreateWorkspace`, `CreateProject`, `CreateIssue`,
  and `RemoveWorkspaceMember`

Issue activity cleanup:
- remove the experimental `IssueActivity` model, factory, relationship,
  migration/table, activity listener, and activity-only tests
- remove `IssueUpdated` / `UpdateIssue` if no domain orchestration remains
- keep audit history out until the product has concrete retention and query
  requirements; later prefer a deliberate generic audit design instead of
  recreating a narrowly scoped demo table

Observer convention:
- observers are only for secondary model lifecycle reactions such as an
  assignee-change notification, cache invalidation, or search indexing
- never hide core invariants, role assignment, numbering, or multi-write
  workflows inside observers; those remain explicit Actions
- when notification work resumes, replace the demo event/listener chain with
  an `IssueObserver` for assignee changes and dispatch after commit

Refactor cleanup:
- move `ProjectMemberController` and `WorkspaceMemberController` out of `Api`
- remove unused/dead controller methods and parameters
- simplify the issue index with a dedicated query object or cohesive query
  scope while keeping filters explicit and validated
- update this hand-off and `BACKEND_OVERVIEW.md` after the refactor lands

--------------------------------------------------

PRIORITY 1 — FINISH WORKSPACE DOMAIN

Complete and normalize WorkspaceController.

Endpoints:

GET    /api/workspaces
POST   /api/workspaces
GET    /api/workspaces/{workspace}
PATCH  /api/workspaces/{workspace}

POST   /api/workspaces/{workspace}/archive
POST   /api/workspaces/{workspace}/restore

Requirements:
- WorkspaceResource for all workspace representations.
- Creating workspace must automatically attach creator as `owner`.
- Creation + owner attachment must be transactional.
- Workspace index returns only workspaces the authenticated user belongs to.
- Include current user's pivot role where appropriate.
- Archive uses archived_at.
- Restore sets archived_at back to null.
- Do not expose archived_at through generic workspace update.
- Only appropriate roles can update/archive/restore workspace.
- Prefer owner-only for archive/restore.

Complete WorkspacePolicy.

Useful abilities:
- view
- update
- manageMembers
- archive
- restore

Role semantics:

owner:
- full workspace management
- manage admins and members
- archive/restore workspace

admin:
- operational management
- manage normal members
- must not modify/remove owner

member:
- normal workspace access
- cannot manage workspace membership

Extract reusable Workspace membership/role helpers if duplication now
justifies it, e.g.:

    hasMember(User $user)
    hasRole(User $user, WorkspaceRole $role)
    hasAnyRole(User $user, array $roles)

Keep helpers simple.

--------------------------------------------------

PRIORITY 2 — WORKSPACE MEMBER MANAGEMENT

Endpoints:

GET    /api/workspaces/{workspace}/members
POST   /api/workspaces/{workspace}/members
PATCH  /api/workspaces/{workspace}/members/{user}
DELETE /api/workspaces/{workspace}/members/{user}

Use:
- WorkspaceMemberController
- StoreWorkspaceMemberRequest
- UpdateWorkspaceMemberRequest
- WorkspaceMemberResource

WorkspaceMemberResource should expose only membership-relevant user
information, e.g.:
- id
- name
- email
- role from user_workspace pivot

Do not inherit it from UserResource.

POST member:
- user must exist
- user cannot already belong to workspace
- role must be valid
- generic add-member endpoint should only allow admin/member
- do not create additional owners through this endpoint

PATCH member:
- change admin/member role
- generic endpoint must not demote/change owner
- enforce actor vs target role rules

DELETE member:
- remove membership
- cannot remove owner
- enforce actor vs target role rules
- return 204

Important:
- `$request->user()` = authenticated actor
- route-bound `User $user` = target member

Do not implement invitation-by-email/token yet.
For now adding an already-existing user is enough.

Ownership transfer can be implemented later as a dedicated operation
rather than allowing generic role mutation.

--------------------------------------------------

PRIORITY 3 — PROJECT DOMAIN

Implement/finish:

GET    /api/workspaces/{workspace}/projects
POST   /api/workspaces/{workspace}/projects
GET    /api/projects/{project}
PATCH  /api/projects/{project}

Use:
- ProjectController
- StoreProjectRequest
- UpdateProjectRequest
- ProjectResource
- ProjectPolicy

Rules:
- only workspace members can view projects
- owner/admin can create/update projects
- member is read-only unless existing business rules say otherwise
- project key is unique within workspace
- normalize project key to uppercase
- do not allow workspace_id to be changed through generic update
- use explicit routes
- use API Resources

Project key + issue number forms issue identifier:

    PROJECTKEY-123

Do not store the combined identifier if it can be derived.

--------------------------------------------------

PRIORITY 4 — LABEL DOMAIN

Implement labels.

Suggested model:

Label:
- id
- workspace_id
- name
- color
- timestamps

Relationships:
- Workspace hasMany Labels
- Label belongsTo Workspace
- Issue belongsToMany Labels
- Label belongsToMany Issues

Pivot:
issue_label
issue_id
label_id

Implement:
- create/update/delete/list labels
- attach label to issue
- detach label from issue
- expose labels in IssueResource
- eager load where appropriate
- validate that a label belongs to the same workspace as the issue/project

Do not allow cross-workspace label attachment.

--------------------------------------------------

PRIORITY 5 — COMPLETE ISSUE LISTING / SEARCH / FILTER

Complete issue listing API.

Support useful filters:
- status
- priority
- assignee
- reporter
- labels
- text search
- project

Support:
- sorting
- pagination

Avoid N+1.

Typical eager loads:
- project
- reporter
- assignee
- labels

Use Eloquent for entity-oriented queries.

Query Builder/raw SQL may be used where projection/aggregation becomes
more appropriate; do not force everything through Eloquent.

--------------------------------------------------

PRIORITY 6 — ARCHIVE BEHAVIOR

Workspace archive already exists/planned.

Define consistent behavior for archived workspace.

At minimum:
- archived workspace remains readable where appropriate
- mutation endpoints under archived workspace should be rejected
- EnsureWorkspaceIsActive middleware already exists/planned
- register alias using modern Laravel middleware configuration
- apply middleware only where mutation should be prevented

Do not hard-delete workspace as the normal lifecycle operation.

Consider Project/Issue archive only if useful after Workspace behavior is
complete; do not expand scope unnecessarily.

--------------------------------------------------

PRIORITY 7 — NOTIFICATION API

Database notification creation already exists.

Add API endpoints needed by frontend, e.g.:

GET   /api/notifications
PATCH /api/notifications/{notification}/read
POST  /api/notifications/read-all

Requirements:
- authenticated user's notifications only
- pagination
- unread state
- no ability to mutate another user's notification

Use a NotificationResource if useful.

--------------------------------------------------

PRIORITY 8 — SCHEDULER + ARTISAN COMMAND

Add at least one realistic scheduled maintenance workflow so the
portfolio demonstrates Laravel Scheduler and Commands.

Prefer something domain-relevant rather than a fake demo.

Possible implementation:
- command to clean expired/stale domain data
- scheduled digest/reminder job
- overdue/open issue reminder
- notification cleanup

Keep it small but production-plausible.

Use Laravel 13 scheduling conventions.

--------------------------------------------------

PRIORITY 9 — TEST COVERAGE

Use Pest feature tests.

Prioritize business rules rather than trivial framework behavior.

Workspace:
- creator becomes owner
- non-member cannot view workspace
- member cannot update workspace
- archive authorization
- restore authorization

Members:
- owner can add member
- duplicate membership rejected
- member cannot manage members
- admin cannot modify/remove owner
- owner cannot be removed through generic endpoint
- role update works
- removal works

Projects:
- non-member cannot access
- owner/admin can create
- member cannot create if that is the chosen rule
- project key uniqueness is scoped to workspace

Issues:
- authorization
- create
- update
- assignee validation
- per-project numbering
- comments
- activity creation
- notification dispatch

Labels:
- same-workspace constraint
- attach/detach
- filtering

Use SQLite for normal feature tests where appropriate, but do not claim
SQLite tests prove MySQL locking semantics.

--------------------------------------------------

PRIORITY 10 — REACT SPA

After backend domain APIs are stable:

Create/use frontend/ as a traditional React SPA.

Do NOT use Inertia for the final frontend architecture.

Flow:

React SPA
↓ HTTP/JSON
Laravel REST API
↓
MySQL / Redis

Use Fortify + Sanctum SPA authentication.

Expected auth flow:

GET  /sanctum/csrf-cookie
POST /login
GET  /api/user
...
POST /logout

Frontend features:
- login/register
- workspace selector/sidebar
- workspace members
- project list
- issue list
- issue detail
- create/edit issue
- comments
- activity timeline
- labels
- filters/search
- notifications
- Kanban board
- drag/drop issue status

Do not rebuild auth backend.

After React SPA is working, remove obsolete Inertia/settings UI code if
it is no longer referenced.

--------------------------------------------------

PRIORITY 11 — PRODUCTION / PORTFOLIO

Containerize/run:
- Laravel app
- queue worker
- scheduler
- MySQL
- Redis
- frontend as appropriate

Production worker should use queue:work, not queue:listen.

Add:
- Nginx
- HTTPS
- environment configuration
- GitHub Actions CI/CD
- migrations during deployment with appropriate safeguards
- demo seed data
- README

README should contain:
- architecture
- domain overview
- local setup
- API overview
- screenshots
- deployment architecture
- important design decisions
- concurrency handling for issue numbering
- queue/event/notification architecture

--------------------------------------------------

OPTIONAL / AFTER CORE PRODUCT — AI TOOL CALLING

Only after the normal IssueFlow application is complete.

Add an LLM/tool-calling layer where natural language can invoke a small
set of constrained IssueFlow operations.

Examples:

"Mark IF-123 as done"
-> update_issue(issue_key="IF-123", status="done")

"Assign IF-50 to Nam"
-> assign_issue(...)

"Show my urgent unfinished issues"
-> search_issues(...)

"Create a bug for login failure"
-> create_issue(...)

Important architecture:

LLM
↓ chooses structured tool
Tool adapter
↓
Laravel domain/application layer
↓
Policy / authorization
↓
Database

The LLM must NOT bypass Laravel authorization or become the source of
truth.

Do not implement this before the core product works.

==================================================
IMPLEMENTATION ORDER
==================================================

Work in this order unless existing code shows a dependency requiring a
small adjustment:

1. Finish Workspace CRUD/archive/restore
2. Finish Workspace members + role authorization
3. Pest tests for Workspace/member rules
4. Project CRUD + Policy + Resource
5. Project tests
6. Labels
7. Issue search/filter/sort/pagination
8. Archive mutation protection
9. Notification API
10. Scheduler/Command
11. Remaining backend tests
12. React SPA
13. Production Docker/deployment/CI
14. README/demo data/screenshots
15. Optional AI tool calling

Before creating new classes, inspect the existing project because some
models/controllers/requests/resources/policies may already exist.

Do not duplicate existing functionality.

Keep changes incremental and runnable.
After each domain slice:
- run tests
- run formatter/linter if configured
- inspect routes
- fix failures before continuing.
