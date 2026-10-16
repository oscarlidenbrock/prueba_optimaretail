import { Head, Link } from '@inertiajs/react';
import { IncidentComments } from '@/components/incidents/incident-comments';
import { IncidentDate } from '@/components/incidents/incident-date';
import { IncidentHistory } from '@/components/incidents/incident-history';
import { IncidentStatusActions } from '@/components/incidents/incident-status-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { incidentPriorities, incidentStatuses } from '@/lib/incidents';
import { index } from '@/routes/incidents';
import type { IncidentDetail, IncidentStatus } from '@/types/incidents';

type Props = { incident: IncidentDetail; allowedTransitions: IncidentStatus[] };

export default function IncidentShow({ incident, allowedTransitions }: Props) {
    return (
        <>
            <Head title={`Incidencia #${incident.id} — ${incident.title}`} />
            <div className="mx-auto flex w-full max-w-screen-2xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="grid min-w-0 gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="w-fit"
                    >
                        <Link href={index()}>Volver al listado</Link>
                    </Button>
                    <p className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        Incidencia #{incident.id}
                    </p>
                    <h1 className="text-2xl font-semibold break-words sm:text-3xl">
                        {incident.title}
                    </h1>
                    <div className="flex flex-wrap gap-2">
                        <Badge
                            variant="outline"
                            className={
                                incidentStatuses[incident.status].className
                            }
                        >
                            {incidentStatuses[incident.status].label}
                        </Badge>
                        <Badge
                            variant="outline"
                            className={
                                incidentPriorities[incident.priority].className
                            }
                        >
                            Prioridad{' '}
                            {incidentPriorities[
                                incident.priority
                            ].label.toLowerCase()}
                        </Badge>
                    </div>
                </header>
                <div className="grid min-w-0 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="grid min-w-0 gap-6">
                        <Card>
                            <CardHeader>
                                <h2 className="text-lg font-semibold">
                                    Información de la incidencia
                                </h2>
                            </CardHeader>
                            <CardContent className="grid gap-6">
                                <p className="text-sm leading-relaxed break-words whitespace-pre-wrap">
                                    {incident.description}
                                </p>
                                <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                    <div className="grid gap-1">
                                        <dt className="text-muted-foreground">
                                            Solicitante
                                        </dt>
                                        <dd className="break-words">
                                            {incident.requester_name}
                                        </dd>
                                    </div>
                                    <div className="grid gap-1">
                                        <dt className="text-muted-foreground">
                                            Responsable
                                        </dt>
                                        <dd className="break-words">
                                            {incident.assigned_to ??
                                                'Sin asignar'}
                                        </dd>
                                    </div>
                                    <div className="grid gap-1">
                                        <dt className="text-muted-foreground">
                                            Creación
                                        </dt>
                                        <dd>
                                            <IncidentDate
                                                value={incident.created_at}
                                            />
                                        </dd>
                                    </div>
                                    <div className="grid gap-1">
                                        <dt className="text-muted-foreground">
                                            Última actualización
                                        </dt>
                                        <dd>
                                            <IncidentDate
                                                value={incident.updated_at}
                                            />
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                        <IncidentStatusActions
                            key={incident.id}
                            incidentId={incident.id}
                            currentStatus={incident.status}
                            allowedTransitions={allowedTransitions}
                        />
                        <IncidentComments
                            key={incident.id}
                            incidentId={incident.id}
                            comments={incident.comments}
                        />
                    </div>
                    <IncidentHistory logs={incident.logs} />
                </div>
            </div>
        </>
    );
}

IncidentShow.layout = {
    breadcrumbs: [
        { title: 'Incidencias', href: index() },
        { title: 'Detalle' },
    ],
};
