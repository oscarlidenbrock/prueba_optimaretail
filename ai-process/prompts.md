# Registro de peticiones

## 2026-10-16 — Pruebas aisladas en MySQL 5.7.44

- **Resumen fiel del prompt (parte de programación):** proteger la ejecución de pruebas comprobando la base efectiva antes de ejecutar operaciones que puedan migrar o limpiar tablas; impedir que las pruebas actúen sobre `prueba_optimaretail`.
- **Cambios de programación:** añadida protección en `tests/TestCase.php`, anterior a `RefreshDatabase`, mediante validación del entorno, archivo de configuración, driver y nombre de base, y `SELECT DATABASE()` sobre las conexiones de lectura y escritura. Corregida la comprobación del posible retorno `false` de `PDO::query()` antes de llamar a `fetchColumn()`.
- **Decisión técnica:** detener las pruebas antes de cualquier migración o reinicio si la configuración o la conexión efectiva no corresponden a MySQL `prueba_optimaretail_test`, para proteger los datos de desarrollo.
- **Revisión manual:** pendiente de confirmación del usuario.

## 2026-10-16 — Corregir el contrato de navegación de ajustes

- **Resumen fiel del prompt:** revisar el error de Wayfinder asociado a la redirección de ajustes y limitarla a GET/HEAD si solo sirve para navegar, conservando su destino y comportamiento, sin alterar los tipos para ocultar el defecto.
- **Cambios de programación:** sustituido el registro para todos los verbos por GET/HEAD, conservando el controlador de redirección de Laravel, `/settings/profile`, el estado 302 y el middleware de autenticación. Añadida cobertura del contrato de navegación y del rechazo de métodos no admitidos.
- **Decisión:** una ruta que solo navega no debe aceptar operaciones de escritura ni QUERY. La corrección se aplica a su definición, conservando el contrato tipado existente.
- **Revisión manual:** pendiente de confirmación del usuario.

## 2026-10-16 — Completar el estado `withTwoFactor()`

- **Resumen fiel del prompt:** revisar el método y sus referencias; eliminarlo si es un resto innecesario o implementar correctamente su estado y retorno si debe conservarse, sin activar 2FA ni ocultar el defecto.
- **Cambios de programación:** implementado un estado nuevo de factory con secreto válido, códigos de recuperación cifrados y fecha de confirmación. Añadida cobertura del estado sin persistirlo ni habilitar la función.
- **Decisión:** conservar el método porque un test condicional heredado todavía lo referencia; mantener ese contrato y corregir su implementación, sin eliminar tests ni devolver simplemente la factory original.
- **Revisión manual:** pendiente de confirmación del usuario.

## 2026-10-16 — Modelo de datos del panel de incidencias

- **Resumen fiel del prompt:** implementar exclusivamente migraciones, modelos y relaciones de incidencias, logs y comentarios; enums PHP para los cuatro estados y tres prioridades persistidos en VARCHAR; responsable como nombre opcional, metadata JSON nullable y valores auditados nullable; FKs, índices justificados, logs con solo `created_at` y tests focalizados. Sin CRUD, transiciones, controladores, seeders ni frontend.
- **Cambios de programación:** tres migraciones y modelos, dos enums, tres factories de pruebas y cobertura del esquema, relaciones, integridad referencial, casts, defaults y timestamps. `UPDATED_AT = null` evita que Eloquent intente escribir un timestamp inexistente en logs. Las relaciones ordenan por fecha e ID para dar un resultado determinista.
- **Decisiones técnicas:** IDs compatibles `BIGINT UNSIGNED`, InnoDB y `utf8mb4_unicode_ci`; borrado y actualización del ID del padre restringidos para conservar evidencia. Índices fecha, estado/fecha y prioridad/fecha para consultas previstas; incidencia/fecha para historiales y FKs sin índices duplicados. Defaults `open` y `medium`; título de 255 caracteres, nombres de 120 y acción de 64. Valores auditados TEXT para admitir distintos tipos de acción; metadata se convierte a array. Las migraciones guardan defaults literales para no depender de la evolución de los enums.
- **Decisiones confirmadas por el usuario:** valores de estados/prioridades, almacenamiento VARCHAR, responsable textual opcional, nulabilidad del JSON y valores auditados, y timestamp único del log. Los tamaños, defaults, índices y política restrictiva son decisiones de implementación pendientes de revisión manual.

## 2026-10-16 — Transiciones de estado con auditoría transaccional

- **Resumen fiel del prompt:** centralizar en `IncidentStatus` exclusivamente `open → in_review` e `in_review → blocked/resolved`; rechazar otros cambios y repeticiones. Crear `ChangeIncidentStatus` con transacción y bloqueo, consultar el estado actual antes de validar y guardar estado y log `status_changed` con valores, actor y fecha. Propagar fallos con rollback y añadir tests focalizados. Sin controladores, rutas, frontend ni dependencias nuevas.
- **Cambios de programación:** método `canTransitionTo()`, acción `handle()` y tests de las 16 combinaciones de estados, repetición, instancias desactualizadas, auditoría, actor, incidencia ausente y rollback ante errores SQL, fallos posteriores a la inserción y cancelaciones de escrituras por eventos Eloquent.
- **Decisiones técnicas:** consultar una instancia nueva bajo bloqueo para no confiar en el estado del llamador y devolver esa instancia tras guardar. Excepciones de validación con mensajes en español para transiciones y actor; nombre recortado, obligatorio y limitado a 120 caracteres según el esquema. Fecha automática del modelo de logs; excepciones de persistencia sin capturar y cancelaciones silenciosas convertidas en errores para mantener la atomicidad.
- **Decisiones confirmadas por el usuario:** matriz de transiciones, rechazo de repetidos, acción transaccional con `lockForUpdate()` y contrato del log. Validación del actor, tipos de excepción y retorno de una instancia nueva son decisiones de implementación pendientes de revisión manual.

## 2026-10-16 — Backend de creación y listado de incidencias

- **Resumen fiel del prompt:** implementar rutas y controladores Laravel, creación validada con Form Request y estado inicial `open` impuesto por servidor, listado con filtros combinables de estado/prioridad/texto en título y descripción, orden descendente por fecha e ID y paginación conservando filtros. Mantener autenticación y proteger rutas; añadir tests focalizados sin componentes React, cambios de estado HTTP, historial, comentarios, diagnóstico ni dependencias nuevas.
- **Cambios de programación:** controlador con `index`/`store`, rutas resource limitadas a esas operaciones, dos Form Requests y scope Eloquent para filtros agrupados. Solo se usan campos validados; se rechaza cualquier presencia de `status` al crear. Mensajes de validación en español y cobertura de creación, límites, estado no manipulable, filtros, búsqueda literal, paginación y acceso autenticado.
- **Decisiones técnicas:** reutilizar `auth`/`verified`, respuestas Inertia y redirección según el starter kit. Preparar el contrato de `incidents/index` sin crear aún el componente; el renderizado queda pendiente. Paginación fija de 15 registros con parámetros validados, búsqueda literal con `LOCATE` enlazado, descripción limitada a 10000 caracteres y página limitada para evitar desbordamientos. Mantener la auditoría actual de cambios de estado sin añadir un log de creación en esta fase. No se extraen servicios para operaciones pequeñas.
- **Decisiones confirmadas por el usuario:** campos requeridos/opcional, estado inicial impuesto en servidor, filtros, orden, paginación, protección autenticada y alcance exclusivamente backend. El contrato Inertia sin página, tamaños adicionales, tamaño de página y política de auditoría de creación son decisiones de implementación pendientes de revisión manual; no se atribuye una respuesta a la consulta opcional de formato del listado.
