import { Link } from '@inertiajs/react';
import {
    Check,
    ChevronsUpDown,
    FolderKanban,
    LayoutGrid,
    Plus,
} from 'lucide-react';
import { NavUser } from '@/components/nav-user';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Project, Workspace } from '@/types/issue-flow';

type Props = {
    workspaces: Workspace[];
    workspace: Workspace | null;
    projects: Project[];
    project: Project | null;
    canCreateProject: boolean;
    onCreateWorkspace: () => void;
    onCreateProject: () => void;
};

export function WorkspaceSidebar({
    workspaces,
    workspace,
    projects,
    project,
    canCreateProject,
    onCreateWorkspace,
    onCreateProject,
}: Props) {
    return (
        <Sidebar collapsible="offcanvas" variant="sidebar">
            <SidebarHeader className="border-sidebar-border border-b p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton size="lg" className="h-12">
                            <span className="bg-primary text-primary-foreground grid size-8 shrink-0 place-items-center rounded-lg text-xs font-semibold">
                                {workspace?.name.slice(0, 2).toUpperCase() ??
                                    'IF'}
                            </span>
                            <span className="min-w-0 flex-1 text-left">
                                <span className="block truncate text-sm font-semibold">
                                    {workspace?.name ?? 'Select workspace'}
                                </span>
                                <span className="text-muted-foreground block text-xs">
                                    {workspace?.role ?? 'Issue Flow'}
                                </span>
                            </span>
                            <ChevronsUpDown className="text-muted-foreground size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="min-w-60" align="start">
                        {workspaces.map((item) => (
                            <DropdownMenuItem key={item.id} asChild>
                                <Link
                                    href={dashboard.url({
                                        query: { workspace: item.id },
                                    })}
                                >
                                    <span className="truncate">
                                        {item.name}
                                    </span>
                                    {item.id === workspace?.id && (
                                        <Check className="ml-auto size-4" />
                                    )}
                                </Link>
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onSelect={onCreateWorkspace}>
                            <Plus className="size-4" />
                            New workspace
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarHeader>

            <SidebarContent className="gap-0 p-2">
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild>
                                    <Link href={dashboard()}>
                                        <LayoutGrid className="size-4" />
                                        <span>Overview</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>

                <SidebarGroup>
                    <SidebarGroupLabel>Projects</SidebarGroupLabel>
                    {canCreateProject && (
                        <SidebarGroupAction
                            type="button"
                            title="New project"
                            onClick={onCreateProject}
                        >
                            <Plus className="size-4" />
                            <span className="sr-only">New project</span>
                        </SidebarGroupAction>
                    )}
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {projects.length === 0 && (
                                <p className="text-muted-foreground px-2 py-2 text-xs">
                                    No accessible projects yet.
                                </p>
                            )}
                            {projects.map((item) => (
                                <SidebarMenuItem key={item.id}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={item.id === project?.id}
                                        tooltip={item.name}
                                    >
                                        <Link
                                            href={dashboard.url({
                                                query: {
                                                    workspace: workspace?.id,
                                                    project: item.id,
                                                },
                                            })}
                                        >
                                            <FolderKanban className="size-4" />
                                            <span className="min-w-0 flex-1 truncate">
                                                {item.name}
                                            </span>
                                            <span className="text-muted-foreground text-xs">
                                                {item.key}
                                            </span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter className="border-sidebar-border border-t p-2">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
