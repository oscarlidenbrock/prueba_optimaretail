import type { IncidentPriority, IncidentStatus } from '@/types/incidents';

// Presentation only: transition rules remain exclusively on the server.
export const incidentStatuses = {
    open: {
        label: 'Abierta',
        className:
            'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-200',
    },
    in_review: {
        label: 'En revisión',
        className:
            'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200',
    },
    blocked: {
        label: 'Bloqueada',
        className:
            'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-200',
    },
    resolved: {
        label: 'Resuelta',
        className:
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    },
} satisfies Record<IncidentStatus, { label: string; className: string }>;

export const incidentPriorities = {
    low: {
        label: 'Baja',
        className:
            'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
    },
    medium: {
        label: 'Media',
        className:
            'border-orange-200 bg-orange-50 text-orange-900 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200',
    },
    high: {
        label: 'Alta',
        className:
            'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200',
    },
} satisfies Record<IncidentPriority, { label: string; className: string }>;

const dateFormatter = new Intl.DateTimeFormat('es-ES', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'UTC',
});

export function formatIncidentDate(value: string | null): string {
    return value ? `${dateFormatter.format(new Date(value))} UTC` : 'Sin fecha';
}
