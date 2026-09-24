import { Sparkles } from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { Project, Workspace } from '@/types/issue-flow';

export function AiPanel({
    open,
    onClose,
    workspace,
    project,
}: {
    open: boolean;
    onClose: () => void;
    workspace: Workspace | null;
    project: Project | null;
}) {
    return (
        <Sheet open={open} onOpenChange={(nextOpen) => !nextOpen && onClose()}>
            <SheetContent className="w-full gap-0 sm:max-w-md">
                <SheetHeader className="border-b px-6 py-5">
                    <SheetTitle className="flex items-center gap-2">
                        <Sparkles className="size-4" /> Ask AI
                    </SheetTitle>
                    <SheetDescription>
                        {[workspace?.name, project?.name]
                            .filter(Boolean)
                            .join(' · ') || 'No project selected'}
                    </SheetDescription>
                </SheetHeader>
                <div className="flex flex-1 flex-col items-center justify-center gap-3 px-8 text-center">
                    <span className="bg-muted grid size-12 place-items-center rounded-xl">
                        <Sparkles className="size-5" />
                    </span>
                    <h3 className="font-medium">AI workspace assistant</h3>
                    <p className="text-muted-foreground max-w-64 text-sm">
                        The chat interface is ready. Conversation and tool calls
                        will be connected when their API contract is designed.
                    </p>
                </div>
            </SheetContent>
        </Sheet>
    );
}
