import { Circle, CircleCheck, CircleDot, Flag } from "lucide-react";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { Issue, IssueStatus } from "@/types/issue-flow";

const columns: { status: IssueStatus; label: string; icon: typeof Circle }[] = [
    { status: "todo", label: "Todo", icon: Circle },
    { status: "in_progress", label: "In progress", icon: CircleDot },
    { status: "done", label: "Done", icon: CircleCheck },
];

export function issueKey(issue: Issue): string {
    return `${issue.project.key}-${issue.number}`;
}

function IssueCard({
    issue,
    canMove,
    isDragging,
    onOpen,
    onDragStart,
    onDragEnd,
}: {
    issue: Issue;
    canMove: boolean;
    isDragging: boolean;
    onOpen: (issue: Issue) => void;
    onDragStart: (issue: Issue) => void;
    onDragEnd: () => void;
}) {
    return (
        <article
            draggable={canMove}
            onDragStart={() => onDragStart(issue)}
            onDragEnd={onDragEnd}
            onClick={() => !isDragging && onOpen(issue)}
            className={cn(
                "group bg-card cursor-pointer rounded-lg border p-3 shadow-xs transition hover:-translate-y-0.5 hover:shadow-sm",
                canMove && "cursor-grab active:cursor-grabbing",
                isDragging && "rotate-1 opacity-50",
            )}
        >
            <div className="flex items-start gap-2">
                <p className="line-clamp-2 flex-1 text-sm leading-5 font-medium">{issue.title}</p>
                {issue.labels[0] && (
                    <Badge variant="secondary" className="text-[10px]">
                        {issue.labels[0].name}
                    </Badge>
                )}
            </div>
            {issue.description && (
                <p className="text-muted-foreground mt-2 line-clamp-2 text-xs leading-5">
                    {issue.description}
                </p>
            )}
            <div className="text-muted-foreground mt-3 flex items-center gap-2 text-xs">
                <span className="font-mono">{issueKey(issue)}</span>
                <span className="ml-auto flex items-center gap-1 capitalize">
                    <Flag className="size-3.5" />
                    {issue.priority}
                </span>
                <span className="grid size-6 place-items-center rounded-full bg-zinc-200 text-[9px] font-semibold dark:bg-zinc-700">
                    {initials(issue.assignee?.name ?? "?")}
                </span>
            </div>
        </article>
    );
}

function initials(name: string): string {
    return name
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();
}

type Props = {
    issues: Issue[];
    view: "board" | "list";
    canMove: boolean;
    onOpen: (issue: Issue) => void;
    onMove: (issue: Issue, status: IssueStatus) => void;
};

export function IssueCollection({ issues, view, canMove, onOpen, onMove }: Props) {
    const [draggedIssue, setDraggedIssue] = useState<Issue | null>(null);
    const [dropTarget, setDropTarget] = useState<IssueStatus | null>(null);

    if (view === "list") {
        return (
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full min-w-[650px] text-left text-sm">
                    <thead className="bg-muted/50 text-muted-foreground border-b text-xs">
                        <tr>
                            <th className="px-4 py-3 font-medium">Issue</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">Priority</th>
                            <th className="px-4 py-3 font-medium">Assignee</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {issues.map((issue) => (
                            <tr key={issue.id} className="hover:bg-muted/30">
                                <td className="px-4 py-3">
                                    <Button
                                        variant="link"
                                        className="h-auto justify-start gap-3 p-0 text-left"
                                        onClick={() => onOpen(issue)}
                                    >
                                        <span className="text-muted-foreground font-mono text-xs">
                                            {issueKey(issue)}
                                        </span>
                                        <span className="text-foreground max-w-80 truncate font-medium">
                                            {issue.title}
                                        </span>
                                    </Button>
                                </td>
                                <td className="px-4 py-3 capitalize">
                                    {issue.status.replace("_", " ")}
                                </td>
                                <td className="px-4 py-3 capitalize">{issue.priority}</td>
                                <td className="text-muted-foreground px-4 py-3">
                                    {issue.assignee?.name ?? "Unassigned"}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {issues.length === 0 && (
                    <p className="text-muted-foreground py-12 text-center text-sm">
                        No issues match these filters.
                    </p>
                )}
            </div>
        );
    }

    function finishDrag(): void {
        setDraggedIssue(null);
        setDropTarget(null);
    }

    return (
        <div className="grid flex-1 auto-cols-[minmax(280px,340px)] grid-flow-col gap-3 overflow-x-auto pb-5">
            {columns.map(({ status, label, icon: Icon }) => {
                const columnIssues = issues.filter((issue) => issue.status === status);

                return (
                    <section
                        key={status}
                        aria-label={label}
                        onDragOver={(event) => {
                            if (!draggedIssue) return;
                            event.preventDefault();
                            setDropTarget(status);
                        }}
                        onDragLeave={(event) => {
                            if (!event.currentTarget.contains(event.relatedTarget as Node)) {
                                setDropTarget(null);
                            }
                        }}
                        onDrop={(event) => {
                            event.preventDefault();
                            if (draggedIssue && draggedIssue.status !== status) {
                                onMove(draggedIssue, status);
                            }
                            finishDrag();
                        }}
                        className={cn(
                            "bg-muted/65 flex min-h-[420px] flex-col rounded-xl border border-transparent transition-colors",
                            dropTarget === status && "bg-accent ring-ring/30 ring-2 ring-inset",
                        )}
                    >
                        <header className="flex h-11 items-center gap-2 px-3">
                            <Icon className="text-muted-foreground size-4" />
                            <h2 className="text-xs font-semibold tracking-wide uppercase">
                                {label}
                            </h2>
                            <span className="text-muted-foreground text-xs">
                                {columnIssues.length}
                            </span>
                        </header>
                        <div className="flex flex-1 flex-col gap-2 px-2 pb-2">
                            {columnIssues.map((issue) => (
                                <IssueCard
                                    key={issue.id}
                                    issue={issue}
                                    canMove={canMove}
                                    isDragging={draggedIssue?.id === issue.id}
                                    onOpen={onOpen}
                                    onDragStart={setDraggedIssue}
                                    onDragEnd={finishDrag}
                                />
                            ))}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
