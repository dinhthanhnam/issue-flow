import { useFlashToast } from "@/hooks/use-flash-toast";
import { useAppearance } from "@/hooks/use-appearance";
import { Check, CircleAlert } from "lucide-react";
import { Toaster as Sonner, type ToasterProps } from "sonner";

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();

    return (
        <Sonner
            theme={appearance}
            className="toaster group"
            position="bottom-center"
            icons={{
                success: <Check className="size-4" />,
                error: <CircleAlert className="size-4" />,
            }}
            toastOptions={{
                unstyled: true,
                classNames: {
                    toast: "bg-foreground text-background flex items-center gap-2 rounded-lg border border-foreground/10 px-4 py-3 text-sm shadow-lg",
                    title: "font-medium",
                    description: "text-background/70",
                    actionButton:
                        "bg-background text-foreground ml-auto rounded-md px-2 py-1 text-xs font-medium",
                    cancelButton:
                        "text-background/70 ml-auto rounded-md px-2 py-1 text-xs font-medium",
                },
            }}
            {...props}
        />
    );
}

export { Toaster };
