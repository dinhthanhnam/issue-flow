import {
    destroy as removeProjectMember,
    update as updateProjectMember,
} from '@/actions/App/Http/Controllers/ProjectMemberController';
import {
    MemberManagementSheet,
    type MemberRoleOption,
} from '@/components/issue-flow/member-management-sheet';
import { deleteWithoutResponse, patchJson } from '@/lib/api';
import type {
    Project,
    ProjectMember,
    ProjectRole,
    Workspace,
} from '@/types/issue-flow';

const managerRoleOptions: MemberRoleOption[] = [
    { value: 'lead', label: 'Lead' },
    { value: 'member', label: 'Member' },
    { value: 'viewer', label: 'Viewer' },
];

const leadRoleOptions: MemberRoleOption[] = [
    { value: 'member', label: 'Member' },
    { value: 'viewer', label: 'Viewer' },
];

type Props = {
    open: boolean;
    loading: boolean;
    workspace: Workspace;
    project: Project;
    currentUserId: number;
    members: ProjectMember[];
    onClose: () => void;
    onMembersChange: (members: ProjectMember[]) => void;
};

export function ProjectMembersSheet({
    open,
    loading,
    workspace,
    project,
    currentUserId,
    members,
    onClose,
    onMembersChange,
}: Props) {
    const isWorkspaceManager =
        workspace.role === 'owner' || workspace.role === 'admin';
    const canManageMembers =
        workspace.archived_at === null && project.can_manage_members;

    function canManageTarget(member: ProjectMember): boolean {
        return (
            canManageMembers && (isWorkspaceManager || member.role !== 'lead')
        );
    }

    function roleOptions(member: ProjectMember): MemberRoleOption[] {
        if (!canManageTarget(member)) {
            return [];
        }

        return isWorkspaceManager ? managerRoleOptions : leadRoleOptions;
    }

    async function updateRole(
        member: ProjectMember,
        role: string,
    ): Promise<void> {
        const response = await patchJson<{ data: ProjectMember }>(
            updateProjectMember.url({
                project: project.id,
                user: member.id,
            }),
            { role: role as ProjectRole },
        );

        onMembersChange(
            members.map((current) =>
                current.id === response.data.id ? response.data : current,
            ),
        );
    }

    async function removeMember(member: ProjectMember): Promise<void> {
        await deleteWithoutResponse(
            removeProjectMember.url({
                project: project.id,
                user: member.id,
            }),
        );

        onMembersChange(members.filter((current) => current.id !== member.id));
    }

    return (
        <MemberManagementSheet
            open={open}
            loading={loading}
            title="Project members"
            description={`${project.name} · ${project.key}`}
            removalContext={project.name}
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
