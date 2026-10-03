import { useHttp } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { MessageSquare, Pencil, Plus } from 'lucide-react';
import { toast } from 'sonner';
import { store as storeComment } from '@/actions/App/Http/Controllers/CommentController';
import {
    destroy as detachLabel,
    store as attachLabel,
} from '@/actions/App/Http/Controllers/IssueLabelController';
import { update as updateIssue } from '@/actions/App/Http/Controllers/IssueController';
import { issueKey } from '@/components/issue-flow/issue-collection';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { deleteWithoutResponse, postWithoutResponse } from '@/lib/api';
import type {
    Comment,
    Issue,
    IssueLabel,
    IssuePriority,
    IssueStatus,
    ProjectMember,
} from '@/types/issue-flow';

type UpdateIssueData = {
    title: string;
    description: string;
    status: IssueStatus;
    priority: IssuePriority;
    assignee_id: number | null;
};

type Props = {
    issue: Issue;
    comments: Comment[];
    commentsLoading: boolean;
    canUpdate: boolean;
    assignees: ProjectMember[];
    availableLabels: IssueLabel[];
    onClose: () => void;
    onUpdated: (issue: Issue) => void;
    onCommentCreated: (comment: Comment) => void;
};

export function IssueDetail({
    issue,
    comments,
    commentsLoading,
    canUpdate,
    assignees,
    availableLabels,
    onClose,
    onUpdated,
    onCommentCreated,
}: Props) {
    const [editing, setEditing] = useState(false);
    const [updatingLabel, setUpdatingLabel] = useState<number | null>(null);
    const issueForm = useHttp<UpdateIssueData, { data: Issue }>({
        title: issue.title,
        description: issue.description ?? '',
        status: issue.status,
        priority: issue.priority,
        assignee_id: issue.assignee?.id ?? null,
    });
    const commentForm = useHttp<{ body: string }, { data: Comment }>({
        body: '',
    });

    function startEditing(): void {
        issueForm.setData({
            title: issue.title,
            description: issue.description ?? '',
            status: issue.status,
            priority: issue.priority,
            assignee_id: issue.assignee?.id ?? null,
        });
        issueForm.clearErrors();
        setEditing(true);
    }

    function cancelEditing(): void {
        issueForm.clearErrors();
        setEditing(false);
    }

    function saveIssue(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        void issueForm
            .patch(updateIssue.url(issue.id), {
                onSuccess: (response) => {
                    onUpdated(response.data);
                    setEditing(false);
                    toast.success(`${issueKey(issue)} updated`);
                },
            })
            .catch(() => toast.error('Could not update the issue.'));
    }

    function createComment(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        void commentForm
            .post(storeComment.url(issue.id), {
                onSuccess: (response) => {
                    onCommentCreated(response.data);
                    commentForm.reset();
                    toast.success('Comment added');
                },
            })
            .catch(() => toast.error('Could not add the comment.'));
    }

    function toggleLabel(label: IssueLabel): void {
        if (updatingLabel !== null) {
            return;
        }

        const isAttached = issue.labels.some(
            (current) => current.id === label.id,
        );
        const nextLabels = isAttached
            ? issue.labels.filter((current) => current.id !== label.id)
            : [...issue.labels, label].sort((left, right) =>
                  left.name.localeCompare(right.name),
              );
        const previousIssue = issue;

        setUpdatingLabel(label.id);
        onUpdated({ ...issue, labels: nextLabels });

        const routeArguments = { issue: issue.id, label: label.id };
        const request = isAttached
            ? deleteWithoutResponse(detachLabel.url(routeArguments))
            : postWithoutResponse(attachLabel.url(routeArguments));

        void request
            .catch(() => {
                onUpdated(previousIssue);
                toast.error('Could not update issue labels.');
            })
            .finally(() => setUpdatingLabel(null));
    }

    return (
        <Sheet open onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="w-full gap-0 p-0 sm:max-w-xl">
                <SheetHeader className="flex h-14 shrink-0 flex-row items-center gap-3 border-b px-4 py-0">
                    <SheetDescription className="font-mono text-sm font-medium">
                        {issueKey(issue)}
                    </SheetDescription>
                    <Badge variant="secondary">
                        {statusLabel(issue.status)}
                    </Badge>
                    <SheetTitle className="sr-only">{issue.title}</SheetTitle>
                    {canUpdate && !editing && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="mr-7 ml-auto"
                            onClick={startEditing}
                        >
                            <Pencil className="size-3.5" />
                            Edit
                        </Button>
                    )}
                </SheetHeader>

                <div className="flex-1 overflow-y-auto p-5 sm:p-6">
                    {editing ? (
                        <IssueEditForm
                            form={issueForm}
                            issue={issue}
                            assignees={assignees}
                            availableLabels={availableLabels}
                            updatingLabel={updatingLabel}
                            onToggleLabel={toggleLabel}
                            onSubmit={saveIssue}
                            onCancel={cancelEditing}
                        />
                    ) : (
                        <IssueOverview
                            issue={issue}
                            availableLabels={availableLabels}
                            canUpdate={canUpdate}
                            updatingLabel={updatingLabel}
                            onToggleLabel={toggleLabel}
                        />
                    )}

                    <div className="my-6 border-t" />

                    <h3 className="font-medium">Activity</h3>
                    <form onSubmit={createComment} className="mt-4 flex gap-3">
                        <Avatar name={issue.reporter.name} />
                        <div className="flex-1">
                            <Textarea
                                value={commentForm.data.body}
                                onChange={(event) =>
                                    commentForm.setData(
                                        'body',
                                        event.target.value,
                                    )
                                }
                                className="min-h-20"
                                placeholder="Write a comment..."
                            />
                            <InputError
                                className="mt-1"
                                message={commentForm.errors.body}
                            />
                            <div className="mt-2 flex justify-end">
                                <Button
                                    size="sm"
                                    disabled={
                                        commentForm.processing ||
                                        commentForm.data.body.trim() === ''
                                    }
                                >
                                    {commentForm.processing
                                        ? 'Commenting…'
                                        : 'Comment'}
                                </Button>
                            </div>
                        </div>
                    </form>

                    <div className="mt-6 flex flex-col gap-5">
                        <ActivityItem
                            name={issue.reporter.name}
                            timestamp={issue.created_at}
                        >
                            created this issue.
                        </ActivityItem>

                        {commentsLoading && (
                            <p className="text-muted-foreground pl-10 text-sm">
                                Loading comments…
                            </p>
                        )}

                        {!commentsLoading &&
                            comments.map((comment) => (
                                <ActivityItem
                                    key={comment.id}
                                    name={comment.user.name}
                                    timestamp={comment.created_at}
                                >
                                    <p className="mt-1 whitespace-pre-wrap">
                                        {comment.body}
                                    </p>
                                </ActivityItem>
                            ))}

                        {!commentsLoading && comments.length === 0 && (
                            <div className="text-muted-foreground flex items-center gap-2 pl-10 text-sm">
                                <MessageSquare className="size-4" />
                                No comments yet.
                            </div>
                        )}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}

function IssueOverview({
    issue,
    availableLabels,
    canUpdate,
    updatingLabel,
    onToggleLabel,
}: {
    issue: Issue;
    availableLabels: IssueLabel[];
    canUpdate: boolean;
    updatingLabel: number | null;
    onToggleLabel: (label: IssueLabel) => void;
}) {
    return (
        <>
            <h2 className="text-xl font-semibold tracking-tight">
                {issue.title}
            </h2>
            <p className="text-muted-foreground mt-3 text-sm leading-6 whitespace-pre-wrap">
                {issue.description || 'No description yet.'}
            </p>

            <div className="mt-6 grid grid-cols-[110px_1fr] items-start gap-x-3 gap-y-4 text-sm">
                <span className="text-muted-foreground">Status</span>
                <span>{statusLabel(issue.status)}</span>

                <span className="text-muted-foreground">Priority</span>
                <span className="capitalize">{issue.priority}</span>

                <span className="text-muted-foreground">Assignee</span>
                <span>{issue.assignee?.name ?? 'Unassigned'}</span>

                <span className="text-muted-foreground">Reporter</span>
                <span>{issue.reporter.name}</span>

                <span className="text-muted-foreground">Labels</span>
                <IssueLabels
                    issue={issue}
                    availableLabels={availableLabels}
                    canUpdate={canUpdate}
                    updatingLabel={updatingLabel}
                    onToggle={onToggleLabel}
                />
            </div>
        </>
    );
}

function IssueEditForm({
    form,
    issue,
    assignees,
    availableLabels,
    updatingLabel,
    onToggleLabel,
    onSubmit,
    onCancel,
}: {
    form: ReturnType<typeof useHttp<UpdateIssueData, { data: Issue }>>;
    issue: Issue;
    assignees: ProjectMember[];
    availableLabels: IssueLabel[];
    updatingLabel: number | null;
    onToggleLabel: (label: IssueLabel) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onCancel: () => void;
}) {
    return (
        <form onSubmit={onSubmit} className="grid gap-5">
            <div>
                <Input
                    value={form.data.title}
                    onChange={(event) =>
                        form.setData('title', event.target.value)
                    }
                    className="h-auto px-0 py-1 text-xl font-semibold tracking-tight shadow-none focus-visible:ring-0"
                    aria-label="Issue title"
                    maxLength={255}
                    autoFocus
                />
                <InputError className="mt-1" message={form.errors.title} />
            </div>

            <div>
                <Textarea
                    value={form.data.description}
                    onChange={(event) =>
                        form.setData('description', event.target.value)
                    }
                    className="min-h-28"
                    placeholder="Add a description..."
                    aria-label="Issue description"
                />
                <InputError
                    className="mt-1"
                    message={form.errors.description}
                />
            </div>

            <div className="grid grid-cols-[110px_1fr] items-start gap-x-3 gap-y-4 text-sm">
                <span className="text-muted-foreground pt-2">Status</span>
                <div>
                    <Select
                        value={form.data.status}
                        onValueChange={(value) =>
                            form.setData('status', value as IssueStatus)
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label="Issue status"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todo">Todo</SelectItem>
                            <SelectItem value="in_progress">
                                In progress
                            </SelectItem>
                            <SelectItem value="done">Done</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError className="mt-1" message={form.errors.status} />
                </div>

                <span className="text-muted-foreground pt-2">Priority</span>
                <div>
                    <Select
                        value={form.data.priority}
                        onValueChange={(value) =>
                            form.setData('priority', value as IssuePriority)
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label="Issue priority"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="low">Low</SelectItem>
                            <SelectItem value="medium">Medium</SelectItem>
                            <SelectItem value="high">High</SelectItem>
                            <SelectItem value="urgent">Urgent</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError
                        className="mt-1"
                        message={form.errors.priority}
                    />
                </div>

                <span className="text-muted-foreground pt-2">Assignee</span>
                <div>
                    <Select
                        value={
                            form.data.assignee_id?.toString() ?? 'unassigned'
                        }
                        onValueChange={(value) =>
                            form.setData(
                                'assignee_id',
                                value === 'unassigned' ? null : Number(value),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-full max-w-64"
                            aria-label="Issue assignee"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="unassigned">
                                Unassigned
                            </SelectItem>
                            {assignees.map((assignee) => (
                                <SelectItem
                                    key={assignee.id}
                                    value={assignee.id.toString()}
                                >
                                    {assignee.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError
                        className="mt-1"
                        message={form.errors.assignee_id}
                    />
                </div>

                <span className="text-muted-foreground">Reporter</span>
                <span>{issue.reporter.name}</span>

                <span className="text-muted-foreground">Labels</span>
                <IssueLabels
                    issue={issue}
                    availableLabels={availableLabels}
                    canUpdate
                    updatingLabel={updatingLabel}
                    onToggle={onToggleLabel}
                />
            </div>

            <div className="flex justify-end gap-2 border-t pt-4">
                <Button type="button" variant="outline" onClick={onCancel}>
                    Cancel
                </Button>
                <Button
                    type="submit"
                    disabled={form.processing || form.data.title.trim() === ''}
                >
                    {form.processing ? 'Saving…' : 'Save changes'}
                </Button>
            </div>
        </form>
    );
}

function IssueLabels({
    issue,
    availableLabels,
    canUpdate,
    updatingLabel,
    onToggle,
}: {
    issue: Issue;
    availableLabels: IssueLabel[];
    canUpdate: boolean;
    updatingLabel: number | null;
    onToggle: (label: IssueLabel) => void;
}) {
    return (
        <span className="flex flex-wrap items-center gap-1">
            {issue.labels.length > 0
                ? issue.labels.map((label) => (
                      <Badge key={label.id} variant="secondary">
                          <span
                              className="size-1.5 rounded-full"
                              style={{ backgroundColor: label.color }}
                          />
                          {label.name}
                      </Badge>
                  ))
                : 'None'}
            {canUpdate && availableLabels.length > 0 && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-6 rounded-full"
                            aria-label="Manage issue labels"
                            disabled={updatingLabel !== null}
                        >
                            <Plus className="size-3.5" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" className="min-w-48">
                        {availableLabels.map((label) => (
                            <DropdownMenuCheckboxItem
                                key={label.id}
                                checked={issue.labels.some(
                                    (current) => current.id === label.id,
                                )}
                                disabled={updatingLabel !== null}
                                onCheckedChange={() => onToggle(label)}
                            >
                                <span
                                    className="size-2 rounded-full"
                                    style={{ backgroundColor: label.color }}
                                />
                                {label.name}
                            </DropdownMenuCheckboxItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </span>
    );
}

function ActivityItem({
    name,
    timestamp,
    children,
}: {
    name: string;
    timestamp: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex gap-3">
            <Avatar name={name} />
            <div className="min-w-0 text-sm">
                <div>
                    <span className="font-medium">{name}</span> {children}
                </div>
                <p className="text-muted-foreground mt-1 text-xs">
                    {formatTimestamp(timestamp)}
                </p>
            </div>
        </div>
    );
}

function Avatar({ name }: { name: string }) {
    return (
        <span className="grid size-7 shrink-0 place-items-center rounded-full bg-zinc-200 text-[9px] font-semibold dark:bg-zinc-700">
            {name
                .split(' ')
                .map((part) => part[0])
                .join('')
                .slice(0, 2)
                .toUpperCase()}
        </span>
    );
}

function statusLabel(status: IssueStatus): string {
    return status === 'in_progress'
        ? 'In progress'
        : status.charAt(0).toUpperCase() + status.slice(1);
}

function formatTimestamp(timestamp: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(timestamp));
}
