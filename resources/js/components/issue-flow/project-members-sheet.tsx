import {
    destroy as removeProjectMember,
    store as addProjectMember,
    update as updateProjectMember,
} from '@/actions/App/Http/Controllers/ProjectMemberController';
import { show as findProjectMemberCandidate } from '@/actions/App/Http/Controllers/ProjectMemberCandidateController';
import { AddMemberForm } from '@/components/issue-flow/add-member-form';
import {
    MemberManagementSheet,
    type MemberRoleOption,
} from '@/components/issue-flow/member-management-sheet';
import { deleteWithoutResponse, getJson, patchJson, postJson } from '@/lib/api';
import type {
    MemberCandidate,
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

    async function findCandidate(email: string): Promise<MemberCandidate> {
        const response = await getJson<{ data: MemberCandidate }>(
            findProjectMemberCandidate.url(project.id, {
                query: { email },
            }),
        );

        return response.data;
    }

    async function addMember(
        candidate: MemberCandidate,
        role: string,
    ): Promise<void> {
        const response = await postJson<{ data: ProjectMember }>(
            addProjectMember.url(project.id),
            {
                user_id: candidate.id,
                role: role as ProjectRole,
            },
        );

        onMembersChange(
            [...members, response.data].sort((left, right) =>
                left.name.localeCompare(right.name),
            ),
        );
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
            addMember={
                canManageMembers ? (
                    <AddMemberForm
                        roleOptions={
                            isWorkspaceManager
                                ? managerRoleOptions
                                : leadRoleOptions
                        }
                        onFind={findCandidate}
                        onAdd={addMember}
                    />
                ) : undefined
            }
            roleOptions={roleOptions}
            canRemove={canManageTarget}
            onClose={onClose}
            onUpdateRole={updateRole}
            onRemove={removeMember}
        />
    );
}
