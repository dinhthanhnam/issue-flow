export type WorkspaceRole = 'owner' | 'admin' | 'member';
export type ProjectRole = 'lead' | 'member' | 'viewer';
export type IssueStatus = 'todo' | 'in_progress' | 'done';
export type IssuePriority = 'low' | 'medium' | 'high' | 'urgent';

export type Workspace = {
    id: number;
    name: string;
    slug: string;
    archived_at: string | null;
    created_at: string;
    role: WorkspaceRole;
};

export type Project = {
    id: number;
    workspace_id: number;
    name: string;
    key: string;
    description: string | null;
    created_at: string;
    can_create_issue: boolean;
};

export type ProjectMember = {
    id: number;
    name: string;
    email: string;
    role: ProjectRole;
};

export type WorkspaceMember = {
    id: number;
    name: string;
    email: string;
    role: WorkspaceRole;
    created_at: string;
};

export type IssueLabel = {
    id: number;
    workspace_id: number;
    name: string;
    color: string;
};

export type Issue = {
    id: number;
    number: number;
    title: string;
    description: string | null;
    status: IssueStatus;
    priority: IssuePriority;
    project: Pick<Project, 'id' | 'key'>;
    reporter: { id: number; name: string };
    assignee: { id: number; name: string } | null;
    labels: IssueLabel[];
    created_at: string;
};

export type Comment = {
    id: number;
    body: string;
    user: { id: number; name: string };
    created_at: string;
};

export type ApiCollection<T> = { data: T[] };

export type ApiPage<T> = ApiCollection<T> & {
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};
