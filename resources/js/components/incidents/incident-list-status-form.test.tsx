import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { IncidentListStatusForm } from '@/components/incidents/incident-list-status-form';
import type { IncidentListItem } from '@/types/incidents';

const form = vi.hoisted(() => {
    const errors: Record<string, string> = {};
    return {
        data: { status: 'in_review' },
        processing: false,
        recentlySuccessful: false,
        errors,
        transform: vi.fn(),
        submit: vi.fn(),
        setData: vi.fn(),
    };
});

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    useForm: () => form,
}));

const context = { status: null, priority: null, q: 'Printer', page: 2 };
const incident: IncidentListItem = {
    id: 42,
    title: 'Printer offline',
    description: 'Check the printer.',
    status: 'open',
    priority: 'medium',
    requester_name: 'Requester',
    assigned_to: null,
    created_at: null,
    updated_at: null,
    allowed_transitions: ['in_review'],
};

beforeEach(() => {
    form.data.status = 'in_review';
    form.processing = false;
    form.recentlySuccessful = false;
    Object.keys(form.errors).forEach((key) => delete form.errors[key]);
    vi.clearAllMocks();
});

describe('incident list status controls', () => {
    it('offers only the server destination and no confirmation for an open incident', () => {
        const html = renderToStaticMarkup(
            <IncidentListStatusForm incident={incident} context={context} />,
        );

        expect(html).toContain('value="in_review"');
        expect(html).not.toContain('value="open"');
        expect(html).not.toContain('value="blocked"');
        expect(html).toContain('Cambiar estado');
        expect(html).not.toContain('aria-haspopup="dialog"');
        expect(form.submit).not.toHaveBeenCalled();
    });

    it.each(['blocked', 'resolved'])(
        'requires the shared confirmation for the selected %s destination',
        (status) => {
            form.data.status = status;
            const html = renderToStaticMarkup(
                <IncidentListStatusForm
                    incident={{
                        ...incident,
                        status: 'in_review',
                        allowed_transitions: ['blocked', 'resolved'],
                    }}
                    context={context}
                />,
            );

            expect(html).toContain(`value="${status}" selected=""`);
            expect(html).toContain('aria-haspopup="dialog"');
            expect(html).not.toContain('value="in_review"');
            expect(form.submit).not.toHaveBeenCalled();
        },
    );

    it('disables both selector and submit button while processing', () => {
        form.processing = true;
        const html = renderToStaticMarkup(
            <IncidentListStatusForm incident={incident} context={context} />,
        );

        expect(html).toMatch(/<select[^>]*disabled=""/);
        expect(html).toMatch(/<button[^>]*disabled=""/);
        expect(html).toContain('Guardando…');
    });

    it.each(['blocked', 'resolved'] as const)(
        'shows only detail navigation for a terminal %s incident',
        (status) => {
            const html = renderToStaticMarkup(
                <IncidentListStatusForm
                    incident={{ ...incident, status, allowed_transitions: [] }}
                    context={context}
                />,
            );

            expect(html).toContain('href="/incidents/42"');
            expect(html).toContain('Ver detalle');
            expect(html).not.toContain('<select');
            expect(html).not.toContain('Cambiar estado');
        },
    );

    it('keeps conflict feedback visible after refreshed data becomes terminal', () => {
        form.errors.status =
            'El cambio de estado solicitado no está permitido para el estado actual de la incidencia.';
        const html = renderToStaticMarkup(
            <IncidentListStatusForm
                incident={{
                    ...incident,
                    status: 'resolved',
                    allowed_transitions: [],
                }}
                context={context}
            />,
        );

        expect(html).toContain(form.errors.status);
        expect(html).toContain('role="alert"');
        expect(html).toContain('Ver detalle');
    });

    it('reconciles a preserved selection with refreshed server destinations', () => {
        form.data.status = 'in_review';
        const html = renderToStaticMarkup(
            <IncidentListStatusForm
                incident={{
                    ...incident,
                    status: 'in_review',
                    allowed_transitions: ['blocked', 'resolved'],
                }}
                context={context}
            />,
        );

        expect(html).toContain('value="blocked" selected=""');
        expect(html).not.toContain('value="in_review"');
    });

    it('shows a successful update confirmation', () => {
        form.recentlySuccessful = true;
        const html = renderToStaticMarkup(
            <IncidentListStatusForm incident={incident} context={context} />,
        );

        expect(html).toContain('Estado actualizado correctamente.');
        expect(html).toContain('role="status"');
    });
});
