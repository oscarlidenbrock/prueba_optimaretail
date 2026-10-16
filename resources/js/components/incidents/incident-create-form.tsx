import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { IncidentSelect } from '@/components/incidents/incident-select';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { incidentPriorities } from '@/lib/incidents';
import { store } from '@/routes/incidents';
import type { CreateIncidentValues } from '@/types/incidents';

function Field({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError id={`${id}-error`} message={error} role="alert" />
        </div>
    );
}

export function IncidentCreateForm() {
    return (
        <Card id="new-incident" className="scroll-mt-6">
            <CardHeader>
                <h2 className="text-lg font-semibold">Nueva incidencia</h2>
                <CardDescription>
                    Describe el problema para iniciar su seguimiento. Se
                    registrará como abierta.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form<CreateIncidentValues>
                    {...store.form()}
                    noValidate
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    errorBag="createIncident"
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <fieldset
                            disabled={processing}
                            aria-busy={processing}
                            className="grid min-w-0 gap-4"
                        >
                            <Field
                                id="incident-title"
                                label="Título"
                                error={errors.title}
                            >
                                <Input
                                    id="incident-title"
                                    name="title"
                                    required
                                    maxLength={255}
                                    aria-invalid={!!errors.title}
                                    aria-describedby={
                                        errors.title
                                            ? 'incident-title-error'
                                            : undefined
                                    }
                                />
                            </Field>
                            <Field
                                id="incident-description"
                                label="Descripción"
                                error={errors.description}
                            >
                                <textarea
                                    id="incident-description"
                                    name="description"
                                    required
                                    maxLength={10000}
                                    rows={4}
                                    aria-invalid={!!errors.description}
                                    aria-describedby={
                                        errors.description
                                            ? 'incident-description-error'
                                            : undefined
                                    }
                                    className="w-full min-w-0 resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50 aria-invalid:border-destructive"
                                />
                            </Field>
                            <Field
                                id="incident-priority"
                                label="Prioridad"
                                error={errors.priority}
                            >
                                <IncidentSelect
                                    id="incident-priority"
                                    name="priority"
                                    defaultValue="medium"
                                    required
                                    aria-invalid={!!errors.priority}
                                    aria-describedby={
                                        errors.priority
                                            ? 'incident-priority-error'
                                            : undefined
                                    }
                                >
                                    {Object.entries(incidentPriorities).map(
                                        ([value, item]) => (
                                            <option key={value} value={value}>
                                                {item.label}
                                            </option>
                                        ),
                                    )}
                                </IncidentSelect>
                            </Field>
                            <Field
                                id="incident-requester"
                                label="Solicitante"
                                error={errors.requester_name}
                            >
                                <Input
                                    id="incident-requester"
                                    name="requester_name"
                                    required
                                    maxLength={120}
                                    autoComplete="name"
                                    aria-invalid={!!errors.requester_name}
                                    aria-describedby={
                                        errors.requester_name
                                            ? 'incident-requester-error'
                                            : undefined
                                    }
                                />
                            </Field>
                            <Field
                                id="incident-assignee"
                                label="Responsable (opcional)"
                                error={errors.assigned_to}
                            >
                                <Input
                                    id="incident-assignee"
                                    name="assigned_to"
                                    maxLength={120}
                                    aria-invalid={!!errors.assigned_to}
                                    aria-describedby={
                                        errors.assigned_to
                                            ? 'incident-assignee-error'
                                            : undefined
                                    }
                                />
                            </Field>
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? 'Creando incidencia…'
                                    : 'Crear incidencia'}
                            </Button>
                            {recentlySuccessful && (
                                <p
                                    role="status"
                                    className="text-sm text-emerald-700 dark:text-emerald-300"
                                >
                                    Incidencia creada correctamente.
                                </p>
                            )}
                        </fieldset>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
