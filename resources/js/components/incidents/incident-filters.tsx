import { Form, Link } from '@inertiajs/react';
import { IncidentSelect } from '@/components/incidents/incident-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { incidentPriorities, incidentStatuses } from '@/lib/incidents';
import { index } from '@/routes/incidents';
import type { IncidentFilters as Filters } from '@/types/incidents';

type FilterValues = { status: string; priority: string; q: string };

export function IncidentFilters({ filters }: { filters: Filters }) {
    return (
        <Form<FilterValues>
            {...index.form()}
            errorBag="incidentFilters"
            options={{ preserveState: true, preserveScroll: true }}
        >
            {({ processing, errors, resetAndClearErrors }) => (
                <fieldset
                    disabled={processing}
                    aria-busy={processing}
                    className="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_2fr]"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <IncidentSelect
                            id="filter-status"
                            name="status"
                            defaultValue={filters.status ?? ''}
                            onChange={(event) =>
                                event.currentTarget.form?.requestSubmit()
                            }
                            aria-invalid={!!errors.status}
                            aria-describedby={
                                errors.status
                                    ? 'filter-status-error'
                                    : undefined
                            }
                        >
                            <option value="">Todos los estados</option>
                            {Object.entries(incidentStatuses).map(
                                ([value, item]) => (
                                    <option key={value} value={value}>
                                        {item.label}
                                    </option>
                                ),
                            )}
                        </IncidentSelect>
                        <InputError
                            id="filter-status-error"
                            message={errors.status}
                            role="alert"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="filter-priority">Prioridad</Label>
                        <IncidentSelect
                            id="filter-priority"
                            name="priority"
                            defaultValue={filters.priority ?? ''}
                            onChange={(event) =>
                                event.currentTarget.form?.requestSubmit()
                            }
                            aria-invalid={!!errors.priority}
                            aria-describedby={
                                errors.priority
                                    ? 'filter-priority-error'
                                    : undefined
                            }
                        >
                            <option value="">Todas las prioridades</option>
                            {Object.entries(incidentPriorities).map(
                                ([value, item]) => (
                                    <option key={value} value={value}>
                                        {item.label}
                                    </option>
                                ),
                            )}
                        </IncidentSelect>
                        <InputError
                            id="filter-priority-error"
                            message={errors.priority}
                            role="alert"
                        />
                    </div>
                    <div className="grid gap-2 sm:col-span-2 xl:col-span-1">
                        <Label htmlFor="filter-search">
                            Buscar en título y descripción
                        </Label>
                        <Input
                            id="filter-search"
                            name="q"
                            type="search"
                            maxLength={255}
                            defaultValue={filters.q}
                            placeholder="Buscar una incidencia…"
                            aria-invalid={!!errors.q}
                            aria-describedby={
                                errors.q ? 'filter-search-error' : undefined
                            }
                        />
                        <InputError
                            id="filter-search-error"
                            message={errors.q}
                            role="alert"
                        />
                    </div>
                    <div className="flex flex-wrap gap-2 sm:col-span-2 xl:col-span-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Buscando…' : 'Aplicar filtros'}
                        </Button>
                        {processing ? (
                            <Button type="button" variant="outline" disabled>
                                Limpiar filtros
                            </Button>
                        ) : (
                            <Button variant="outline" asChild>
                                <Link
                                    href={index()}
                                    preserveState
                                    preserveScroll
                                    onSuccess={() => resetAndClearErrors()}
                                >
                                    Limpiar filtros
                                </Link>
                            </Button>
                        )}
                    </div>
                </fieldset>
            )}
        </Form>
    );
}
