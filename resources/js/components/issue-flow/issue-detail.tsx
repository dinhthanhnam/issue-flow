import { useHttp } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { update as updateIssue } from '@/actions/App/Http/Controllers/IssueController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { issueKey } from '@/components/issue-flow/issue-collection';
import type { Issue, IssueStatus } from '@/types/issue-flow';

export function IssueDetail({
    issue,
    onClose,
    canUpdate,
    onUpdated,
}: {
    issue: Issue | null;
    onClose: () => void;
    canUpdate: boolean;
    onUpdated: (issue: Issue) => void;
}) {
    const form = useHttp<{ status: IssueStatus }, { data: Issue }>({
        status: 'todo',
    });

    useEffect(() => {
        if (issue) {
            form.setData('status', issue.status);
            form.clearErrors();
        }
    }, [issue?.id]);

    function saveStatus() {
        if (!issue) return;

        void form
            .patch(updateIssue.url(issue.id), {
                onSuccess: (response) => {
                    toast.success('Issue updated');
                    onUpdated(response.data);
                },
            })
            .catch(() =>
                toast.error('Could not update issue. Please try again.'),
            );
    }

    return (
        <Sheet
            open={issue !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <SheetContent className="w-full gap-0 sm:max-w-lg">
                {issue && (
                    <>
                        <SheetHeader className="border-b px-6 py-5">
                            <SheetDescription>
                                {issueKey(issue)}
                            </SheetDescription>
                            <SheetTitle className="pr-8 text-left text-xl leading-snug">
                                {issue.title}
                            </SheetTitle>
                        </SheetHeader>
                        <div className="flex flex-col gap-6 overflow-y-auto px-6 py-6 text-sm">
                            <div className="grid grid-cols-[110px_1fr] gap-x-4 gap-y-4">
                                <span className="text-muted-foreground">
                                    Status
                                </span>
                                {canUpdate ? (
                                    <div className="flex flex-col items-start gap-2">
                                        <Select
                                            value={form.data.status}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'status',
                                                    value as IssueStatus,
                                                )
                                            }
                                        >
                                            <SelectTrigger aria-label="Issue status">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
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
                                        <InputError
                                            message={form.errors.status}
                                        />
                                        <Button
                                            size="sm"
                                            disabled={
                                                form.processing ||
                                                form.data.status ===
                                                    issue.status
                                            }
                                            onClick={saveStatus}
                                        >
                                            {form.processing
                                                ? 'Saving…'
                                                : 'Save status'}
                                        </Button>
                                    </div>
                                ) : (
                                    <span className="capitalize">
                                        {issue.status.replace('_', ' ')}
                                    </span>
                                )}
                                <span className="text-muted-foreground">
                                    Priority
                                </span>
                                <span className="capitalize">
                                    {issue.priority}
                                </span>
                                <span className="text-muted-foreground">
                                    Assignee
                                </span>
                                <span>
                                    {issue.assignee?.name ?? 'Unassigned'}
                                </span>
                                <span className="text-muted-foreground">
                                    Reporter
                                </span>
                                <span>{issue.reporter.name}</span>
                                <span className="text-muted-foreground">
                                    Labels
                                </span>
                                <span className="flex flex-wrap gap-1">
                                    {issue.labels.length > 0
                                        ? issue.labels.map((label) => (
                                              <Badge
                                                  key={label.id}
                                                  variant="secondary"
                                              >
                                                  {label.name}
                                              </Badge>
                                          ))
                                        : 'None'}
                                </span>
                            </div>
                            <div className="border-t pt-5">
                                <h3 className="font-medium">Description</h3>
                                <p className="text-muted-foreground mt-3 whitespace-pre-wrap">
                                    {issue.description || 'No description yet.'}
                                </p>
                            </div>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
