import { IncidentDate } from '@/components/incidents/incident-date';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { incidentStatuses } from '@/lib/incidents';
import type { IncidentLog } from '@/types/incidents';

function statusLabel(value: string | null): string {
    const status = Object.entries(incidentStatuses).find(
        ([key]) => key === value,
    );
    return status?.[1].label ?? value ?? 'Sin valor';
}

export function IncidentHistory({ logs }: { logs: IncidentLog[] }) {
    return (
        <Card>
            <CardHeader>
                <h2 className="text-lg font-semibold">
                    Historial de actividad
                </h2>
                <p className="text-sm text-muted-foreground">
                    Los cambios más recientes aparecen primero.
                </p>
            </CardHeader>
            <CardContent>
                {logs.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Todavía no hay registros de actividad.
                    </p>
                ) : (
                    <ol
                        aria-label="Historial de más reciente a más antiguo"
                        className="grid gap-6 border-l-2 border-border pl-5"
                    >
                        {logs.map((log) => (
                            <li
                                key={log.id}
                                className="relative grid min-w-0 gap-2 text-sm"
                            >
                                <span
                                    aria-hidden="true"
                                    className="absolute top-1 -left-[1.6875rem] size-3 rounded-full border-2 border-background bg-primary"
                                />
                                <p className="font-medium">
                                    {log.action === 'status_changed'
                                        ? 'Cambio de estado'
                                        : log.action === 'created'
                                          ? 'Incidencia creada'
                                          : `Actividad: ${log.action}`}
                                </p>
                                {log.action === 'status_changed' ? (
                                    <p className="break-words">
                                        {statusLabel(log.old_value)}{' '}
                                        <span aria-hidden="true">→</span>
                                        <span className="sr-only">
                                            {' '}
                                            a{' '}
                                        </span>{' '}
                                        {statusLabel(log.new_value)}
                                    </p>
                                ) : (
                                    <dl className="grid gap-1">
                                        <div>
                                            <dt className="inline text-muted-foreground">
                                                Valor anterior:{' '}
                                            </dt>
                                            <dd className="inline break-words">
                                                {log.old_value ?? 'Sin valor'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="inline text-muted-foreground">
                                                Valor nuevo:{' '}
                                            </dt>
                                            <dd className="inline break-words">
                                                {log.new_value ?? 'Sin valor'}
                                            </dd>
                                        </div>
                                    </dl>
                                )}
                                <p className="break-words">
                                    Por {log.user_name}
                                </p>
                                <p className="text-muted-foreground">
                                    <IncidentDate value={log.created_at} />
                                </p>
                                {log.metadata !== null && (
                                    <details>
                                        <summary className="cursor-pointer text-muted-foreground">
                                            Datos adicionales del registro
                                        </summary>
                                        <pre className="mt-2 max-w-full overflow-x-auto rounded-md bg-muted p-3 text-xs break-words whitespace-pre-wrap">
                                            {JSON.stringify(
                                                log.metadata,
                                                null,
                                                2,
                                            )}
                                        </pre>
                                    </details>
                                )}
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
