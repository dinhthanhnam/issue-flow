import { Circle, CircleCheck, CircleDot, Flag, UserRound } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Issue, IssueStatus } from '@/types/issue-flow';

const columns: { status: IssueStatus; label: string; icon: typeof Circle }[] = [
    { status: 'todo', label: 'Todo', icon: Circle },
    { status: 'in_progress', label: 'In progress', icon: CircleDot },
    { status: 'done', label: 'Done', icon: CircleCheck },
];

export function issueKey(issue: Issue): string {
    return `${issue.project.key}-${issue.number}`;
}

function IssueCard({
    issue,
    onOpen,
}: {
    issue: Issue;
    onOpen: (issue: Issue) => void;
}) {
    return (
        <button
            type="button"
            onClick={() => onOpen(issue)}
            className="bg-card hover:border-foreground/30 focus-visible:ring-ring w-full rounded-lg border p-3 text-left shadow-xs transition-colors focus-visible:ring-2 focus-visible:outline-none"
        >
            <span className="text-muted-foreground text-xs font-medium">
                {issueKey(issue)}
            </span>
            <span className="mt-1 block text-sm leading-snug font-medium">
                {issue.title}
            </span>
            {issue.labels.length > 0 && (
                <span className="mt-3 flex flex-wrap gap-1">
                    {issue.labels.map((label) => (
                        <Badge key={label.id} variant="secondary">
                            {label.name}
                        </Badge>
                    ))}
                </span>
            )}
            <span className="text-muted-foreground mt-3 flex items-center justify-between gap-2 text-xs">
                <span className="flex items-center gap-1 capitalize">
                    <Flag className="size-3.5" />
                    {issue.priority}
                </span>
                <span className="flex min-w-0 items-center gap-1 truncate">
                    <UserRound className="size-3.5 shrink-0" />
                    {issue.assignee?.name ?? 'Unassigned'}
                </span>
            </span>
        </button>
    );
}

type Props = {
    issues: Issue[];
    view: 'board' | 'list';
    onOpen: (issue: Issue) => void;
};

export function IssueCollection({ issues, view, onOpen }: Props) {
    if (issues.length === 0) {
        return (
            <div className="text-muted-foreground flex min-h-52 items-center justify-center rounded-lg border border-dashed text-sm">
                No issues match these filters.
            </div>
        );
    }

    if (view === 'list') {
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
                                        <span className="text-muted-foreground text-xs">
                                            {issueKey(issue)}
                                        </span>
                                        <span className="text-foreground max-w-80 truncate font-medium">
                                            {issue.title}
                                        </span>
                                    </Button>
                                </td>
                                <td className="px-4 py-3 capitalize">
                                    {issue.status.replace('_', ' ')}
                                </td>
                                <td className="px-4 py-3 capitalize">
                                    {issue.priority}
                                </td>
                                <td className="text-muted-foreground px-4 py-3">
                                    {issue.assignee?.name ?? 'Unassigned'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        );
    }

    return (
        <div className="grid auto-cols-[minmax(260px,1fr)] grid-flow-col gap-3 overflow-x-auto pb-4 lg:grid-flow-row lg:grid-cols-3">
            {columns.map(({ status, label, icon: Icon }) => {
                const columnIssues = issues.filter(
                    (issue) => issue.status === status,
                );

                return (
                    <section
                        key={status}
                        aria-label={label}
                        className="bg-muted/40 min-h-72 rounded-lg border p-2"
                    >
                        <div className="flex items-center gap-2 px-2 py-2 text-sm font-medium">
                            <Icon className="text-muted-foreground size-4" />
                            {label}
                            <span className="text-muted-foreground ml-auto text-xs">
                                {columnIssues.length}
                            </span>
                        </div>
                        <div className="flex flex-col gap-2">
                            {columnIssues.map((issue) => (
                                <IssueCard
                                    key={issue.id}
                                    issue={issue}
                                    onOpen={onOpen}
                                />
                            ))}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
