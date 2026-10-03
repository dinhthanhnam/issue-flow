import { Trash2, Users } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { toast } from 'sonner';
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

export type ManagedMember = {
    id: number;
    name: string;
    email: string;
    role: string;
};

export type MemberRoleOption = {
    value: string;
    label: string;
};

type Props<TMember extends ManagedMember> = {
    open: boolean;
    loading: boolean;
    title: string;
    description: string;
    removalContext: string;
    currentUserId: number;
    members: TMember[];
    addMember?: ReactNode;
    roleOptions: (member: TMember) => MemberRoleOption[];
    canRemove: (member: TMember) => boolean;
    onClose: () => void;
    onUpdateRole: (member: TMember, role: string) => Promise<void>;
    onRemove: (member: TMember) => Promise<void>;
};

export function MemberManagementSheet<TMember extends ManagedMember>({
    open,
    loading,
    title,
    description,
    removalContext,
    currentUserId,
    members,
    addMember,
    roleOptions,
    canRemove,
    onClose,
    onUpdateRole,
    onRemove,
}: Props<TMember>) {
    const [busyMemberId, setBusyMemberId] = useState<number | null>(null);
    const [memberToRemove, setMemberToRemove] = useState<TMember | null>(null);

    function updateRole(member: TMember, role: string): void {
        if (busyMemberId !== null || member.role === role) {
            return;
        }

        setBusyMemberId(member.id);

        void onUpdateRole(member, role)
            .then(() => toast.success(`${member.name} is now ${role}`))
            .catch(() => toast.error('Could not update the member role.'))
            .finally(() => setBusyMemberId(null));
    }

    function removeMember(): void {
        if (memberToRemove === null || busyMemberId !== null) {
            return;
        }

        const member = memberToRemove;
        setBusyMemberId(member.id);

        void onRemove(member)
            .then(() => {
                setMemberToRemove(null);
                toast.success(`${member.name} removed from ${removalContext}`);
            })
            .catch(() => toast.error('Could not remove the member.'))
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
                            {title}
                        </SheetTitle>
                        <SheetDescription>{description}</SheetDescription>
                    </SheetHeader>

                    <div className="flex-1 overflow-y-auto p-5">
                        {addMember}

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
                                    const options = roleOptions(member);

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

                                            {options.length > 0 ? (
                                                <Select
                                                    value={member.role}
                                                    disabled={
                                                        busyMemberId !== null
                                                    }
                                                    onValueChange={(role) =>
                                                        updateRole(member, role)
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
                                                        {options.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
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

                                            {canRemove(member) && (
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
                        <DialogTitle>Remove member?</DialogTitle>
                        <DialogDescription>
                            Remove {memberToRemove?.name} from {removalContext}?
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
