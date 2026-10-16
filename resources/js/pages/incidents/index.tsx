import { Head } from '@inertiajs/react';
import { IncidentCreateForm } from '@/components/incidents/incident-create-form';
import { IncidentFilters } from '@/components/incidents/incident-filters';
import { IncidentList } from '@/components/incidents/incident-list';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { index } from '@/routes/incidents';
import type {
    IncidentFilters as Filters,
    IncidentPagination,
} from '@/types/incidents';

type Props = { incidents: IncidentPagination; filters: Filters };

export default function IncidentsIndex({ incidents, filters }: Props) {
    return (
        <>
            <Head title="Panel operativo de incidencias" />
            <div className="mx-auto flex w-full max-w-screen-2xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="grid gap-2">
                    <p className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        Seguimiento operativo
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Panel operativo de incidencias
                    </h1>
                    <p className="max-w-2xl text-sm text-muted-foreground">
                        Registra problemas, consulta su estado y localiza las
                        incidencias que necesitan atención.
                    </p>
                    <Button asChild className="mt-2 w-fit 2xl:hidden">
                        <a href="#new-incident">Nueva incidencia</a>
                    </Button>
                </header>
                <div className="grid min-w-0 items-start gap-6 2xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <Card className="min-w-0">
                        <CardHeader>
                            <h2 className="text-lg font-semibold">
                                Incidencias
                            </h2>
                        </CardHeader>
                        <CardContent className="grid min-w-0 gap-6">
                            <IncidentFilters
                                key={JSON.stringify(filters)}
                                filters={filters}
                            />
                            <IncidentList
                                incidents={incidents}
                                filters={filters}
                            />
                        </CardContent>
                    </Card>
                    <IncidentCreateForm />
                </div>
            </div>
        </>
    );
}

IncidentsIndex.layout = {
    breadcrumbs: [{ title: 'Incidencias', href: index() }],
};
