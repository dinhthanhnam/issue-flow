import { useHttp } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { store as storeIssue } from '@/actions/App/Http/Controllers/IssueController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type {
    Issue,
    IssuePriority,
    Project,
    ProjectMember,
} from '@/types/issue-flow';

type CreateIssueData = {
    title: string;
    description: string;
    priority: IssuePriority;
    assignee_id: number | null;
};

export function CreateIssueDialog({
    open,
    onClose,
    project,
    assignees,
    onCreated,
}: {
    open: boolean;
    onClose: () => void;
    project: Project;
    assignees: ProjectMember[];
    onCreated: (issue: Issue) => void;
}) {
    const form = useHttp<CreateIssueData, { data: Issue }>({
        title: '',
        description: '',
        priority: 'medium',
        assignee_id: null,
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        void form
            .post(storeIssue.url(project.id), {
                onSuccess: (response) => {
                    toast.success(
                        `${response.data.project.key}-${response.data.number} created`,
                    );
                    form.reset();
                    onCreated(response.data);
                    onClose();
                },
            })
            .catch(() =>
                toast.error('Could not create issue. Please try again.'),
            );
    }

    return (
        <Dialog open={open} onOpenChange={(nextOpen) => !nextOpen && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New issue</DialogTitle>
                    <DialogDescription>
                        Create an issue in {project.name}.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="issue-title">Title</Label>
                        <Input
                            id="issue-title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            autoFocus
                            required
                            maxLength={255}
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="issue-description">Description</Label>
                        <Textarea
                            id="issue-description"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="issue-priority">Priority</Label>
                        <Select
                            value={form.data.priority}
                            onValueChange={(value) =>
                                form.setData('priority', value as IssuePriority)
                            }
                        >
                            <SelectTrigger id="issue-priority">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="low">Low</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="urgent">Urgent</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.priority} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="issue-assignee">Assignee</Label>
                        <Select
                            value={
                                form.data.assignee_id?.toString() ??
                                'unassigned'
                            }
                            onValueChange={(value) =>
                                form.setData(
                                    'assignee_id',
                                    value === 'unassigned'
                                        ? null
                                        : Number(value),
                                )
                            }
                        >
                            <SelectTrigger
                                id="issue-assignee"
                                className="w-full"
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
                        <InputError message={form.errors.assignee_id} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                form.processing || !form.data.title.trim()
                            }
                        >
                            {form.processing ? 'Creating…' : 'Create issue'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
