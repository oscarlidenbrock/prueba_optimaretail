# Panel operativo de incidencias

Aplicación para consultar y gestionar incidencias, sus estados, comentarios e historial. Está implementado el modelo de datos con migraciones, modelos Eloquent y enums; las operaciones y pantallas de incidencias siguen pendientes. Se conservan las pantallas y la autenticación del starter kit.

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

Crea únicamente el usuario de demostración del starter kit: correo `test@example.com` y contraseña `password`. Son datos sintéticos de demostración, no credenciales de MySQL. Ejecutarlo de nuevo puede fallar porque el correo ya existe. No hay todavía seeders de incidencias ni fixtures inconsistentes.

La autenticación procede del starter kit y no es obligatoria para la prueba técnica. El panel heredado requiere iniciar sesión.

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
2. Crear `.env.testing` en la raíz a partir de `.env.example`, sin copiar las credenciales de desarrollo a archivos versionados. Definir `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=prueba_optimaretail_test` y completar únicamente en ese archivo las variables reales de conexión: `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` y, si procede, `DB_SOCKET`. Dejar `DB_URL` vacío y definir `DB_CHARSET` y `DB_COLLATION` con los valores indicados.
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

La suite prepara y reinicia únicamente la base de pruebas mediante `RefreshDatabase`; no necesita una migración manual previa. Las migraciones normales de desarrollo están permitidas en `prueba_optimaretail`, pero no los reinicios destructivos, la limpieza de datos ni las pruebas. Una instalación nueva debe aprovisionar su propia base y `.env.testing` siguiendo los pasos anteriores.

## Integración continua

El workflow `.github/workflows/tests.yml` utiliza PHP 8.5, Node 24 y un servicio efímero `mysql:5.7.44`. El servicio crea únicamente `prueba_optimaretail_test` y un usuario `ci_runner` con acceso a esa base. Las contraseñas sintéticas del workflow son exclusivas de ese contenedor de CI; no son secretos locales ni deben reutilizarse fuera de él. Docker solo se utiliza como servicio de GitHub Actions; no es necesario para el desarrollo local con un servidor MySQL accesible.

El job genera `.env.testing` desde `.env.example`, con permisos `600`, una clave de aplicación nueva, host TCP y el puerto asignado al servicio. No copia `.env` ni credenciales del equipo de desarrollo. Antes de cualquier migración verifica el entorno, la base efectiva, la versión MySQL 5.7 y el motor InnoDB; después establece `utf8mb4_unicode_ci` en esa base de CI. Las migraciones las ejecuta la suite bajo la protección adicional de `tests/TestCase.php`.

La instalación usa `composer install` y `npm ci`, compila el frontend y ejecuta `composer ci:check` (formato, lint, tipos, análisis PHP y pruebas). PHP dispone de 512 MB para el análisis estático. El job no ejecuta `composer setup`, seeders ni migraciones sobre bases de desarrollo. La ejecución real del workflow en GitHub sigue pendiente hasta subir estos cambios; validar su configuración localmente no equivale a una ejecución remota correcta.

## Aviso de fuentes

El plugin de fuentes de Laravel Vite intenta generar fuentes de sustitución con métricas optimizadas mediante la dependencia opcional `fontaine`, que no está instalada. Instrument Sans se descarga y compila correctamente; el aviso solo afecta a esa optimización del texto mientras carga la fuente principal. Se conserva el aviso: no se añaden dependencias ni se desactiva la optimización únicamente para silenciarlo. No impide compilar o ejecutar la aplicación.

## Diagnóstico de inconsistencias

Pendiente de implementación. Todavía no existen el comando o servicio de diagnóstico, sus consultas SQL/Eloquent, la opción `dry-run` ni los tres casos de ejemplo exigidos: resolución sin log, estado distinto del último cambio y log duplicado o incoherente. No hay un comando de diagnóstico ejecutable que pueda documentarse aún.

Al implementar esa tarea se añadirán aquí los comandos reales, las consultas, su justificación, la política de reparación y los casos que requieren revisión manual. El modelo de datos de incidencias, logs y comentarios ya existe; sus operaciones de aplicación todavía están pendientes.

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

Los campos son obligatorios salvo los marcados nullable y los timestamps convencionales de Laravel (`created_at`/`updated_at`), que permiten NULL en el esquema y se rellenan automáticamente desde Eloquent. La aplicación mantiene su zona horaria UTC; los timestamps tienen precisión de segundos. `IncidentLog::UPDATED_AT = null` conserva la gestión automática de `created_at` sin escribir una columna de modificación inexistente. Esto no convierte los logs en inmutables ni implementa todavía una política de reparación.

`IncidentStatus` centraliza `open`, `in_review`, `blocked` y `resolved`; `IncidentPriority`, `low`, `medium` y `high`. Los casts Eloquent convierten los strings almacenados en enums PHP. No se utilizan ENUM nativos ni CHECK como mecanismo de validación en MySQL 5.7; la futura validación de peticiones deberá respetar los enums y los límites del esquema. Los defaults de las migraciones son literales para conservar su independencia respecto a futuras modificaciones del código de los enums.

`assigned_to` contiene un nombre opcional, sin relación con `users`. Solicitante, responsable y autores son nombres, sin implicar una identidad autenticada. `metadata` se convierte a array; `old_value` y `new_value` permanecen como texto nullable, porque su significado dependerá de `action`. El vocabulario de acciones y el registro automático de cambios críticos se definirán al implementar esas operaciones; los datos de las factories solo sirven para preparar pruebas.

`Incident` tiene relaciones `logs()` y `comments()` de uno a muchos, y ambos modelos relacionados tienen `incident()`. Las claves foráneas restringen borrados y cambios del ID del padre para preservar historial y comentarios. Las relaciones se ordenan por `created_at` y después por `id` para resolver empates.

Índices para las consultas previstas del alcance:

- `incidents(created_at)`: listado por fecha sin filtros.
- `incidents(status, created_at)` y `incidents(priority, created_at)`: filtro por estado o prioridad con orden por fecha.
- `incident_logs(incident_id, created_at)` e `incident_comments(incident_id, created_at)`: historial y comentarios de una incidencia en orden temporal. Estos índices también soportan las claves foráneas, sin otro índice redundante en `incident_id`. InnoDB incorpora la clave primaria a los índices secundarios, permitiendo desempatar por `id`.

La selección de índices se revisará al implementar y medir las consultas reales. No se implementan aún CRUD, transiciones, controladores, seeders ni frontend. Las nuevas migraciones solo se han ejecutado en la base de pruebas; su aplicación en desarrollo queda pendiente del procedimiento de comprobación descrito en instalación. Los métodos `down()` eliminan las tablas y sus datos: no deben usarse como limpieza de desarrollo.

## Verificación y limitaciones

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

Posteriormente, con autorización expresa y tras comprobar la conexión efectiva, se aplicaron las tres migraciones del starter kit en `prueba_optimaretail`: nueve tablas InnoDB con `utf8mb4_unicode_ci`, sin migraciones pendientes. No se ejecutaron seeders, reinicios ni limpieza de datos. Se volvió a verificar que el entorno de pruebas conecta exclusivamente a `prueba_optimaretail_test`; no se repitió la suite en esta tarea operativa. La aplicación responde con HTTP 200 en la raíz y en `/login`. La revisión visual en navegador está pendiente: falló el arranque del navegador integrado y el control de Safari no obtuvo autorización. La instalación completa en otro equipo y la revisión manual del usuario siguen pendientes.

En la preparación posterior de CI se validaron el YAML, la sintaxis bash/PHP del workflow y la generación real de `.env.testing` en un directorio temporal, incluidos sus valores y permisos. La suite local volvió a pasar (30 aprobadas, 3 omitidas y 88 aserciones) sobre MySQL 5.7.44 en la base exclusiva de pruebas; también pasaron Pint, PHPStan con 512 MB, TypeScript, formato/lint del frontend y compilación. Se conserva el aviso opcional de fuentes explicado arriba.

Las instrucciones de instalación se revisaron contra la plantilla de entorno, los scripts, las migraciones y el seeder. `composer check-platform-reqs`, `composer install --dry-run --no-interaction --no-scripts` y `npm ci --dry-run --ignore-scripts --no-audit --no-fund` fueron correctos en el entorno existente. **No se ha realizado una instalación limpia**: esas simulaciones no descargan ni instalan todo desde cero ni prueban la preparación de una base nueva. Tampoco se ha ejecutado todavía el workflow en GitHub; Docker local no dispone de un daemon activo para reproducir su contenedor. Se repitió la comprobación HTTP de la raíz y `/login`, ambas con 200, pero la revisión visual sigue pendiente porque el navegador integrado falla al iniciarse con `Cannot redefine property: process`.
