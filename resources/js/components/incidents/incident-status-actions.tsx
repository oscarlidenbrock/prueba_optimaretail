import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { IncidentStatusConfirmation } from '@/components/incidents/incident-status-confirmation';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Dialog, DialogTrigger } from '@/components/ui/dialog';
import { incidentStatuses } from '@/lib/incidents';
import { update } from '@/routes/incidents/status';
import type { IncidentStatus } from '@/types/incidents';

type StatusValues = { status: IncidentStatus; actor_name?: string };

export function IncidentStatusActions({
    incidentId,
    currentStatus,
    allowedTransitions,
}: {
    incidentId: number;
    currentStatus: IncidentStatus;
    allowedTransitions: IncidentStatus[];
}) {
    const form = useForm<StatusValues>({ status: currentStatus });
    const [pendingStatus, setPendingStatus] = useState<IncidentStatus | null>(
        null,
    );

    function changeStatus(status: IncidentStatus) {
        if (form.processing) {
            return;
        }
        // The server supplies available actions and revalidates the authoritative state under lock.
        form.transform(() => ({ status }));
        form.submit(update(incidentId), {
            preserveScroll: true,
            errorBag: 'statusChange',
            onSuccess: () => setPendingStatus(null),
            onError: () => setPendingStatus(null),
        });
    }

    return (
        <Card>
            <CardHeader>
                <h2 className="text-lg font-semibold">Cambiar estado</h2>
                <p className="text-sm text-muted-foreground">
                    Las acciones disponibles se validan de nuevo al guardar.
                </p>
            </CardHeader>
            <CardContent className="grid gap-3" aria-busy={form.processing}>
                <InputError message={form.errors.status} role="alert" />
                <InputError message={form.errors.actor_name} role="alert" />
                {form.recentlySuccessful && (
                    <p
                        role="status"
                        className="text-sm text-emerald-700 dark:text-emerald-300"
                    >
                        Estado actualizado correctamente.
                    </p>
                )}
                {allowedTransitions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No hay transiciones disponibles para el estado actual.
                    </p>
                ) : (
                    <Dialog
                        open={pendingStatus !== null}
                        onOpenChange={(open) => {
                            if (!open && !form.processing) {
                                setPendingStatus(null);
                            }
                        }}
                    >
                        <div className="flex flex-wrap gap-2">
                            {allowedTransitions.map((status) =>
                                status === 'blocked' ||
                                status === 'resolved' ? (
                                    <DialogTrigger key={status} asChild>
                                        <Button
                                            type="button"
                                            variant={
                                                status === 'blocked'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                            disabled={form.processing}
                                            onClick={() =>
                                                setPendingStatus(status)
                                            }
                                        >
                                            {status === 'blocked'
                                                ? 'Bloquear incidencia'
                                                : 'Resolver incidencia'}
                                        </Button>
                                    </DialogTrigger>
                                ) : (
                                    <Button
                                        key={status}
                                        type="button"
                                        disabled={form.processing}
                                        onClick={() => changeStatus(status)}
                                    >
                                        Cambiar a{' '}
                                        {incidentStatuses[
                                            status
                                        ].label.toLowerCase()}
                                    </Button>
                                ),
                            )}
                        </div>
                        <IncidentStatusConfirmation
                            status={pendingStatus}
                            processing={form.processing}
                            onConfirm={() => {
                                if (pendingStatus) {
                                    changeStatus(pendingStatus);
                                }
                            }}
                        />
                    </Dialog>
                )}
                {form.processing && (
                    <p role="status" className="text-sm text-muted-foreground">
                        Guardando el cambio de estado…
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
