import { Form } from '@inertiajs/react';
import { IncidentDate } from '@/components/incidents/incident-date';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/incidents/comments';
import type { IncidentComment } from '@/types/incidents';

type CommentValues = { body: string; author_name?: string };

export function IncidentComments({
    incidentId,
    comments,
}: {
    incidentId: number;
    comments: IncidentComment[];
}) {
    return (
        <Card>
            <CardHeader>
                <h2 className="text-lg font-semibold">
                    Comentarios operativos
                </h2>
            </CardHeader>
            <CardContent className="grid gap-6">
                {comments.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Todavía no hay comentarios. Añade el primero para dejar
                        constancia del seguimiento.
                    </p>
                ) : (
                    <ol
                        className="grid gap-4"
                        aria-label="Comentarios de más antiguo a más reciente"
                    >
                        {comments.map((comment) => (
                            <li
                                key={comment.id}
                                className="grid min-w-0 gap-2 rounded-lg border p-4 text-sm"
                            >
                                <p className="font-medium break-words">
                                    {comment.author_name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    <IncidentDate value={comment.created_at} />
                                </p>
                                <p className="break-words whitespace-pre-wrap">
                                    {comment.body}
                                </p>
                            </li>
                        ))}
                    </ol>
                )}
                <Form<CommentValues>
                    {...store.form(incidentId)}
                    noValidate
                    resetOnSuccess
                    errorBag="addComment"
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <fieldset
                            className="grid min-w-0 gap-3"
                            disabled={processing}
                            aria-busy={processing}
                        >
                            <Label htmlFor="comment-body">
                                Añadir comentario
                            </Label>
                            <textarea
                                id="comment-body"
                                name="body"
                                required
                                maxLength={10000}
                                rows={4}
                                aria-invalid={!!errors.body}
                                aria-describedby="comment-help comment-body-error"
                                className="w-full min-w-0 resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50 aria-invalid:border-destructive"
                            />
                            <p
                                id="comment-help"
                                className="text-xs text-muted-foreground"
                            >
                                Máximo 10.000 caracteres. Se firmará con tu
                                nombre de usuario.
                            </p>
                            <InputError
                                id="comment-body-error"
                                message={errors.body}
                                role="alert"
                            />
                            <InputError
                                message={errors.author_name}
                                role="alert"
                            />
                            <Button
                                className="w-fit"
                                type="submit"
                                disabled={processing}
                            >
                                {processing
                                    ? 'Añadiendo comentario…'
                                    : 'Añadir comentario'}
                            </Button>
                            {recentlySuccessful && (
                                <p
                                    role="status"
                                    className="text-sm text-emerald-700 dark:text-emerald-300"
                                >
                                    Comentario añadido correctamente.
                                </p>
                            )}
                        </fieldset>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
