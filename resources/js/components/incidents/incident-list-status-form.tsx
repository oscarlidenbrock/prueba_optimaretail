import { Link, useForm } from '@inertiajs/react';
import { useId, useState } from 'react';
import { IncidentSelect } from '@/components/incidents/incident-select';
import { IncidentStatusConfirmation } from '@/components/incidents/incident-status-confirmation';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { incidentStatuses } from '@/lib/incidents';
import { show } from '@/routes/incidents';
import { update } from '@/routes/incidents/status';
import type {
    IncidentListContext,
    IncidentListItem,
    IncidentStatus,
} from '@/types/incidents';

type StatusValues = {
    status: IncidentStatus;
    list_context: IncidentListContext;
    actor_name?: string;
};

export function IncidentListStatusForm({
    incident,
    context,
}: {
    incident: IncidentListItem;
    context: IncidentListContext;
}) {
    const selectId = useId();
    const form = useForm<StatusValues>({
        status: incident.allowed_transitions[0] ?? incident.status,
        list_context: context,
    });
    const [pendingStatus, setPendingStatus] = useState<IncidentStatus | null>(
        null,
    );
    // Reconcile a preserved form with refreshed server options after another operator changes state.
    const selectedStatus =
        incident.allowed_transitions.find(
            (status) => status === form.data.status,
        ) ?? incident.allowed_transitions[0];
    const needsConfirmation =
        selectedStatus === 'blocked' || selectedStatus === 'resolved';

    function changeStatus(status: IncidentStatus) {
        if (form.processing) {
            return;
        }

        form.transform(() => ({ status, list_context: context }));
        form.submit(update(incident.id), {
            preserveState: true,
            preserveScroll: true,
            errorBag: `incidentStatus${incident.id}`,
            onSuccess: () => setPendingStatus(null),
            onError: () => setPendingStatus(null),
        });
    }

    const submitButton = (
        <Button type="submit" size="sm" disabled={form.processing}>
            {form.processing ? 'Guardando…' : 'Cambiar estado'}
        </Button>
    );

    return (
        <div className="grid gap-2" aria-busy={form.processing}>
            {Object.entries(form.errors).map(([field, message]) => (
                <InputError key={field} message={message} role="alert" />
            ))}
            {form.recentlySuccessful && (
                <p
                    role="status"
                    className="text-sm text-emerald-700 dark:text-emerald-300"
                >
                    Estado actualizado correctamente.
                </p>
            )}
            {selectedStatus ? (
                <Dialog
                    open={pendingStatus !== null}
                    onOpenChange={(open) => {
                        if (!open && !form.processing) {
                            setPendingStatus(null);
                        }
                    }}
                >
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            if (form.processing) {
                                return;
                            }
                            if (needsConfirmation) {
                                setPendingStatus(selectedStatus);
                            } else {
                                changeStatus(selectedStatus);
                            }
                        }}
                        className="flex flex-wrap items-end gap-2"
                    >
                        <div className="grid min-w-0 gap-1">
                            <Label htmlFor={selectId} className="sr-only">
                                Nuevo estado de la incidencia {incident.id}
                            </Label>
                            <IncidentSelect
                                id={selectId}
                                value={selectedStatus}
                                disabled={form.processing}
                                aria-invalid={!!form.errors.status}
                                onChange={(event) => {
                                    const status =
                                        incident.allowed_transitions.find(
                                            (value) =>
                                                value === event.target.value,
                                        );
                                    if (status) {
                                        form.setData('status', status);
                                    }
                                }}
                            >
                                {incident.allowed_transitions.map((status) => (
                                    <option key={status} value={status}>
                                        {incidentStatuses[status].label}
                                    </option>
                                ))}
                            </IncidentSelect>
                        </div>
                        {needsConfirmation ? (
                            <DialogTrigger
                                asChild
                                onClick={() => setPendingStatus(selectedStatus)}
                            >
                                {submitButton}
                            </DialogTrigger>
                        ) : (
                            submitButton
                        )}
                    </form>
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
            ) : (
                <Button variant="outline" size="sm" className="w-fit" asChild>
                    <Link href={show(incident.id)}>Ver detalle</Link>
                </Button>
            )}
        </div>
    );
}
