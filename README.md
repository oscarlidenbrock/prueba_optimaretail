# Panel operativo de incidencias

Aplicación para consultar y gestionar incidencias, sus estados, comentarios e historial. Están implementados el modelo de datos, el backend, la pantalla principal React con listado/filtros/creación, el detalle operativo y el diagnóstico con fixtures independientes y evaluación conservadora de reparaciones. Los casos actuales requieren revisión humana; no hay reparaciones automáticas justificadas. Las incidencias normales se crean desde el formulario; no se incluye un seeder normal de incidencias. Se conservan las pantallas y la autenticación del starter kit.

Los requisitos están en [ai-process/technical-assessment-scope.md](ai-process/technical-assessment-scope.md), la fuente original en [docs/prueba_tecnica_mid_senior.pdf](docs/prueba_tecnica_mid_senior.pdf) y el progreso en [docs/development.md](docs/development.md). El uso de IA se documenta en [ai-process/ai-process.md](ai-process/ai-process.md) y las peticiones en [ai-process/prompts.md](ai-process/prompts.md).

## Tecnologías y requisitos

Versiones comprobadas en el entorno local el 16 de octubre de 2026:

| Herramienta            | Versión comprobada     |
| ---------------------- | ---------------------- |
| PHP CLI                | 8.5.0, con `pdo_mysql` |
| Composer               | 2.8.12                 |
| Node.js / npm          | 24.19.0 / 11.17.0      |
| MySQL Community Server | 5.7.44                 |

Dependencias instaladas: Laravel 13.36.0, Inertia Laravel 3.5.1, Inertia React 3.9.1, React 19.3.1, TypeScript 5.9.3, Tailwind CSS 4.3.3 y Vite 8.3.4. Se utiliza Pest 5.3.2 para las pruebas. Conservar `composer.lock` y `package-lock.json` para instalar las versiones resueltas.

Se mantiene Vite Plus 0.3.0 con Oxfmt 0.64.0 y un override de npm limitado a `oxfmt → tinypool@2.1.2`, que corrige los avisos críticos de Tinypool sin sustituir Vite 8.3.4 ni el resto del toolchain. No retirar ese override sin comprobar ambos audits y la compatibilidad de la herramienta. La alternativa Vite Plus 0.3.2 con alias del core produjo un conflicto de peers en `npm ci`; el razonamiento, la comprobación aislada y los resultados finales están en [el registro de desarrollo](docs/development.md).

Usar estas versiones para reproducir el entorno; no se acredita compatibilidad con otras versiones de MySQL. El proyecto no utiliza Next.js. Redis y Docker no son necesarios para esta instalación.

## Instalación local

Partir de un clon del repositorio, con PHP, Composer, Node/npm y acceso a un servidor MySQL. PHP debe incluir `pdo_mysql`; el usuario que ejecute Laravel necesita permisos de escritura en `storage/` y `bootstrap/cache/`. La instalación de dependencias y la primera compilación de fuentes necesitan acceso a Internet.

Estos pasos describen una instalación nueva con una base vacía, por ejemplo `panel_incidents_local`. En este entorno, la base de desarrollo existente es `prueba_optimaretail`: se permite aplicar migraciones normales después de verificar la conexión efectiva y revisar las pendientes. No ejecutar sobre ella reinicios destructivos (`migrate:fresh`, `migrate:refresh`, `db:wipe`), limpieza de datos ni pruebas. No repetir pasos de creación o configuración ya completados.

1. Abrir una terminal en la raíz del repositorio e instalar las dependencias:

    ```bash
    composer install
    npm ci
    ```

2. Crear el archivo local de configuración solo si todavía no existe:

    ```bash
    cp .env.example .env
    ```

    No sobrescribir un `.env` existente. La plantilla no incluye credenciales reales. Configurar `APP_NAME`, `APP_ENV=local`, `APP_URL` y, para la interfaz del proyecto, `APP_LOCALE=es`. La traducción de las pantallas heredadas del starter kit sigue pendiente.

3. Asegurarse de que el servidor MySQL esté iniciado y accesible. Para una instalación nueva, crear una base desde su cliente SQL con:

    ```sql
    CREATE DATABASE panel_incidents_local
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```

    Comprobar que el motor predeterminado sea InnoDB. Definir en `.env` `DB_CONNECTION=mysql`, `DB_DATABASE=panel_incidents_local` y los valores reales de `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD`. Si la conexión utiliza socket, definir `DB_SOCKET` con la ruta real del socket del servidor MySQL. El puerto de la plantilla es orientativo. Mantener `DB_CHARSET=utf8mb4` y `DB_COLLATION=utf8mb4_unicode_ci`; no utilizar collations exclusivas de MySQL 8.

4. Generar la clave local de la aplicación:

    ```bash
    php artisan key:generate --no-interaction
    ```

5. Antes de migrar, comprobar la configuración y, en el cliente SQL con esa misma conexión, el destino real:

    ```bash
    php artisan config:show database.default --no-interaction
    php artisan config:show database.connections.mysql.database --no-interaction
    ```

    ```sql
    SELECT DATABASE() AS database_name, VERSION() AS server_version,
           @@default_storage_engine AS storage_engine;
    ```

    Continuar únicamente si el destino es la base de desarrollo elegida (`prueba_optimaretail` en este entorno). Si Laravel conserva una configuración anterior, ejecutar `php artisan config:clear --no-interaction` y repetir la comprobación. Revisar los archivos de migración pendientes antes de ejecutarlos; si existe el repositorio de migraciones, consultar también:

    ```bash
    php artisan migrate:status --no-interaction
    ```

6. Aplicar las migraciones pendientes en esa base de desarrollo y compilar el frontend:

    ```bash
    php artisan migrate --no-interaction
    npm run build
    ```

    Las migraciones actuales crean las tablas del starter kit, incluidas sesiones, caché y colas, y las tablas `incidents`, `incident_logs` e `incident_comments`. `SESSION_DRIVER`, `CACHE_STORE` y `QUEUE_CONNECTION` utilizan la base de datos; no hace falta instalar Redis.

En el entorno ya preparado, preferir los pasos explícitos de comprobación y `php artisan migrate` a `composer setup`: ese script ejecuta migraciones automáticamente antes de compilar.

## Datos de ejemplo y acceso

En una instalación nueva ya migrada, el seeder actual puede ejecutarse una vez:

```bash
php artisan db:seed --no-interaction
```

Crea únicamente el usuario de demostración del starter kit: correo `test@example.com` y contraseña `password`. Son datos sintéticos de demostración, no credenciales de MySQL. Ejecutarlo de nuevo puede fallar porque el correo ya existe. No hay todavía un seeder normal de incidencias. El seeder independiente de inconsistencias no se llama desde este flujo; consultar [diagnóstico](#diagnóstico-de-inconsistencias) para su ejecución explícita.

La autenticación procede del starter kit y no es obligatoria para la prueba técnica. Las rutas del panel requieren iniciar sesión. Se conserva el destino de Fortify `/dashboard`: esa ruta GET/HEAD redirige con 302 a `/incidents` bajo los middleware `auth` y `verified`. Así, el login sin destino previo y el acceso solicitado a `/dashboard` conducen al listado, sin alterar credenciales ni el comportamiento de destinos solicitados de otras rutas. El sidebar muestra únicamente «Incidencias» como sección principal.

## Arranque

Con la base de desarrollo preparada, ejecutar:

```bash
composer run dev
```

Este comando inicia el servidor PHP, Vite, el consumidor de colas y la lectura de logs. La dirección configurada es [http://localhost:8000](http://localhost:8000); si se cambia el puerto del servidor, ajustar `APP_URL`.

Como alternativa, después de `npm run build`, basta con iniciar el servidor PHP para consultar las pantallas actuales:

```bash
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

## Pruebas con MySQL

La suite utiliza exclusivamente `prueba_optimaretail_test`. La conexión y las credenciales locales se guardan en `.env.testing`, excluido de Git y con permisos `600`. No se comparten en documentación ni en `phpunit.xml`.

Para preparar otro equipo, después de instalar las dependencias y compilar el frontend:

1. Crear una base independiente llamada `prueba_optimaretail_test`, con `utf8mb4`, collation `utf8mb4_unicode_ci` y motor predeterminado InnoDB.

    Si todavía no existe, desde el cliente MySQL:

    ```sql
    CREATE DATABASE prueba_optimaretail_test
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```

2. Crear `.env.testing` en la raíz a partir de `.env.example`, sin copiar las credenciales de desarrollo a archivos versionados. Definir `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=prueba_optimaretail_test` y completar únicamente en ese archivo las variables reales de conexión: `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` y, si procede, `DB_SOCKET`. Dejar `DB_URL` vacío y definir `DB_CHARSET` y `DB_COLLATION` con los valores indicados.

    Solo si `.env.testing` no existe:

    ```bash
    cp .env.example .env.testing
    ```

3. Generar su clave propia y restringir permisos:

    ```bash
    php artisan key:generate --env=testing --no-interaction
    chmod 600 .env.testing
    git check-ignore .env.testing
    ```

4. Comprobar el driver y la base configurados sin mostrar credenciales:

    ```bash
    php artisan config:show database.default --env=testing --no-interaction
    php artisan config:show database.connections.mysql.database --env=testing --no-interaction
    ```

    Deben devolver `mysql` y `prueba_optimaretail_test`. No debe haber configuración cacheada ni variables externas que alteren la conexión.

Ejecutar:

```bash
php artisan test --compact
```

Antes de que `RefreshDatabase` pueda migrar o limpiar tablas, `tests/TestCase.php` exige `.env.testing`, entorno `testing`, driver MySQL y nombre de base correcto. Además, consulta `SELECT DATABASE()` en las conexiones efectivas de lectura y escritura. Si no coinciden, detiene la ejecución. No ejecutar esta suite en paralelo: el aislamiento actual exige ese nombre exacto.

Las pruebas focalizadas de renderizado del cambio de estado en el listado usan Vitest, incluido en la herramienta Vite Plus ya instalada, sin dependencias adicionales:

```bash
node_modules/.bin/vp test run resources/js/components/incidents/incident-list-status-form.test.tsx
```

Se ejecutan en Node sin MySQL ni navegador: verifican opciones recibidas, disparador de confirmación, controles deshabilitados y mensajes. No sustituyen las pruebas interactivas de seleccionar, confirmar/cancelar y enviar en navegador.

La suite prepara y reinicia únicamente la base de pruebas mediante `RefreshDatabase`; no necesita una migración manual previa. Las migraciones normales de desarrollo están permitidas en `prueba_optimaretail`, pero no los reinicios destructivos, la limpieza de datos ni las pruebas. Una instalación nueva debe aprovisionar su propia base y `.env.testing` siguiendo los pasos anteriores.

## Integración continua

El workflow `.github/workflows/tests.yml` utiliza PHP 8.5, Node 24 y un servicio efímero `mysql:5.7.44`. El servicio crea únicamente `prueba_optimaretail_test` y un usuario `ci_runner` con acceso a esa base. Las contraseñas sintéticas del workflow son exclusivas de ese contenedor de CI; no son secretos locales ni deben reutilizarse fuera de él. Docker solo se utiliza como servicio de GitHub Actions; no es necesario para el desarrollo local con un servidor MySQL accesible.

El job genera `.env.testing` desde `.env.example`, con permisos `600`, una clave de aplicación nueva, host TCP y el puerto asignado al servicio. No copia `.env` ni credenciales del equipo de desarrollo. Antes de cualquier migración verifica el entorno, la base efectiva, la versión MySQL 5.7 y el motor InnoDB; después establece `utf8mb4_unicode_ci` en esa base de CI. Las migraciones las ejecuta la suite bajo la protección adicional de `tests/TestCase.php`.

La instalación usa `composer install` y `npm ci`, compila el frontend y ejecuta `composer ci:check` (formato, lint, tipos, análisis PHP y pruebas). PHP dispone de 512 MB para el análisis estático. El job no ejecuta `composer setup`, seeders ni migraciones sobre bases de desarrollo. Se verificó una [ejecución correcta en GitHub del commit `61120b7`, segundo intento](https://github.com/oscarlidenbrock/prueba_optimaretail/actions/runs/38921099681/attempts/2), con todos los pasos del job `ci` aprobados. Ese resultado es anterior al override de Tinypool: **el CI remoto sigue pendiente únicamente para los últimos cambios no publicados, incluida esa corrección y la documentación final**; no se presentan como aprobados por esa ejecución anterior.

## Aviso de fuentes

El plugin de fuentes de Laravel Vite intenta generar fuentes de sustitución con métricas optimizadas mediante la dependencia opcional `fontaine`, que no está instalada. Instrument Sans se descarga y compila correctamente; el aviso solo afecta a esa optimización del texto mientras carga la fuente principal. Se conserva el aviso: no se añaden dependencias ni se desactiva la optimización únicamente para silenciarlo. No impide compilar o ejecutar la aplicación.

## Diagnóstico de inconsistencias

### Diagnóstico y evaluación de reparaciones

```bash
php artisan incidents:diagnose --env=testing --dry-run --no-interaction
php artisan incidents:diagnose --env=testing --apply --no-interaction
```

Revisa la base efectiva del entorno seleccionado antes de ejecutarlo. El modo predeterminado es **dry-run**, idéntico a `--dry-run`. `--apply` exige intención explícita, pero **no autoriza resolver ambigüedades**: actualmente informa de cero reparaciones justificadas y conserva todos los datos, incluso los marcados como simulación. Los tres modos ejecutan solo SELECT; ninguno cambia estados, elimina logs o escribe marcas. **No ejecutar reparaciones en desarrollo.**

Revisa todas las incidencias, no solo las simuladas. Cada hallazgo identifica incidencia, código, evidencia y propuesta de revisión humana; cuando hay logs, incluye sus IDs, valores, actor y fecha UTC. El resumen diferencia lo que se modificaría, correcciones aplicadas (cero) y hallazgos pendientes, sin fingir reparaciones. Código de salida **0** si no hay hallazgos, **1** si quedan hallazgos y **2** si se combinan `--apply` y `--dry-run` (rechazo antes de consultar). Errores de conexión o ejecución también deben investigarse, no interpretarse como datos correctos.

### Tres casos simulados, únicamente bajo ejecución explícita

Desde una instalación nueva, completar primero [instalación local](#instalación-local) y [preparación de testing](#pruebas-con-mysql), incluidas dependencias, base MySQL independiente, `.env.testing` y clave. La secuencia exacta para reproducir el diagnóstico es:

1. Ejecutar solo las pruebas afectadas, antes de cargar fixtures por consola:

    ```bash
    php artisan test --compact tests/Feature/IncidentDiagnosisTest.php
    ```

    `tests/TestCase.php` comprueba las conexiones PDO efectivas de lectura/escritura antes de que `RefreshDatabase` prepare las tablas en `prueba_optimaretail_test`. Esto evita intentar sembrar una base nueva sin esquema. Si falla la protección de conexión o alguna prueba, detenerse y corregir la configuración; no continuar con el seeder. La base de testing es efímera: las pruebas pueden reiniciarla y no debe contener datos que se necesiten conservar.

2. Cargar y repetir el seeder explícito en testing:

    ```bash
    php artisan db:seed --env=testing --class=InconsistentIncidentSeeder --no-interaction
    php artisan db:seed --env=testing --class=InconsistentIncidentSeeder --no-interaction
    ```

3. Ejecutar los tres modos, por separado:

    ```bash
    php artisan incidents:diagnose --env=testing --no-interaction
    php artisan incidents:diagnose --env=testing --dry-run --no-interaction
    php artisan incidents:diagnose --env=testing --apply --no-interaction
    ```

Tras sembrar dos veces se esperan **3 incidencias y 3 logs**, no 6. Los tres modos deben mostrar los mismos IDs, tipos y evidencia, **3 hallazgos pendientes** y **0 correcciones aplicadas**, sin escribir. El modo predeterminado y `--dry-run` producen la misma salida; `--apply` cambia el encabezado y explica por qué no aplica correcciones. Los IDs concretos pueden variar. El código **1** de esos diagnósticos es el resultado esperado al existir hallazgos: no encadenarlos con `&&` ni confundirlo con un fallo de detección. Si se usa un script con `set -e`, hay que tratar ese código expresamente.

Las pruebas del primer paso verifican además la igualdad de snapshots completos antes/después y que las consultas de los tres modos sean solo SELECT. La reproducción por consola deja las tres fixtures en testing; una ejecución posterior de pruebas puede eliminarlas al preparar de nuevo esa base. Esta secuencia se ha reproducido en el entorno existente, **no equivale a haber realizado una instalación limpia en otro equipo**.

**No ejecutar el seeder sobre `prueba_optimaretail` sin autorización adicional.** No se carga desde `DatabaseSeeder`, instalaciones habituales ni el comando de diagnóstico. Crea solo tres incidencias y tres logs en total, sin usuarios o comentarios nuevos y sin modificar incidencias previas:

| Caso                              | Estado      | Evidencia simulada          | Hallazgo esperado                                     |
| --------------------------------- | ----------- | --------------------------- | ----------------------------------------------------- |
| Resuelta sin log de resolución    | `resolved`  | Sin logs                    | `resolved_without_resolution_log`                     |
| Estado distinto del último cambio | `blocked`   | Un log `open → in_review`   | `status_mismatch`                                     |
| Log de estado duplicado           | `in_review` | Dos logs `open → in_review` | `incoherent_status_log`, con referencia al primer log |

Títulos con prefijo `[SIMULACIÓN]`, descripción con identificador estable `diagnóstico:v1:<caso>`, solicitante/actor «Simulación de diagnóstico» y metadata `simulation: true`/`fixture` en los logs distinguen los ejemplos. Los actores son identidades artificiales y las fechas son de generación, no hechos históricos reales. La ausencia de log en el primer caso es deliberada.

La creación completa se envuelve en una transacción. `firstOrCreate` busca la descripción identificadora y el solicitante; solo crea logs cuando la incidencia acaba de crearse. Repetirlo conserva IDs, datos y timestamps, incluso si después alguien ha revisado el ejemplo; no lo restablece ni lo repara. Un título coincidente en otra incidencia no permite reutilizarla ni modificarla. No cambiar los identificadores reservados de las fixtures si se quiere mantener esa repetibilidad. Ejecutar secuencialmente: sin una clave única de fixture en el esquema, no se garantiza deduplicación entre dos ejecuciones simultáneas.

### Consultas y razonamiento

El comando utiliza esta consulta Eloquent por lotes de 100 incidencias, con eager loading y sin N+1:

```php
Incident::query()->select(['id', 'status'])
    ->with(['logs' => fn ($query) => $query->where('action', 'status_changed')])
    ->chunkById(100, $inspectIncidents);
```

`$inspectIncidents` representa el callback de inspección del comando. La relación `logs()` impone `created_at ASC, id ASC`; su último elemento equivale a esta consulta SQL de referencia por incidencia:

```sql
SELECT id, old_value, new_value, user_name, created_at
FROM incident_logs
WHERE incident_id = ? AND action = 'status_changed'
ORDER BY created_at DESC, id DESC
LIMIT 1;
```

La lectura agrupada real equivale a `SELECT * FROM incident_logs WHERE incident_id IN (...) AND action = ? ORDER BY created_at ASC, id ASC`. El filtro por acción excluye comentarios y otras actividades. Los índices existentes `incident_id, created_at` soportan acceso y orden; no se añaden índices sin medición. No se usan funciones de ventana, CTE ni características JSON específicas: las consultas se han probado en MySQL 5.7.44.

Sobre esos resultados se aplican tres comprobaciones:

1. **`resolved_without_resolution_log`:** estado actual `resolved` y ningún log `status_changed` con `new_value = resolved`. La existencia de ese log no prueba que su transición fuese válida: esa validación pertenece al tercer control.
2. **`status_mismatch`:** existe último cambio y su `new_value` no coincide exactamente con el estado persistido. Empates de fecha se resuelven por ID. El estado se lee en bruto para que un identificador corrupto no impida ejecutar el diagnóstico.
3. **`incoherent_status_log`:** se recorren los cambios cronológicamente, validando valores con el enum y su `canTransitionTo()`. Se informa de pares anterior/nuevo repetidos, valores nulos/desconocidos, transiciones prohibidas o de una discontinuidad entre el valor nuevo de un log y el anterior del siguiente. Por log se prioriza duplicado, transición inválida y después discontinuidad para no repetir la misma evidencia; el par repetido referencia su primera aparición, aunque fecha o actor difieran.

### Limitaciones y revisión manual

- Los hallazgos señalan contradicciones o ausencia de evidencia, no una reparación inequívoca. Contrastar IDs, actores, fechas y registros disponibles; no inventar logs, fechas ni autores, ni asumir que el estado actual o el último log son correctos.
- La definición de duplicado se basa en la matriz actual sin reapertura ni ciclos. Si se amplía esa política habrá que revisar el detector; compartir valores no bastaría para declarar duplicación en un flujo con ciclos.
- No acredita la integridad completa del historial: no exige un log de creación ni el primer cambio desde `open`. Una incidencia no resuelta sin logs no se marca por sí sola. No valida actores, metadata o autenticidad de timestamps; tampoco examina otras acciones o comentarios.
- No se toma un snapshot transaccional global ni se bloquean operaciones. Cambios concurrentes entre las consultas pueden producir evidencia transitoria; repetir en un periodo estable antes de concluir. El uso de memoria queda acotado por lotes de incidencias, pero se carga el historial de cambios completo de cada lote.
- `--apply` evalúa las reparaciones, pero el conjunto actual de reparaciones automáticas seguras está vacío. No hay escritura implementada, borrado ni corrección de estado. No se ha añadido una transacción vacía, bloqueo innecesario ni prueba ficticia de rollback para aparentar una reparación.

### Decisión de reparación de los tres casos

| Caso                              | Evidencia insuficiente                                                                                                                                     | Decisión actual                                                                                                |
| --------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Resuelta sin log                  | El estado no demuestra cuándo, por quién ni desde qué estado se resolvió; también podría ser el estado el dato incorrecto.                                 | Revisión humana. No fabricar un `status_changed` histórico.                                                    |
| Estado distinto del último cambio | El último log puede ser correcto, faltar un log posterior o existir un error en cualquiera de las dos fuentes.                                             | Revisión humana. No copiar automáticamente el último `new_value` al estado.                                    |
| Log duplicado o incoherente       | Repetir valores evidencia una contradicción con la matriz, pero no demuestra qué registro sobra, si hubo un intento real ni qué actor/fecha son correctos. | Conservar los originales y solicitar revisión. No borrar, alterar ni declarar ignorado un log automáticamente. |

El marcador de simulación solo identifica datos artificiales; no es prueba operativa ni autorización para repararlos. Por tanto, no se implementa ninguna reparación artificial para demostrar `--apply`. Transacciones de escritura, `lockForUpdate()` y rollback de reparación no son aplicables al comando actual porque no existe una operación de escritura justificada.

### Procedimiento tras una decisión humana documentada

Este procedimiento **no es una función ya implementada de `--apply`**, ni se ha ejecutado en desarrollo. Requiere autorización para la incidencia concreta, evidencia adicional y una intervención de mantenimiento revisada antes de escribir:

1. Ejecutar dry-run sobre la base verificada, conservar la evidencia y asignar una referencia de decisión única. Documentar ID de incidencia/logs, estado y valores observados, fuentes externas, responsable que decide, corrección exacta y motivo. No usar datos de simulación como evidencia real. Si no se puede justificar una corrección, dejar el caso pendiente.
2. Preparar y revisar una intervención acotada en Laravel. Ejecutarla primero en testing; usar `DB::transaction()` y volver a consultar la incidencia con `lockForUpdate()`, después sus logs ordenados con bloqueo. Todos los escritores de estado deben seguir bloqueando primero la incidencia, como la acción existente.
3. Dentro de la transacción, comprobar que estado, IDs/valores/fechas de logs y evidencia siguen coincidiendo con lo aprobado; reejecutar el criterio afectado. Si divergen, abortar con rollback y nueva revisión. Buscar también logs correctivos de esa referencia: si ya se aplicó, terminar sin cambios. La comprobación de referencia debe realizarse bajo el mismo bloqueo, no antes.
4. Solo si la decisión humana acredita el estado actual deseado, reconciliarlo con su valor observado como precondición. Registrar en la misma transacción `action = repair_applied`, valor anterior real, nuevo confirmado, operador que interviene **ahora**, fecha actual y metadata con referencia de decisión, IDs de evidencia, fuente, motivo y límites. Ese log describe una reparación actual, no sustituye un suceso histórico ni se hace pasar por una transición ordinaria. No usar el endpoint de transiciones para forzar un cambio que su matriz prohíbe.
5. Para una resolución histórica no reconstruible o un log duplicado, se puede documentar una revisión aprobada con `action = repair_reviewed`, referencia y evidencia en metadata, sin inventar transición, actor ni fecha del pasado y sin eliminar los originales. Esta anotación **no subsana la ausencia o contradicción histórica**: el diagnóstico sigue mostrándola. La aceptación humana y cualquier futura política para tratar logs anulados necesitan diseño/revisión explícitos; no se ocultan avisos.
6. Exigir resultado de escritura esperado, confirmar estado y registro correctivo juntos y propagar cualquier error para rollback completo. Volver a ejecutar diagnóstico y la intervención con la misma referencia: no debe duplicar logs ni volver a modificar datos. Registrar qué se corrigió y qué sigue pendiente; no declarar el historial íntegro solo por reconciliar el estado.

Consultas de referencia para esa intervención futura, compatibles con MySQL 5.7 y **no ejecutadas por el comando actual**:

```sql
START TRANSACTION;
SELECT id, status, updated_at FROM incidents WHERE id = ? FOR UPDATE;
SELECT id, action, old_value, new_value, user_name, metadata, created_at
FROM incident_logs WHERE incident_id = ?
ORDER BY created_at ASC, id ASC FOR UPDATE;
```

El código de mantenimiento debe comparar aquí la evidencia aprobada y las referencias de decisión dentro del JSON leído. Si ya está aplicada, `COMMIT` sin escrituras; si cambió o es insuficiente, `ROLLBACK`. Solo en una reconciliación de estado expresamente aprobada se ejecutaría esta plantilla parametrizada:

```sql
UPDATE incidents SET status = ?, updated_at = UTC_TIMESTAMP()
WHERE id = ? AND status = ?;
INSERT INTO incident_logs
    (incident_id, action, old_value, new_value, user_name, metadata, created_at)
VALUES (?, 'repair_applied', ?, ?, ?, ?, UTC_TIMESTAMP());
COMMIT;
```

No es un script listo para copiar: hay que vincular los valores revisados, validar el estado destino, construir metadata JSON con la decisión y comprobar que el UPDATE afectó exactamente una fila **antes** del INSERT; ante cualquier fallo, `ROLLBACK`, nunca `COMMIT` parcial. Si ya coincide el estado y no se necesita una nueva evidencia aprobada, no escribir. Las acciones `repair_applied`/`repair_reviewed` son convenciones propuestas para esa intervención, no nuevos eventos emitidos por la aplicación. Al no ser `status_changed`, no alteran qué considera el diagnóstico el último cambio histórico; los problemas que permanezcan se seguirán informando.

## Decisiones técnicas

- Laravel con Inertia y React, TypeScript y Tailwind; Wayfinder para los enlaces y acciones del frontend. Sin API separada ni Next.js.
- MySQL como fuente de datos; tablas transaccionales InnoDB y collation compatible con el servidor 5.7.44 comprobado.
- Base de pruebas independiente, configuración local no versionada y comprobación del destino antes de cualquier reinicio de tablas.
- Se conserva la caché de infraestructura del starter kit en base de datos. No se ha añadido caché de incidencias ni Redis.
- Identificadores y comentarios de código en inglés; documentación e interfaz del dominio en español.

## Modelo de datos de incidencias

Las tres tablas usan IDs `BIGINT UNSIGNED`, InnoDB, `utf8mb4` y `utf8mb4_unicode_ci`, comprobados mediante pruebas en MySQL 5.7.44. Las nuevas migraciones crean tablas vacías; no transforman datos existentes ni cargan ejemplos.

| Tabla               | Campos y decisiones                                                                                                                                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `incidents`         | `title` VARCHAR(255), `description` TEXT, `status` VARCHAR(32) con default `open`, `priority` VARCHAR(16) con default `medium`, `requester_name` VARCHAR(120), `assigned_to` VARCHAR(120) nullable y timestamps de Laravel.           |
| `incident_logs`     | `incident_id` obligatorio, `action` VARCHAR(64), `old_value` y `new_value` TEXT nullable, `user_name` VARCHAR(120), `metadata` JSON nullable y `created_at` TIMESTAMP obligatorio con default de fecha actual. No tiene `updated_at`. |
| `incident_comments` | `incident_id` obligatorio, `author_name` VARCHAR(120), `body` TEXT y timestamps de Laravel.                                                                                                                                           |

Los campos son obligatorios salvo los marcados nullable y los timestamps convencionales de Laravel (`created_at`/`updated_at`), que permiten NULL en el esquema y se rellenan automáticamente desde Eloquent. La aplicación mantiene su zona horaria UTC; los timestamps tienen precisión de segundos. `IncidentLog::UPDATED_AT = null` conserva la gestión automática de `created_at` sin escribir una columna de modificación inexistente. Esto no convierte los logs en inmutables. La política de reparación conservadora está descrita en la sección de diagnóstico; no hay reparaciones automáticas implementadas.

`IncidentStatus` centraliza `open`, `in_review`, `blocked` y `resolved`; `IncidentPriority`, `low`, `medium` y `high`. Los casts Eloquent convierten los strings almacenados en enums PHP. No se utilizan ENUM nativos ni CHECK como mecanismo de validación en MySQL 5.7; los Form Requests implementados validan los enums y los límites del esquema. Los defaults de las migraciones son literales para conservar su independencia respecto a futuras modificaciones del código de los enums.

`assigned_to` contiene un nombre opcional, sin relación con `users`. Solicitante y responsable son nombres suministrados al crear la incidencia; actor de cambios de estado y autor de comentarios proceden del usuario autenticado, aunque se almacenan como nombres sin clave foránea a `users`. `metadata` se convierte a array; `old_value` y `new_value` permanecen como texto nullable, porque su significado depende de `action`. La acción auditada implementada es `status_changed`, descrita abajo. La creación y los comentarios siguen la política de auditoría documentada en la sección de backend. Los datos de las factories solo sirven para preparar pruebas.

`Incident` tiene relaciones `logs()` y `comments()` de uno a muchos, y ambos modelos relacionados tienen `incident()`. Las claves foráneas restringen borrados y cambios del ID del padre para preservar historial y comentarios. Las relaciones se ordenan por `created_at` y después por `id` para resolver empates.

Índices para las consultas previstas del alcance:

- `incidents(created_at)`: listado por fecha sin filtros.
- `incidents(status, created_at)` y `incidents(priority, created_at)`: filtro por estado o prioridad con orden por fecha.
- `incident_logs(incident_id, created_at)` e `incident_comments(incident_id, created_at)`: historial y comentarios de una incidencia en orden temporal. Estos índices también soportan las claves foráneas, sin otro índice redundante en `incident_id`. InnoDB incorpora la clave primaria a los índices secundarios, permitiendo desempatar por `id`.

La selección de índices se revisará al medir las consultas reales. Se han implementado las operaciones HTTP y pantallas de creación, listado, detalle, comentarios y cambio de estado, junto con fixtures de diagnóstico independientes; no se incluyen edición, eliminación ni seeders normales de incidencias. Además de las pruebas en MySQL, el usuario confirmó las migraciones y la creación de incidencias desde un clon independiente en `prueba_optimaretail_check`. Cada instalación debe aplicar sus propias migraciones tras comprobar el destino. Los métodos `down()` eliminan las tablas y sus datos: no deben usarse como limpieza de desarrollo.

## Backend HTTP de incidencias

| Método y ruta                         | Nombre                     | Operación                                                             |
| ------------------------------------- | -------------------------- | --------------------------------------------------------------------- |
| GET/HEAD `/incidents`                 | `incidents.index`          | Listado paginado y filtrado mediante `IncidentController::index`.     |
| POST `/incidents`                     | `incidents.store`          | Creación mediante `IncidentController::store`.                        |
| GET/HEAD `/incidents/{incident}`      | `incidents.show`           | Detalle, historial y comentarios mediante `IncidentController::show`. |
| POST `/incidents/{incident}/comments` | `incidents.comments.store` | Comentario mediante `IncidentCommentController::store`.               |
| PATCH `/incidents/{incident}/status`  | `incidents.status.update`  | Cambio de estado mediante `IncidentController::updateStatus`.         |

Son rutas web con sesión y los middleware `auth` y `verified` existentes, sin ampliar la autenticación. Se conserva la protección CSRF de las peticiones de escritura. No se introducen API separada, servicios ni acciones adicionales para estas operaciones.

`StoreIncidentRequest` exige título (hasta 255 caracteres), descripción (hasta 10000), prioridad del enum y solicitante (hasta 120); responsable es opcional y admite hasta 120 caracteres. El límite de descripción permite almacenar incluso 10000 caracteres de cuatro bytes en la columna TEXT. Los mensajes de validación están en español. Se admiten únicamente los campos validados; el controlador establece `status = open`. La presencia de `status` en el payload, incluso vacío o null, se rechaza. La creación redirige a `incidents.index` con el aviso de éxito habitual de Inertia.

La política de auditoría actual registra cambios de estado con `status_changed`; no se añaden logs de creación de incidencias ni de comentarios. Estos últimos conservan autor y fechas en su propia tabla. Las factories siguen siendo datos exclusivos de pruebas.

`IndexIncidentRequest` valida `status`, `priority`, `q` y `page`. Los filtros vacíos se interpretan como ausencia de filtro; `q` admite hasta 255 caracteres. `page` debe ser un entero entre 1 y 2147483647 para evitar desbordamientos del offset. El scope `matchingFilters()` combina estado y prioridad con una búsqueda parcial en título **o** descripción, agrupada para no omitir restricciones. Se usa `LOCATE` con parámetros enlazados sobre MySQL: `%`, `_` y `!` se buscan literalmente y se conserva la comparación de la collation configurada.

El listado ordena por `created_at DESC, id DESC`, pagina de 15 en 15 y conserva únicamente los filtros validados en los enlaces. La respuesta Inertia incluye `incidents` (paginador) y `filters` (valores activos); no carga logs ni comentarios.

### Detalle, historial y comentarios

GET `incidents.show` prepara el componente Inertia `incidents/show` con una prop `incident`: todos los campos de la incidencia y las colecciones anidadas `logs` y `comments`, sin paginación en este detalle. Los estados y prioridades se serializan con sus identificadores ingleses; las fechas, como ISO 8601 en UTC. Se incluyen todos los campos persistidos de logs (incluido `metadata` como estructura JSON o null, y valores anterior/nuevo nullable) y comentarios. Las colecciones vacías son arrays vacíos. Se cargan exclusivamente las relaciones de la incidencia consultada.

La prop adicional `allowedTransitions` contiene los estados de destino permitidos por `IncidentStatus::allowedTransitions()`, derivado de `canTransitionTo()` para el estado actual. El listado recibe la misma información en `allowed_transitions` de cada incidencia. React utiliza esas listas, sin mantener una matriz propia; el endpoint sigue revalidando bajo bloqueo antes de escribir. Una lista vacía desactiva las acciones, no significa que el frontend pueda autorizar otras.

El historial se ordena por `created_at DESC, id DESC`. La consulta usa `reorder()` para sustituir el orden ascendente predeterminado de la relación sin modificarla globalmente. Los comentarios mantienen `created_at ASC, id ASC`.

POST `incidents.comments.store` recibe `body`: texto obligatorio, no vacío tras el recorte de espacios habitual de Laravel y máximo de 10000 caracteres, compatible con TEXT y `utf8mb4`. `StoreIncidentCommentRequest` sustituye cualquier `author_name` recibido por el nombre del usuario autenticado **antes** de validar; exige un nombre no vacío de hasta 120 caracteres, sin truncarlo ni recurrir al cliente. El controlador solo persiste esos dos campos mediante `comments()`; el ID de incidencia lo determina la ruta, no el payload. No modifica la incidencia ni sus logs y no necesita una transacción de múltiples escrituras.

El éxito redirige con 303 a `incidents.show` y flash `toast` de éxito «Comentario añadido correctamente.». Validación: mensajes españoles por campo, JSON 422 o redirección POST 302 con errores de sesión para Inertia. Incidencias inexistentes: 404. Invitados: redirección a login o JSON 401. Se conserva el acceso actual sin añadir roles ni activar verificación de correo.

### Pantalla principal

La identidad del sidebar es **Panel de incidencias**, con acceso al listado y sin enlaces a Repository/Documentation del starter kit. La opción **Incidencias** abre `incidents/index`, conservando el layout existente. Presenta tabla desde el breakpoint `md`, tarjetas en móvil, indicadores con texto y color, personas, fechas locales, resultados vacíos y navegación anterior/siguiente. Los títulos están subrayados como enlaces al detalle y al historial. En pantallas amplias el formulario aparece junto al listado; en las demás queda debajo, con un acceso directo «Nueva incidencia» que desplaza hasta el formulario y enfoca el título.

Tabla y tarjetas permiten cambiar el estado directamente mediante selector y botón «Cambiar estado». El selector muestra solo `allowed_transitions`, nunca el estado actual; bloqueadas y resueltas muestran únicamente «Ver detalle». Los controles se alinean con flex y se ajustan al espacio disponible. Bloquear o resolver requiere el mismo diálogo de confirmación que el detalle; pasar de abierta a revisión no lo requiere. Selector y botón se deshabilitan mientras se procesa.

El PATCH utiliza Inertia y envía `list_context` con filtros, búsqueda y página actuales. Éxito y conflicto vuelven al listado con esos parámetros y datos recargados, sin recarga completa del documento; se conservan estado de la pantalla y scroll. Si la incidencia deja de cumplir el filtro tras cambiar, desaparece del resultado sin eliminar el filtro ni cambiar automáticamente de página. Los errores usan un error bag por incidencia, permanecen visibles aunque pase a un estado final y el flash informa también si la fila desaparece. No hay actualización optimista ni transacciones/reglas de negocio duplicadas.

Los filtros de estado y prioridad se aplican al seleccionar; el texto se envía con «Aplicar filtros» o Intro. Todo se consulta mediante Inertia, sin recargar el documento: los filtros quedan en la URL, la paginación los conserva y «Limpiar filtros» elimina también los cambios locales no enviados. La navegación conserva el borrador de creación y el scroll. El formulario de filtros se sincroniza con los parámetros devueltos por el servidor, también al navegar por el historial del navegador.

La creación utiliza `Form` tipado, Wayfinder y los componentes existentes. Envía solo los cinco campos de creación y nunca incluye estado. Se bloquea durante el envío, conserva valores cuando hay errores y reinicia los campos tras éxito (prioridad predeterminada media). La validación se delega al servidor con `noValidate` para mostrar sus mensajes españoles junto a los campos; se mantienen límites HTML y atributos accesibles. Se reutiliza el toast de éxito existente y se muestra confirmación temporal en el formulario. Los formularios separan sus errores mediante error bags.

### Pantalla de detalle

Los títulos del listado abren `incidents/show`, con regreso al listado, todos los datos de la incidencia, historial descendente como línea temporal y comentarios ascendentes. Los cambios de estado muestran valores anterior/nuevo traducidos, actor y fecha; las acciones o valores desconocidos se presentan sin inventar su significado. El metadata disponible se puede desplegar. Descripción, comentarios y datos de auditoría se muestran como texto, sin HTML sin escapar.

El formulario de comentario usa la ruta existente con Inertia y Wayfinder: envía solo `body`, muestra errores españoles de contenido o nombre autenticado, bloquea el envío mientras procesa y reinicia únicamente tras éxito. El detalle y sus comentarios se actualizan sin recarga completa; las fechas y autor proceden del backend.

Las acciones de estado proceden de `allowedTransitions`. Bloquear o resolver abre un diálogo de confirmación del starter kit con cancelar/confirmar; ninguna acción envía actor desde el navegador. Durante el PATCH los botones quedan deshabilitados. Éxito y conflicto redirigen al **detalle** y recargan su estado, historial y acciones autoritativas; se muestran flash/toast y errores en el panel de estado, incluso si el nuevo estado ya no permite transiciones. Los errores de comentarios y estado están separados mediante error bags. No hay actualizaciones optimistas ni una segunda implementación de las transiciones.

Las fechas de listado, detalle, historial y comentarios se formatean en español en la zona horaria del navegador, sin etiquetas de zona ni explicaciones técnicas en las pantallas. Los datos siguen almacenados y transmitidos en UTC; `dateTime` conserva el ISO original y el tooltip nativo de cada fecha permite consultar UTC. El render inicial SSR utiliza UTC y después adopta la zona del navegador para evitar discrepancias de hidratación.

El usuario ha confirmado las verificaciones manuales de interfaz y responsive. Esta confirmación se distingue de los intentos fallidos del navegador integrado del agente y no acredita una matriz exhaustiva de dispositivos, zonas horarias o concurrencia. La implementación incluye filtro rápido por estado y confirmación antes de bloquear/resolver como las dos mejoras elegidas del enunciado. La instalación independiente con migraciones y creación de incidencias también está confirmada; sus resultados y procedencia constan en el registro de desarrollo.

## Transiciones de estado

`IncidentStatus::canTransitionTo()` centraliza únicamente estos cambios:

| Estado actual | Nuevo estado |
| ------------- | ------------ |
| `open`        | `in_review`  |
| `in_review`   | `blocked`    |
| `in_review`   | `resolved`   |

Se rechazan los demás cambios, incluido repetir el estado actual. `blocked` y `resolved` no tienen salidas; no se implementa reapertura.

`ChangeIncidentStatus::handle(Incident $incident, IncidentStatus $newStatus, string $actorName): Incident` recorta espacios del nombre del actor y exige entre 1 y 120 caracteres. La acción no autentica al actor por sí misma; su endpoint HTTP obtiene el nombre del usuario de sesión. La acción abre `DB::transaction()`, vuelve a consultar la incidencia por ID con `lockForUpdate()` y solo entonces valida la transición contra el estado actual de MySQL.

La actualización y el log se guardan juntos. El log contiene `action = status_changed`, el estado anterior real, el nuevo, el actor y `created_at` automático en UTC. Una transición incompatible con el estado actual lanza `IncidentStatusConflict`; la validación del actor mantiene `ValidationException` con clave `actor_name`. Los fallos de persistencia se propagan y provocan rollback. Una cancelación de escritura por eventos Eloquent se convierte en error explícito para impedir una actualización sin log. Si la incidencia ya no existe, se propaga `ModelNotFoundException`.

La acción devuelve una instancia consultada de nuevo; no actualiza la instancia original del llamador ni sus relaciones cargadas. Las peticiones repetidas se rechazan sin duplicar el historial. El bloqueo de fila y la validación dentro de la transacción evitan decidir con un estado obsoleto. Los tests cubren instancias desactualizadas; no se ha realizado una prueba con conexiones concurrentes independientes.

El endpoint PATCH usa `UpdateIncidentStatusRequest` para exigir un `status` textual del enum. El controlador obtiene el nombre del actor del usuario autenticado y llama a la acción por inyección de dependencias. Ignora cualquier actor o usuario enviado en el payload; no reproduce la matriz de transición ni abre otra transacción.

El éxito redirige con 303 a `incidents.show` de la incidencia, o a `incidents.index` si se envía `list_context`, y flash `toast.type = success` con mensaje en español. El contexto admite únicamente `status`, `priority`, `q` y `page`, reutilizando las reglas y mensajes de `IndexIncidentRequest`; no acepta URLs de retorno ni claves arbitrarias. Los valores vacíos se normalizan según Laravel. Un contexto inválido impide ejecutar la acción. Los errores de validación vuelven a la URL de origen mediante el comportamiento habitual del formulario; éxito y conflicto usan el contexto validado. La distinción entre errores es:

| Caso                                                                   | Petición JSON                                               | Petición Inertia                                                                                           |
| ---------------------------------------------------------------------- | ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Estado ausente, tipo incorrecto o valor fuera del enum                 | 422, mensaje en español en `errors.status`                  | Redirección 303 con error de campo, sin código de conflicto.                                               |
| Estado del enum incompatible con el estado actual, incluida repetición | 409 con `code = incident_status_conflict` y `errors.status` | Redirección 303 con error de campo y flash `toast` de tipo `error`, con `code = incident_status_conflict`. |

Así se conserva el flujo de errores de formularios de Inertia sin usar un 409 que el cliente interprete como recarga o respuesta inesperada. El conflicto mantiene HTTP 409 en JSON; en Inertia se comunica con `incident_status_conflict`, error de campo y redirección 303 que recarga el listado o detalle con el estado y destinos actuales. Las peticiones JSON se distinguen por negociación de contenido; no se crea una API separada. Una incidencia inexistente devuelve 404; un invitado es redirigido a login o recibe 401 si solicita JSON. Fallos de persistencia no se convierten en conflictos: se propagan como errores de servidor y se revierten las escrituras.

Se mantienen los middleware `web`, `auth` y `verified` del starter kit. No existen roles ni propiedad de incidencia que restrinjan a otros operadores autenticados. El modelo `User` actual no implementa `MustVerifyEmail`: conservar `verified` no activa una función nueva de verificación. El nombre autenticado debe respetar el límite de 120 caracteres del log; la acción devuelve error de validación si no lo cumple, sin truncarlo ni sustituirlo por datos del cliente. Los tests de integración comprueban el detalle actualizado después del PATCH y de un conflicto; las comprobaciones manuales de interfaz confirmadas por el usuario complementan esa cobertura, sin equivaler a tests automatizados de navegador.

## Verificación y limitaciones

Estado final documentado (16 de octubre de 2026): el usuario confirma una **instalación limpia desde un clon independiente en el mismo Mac**, con base `prueba_optimaretail_check`, migraciones, seeder, build, login y creación de incidencias correctos; también confirma la revisión manual de interfaz y responsive. No se presenta como prueba en otro equipo ni como ejecución del agente. La confirmación no identifica el SHA del clon: no acredita por sí sola cambios posteriores no publicados. CI remoto aprobado para `61120b7`; pendiente exclusivamente para los últimos cambios no publicados. El [cierre del registro de desarrollo](docs/development.md#2026-10-16--cierre-documental-y-verificaciones-confirmadas) distingue esas evidencias de los resultados históricos siguientes.

Corrección de seguridad del toolchain (16 de octubre de 2026): `npm ci` real sin flags, resolución sin conflictos, `npm audit` y `npm audit --omit=dev` con **0 vulnerabilidades**, TypeScript, formato/lint sin autocorrección, las **9 pruebas frontend de renderizado** y build de producción correctos. Se conserva `ignore-scripts=true` de `.npmrc`. El override se comprobó primero en una instalación aislada y se verificó la compatibilidad de Oxfmt con Tinypool 2.1.2. Estas comprobaciones acreditan por sí solas la reinstalación frontend; la instalación limpia completa y la revisión de interfaz están confirmadas separadamente por el usuario, como se indica arriba. Persisten los avisos conocidos de `fontaine` y deprecación de bindings Yuku; CI remoto de esta corrección pendiente. Detalles en [el registro de desarrollo](docs/development.md).

Navegación/login (16 de octubre de 2026): tras autorización expresa para `RefreshDatabase`, 5 casos afectados aprobados y 20 aserciones, sin fallos ni omisiones. Verificadas antes ambas conexiones PDO como MySQL `prueba_optimaretail_test`, servidor 5.7.44, y las protecciones activas de `tests/TestCase.php`. Cubiertos invitado a login, GET/HEAD de dashboard a incidencias y login normal/con destino solicitado a dashboard. No hubo correcciones de código ni operaciones en desarrollo. Comando focalizado y alcance en [registro de desarrollo](docs/development.md); no equivale a una comprobación interactiva en navegador.

Ajustes finales de interfaz (16 de octubre de 2026): TypeScript, formato/lint de los seis archivos frontend modificados y build correctos. Conservada la estructura ya comprobada manualmente según el usuario; revisados en código destinos de enlaces, foco y coherencia de mensajes. En esa fase el navegador integrado impidió la comprobación interactiva del agente; posteriormente el usuario confirmó la revisión manual de interfaz y responsive. Sin tests backend, cambios PHP, operaciones de base de datos o dependencias nuevas en aquella tarea. Persiste el aviso conocido de `fontaine`.

Evaluación de reparación (16 de octubre de 2026): 21 pruebas afectadas aprobadas, 102 aserciones en `IncidentDiagnosisTest`; conexiones de lectura/escritura verificadas como `prueba_optimaretail_test`, MySQL 5.7.44. Dry-run, rechazo de flags incompatibles, `--apply` para casos ambiguos/correctos y repetición sin cambios ni consultas de escritura comprobados. No hay reparación justificable ni rama de escritura/rollback que probar. Pint, PHPStan focalizado, ayuda del comando, formato y enlaces correctos; sin operaciones en desarrollo ni suite completa/frontend.

Diagnóstico inicial (16 de octubre de 2026): 16 pruebas focalizadas aprobadas y 69 aserciones con `php artisan test --compact tests/Feature/IncidentDiagnosisTest.php`, tras comprobar ambas conexiones efectivas como MySQL `prueba_optimaretail_test`, servidor 5.7.44. Pint, PHPStan focalizado y ayuda del comando correctos. Incluye generación/repetición segura de ejemplos, datos válidos, tres clases de hallazgos, fecha/ID, exclusión de otras acciones y comprobación de solo SELECT/snapshots intactos en ejecución repetida. No se ejecutaron el seeder en desarrollo, suite completa ni checks frontend.

Detalle React (16 de octubre de 2026): TypeScript, formato/lint focalizado, build, Pint y PHPStan del controlador correctos; cinco rutas y middleware conservados. Tras verificar lectura/escritura como MySQL `prueba_optimaretail_test` (5.7.44), pruebas afectadas: 93 aprobadas, 448 aserciones. Suite completa: 169 aprobadas, 3 omitidas, 712 aserciones. Sin operaciones en desarrollo. En aquella fase el agente no pudo realizar la revisión visual por `Cannot redefine property: process`; la confirmación manual posterior del usuario consta en el cierre. Persistía el aviso previo de `fontaine` sin cambios de dependencias.

Pantalla principal (16 de octubre de 2026): formato/lint focalizados mediante `npm run check --` sobre los ocho archivos frontend afectados, `npm run types:check` y `npm run build` correctos. Rutas revisadas y Wayfinder regenerado mediante Artisan/plugin Vite, sin editar código generado. El aviso conocido de `fontaine` permanece; no se añadieron dependencias ni se ocultó. Sin cambios de backend, operaciones sobre bases de datos ni repetición de tests PHP. La revisión visual/interactiva estaba pendiente en esa fase y fue confirmada posteriormente por el usuario; ver [registro de desarrollo](docs/development.md).

Fase de cambio de estado HTTP (16 de octubre de 2026): `php artisan test --compact tests/Feature/ChangeIncidentStatusEndpointTest.php tests/Feature/ChangeIncidentStatusTest.php tests/Feature/IncidentControllerTest.php`, 97 pruebas aprobadas y 437 aserciones. Verificados previamente los destinos efectivos de lectura y escritura como `prueba_optimaretail_test`, MySQL 5.7.44. Pint, PHPStan del código afectado y comprobación de rutas/middleware correctos. No se repitieron suite completa ni checks de frontend.

Fase de backend de creación/listado (16 de octubre de 2026): `php artisan test --compact tests/Feature/IncidentControllerTest.php tests/Feature/IncidentModelTest.php`, 66 pruebas aprobadas y 253 aserciones. Verificadas las conexiones efectivas de lectura y escritura como `prueba_optimaretail_test`, MySQL 5.7.44. Pint, PHPStan de los archivos afectados, rutas/middleware, formato documental y enlaces correctos. Se ejecutaron únicamente las comprobaciones afectadas; no se repitieron suite completa, build ni frontend. Los resultados de fases anteriores se mantienen como históricos.

Fase de transiciones (16 de octubre de 2026): 27 pruebas focalizadas y 111 aserciones correctas con `php artisan test --compact tests/Feature/ChangeIncidentStatusTest.php`; suite completa con 76 aprobadas, 3 omitidas por 2FA desactivado y 264 aserciones. Ambas conexiones efectivas se verificaron como MySQL `prueba_optimaretail_test`, servidor 5.7.44, antes de probar. Pint y PHPStan del código cambiado correctos; sin comprobaciones de frontend ni operaciones sobre la base de desarrollo en esta fase.

Fase del modelo de datos (16 de octubre de 2026): `php artisan test --compact tests/Feature/IncidentModelTest.php` pasa con 19 pruebas y 65 aserciones. Antes de ejecutarlo se verificaron ambas conexiones efectivas (lectura y escritura) como MySQL `prueba_optimaretail_test`, servidor 5.7.44. Pasan Pint y PHPStan sobre el código nuevo. No se repitieron la suite completa ni los checks de frontend en esta fase; los resultados siguientes corresponden a comprobaciones anteriores.

Comprobaciones de preparación anteriores al modelo de datos, incluida la corrección del estado de factory `withTwoFactor()`:

- Prueba de humo: 1 aprobada.
- Redirección de ajustes: 8 pruebas aprobadas; se conserva el destino, el estado 302 y la autenticación, y se rechazan los métodos de escritura y QUERY.
- Estado de factory 2FA comprobado con `make()`, sin activar la función ni persistir columnas opcionales. Se conserva porque un test heredado todavía lo referencia.
- Suite completa: 30 aprobadas, 3 omitidas por 2FA desactivado y 88 aserciones.
- Wayfinder regenerado con variantes de formulario; comprobación de tipos TypeScript y compilación del frontend correctas.
- Tablas de pruebas verificadas en MySQL 5.7.44 con InnoDB y `utf8mb4_unicode_ci`.
- Protección comprobada con un nombre de base inválido: rechazo antes de conectar o migrar.
- Pint y PHPStan sobre `tests/TestCase.php`: correctos.
- Análisis estático general correcto con `vendor/bin/phpstan analyse --debug --memory-limit=512M --no-progress`, sin nuevas exclusiones. El límite local de 128 MB resultaba insuficiente; no se modificó la configuración de PHPStan.

La documentación se contrastó con las versiones, scripts y ayudas locales, y se comprobaron sus enlaces y el diff. Antes de probar la factory se verificó la base efectiva `prueba_optimaretail_test` en MySQL 5.7.44. Pint, PHPStan y las pruebas pasaron en esas correcciones; TypeScript y compilación se comprobaron en la corrección anterior de Wayfinder.

Posteriormente, con autorización expresa y tras comprobar la conexión efectiva, se aplicaron las tres migraciones del starter kit en `prueba_optimaretail`: nueve tablas InnoDB con `utf8mb4_unicode_ci`, sin migraciones pendientes. No se ejecutaron seeders, reinicios ni limpieza de datos. Se volvió a verificar que el entorno de pruebas conecta exclusivamente a `prueba_optimaretail_test`; no se repitió la suite en esta tarea operativa. La aplicación responde con HTTP 200 en la raíz y en `/login`. En aquella fase falló el navegador integrado y el control de Safari no obtuvo autorización. Posteriormente el usuario confirmó la interfaz y la instalación desde un clon independiente en el mismo Mac; no se acredita una instalación en otro equipo.

En la preparación posterior de CI se validaron el YAML, la sintaxis bash/PHP del workflow y la generación real de `.env.testing` en un directorio temporal, incluidos sus valores y permisos. La suite local volvió a pasar (30 aprobadas, 3 omitidas y 88 aserciones) sobre MySQL 5.7.44 en la base exclusiva de pruebas; también pasaron Pint, PHPStan con 512 MB, TypeScript, formato/lint del frontend y compilación. Se conserva el aviso opcional de fuentes explicado arriba.

Las instrucciones de instalación se revisaron contra la plantilla de entorno, los scripts, las migraciones y el seeder. `composer check-platform-reqs`, `composer install --dry-run --no-interaction --no-scripts` y `npm ci --dry-run --ignore-scripts --no-audit --no-fund` fueron correctos en el entorno existente. **En esa fase no se había realizado una instalación limpia completa de Laravel**: esas simulaciones no descargaban ni instalaban todo desde cero ni probaban una base nueva. La instalación independiente posterior en `prueba_optimaretail_check` está confirmada por el usuario. En aquella fase todavía no se había ejecutado el workflow en GitHub y Docker local no disponía de un daemon activo para reproducir su contenedor; la ejecución remota posterior y la reinstalación real de dependencias frontend se distinguen arriba. Se repitió la comprobación HTTP de la raíz y `/login`, ambas con 200, pero el intento de revisión visual del agente falló al iniciarse el navegador integrado con `Cannot redefine property: process`; se distingue de la confirmación manual posterior del usuario.
