import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatIncidentDate,
    incidentPriorities,
    incidentStatuses,
} from '@/lib/incidents';
import { index, show } from '@/routes/incidents';
import type {
    Incident,
    IncidentFilters,
    IncidentPagination,
} from '@/types/incidents';

function IncidentBadges({ incident }: { incident: Incident }) {
    return (
        <div className="flex flex-wrap gap-2">
            <Badge
                variant="outline"
                className={incidentStatuses[incident.status].className}
            >
                {incidentStatuses[incident.status].label}
            </Badge>
            <Badge
                variant="outline"
                className={incidentPriorities[incident.priority].className}
            >
                Prioridad{' '}
                {incidentPriorities[incident.priority].label.toLowerCase()}
            </Badge>
        </div>
    );
}

function IncidentTitle({ incident }: { incident: Incident }) {
    return (
        <Link
            href={show(incident.id)}
            className="font-medium break-words underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-ring"
        >
            {incident.title}
            <span className="sr-only">
                {' '}
                — Ver detalle de la incidencia {incident.id}
            </span>
        </Link>
    );
}

export function IncidentList({
    incidents,
    filters,
}: {
    incidents: IncidentPagination;
    filters: IncidentFilters;
}) {
    const filtered = !!(filters.status || filters.priority || filters.q);

    return (
        <div className="grid min-w-0 gap-4">
            <p role="status" className="text-sm text-muted-foreground">
                {incidents.total === 0
                    ? '0 incidencias'
                    : `${incidents.from ?? 0}–${incidents.to ?? 0} de ${incidents.total} incidencias`}
            </p>
            {incidents.data.length === 0 ? (
                <div className="rounded-lg border border-dashed px-4 py-12 text-center">
                    <h3 className="font-medium">
                        {filtered
                            ? 'No hay resultados para estos filtros'
                            : incidents.total > 0
                              ? 'No hay incidencias en esta página'
                              : 'Todavía no hay incidencias'}
                    </h3>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {filtered
                            ? 'Prueba otra búsqueda o limpia los filtros.'
                            : incidents.total > 0
                              ? 'Esta página no contiene resultados. Vuelve a una página anterior.'
                              : 'Utiliza el formulario de nueva incidencia para empezar.'}
                    </p>
                </div>
            ) : (
                <>
                    <div className="hidden overflow-x-auto rounded-lg border md:block">
                        <table className="w-full text-left text-sm">
                            <caption className="sr-only">
                                Incidencias ordenadas de más reciente a más
                                antigua
                            </caption>
                            <thead className="border-b bg-muted/50 text-muted-foreground">
                                <tr>
                                    {[
                                        'Incidencia',
                                        'Prioridad',
                                        'Estado',
                                        'Fecha (UTC)',
                                        'Personas',
                                    ].map((title) => (
                                        <th
                                            key={title}
                                            scope="col"
                                            className="px-4 py-3 font-medium"
                                        >
                                            {title}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {incidents.data.map((incident) => (
                                    <tr
                                        key={incident.id}
                                        className="align-top hover:bg-muted/30"
                                    >
                                        <td className="max-w-xs px-4 py-4">
                                            <IncidentTitle
                                                incident={incident}
                                            />
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                #{incident.id}
                                            </p>
                                        </td>
                                        <td className="px-4 py-4">
                                            <Badge
                                                variant="outline"
                                                className={
                                                    incidentPriorities[
                                                        incident.priority
                                                    ].className
                                                }
                                            >
                                                {
                                                    incidentPriorities[
                                                        incident.priority
                                                    ].label
                                                }
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-4">
                                            <Badge
                                                variant="outline"
                                                className={
                                                    incidentStatuses[
                                                        incident.status
                                                    ].className
                                                }
                                            >
                                                {
                                                    incidentStatuses[
                                                        incident.status
                                                    ].label
                                                }
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-4">
                                            <time
                                                dateTime={
                                                    incident.created_at ??
                                                    undefined
                                                }
                                            >
                                                {formatIncidentDate(
                                                    incident.created_at,
                                                )}
                                            </time>
                                        </td>
                                        <td className="max-w-48 px-4 py-4 break-words">
                                            <p>
                                                Solicitante:{' '}
                                                {incident.requester_name}
                                            </p>
                                            <p className="mt-1 text-muted-foreground">
                                                Responsable:{' '}
                                                {incident.assigned_to ??
                                                    'Sin asignar'}
                                            </p>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <ul
                        className="grid gap-3 md:hidden"
                        aria-label="Incidencias de más reciente a más antigua"
                    >
                        {incidents.data.map((incident) => (
                            <li
                                key={incident.id}
                                className="grid min-w-0 gap-3 rounded-lg border p-4"
                            >
                                <div className="min-w-0">
                                    <p className="mb-1 text-xs text-muted-foreground">
                                        Incidencia #{incident.id}
                                    </p>
                                    <IncidentTitle incident={incident} />
                                </div>
                                <IncidentBadges incident={incident} />
                                <dl className="grid gap-2 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Fecha de creación
                                        </dt>
                                        <dd>
                                            <time
                                                dateTime={
                                                    incident.created_at ??
                                                    undefined
                                                }
                                            >
                                                {formatIncidentDate(
                                                    incident.created_at,
                                                )}
                                            </time>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Solicitante
                                        </dt>
                                        <dd className="break-words">
                                            {incident.requester_name}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Responsable
                                        </dt>
                                        <dd className="break-words">
                                            {incident.assigned_to ??
                                                'Sin asignar'}
                                        </dd>
                                    </div>
                                </dl>
                            </li>
                        ))}
                    </ul>
                </>
            )}
            {(incidents.last_page > 1 || incidents.current_page > 1) && (
                <nav
                    aria-label="Paginación de incidencias"
                    className="flex flex-wrap items-center justify-between gap-3 border-t pt-4"
                >
                    <p className="text-sm text-muted-foreground">
                        Página {incidents.current_page} de {incidents.last_page}
                    </p>
                    <div className="flex gap-2">
                        {incidents.prev_page_url ? (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={index({
                                        query: {
                                            ...filters,
                                            page: incidents.current_page - 1,
                                        },
                                    })}
                                    preserveState
                                    preserveScroll
                                >
                                    Anterior
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" size="sm" disabled>
                                Anterior
                            </Button>
                        )}
                        {incidents.next_page_url ? (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={index({
                                        query: {
                                            ...filters,
                                            page: incidents.current_page + 1,
                                        },
                                    })}
                                    preserveState
                                    preserveScroll
                                >
                                    Siguiente
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" size="sm" disabled>
                                Siguiente
                            </Button>
                        )}
                    </div>
                </nav>
            )}
        </div>
    );
}
