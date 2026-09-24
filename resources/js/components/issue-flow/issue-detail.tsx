import { useHttp } from "@inertiajs/react";
import type { FormEvent } from "react";
import { MessageSquare } from "lucide-react";
import { toast } from "sonner";
import { store as storeComment } from "@/actions/App/Http/Controllers/CommentController";
import { update as updateIssue } from "@/actions/App/Http/Controllers/IssueController";
import { issueKey } from "@/components/issue-flow/issue-collection";
import InputError from "@/components/input-error";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { Textarea } from "@/components/ui/textarea";
import type { Comment, Issue, IssueStatus } from "@/types/issue-flow";

type Props = {
    issue: Issue;
    comments: Comment[];
    commentsLoading: boolean;
    canUpdate: boolean;
    onClose: () => void;
    onUpdated: (issue: Issue) => void;
    onCommentCreated: (comment: Comment) => void;
};

export function IssueDetail({
    issue,
    comments,
    commentsLoading,
    canUpdate,
    onClose,
    onUpdated,
    onCommentCreated,
}: Props) {
    const statusForm = useHttp<{ status: IssueStatus }, { data: Issue }>({
        status: issue.status,
    });
    const commentForm = useHttp<{ body: string }, { data: Comment }>({
        body: "",
    });

    function saveStatus(): void {
        void statusForm
            .patch(updateIssue.url(issue.id), {
                onSuccess: (response) => {
                    onUpdated(response.data);
                    toast.success(
                        `${issueKey(issue)} moved to ${statusLabel(response.data.status)}`,
                    );
                },
            })
            .catch(() => toast.error("Could not update the issue."));
    }

    function createComment(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        void commentForm
            .post(storeComment.url(issue.id), {
                onSuccess: (response) => {
                    onCommentCreated(response.data);
                    commentForm.reset();
                    toast.success("Comment added");
                },
            })
            .catch(() => toast.error("Could not add the comment."));
    }

    return (
        <Sheet open onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="w-full gap-0 p-0 sm:max-w-xl">
                <SheetHeader className="flex h-14 shrink-0 flex-row items-center gap-3 border-b px-4 py-0">
                    <SheetDescription className="font-mono text-sm font-medium">
                        {issueKey(issue)}
                    </SheetDescription>
                    <Badge variant="secondary">{statusLabel(issue.status)}</Badge>
                    <SheetTitle className="sr-only">{issue.title}</SheetTitle>
                </SheetHeader>

                <div className="flex-1 overflow-y-auto p-5 sm:p-6">
                    <h2 className="text-xl font-semibold tracking-tight">{issue.title}</h2>
                    <p className="text-muted-foreground mt-3 text-sm leading-6 whitespace-pre-wrap">
                        {issue.description || "No description yet."}
                    </p>

                    <div className="mt-6 grid grid-cols-[110px_1fr] items-start gap-x-3 gap-y-4 text-sm">
                        <span className="text-muted-foreground pt-2">Status</span>
                        {canUpdate ? (
                            <div className="flex flex-wrap items-start gap-2">
                                <div>
                                    <Select
                                        value={statusForm.data.status}
                                        onValueChange={(value) =>
                                            statusForm.setData("status", value as IssueStatus)
                                        }
                                    >
                                        <SelectTrigger className="w-40" aria-label="Issue status">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="todo">Todo</SelectItem>
                                            <SelectItem value="in_progress">In progress</SelectItem>
                                            <SelectItem value="done">Done</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        className="mt-1"
                                        message={statusForm.errors.status}
                                    />
                                </div>
                                <Button
                                    size="sm"
                                    disabled={
                                        statusForm.processing ||
                                        statusForm.data.status === issue.status
                                    }
                                    onClick={saveStatus}
                                >
                                    {statusForm.processing ? "Saving…" : "Save"}
                                </Button>
                            </div>
                        ) : (
                            <span className="pt-2">{statusLabel(issue.status)}</span>
                        )}

                        <span className="text-muted-foreground">Priority</span>
                        <span className="capitalize">{issue.priority}</span>

                        <span className="text-muted-foreground">Assignee</span>
                        <span>{issue.assignee?.name ?? "Unassigned"}</span>

                        <span className="text-muted-foreground">Reporter</span>
                        <span>{issue.reporter.name}</span>

                        <span className="text-muted-foreground">Labels</span>
                        <span className="flex flex-wrap gap-1">
                            {issue.labels.length > 0
                                ? issue.labels.map((label) => (
                                      <Badge key={label.id} variant="secondary">
                                          {label.name}
                                      </Badge>
                                  ))
                                : "None"}
                        </span>
                    </div>

                    <div className="my-6 border-t" />

                    <h3 className="font-medium">Activity</h3>
                    <form onSubmit={createComment} className="mt-4 flex gap-3">
                        <Avatar name={issue.reporter.name} />
                        <div className="flex-1">
                            <Textarea
                                value={commentForm.data.body}
                                onChange={(event) =>
                                    commentForm.setData("body", event.target.value)
                                }
                                className="min-h-20"
                                placeholder="Write a comment..."
                            />
                            <InputError className="mt-1" message={commentForm.errors.body} />
                            <div className="mt-2 flex justify-end">
                                <Button
                                    size="sm"
                                    disabled={
                                        commentForm.processing ||
                                        commentForm.data.body.trim() === ""
                                    }
                                >
                                    {commentForm.processing ? "Commenting…" : "Comment"}
                                </Button>
                            </div>
                        </div>
                    </form>

                    <div className="mt-6 flex flex-col gap-5">
                        <ActivityItem name={issue.reporter.name} timestamp={issue.created_at}>
                            created this issue.
                        </ActivityItem>

                        {commentsLoading && (
                            <p className="text-muted-foreground pl-10 text-sm">Loading comments…</p>
                        )}

                        {!commentsLoading &&
                            comments.map((comment) => (
                                <ActivityItem
                                    key={comment.id}
                                    name={comment.user.name}
                                    timestamp={comment.created_at}
                                >
                                    <p className="mt-1 whitespace-pre-wrap">{comment.body}</p>
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
                <p className="text-muted-foreground mt-1 text-xs">{formatTimestamp(timestamp)}</p>
            </div>
        </div>
    );
}

function Avatar({ name }: { name: string }) {
    return (
        <span className="grid size-7 shrink-0 place-items-center rounded-full bg-zinc-200 text-[9px] font-semibold dark:bg-zinc-700">
            {name
                .split(" ")
                .map((part) => part[0])
                .join("")
                .slice(0, 2)
                .toUpperCase()}
        </span>
    );
}

function statusLabel(status: IssueStatus): string {
    return status === "in_progress"
        ? "In progress"
        : status.charAt(0).toUpperCase() + status.slice(1);
}

function formatTimestamp(timestamp: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(timestamp));
}
