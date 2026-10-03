import {
    destroy as removeWorkspaceMember,
    update as updateWorkspaceMember,
} from '@/actions/App/Http/Controllers/WorkspaceMemberController';
import {
    MemberManagementSheet,
    type MemberRoleOption,
} from '@/components/issue-flow/member-management-sheet';
import { deleteWithoutResponse, patchJson } from '@/lib/api';
import type {
    Workspace,
    WorkspaceMember,
    WorkspaceRole,
} from '@/types/issue-flow';

const workspaceRoleOptions: MemberRoleOption[] = [
    { value: 'admin', label: 'Admin' },
    { value: 'member', label: 'Member' },
];

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
    const canManageMembers =
        workspace.archived_at === null &&
        (workspace.role === 'owner' || workspace.role === 'admin');

    function canManageTarget(member: WorkspaceMember): boolean {
        if (!canManageMembers || member.role === 'owner') {
            return false;
        }

        return workspace.role === 'owner' || member.role === 'member';
    }

    function roleOptions(member: WorkspaceMember): MemberRoleOption[] {
        return canManageTarget(member) && workspace.role === 'owner'
            ? workspaceRoleOptions
            : [];
    }

    async function updateRole(
        member: WorkspaceMember,
        role: string,
    ): Promise<void> {
        const response = await patchJson<{ data: WorkspaceMember }>(
            updateWorkspaceMember.url({
                workspace: workspace.id,
                user: member.id,
            }),
            { role: role as WorkspaceRole },
        );

        onMembersChange(
            members.map((current) =>
                current.id === response.data.id ? response.data : current,
            ),
        );
    }

    async function removeMember(member: WorkspaceMember): Promise<void> {
        await deleteWithoutResponse(
            removeWorkspaceMember.url({
                workspace: workspace.id,
                user: member.id,
            }),
        );

        onMembersChange(members.filter((current) => current.id !== member.id));
    }

    return (
        <MemberManagementSheet
            open={open}
            loading={loading}
            title="Workspace members"
            description={workspace.name}
            removalContext={workspace.name}
            currentUserId={currentUserId}
            members={members}
            roleOptions={roleOptions}
            canRemove={canManageTarget}
            onClose={onClose}
            onUpdateRole={updateRole}
            onRemove={removeMember}
        />
    );
}
