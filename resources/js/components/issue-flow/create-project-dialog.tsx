import { router, useHttp } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { store as storeProject } from '@/actions/App/Http/Controllers/ProjectController';
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
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import type { Project, Workspace } from '@/types/issue-flow';

type CreateProjectData = {
    name: string;
    key: string;
    description: string;
};

export function CreateProjectDialog({
    open,
    onClose,
    workspace,
}: {
    open: boolean;
    onClose: () => void;
    workspace: Workspace;
}) {
    const form = useHttp<CreateProjectData, { data: Project }>({
        name: '',
        key: '',
        description: '',
    });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        void form
            .post(storeProject.url(workspace.id), {
                onSuccess: (response) => {
                    toast.success(`${response.data.name} created`);
                    form.reset();
                    onClose();
                    router.visit(
                        dashboard.url({
                            query: {
                                workspace: workspace.id,
                                project: response.data.id,
                            },
                        }),
                    );
                },
            })
            .catch(() => toast.error('Could not create the project.'));
    }

    return (
        <Dialog open={open} onOpenChange={(nextOpen) => !nextOpen && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New project</DialogTitle>
                    <DialogDescription>
                        Create a project in {workspace.name}.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="project-name">Name</Label>
                        <Input
                            id="project-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            maxLength={255}
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="project-key">Key</Label>
                        <Input
                            id="project-key"
                            value={form.data.key}
                            onChange={(event) =>
                                form.setData(
                                    'key',
                                    event.target.value
                                        .replace(/[^a-z0-9]/gi, '')
                                        .toUpperCase(),
                                )
                            }
                            className="font-mono uppercase"
                            maxLength={10}
                            placeholder="ENG"
                        />
                        <InputError message={form.errors.key} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="project-description">Description</Label>
                        <Textarea
                            id="project-description"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            placeholder="What work belongs in this project?"
                        />
                        <InputError message={form.errors.description} />
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
                                form.processing ||
                                form.data.name.trim() === '' ||
                                form.data.key === ''
                            }
                        >
                            {form.processing ? 'Creating…' : 'Create project'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
