import { router, useHttp } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { store as storeWorkspace } from '@/actions/App/Http/Controllers/WorkspaceController';
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
import { dashboard } from '@/routes';
import type { Workspace } from '@/types/issue-flow';

type CreateWorkspaceData = {
    name: string;
    slug: string;
};

export function CreateWorkspaceDialog({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const form = useHttp<CreateWorkspaceData, { data: Workspace }>({
        name: '',
        slug: '',
    });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        void form
            .post(storeWorkspace.url(), {
                onSuccess: (response) => {
                    toast.success(`${response.data.name} created`);
                    form.reset();
                    onClose();
                    router.visit(
                        dashboard.url({
                            query: { workspace: response.data.id },
                        }),
                    );
                },
            })
            .catch(() => toast.error('Could not create the workspace.'));
    }

    function updateName(name: string): void {
        const previousGeneratedSlug = slugify(form.data.name);

        form.setData((current) => ({
            ...current,
            name,
            slug:
                current.slug === '' || current.slug === previousGeneratedSlug
                    ? slugify(name)
                    : current.slug,
        }));
    }

    return (
        <Dialog open={open} onOpenChange={(nextOpen) => !nextOpen && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New workspace</DialogTitle>
                    <DialogDescription>
                        Create a workspace for a team or area of work.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="workspace-name">Name</Label>
                        <Input
                            id="workspace-name"
                            value={form.data.name}
                            onChange={(event) => updateName(event.target.value)}
                            maxLength={255}
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="workspace-slug">Slug</Label>
                        <Input
                            id="workspace-slug"
                            value={form.data.slug}
                            onChange={(event) =>
                                form.setData(
                                    'slug',
                                    slugify(event.target.value),
                                )
                            }
                            maxLength={255}
                        />
                        <p className="text-muted-foreground text-xs">
                            Used as a stable workspace identifier.
                        </p>
                        <InputError message={form.errors.slug} />
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
                                form.data.slug === ''
                            }
                        >
                            {form.processing ? 'Creating…' : 'Create workspace'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function slugify(value: string): string {
    return value
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}
