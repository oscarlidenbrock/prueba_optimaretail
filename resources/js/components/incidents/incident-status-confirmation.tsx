import { Button } from '@/components/ui/button';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { IncidentStatus } from '@/types/incidents';

export function IncidentStatusConfirmation({
    status,
    processing,
    onConfirm,
}: {
    status: IncidentStatus | null;
    processing: boolean;
    onConfirm: () => void;
}) {
    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {status === 'blocked'
                        ? 'Confirmar bloqueo'
                        : 'Confirmar resolución'}
                </DialogTitle>
                <DialogDescription>
                    ¿Quieres {status === 'blocked' ? 'bloquear' : 'resolver'}{' '}
                    esta incidencia? El cambio quedará registrado con tu nombre
                    y su fecha. Este estado no dispone de transiciones
                    posteriores.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose asChild>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={processing}
                    >
                        Cancelar
                    </Button>
                </DialogClose>
                <Button
                    type="button"
                    disabled={processing}
                    variant={status === 'blocked' ? 'destructive' : 'default'}
                    onClick={onConfirm}
                >
                    {processing ? 'Guardando…' : 'Confirmar cambio'}
                </Button>
            </DialogFooter>
        </DialogContent>
    );
}
