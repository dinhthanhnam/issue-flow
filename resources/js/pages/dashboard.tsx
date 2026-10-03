import { Head, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    List,
    Plus,
    Search,
    Sparkles,
    SunMoon,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { index as listComments } from '@/actions/App/Http/Controllers/CommentController';
import { update as updateIssue } from '@/actions/App/Http/Controllers/IssueController';
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
import { useAppearance } from '@/hooks/use-appearance';
import { apiErrorMessage, getJson, patchJson } from '@/lib/api';
import type {
    ApiCollection,
    ApiPage,
    Comment,
    Issue,
    IssueStatus,
    Project,
    ProjectMember,
    Workspace,
} from '@/types/issue-flow';

type DashboardProps = {
    workspaces: Workspace[];
    workspace: Workspace | null;
    projects: Project[];
    project: Project | null;
    assignees: ProjectMember[];
    issues: ApiPage<Issue> | null;
};

type IssueFilter = IssueStatus | 'all';

export default function Dashboard(props: DashboardProps) {
    const pageKey = `${props.workspace?.id ?? 'none'}:${props.project?.id ?? 'none'}`;

    return <IssueWorkspace key={pageKey} {...props} />;
}

function IssueWorkspace({
    workspaces,
    workspace,
    projects,
    project,
    assignees,
    issues: issuesPage,
}: DashboardProps) {
    const { props } = usePage();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const commentsRequest = useRef<AbortController | null>(null);
    const [issues, setIssues] = useState(issuesPage?.data ?? []);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<IssueFilter>('all');
    const [view, setView] = useState<'board' | 'list'>('board');
    const [selectedIssue, setSelectedIssue] = useState<Issue | null>(null);
    const [comments, setComments] = useState<Comment[]>([]);
    const [commentsLoading, setCommentsLoading] = useState(false);
    const [chatOpen, setChatOpen] = useState(false);
    const [createOpen, setCreateOpen] = useState(false);

    const filteredIssues = issues.filter((issue) => {
        const matchesStatus = status === 'all' || issue.status === status;
        const term = search.trim().toLowerCase();
        const matchesSearch =
            term === '' ||
            `${issue.project.key}-${issue.number} ${issue.title}`
                .toLowerCase()
                .includes(term);

        return matchesStatus && matchesSearch;
    });

    function replaceIssue(updatedIssue: Issue): void {
        setIssues((current) =>
            current.map((issue) =>
                issue.id === updatedIssue.id ? updatedIssue : issue,
            ),
        );
        setSelectedIssue((current) =>
            current?.id === updatedIssue.id ? updatedIssue : current,
        );
    }

    function openIssue(issue: Issue): void {
        commentsRequest.current?.abort();
        const request = new AbortController();
        commentsRequest.current = request;

        setChatOpen(false);
        setSelectedIssue(issue);
        setComments([]);
        setCommentsLoading(true);

        void getJson<ApiCollection<Comment>>(
            listComments.url(issue.id),
            request.signal,
        )
            .then((response) => setComments(response.data))
            .catch((error: unknown) => {
                if (!request.signal.aborted) {
                    toast.error(apiErrorMessage(error));
                }
            })
            .finally(() => {
                if (!request.signal.aborted) {
                    setCommentsLoading(false);
                }
            });
    }

    function closeIssue(): void {
        commentsRequest.current?.abort();
        setSelectedIssue(null);
        setComments([]);
        setCommentsLoading(false);
    }

    function moveIssue(issue: Issue, nextStatus: IssueStatus): void {
        const previousStatus = issue.status;
        const optimisticIssue = { ...issue, status: nextStatus };
        replaceIssue(optimisticIssue);

        void patchJson<{ data: Issue }>(updateIssue.url(issue.id), {
            status: nextStatus,
        })
            .then((response) => {
                replaceIssue(response.data);
                toast.success(
                    `${issue.project.key}-${issue.number} moved to ${statusLabel(nextStatus)}`,
                );
            })
            .catch(() => {
                replaceIssue({ ...issue, status: previousStatus });
                toast.error(
                    'Could not move the issue. The board was restored.',
                );
            });
    }

    return (
        <>
            <Head title={project ? `${project.name} issues` : 'Workspace'} />
            <SidebarProvider defaultOpen={props.sidebarOpen}>
                <WorkspaceSidebar
                    workspaces={workspaces}
                    workspace={workspace}
                    projects={projects}
                    project={project}
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

                    <main className="flex min-h-0 min-w-0 flex-1 overflow-auto px-4 py-5 sm:px-6 lg:px-8">
                        <div className="mx-auto flex min-h-full w-full max-w-[1680px] flex-col">
                            {!workspace && <EmptyWorkspace />}
                            {workspace && !project && <EmptyProject />}
                            {project && (
                                <>
                                    <div className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <h1 className="text-xl font-semibold tracking-tight sm:text-2xl">
                                                    {project.name} issues
                                                </h1>
                                                <span className="bg-muted text-muted-foreground rounded-full px-2 py-0.5 text-xs font-medium">
                                                    {issues.length} issues
                                                </span>
                                            </div>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {project.description ||
                                                    `Plan and track work in ${project.key}.`}
                                            </p>
                                        </div>
                                        <ViewToggle
                                            view={view}
                                            onChange={setView}
                                        />
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2 py-4">
                                        <label className="relative min-w-56 flex-1 sm:max-w-xs">
                                            <Search className="text-muted-foreground absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
                                            <Input
                                                value={search}
                                                onChange={(event) =>
                                                    setSearch(
                                                        event.target.value,
                                                    )
                                                }
                                                className="pl-9"
                                                placeholder="Filter this project..."
                                                aria-label="Search issues"
                                            />
                                        </label>
                                        <Select
                                            value={status}
                                            onValueChange={(value) =>
                                                setStatus(value as IssueFilter)
                                            }
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
                                        {(search || status !== 'all') && (
                                            <Button
                                                variant="ghost"
                                                onClick={() => {
                                                    setSearch('');
                                                    setStatus('all');
                                                }}
                                            >
                                                Clear
                                            </Button>
                                        )}
                                    </div>

                                    <IssueCollection
                                        issues={filteredIssues}
                                        view={view}
                                        canMove={project.can_create_issue}
                                        onOpen={openIssue}
                                        onMove={moveIssue}
                                    />
                                </>
                            )}
                        </div>
                    </main>
                </SidebarInset>
            </SidebarProvider>

            {selectedIssue && (
                <IssueDetail
                    key={selectedIssue.id}
                    issue={selectedIssue}
                    comments={comments}
                    commentsLoading={commentsLoading}
                    canUpdate={project?.can_create_issue ?? false}
                    assignees={assignees}
                    onClose={closeIssue}
                    onUpdated={replaceIssue}
                    onCommentCreated={(comment) =>
                        setComments((current) => [...current, comment])
                    }
                />
            )}

            {project && (
                <CreateIssueDialog
                    open={createOpen}
                    onClose={() => setCreateOpen(false)}
                    project={project}
                    assignees={assignees}
                    onCreated={(issue) =>
                        setIssues((current) => [issue, ...current])
                    }
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

function ViewToggle({
    view,
    onChange,
}: {
    view: 'board' | 'list';
    onChange: (view: 'board' | 'list') => void;
}) {
    return (
        <div className="bg-muted inline-flex w-fit rounded-lg border p-1">
            <Button
                size="sm"
                variant={view === 'board' ? 'secondary' : 'ghost'}
                onClick={() => onChange('board')}
            >
                <LayoutGrid className="size-4" /> Board
            </Button>
            <Button
                size="sm"
                variant={view === 'list' ? 'secondary' : 'ghost'}
                onClick={() => onChange('list')}
            >
                <List className="size-4" /> List
            </Button>
        </div>
    );
}

function EmptyWorkspace() {
    return (
        <div className="grid flex-1 place-items-center rounded-lg border border-dashed p-10 text-center">
            <div>
                <h1 className="text-xl font-semibold">No workspaces yet</h1>
                <p className="text-muted-foreground mt-2 text-sm">
                    Create or join a workspace to start managing projects.
                </p>
            </div>
        </div>
    );
}

function EmptyProject() {
    return (
        <div className="grid flex-1 place-items-center rounded-lg border border-dashed p-10 text-center">
            <div>
                <h1 className="text-xl font-semibold">
                    No projects to display
                </h1>
                <p className="text-muted-foreground mt-2 text-sm">
                    Select another workspace or ask a manager for project
                    access.
                </p>
            </div>
        </div>
    );
}

function statusLabel(status: IssueStatus): string {
    return status === 'in_progress'
        ? 'In progress'
        : status.charAt(0).toUpperCase() + status.slice(1);
}
