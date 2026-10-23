import { useFlashToast } from '@/hooks/use-flash-toast';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

const icons = {
    success: <span className="font-mono text-[12px] text-accent">✓</span>,
    info: <span className="font-mono text-[12px] text-info">›</span>,
    warning: <span className="font-mono text-[12px] text-warning">!</span>,
    error: <span className="font-mono text-[12px] text-danger">×</span>,
};

function Toaster({ ...props }: ToasterProps) {
    useFlashToast();

    return (
        <Sonner
            theme="dark"
            position="bottom-center"
            icons={icons}
            toastOptions={{
                unstyled: true,
                classNames: {
                    toast: 'flex w-full items-center gap-2.5 rounded-panel border border-line bg-surface px-3.5 py-3 font-mono text-[11px] text-fg',
                    title: 'text-fg',
                    description: 'text-muted',
                    icon: 'flex-none',
                },
            }}
            {...props}
        />
    );
}

export { Toaster };
