import { useSyncExternalStore } from 'react';
import { formatIncidentDate } from '@/lib/incidents';

function subscribe() {
    return () => {};
}

function localTimeZone() {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export function IncidentDate({ value }: { value: string | null }) {
    // Keep the initial server render stable; use the browser timezone after hydration.
    const timeZone = useSyncExternalStore(
        subscribe,
        localTimeZone,
        () => 'UTC',
    );
    if (!value) {
        return <span>Sin fecha</span>;
    }
    const localDate = new Intl.DateTimeFormat('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone,
        timeZoneName: 'short',
    }).format(new Date(value));

    return (
        <time
            dateTime={value}
            title={`Almacenamiento UTC: ${formatIncidentDate(value)}`}
        >
            {localDate}{' '}
            <span className="text-xs text-muted-foreground">({timeZone})</span>
        </time>
    );
}
