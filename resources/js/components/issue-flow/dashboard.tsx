import { Head, usePage } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    LayoutGrid,
    List,
    Plus,
    Search,
    Sparkles,
    SunMoon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { index as listIssues } from '@/actions/App/Http/Controllers/IssueController';
import { index as listProjects } from '@/actions/App/Http/Controllers/ProjectController';
import { index as listWorkspaces } from '@/actions/App/Http/Controllers/WorkspaceController';
import { AiPanel } from '@/components/issue-flow/ai-panel';
import { CreateIssueDialog } from '@/components/issue-flow/create-issue-dialog';
import { IssueCollection } from '@/components/issue-flow/issue-collection';
import { IssueDetail } from '@/components/issue-flow/issue-detail';
import { WorkspaceSidebar } from '@/components/issue-flow/workspace-sidebar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppearance } from '@/hooks/use-appearance';
import { apiErrorMessage, getJson } from '@/lib/api';
import type {
    ApiCollection,
    ApiPage,
    Issue,
    IssueStatus,
    Project,
    Workspace,
} from '@/types/issue-flow';

type IssueFilter = IssueStatus | 'all';

export default function IssueFlowDashboard() {
    const { url, props } = usePage();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const params = new URL(
        url,
        typeof window === 'undefined'
            ? 'http://localhost'
            : window.location.origin,
    ).searchParams;
    const requestedWorkspaceId = Number(params.get('workspace'));
    const requestedProjectId = Number(params.get('project'));

    const [workspaces, setWorkspaces] = useState<Workspace[]>([]);
    const [workspacesLoading, setWorkspacesLoading] = useState(true);
    const [workspaceError, setWorkspaceError] = useState<string | null>(null);
    const [workspaceReload, setWorkspaceReload] = useState(0);
    const workspace =
        workspaces.find((item) => item.id === requestedWorkspaceId) ??
        workspaces[0] ??
        null;

    const [projects, setProjects] = useState<Project[]>([]);
    const [projectsWorkspaceId, setProjectsWorkspaceId] = useState<
        number | null
    >(null);
    const [projectsLoading, setProjectsLoading] = useState(false);
    const [projectError, setProjectError] = useState<string | null>(null);
    const visibleProjects =
        workspace?.id === projectsWorkspaceId ? projects : [];
    const project =
        visibleProjects.find((item) => item.id === requestedProjectId) ??
        visibleProjects[0] ??
        null;

    const [issuesPage, setIssuesPage] = useState<ApiPage<Issue> | null>(null);
    const [issuesProjectId, setIssuesProjectId] = useState<number | null>(null);
    const [issuesLoading, setIssuesLoading] = useState(false);
    const [issueError, setIssueError] = useState<string | null>(null);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<IssueFilter>('all');
    const [page, setPage] = useState(1);
    const [view, setView] = useState<'board' | 'list'>('board');
    const [selectedIssue, setSelectedIssue] = useState<Issue | null>(null);
    const [chatOpen, setChatOpen] = useState(false);
    const [createOpen, setCreateOpen] = useState(false);
    const [issuesReload, setIssuesReload] = useState(0);
    const visibleIssuesPage =
        project?.id === issuesProjectId ? issuesPage : null;

    useEffect(() => {
        const abort = new AbortController();
        setWorkspacesLoading(true);
        setWorkspaceError(null);
        setWorkspaces([]);
        void getJson<ApiCollection<Workspace>>(
            listWorkspaces.url(),
            abort.signal,
        )
            .then((response) => setWorkspaces(response.data))
            .catch((error: unknown) => {
                if (!abort.signal.aborted)
                    setWorkspaceError(apiErrorMessage(error));
            })
            .finally(() => {
                if (!abort.signal.aborted) setWorkspacesLoading(false);
            });
        return () => abort.abort();
    }, [workspaceReload]);

    useEffect(() => {
        if (!workspace) return;
        const abort = new AbortController();
        setProjectsLoading(true);
        setProjectError(null);
        void getJson<ApiCollection<Project>>(
            listProjects.url(workspace.id),
            abort.signal,
        )
            .then((response) => {
                setProjects(response.data);
                setProjectsWorkspaceId(workspace.id);
            })
            .catch((error: unknown) => {
                if (!abort.signal.aborted)
                    setProjectError(apiErrorMessage(error));
            })
            .finally(() => {
                if (!abort.signal.aborted) setProjectsLoading(false);
            });
        return () => abort.abort();
    }, [workspace?.id]);

    useEffect(() => {
        if (!project) return;
        const abort = new AbortController();
        setIssuesPage(null);
        setIssuesProjectId(null);
        setIssuesLoading(true);
        setIssueError(null);
        const timeout = window.setTimeout(
            () => {
                void getJson<ApiPage<Issue>>(
                    listIssues.url(project.id, {
                        query: {
                            page,
                            per_page: 100,
                            ...(search.trim() ? { search: search.trim() } : {}),
                            ...(status !== 'all' ? { status } : {}),
                        },
                    }),
                    abort.signal,
                )
                    .then((response) => {
                        setIssuesPage(response);
                        setIssuesProjectId(project.id);
                    })
                    .catch((error: unknown) => {
                        if (!abort.signal.aborted)
                            setIssueError(apiErrorMessage(error));
                    })
                    .finally(() => {
                        if (!abort.signal.aborted) setIssuesLoading(false);
                    });
            },
            search ? 250 : 0,
        );
        return () => {
            window.clearTimeout(timeout);
            abort.abort();
        };
    }, [project?.id, page, search, status, issuesReload]);

    return (
        <>
            <Head title={project ? `${project.name} issues` : 'Workspace'} />
            <SidebarProvider defaultOpen={props.sidebarOpen}>
                <WorkspaceSidebar
                    workspaces={workspaces}
                    workspace={workspace}
                    projects={visibleProjects}
                    project={project}
                    loadingProjects={projectsLoading}
                />
                <SidebarInset className="min-w-0">
                    <header className="flex h-14 shrink-0 items-center gap-3 border-b px-4 sm:px-6">
                        <SidebarTrigger className="-ml-1" />
                        <div className="min-w-0 truncate text-sm">
                            <span className="text-muted-foreground">
                                {workspace?.name ?? 'Workspace'}
                            </span>
                            {project && (
                                <span className="font-medium">
                                    {' '}
                                    / {project.name}
                                </span>
                            )}
                        </div>
                        <div className="ml-auto flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                aria-label="Toggle appearance"
                                onClick={() =>
                                    updateAppearance(
                                        resolvedAppearance === 'dark'
                                            ? 'light'
                                            : 'dark',
                                    )
                                }
                            >
                                <SunMoon className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setChatOpen(true)}
                            >
                                <Sparkles className="size-4" />
                                <span className="hidden sm:inline">Ask AI</span>
                            </Button>
                            {project?.can_create_issue && (
                                <Button onClick={() => setCreateOpen(true)}>
                                    <Plus className="size-4" />
                                    <span className="hidden sm:inline">
                                        New issue
                                    </span>
                                </Button>
                            )}
                        </div>
                    </header>

                    <main className="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
                        <div className="mx-auto max-w-[1680px]">
                            {workspacesLoading && (
                                <div className="space-y-4">
                                    <Skeleton className="h-8 w-48" />
                                    <Skeleton className="h-40 w-full" />
                                </div>
                            )}
                            {workspaceError && (
                                <div className="rounded-lg border p-6 text-sm">
                                    <p>{workspaceError}</p>
                                    <Button
                                        className="mt-4"
                                        variant="outline"
                                        onClick={() =>
                                            setWorkspaceReload(
                                                (value) => value + 1,
                                            )
                                        }
                                    >
                                        Retry
                                    </Button>
                                </div>
                            )}
                            {!workspacesLoading &&
                                !workspaceError &&
                                !workspace && (
                                    <div className="rounded-lg border border-dashed p-10 text-center">
                                        <h1 className="text-xl font-semibold">
                                            No workspaces yet
                                        </h1>
                                        <p className="text-muted-foreground mt-2 text-sm">
                                            Create or join a workspace to start
                                            managing projects.
                                        </p>
                                    </div>
                                )}
                            {workspace && projectError && (
                                <p className="text-destructive mb-4 text-sm">
                                    {projectError}
                                </p>
                            )}
                            {workspace &&
                                !projectsLoading &&
                                !projectError &&
                                !project && (
                                    <div className="rounded-lg border border-dashed p-10 text-center">
                                        <h1 className="text-xl font-semibold">
                                            No projects to display
                                        </h1>
                                        <p className="text-muted-foreground mt-2 text-sm">
                                            Select another workspace or ask a
                                            manager for project access.
                                        </p>
                                    </div>
                                )}
                            {project && (
                                <>
                                    <div className="flex flex-wrap items-end justify-between gap-4 border-b pb-5">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <h1 className="text-2xl font-semibold tracking-tight">
                                                    {project.name} issues
                                                </h1>
                                                {visibleIssuesPage && (
                                                    <span className="bg-muted text-muted-foreground rounded-full px-2 py-0.5 text-xs">
                                                        {
                                                            visibleIssuesPage
                                                                .meta.total
                                                        }
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {project.description ||
                                                    `Plan and track work in ${project.key}.`}
                                            </p>
                                        </div>
                                        <div className="bg-muted flex rounded-lg border p-1">
                                            <Button
                                                size="sm"
                                                variant={
                                                    view === 'board'
                                                        ? 'secondary'
                                                        : 'ghost'
                                                }
                                                onClick={() => setView('board')}
                                            >
                                                <LayoutGrid className="size-4" />{' '}
                                                Board
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant={
                                                    view === 'list'
                                                        ? 'secondary'
                                                        : 'ghost'
                                                }
                                                onClick={() => setView('list')}
                                            >
                                                <List className="size-4" /> List
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2 py-4">
                                        <label className="relative min-w-56 flex-1 sm:max-w-xs">
                                            <Search className="text-muted-foreground absolute top-2.5 left-3 size-4" />
                                            <Input
                                                value={search}
                                                onChange={(event) => {
                                                    setSearch(
                                                        event.target.value,
                                                    );
                                                    setPage(1);
                                                }}
                                                className="pl-9"
                                                placeholder="Filter this project..."
                                                aria-label="Search issues"
                                            />
                                        </label>
                                        <Select
                                            value={status}
                                            onValueChange={(value) => {
                                                setStatus(value as IssueFilter);
                                                setPage(1);
                                            }}
                                        >
                                            <SelectTrigger aria-label="Filter status">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All statuses
                                                </SelectItem>
                                                <SelectItem value="todo">
                                                    Todo
                                                </SelectItem>
                                                <SelectItem value="in_progress">
                                                    In progress
                                                </SelectItem>
                                                <SelectItem value="done">
                                                    Done
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    {issueError && (
                                        <p className="text-destructive mb-4 text-sm">
                                            {issueError}
                                        </p>
                                    )}
                                    {issuesLoading && !visibleIssuesPage && (
                                        <div className="grid gap-3 md:grid-cols-3">
                                            {[1, 2, 3].map((item) => (
                                                <Skeleton
                                                    key={item}
                                                    className="h-64"
                                                />
                                            ))}
                                        </div>
                                    )}
                                    {visibleIssuesPage && (
                                        <div
                                            className={
                                                issuesLoading
                                                    ? 'opacity-60'
                                                    : ''
                                            }
                                        >
                                            <IssueCollection
                                                issues={visibleIssuesPage.data}
                                                view={view}
                                                onOpen={(issue) => {
                                                    setChatOpen(false);
                                                    setSelectedIssue(issue);
                                                }}
                                            />
                                            <div className="text-muted-foreground flex items-center justify-between gap-3 pt-3 text-xs">
                                                <span>
                                                    Showing{' '}
                                                    {
                                                        visibleIssuesPage.data
                                                            .length
                                                    }{' '}
                                                    of{' '}
                                                    {
                                                        visibleIssuesPage.meta
                                                            .total
                                                    }{' '}
                                                    issues
                                                    {view === 'board' &&
                                                        visibleIssuesPage.meta
                                                            .last_page > 1 &&
                                                        ' on this board page'}
                                                </span>
                                                {visibleIssuesPage.meta
                                                    .last_page > 1 && (
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            disabled={
                                                                page <= 1 ||
                                                                issuesLoading
                                                            }
                                                            aria-label="Previous page"
                                                            onClick={() =>
                                                                setPage(
                                                                    (value) =>
                                                                        value -
                                                                        1,
                                                                )
                                                            }
                                                        >
                                                            <ChevronLeft />
                                                        </Button>
                                                        <span>
                                                            {
                                                                visibleIssuesPage
                                                                    .meta
                                                                    .current_page
                                                            }{' '}
                                                            /{' '}
                                                            {
                                                                visibleIssuesPage
                                                                    .meta
                                                                    .last_page
                                                            }
                                                        </span>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            disabled={
                                                                page >=
                                                                    visibleIssuesPage
                                                                        .meta
                                                                        .last_page ||
                                                                issuesLoading
                                                            }
                                                            aria-label="Next page"
                                                            onClick={() =>
                                                                setPage(
                                                                    (value) =>
                                                                        value +
                                                                        1,
                                                                )
                                                            }
                                                        >
                                                            <ChevronRight />
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </main>
                </SidebarInset>
            </SidebarProvider>
            <IssueDetail
                issue={selectedIssue}
                onClose={() => setSelectedIssue(null)}
                canUpdate={project?.can_create_issue ?? false}
                onUpdated={(issue) => {
                    setSelectedIssue(issue);
                    setIssuesReload((value) => value + 1);
                }}
            />
            {project && (
                <CreateIssueDialog
                    open={createOpen}
                    onClose={() => setCreateOpen(false)}
                    project={project}
                    onCreated={() => setIssuesReload((value) => value + 1)}
                />
            )}
            <AiPanel
                open={chatOpen}
                onClose={() => setChatOpen(false)}
                workspace={workspace}
                project={project}
            />
        </>
    );
}
