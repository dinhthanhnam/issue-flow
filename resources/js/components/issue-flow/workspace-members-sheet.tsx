import { Trash2, Users } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    destroy as removeWorkspaceMember,
    update as updateWorkspaceMember,
} from '@/actions/App/Http/Controllers/WorkspaceMemberController';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { deleteWithoutResponse, patchJson } from '@/lib/api';
import type {
    Workspace,
    WorkspaceMember,
    WorkspaceRole,
} from '@/types/issue-flow';

type Props = {
    open: boolean;
    loading: boolean;
    workspace: Workspace;
    currentUserId: number;
    members: WorkspaceMember[];
    onClose: () => void;
    onMembersChange: (members: WorkspaceMember[]) => void;
};

export function WorkspaceMembersSheet({
    open,
    loading,
    workspace,
    currentUserId,
    members,
    onClose,
    onMembersChange,
}: Props) {
    const [busyMemberId, setBusyMemberId] = useState<number | null>(null);
    const [memberToRemove, setMemberToRemove] =
        useState<WorkspaceMember | null>(null);
    const canManageMembers =
        workspace.archived_at === null &&
        (workspace.role === 'owner' || workspace.role === 'admin');

    function canManageTarget(member: WorkspaceMember): boolean {
        if (!canManageMembers || member.role === 'owner') {
            return false;
        }

        return workspace.role === 'owner' || member.role === 'member';
    }

    function updateRole(member: WorkspaceMember, role: WorkspaceRole): void {
        if (busyMemberId !== null || member.role === role) {
            return;
        }

        setBusyMemberId(member.id);

        void patchJson<{ data: WorkspaceMember }>(
            updateWorkspaceMember.url({
                workspace: workspace.id,
                user: member.id,
            }),
            { role },
        )
            .then((response) => {
                onMembersChange(
                    members.map((current) =>
                        current.id === response.data.id
                            ? response.data
                            : current,
                    ),
                );
                toast.success(`${member.name} is now ${role}`);
            })
            .catch(() => toast.error('Could not update the member role.'))
            .finally(() => setBusyMemberId(null));
    }

    function removeMember(): void {
        if (memberToRemove === null || busyMemberId !== null) {
            return;
        }

        const member = memberToRemove;
        setBusyMemberId(member.id);

        void deleteWithoutResponse(
            removeWorkspaceMember.url({
                workspace: workspace.id,
                user: member.id,
            }),
        )
            .then(() => {
                onMembersChange(
                    members.filter((current) => current.id !== member.id),
                );
                setMemberToRemove(null);
                toast.success(`${member.name} removed from ${workspace.name}`);
            })
            .catch(() => toast.error('Could not remove the workspace member.'))
            .finally(() => setBusyMemberId(null));
    }

    return (
        <>
            <Sheet
                open={open}
                onOpenChange={(nextOpen) => !nextOpen && onClose()}
            >
                <SheetContent className="w-full gap-0 p-0 sm:max-w-xl">
                    <SheetHeader className="border-b px-5 py-4">
                        <SheetTitle className="flex items-center gap-2">
                            <Users className="size-4" />
                            Workspace members
                        </SheetTitle>
                        <SheetDescription>{workspace.name}</SheetDescription>
                    </SheetHeader>

                    <div className="flex-1 overflow-y-auto p-5">
                        {loading && (
                            <p className="text-muted-foreground py-10 text-center text-sm">
                                Loading members…
                            </p>
                        )}

                        {!loading && members.length === 0 && (
                            <p className="text-muted-foreground py-10 text-center text-sm">
                                No members found.
                            </p>
                        )}

                        {!loading && members.length > 0 && (
                            <div className="overflow-hidden rounded-lg border">
                                {members.map((member) => {
                                    const canManage = canManageTarget(member);
                                    const canChangeRole =
                                        canManage && workspace.role === 'owner';

                                    return (
                                        <div
                                            key={member.id}
                                            className="flex items-center gap-3 border-b p-3 last:border-b-0"
                                        >
                                            <Avatar className="size-9">
                                                <AvatarFallback className="text-xs">
                                                    {initials(member.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="truncate text-sm font-medium">
                                                        {member.name}
                                                    </p>
                                                    {member.id ===
                                                        currentUserId && (
                                                        <span className="text-muted-foreground text-xs">
                                                            You
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-muted-foreground truncate text-xs">
                                                    {member.email}
                                                </p>
                                            </div>

                                            {canChangeRole ? (
                                                <Select
                                                    value={member.role}
                                                    disabled={
                                                        busyMemberId !== null
                                                    }
                                                    onValueChange={(value) =>
                                                        updateRole(
                                                            member,
                                                            value as WorkspaceRole,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger
                                                        size="sm"
                                                        className="w-28"
                                                        aria-label={`Role for ${member.name}`}
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="admin">
                                                            Admin
                                                        </SelectItem>
                                                        <SelectItem value="member">
                                                            Member
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            ) : (
                                                <Badge
                                                    variant="secondary"
                                                    className="capitalize"
                                                >
                                                    {member.role}
                                                </Badge>
                                            )}

                                            {canManage && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-muted-foreground hover:text-destructive"
                                                    aria-label={`Remove ${member.name}`}
                                                    disabled={
                                                        busyMemberId !== null
                                                    }
                                                    onClick={() =>
                                                        setMemberToRemove(
                                                            member,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </SheetContent>
            </Sheet>

            <Dialog
                open={memberToRemove !== null}
                onOpenChange={(nextOpen) =>
                    !nextOpen &&
                    busyMemberId === null &&
                    setMemberToRemove(null)
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove workspace member?</DialogTitle>
                        <DialogDescription>
                            {memberToRemove?.name} will also lose access
                            inherited through this workspace.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={busyMemberId !== null}
                            onClick={() => setMemberToRemove(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={busyMemberId !== null}
                            onClick={removeMember}
                        >
                            {busyMemberId !== null
                                ? 'Removing…'
                                : 'Remove member'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
