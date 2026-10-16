import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

export function IncidentSelect({
    className,
    ...props
}: ComponentProps<'select'>) {
    return (
        <select
            {...props}
            className={cn(
                'h-9 w-full min-w-0 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50 disabled:opacity-50 aria-invalid:border-destructive',
                className,
            )}
        />
    );
}
