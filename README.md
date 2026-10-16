# Panel operativo de incidencias

Aplicación para consultar y gestionar incidencias, sus estados, comentarios e historial. El proyecto está en preparación: conserva las pantallas y la autenticación del starter kit; las funcionalidades de incidencias todavía no están implementadas.

Los requisitos están en [ai-process/technical-assessment-scope.md](ai-process/technical-assessment-scope.md), la fuente original en [docs/prueba_tecnica_mid_senior.pdf](docs/prueba_tecnica_mid_senior.pdf) y el progreso en [docs/development.md](docs/development.md). El uso de IA se documenta en [ai-process/ai-process.md](ai-process/ai-process.md) y las peticiones en [ai-process/prompts.md](ai-process/prompts.md).

## Tecnologías y requisitos

Versiones comprobadas en el entorno local el 16 de octubre de 2026:

| Herramienta | Versión comprobada |
| --- | --- |
| PHP CLI | 8.5.0, con `pdo_mysql` |
| Composer | 2.8.12 |
| Node.js / npm | 24.19.0 / 11.17.0 |
| MySQL Community Server de MAMP | 5.7.44 |

Dependencias instaladas: Laravel 13.36.0, Inertia Laravel 3.5.1, Inertia React 3.9.1, React 19.3.1, TypeScript 5.9.3, Tailwind CSS 4.3.3 y Vite 8.3.4. Se utiliza Pest 5.3.2 para las pruebas. Conservar `composer.lock` y `package-lock.json` para instalar las versiones resueltas.

Usar estas versiones para reproducir el entorno; no se acredita compatibilidad con otras versiones de MySQL. El proyecto no utiliza Next.js. Redis y Docker no son necesarios para esta instalación.

## Instalación local

Estos pasos describen una instalación nueva con una base vacía, por ejemplo `panel_incidents_local`. No ejecutarlos contra la base existente `prueba_optimaretail`: en este entorno se ha indicado expresamente no migrarla, limpiarla ni utilizarla para pruebas.

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

3. Iniciar MySQL en MAMP y crear una base nueva, desde su cliente SQL, con:

   ```sql
   CREATE DATABASE panel_incidents_local
       CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Comprobar que el motor predeterminado sea InnoDB. Definir en `.env` `DB_CONNECTION=mysql`, `DB_DATABASE=panel_incidents_local` y los valores reales de `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD`. Si la conexión utiliza socket, definir `DB_SOCKET` con la ruta real de MAMP. El puerto de la plantilla es orientativo. Mantener `DB_CHARSET=utf8mb4` y `DB_COLLATION=utf8mb4_unicode_ci`; no utilizar collations exclusivas de MySQL 8.

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

   Continuar únicamente si el destino es la base nueva elegida. Si Laravel conserva una configuración anterior, ejecutar `php artisan config:clear --no-interaction` y repetir la comprobación.

6. Preparar esa base nueva y compilar el frontend:

   ```bash
   php artisan migrate --no-interaction
   npm run build
   ```

   Las migraciones actuales crean las tablas del starter kit, incluidas sesiones, caché y colas. Las migraciones de incidencias siguen pendientes. `SESSION_DRIVER`, `CACHE_STORE` y `QUEUE_CONNECTION` utilizan la base de datos; no hace falta instalar Redis.

No utilizar `composer setup` en la base existente: ese script ejecuta migraciones automáticamente antes de compilar.

## Datos de ejemplo y acceso

En una instalación nueva ya migrada, el seeder actual puede ejecutarse una vez:

```bash
php artisan db:seed --no-interaction
```

Crea únicamente el usuario de demostración del starter kit: correo `test@example.com` y contraseña `password`. Son datos sintéticos de demostración, no credenciales de MySQL. Ejecutarlo de nuevo puede fallar porque el correo ya existe. No hay todavía seeders de incidencias ni fixtures inconsistentes.

La autenticación procede del starter kit y no es obligatoria para la prueba técnica. El panel heredado requiere iniciar sesión.

## Arranque

Con la base nueva preparada, ejecutar:

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

La suite prepara y reinicia únicamente la base de pruebas mediante `RefreshDatabase`; no necesita una migración manual previa. No ejecutar migraciones, limpiezas ni pruebas sobre `prueba_optimaretail`. Una instalación nueva o CI debe aprovisionar su propia base y `.env.testing`; el workflow existente todavía no realiza esa preparación.

## Diagnóstico de inconsistencias

Pendiente de implementación. Todavía no existen el comando o servicio de diagnóstico, sus consultas SQL/Eloquent, la opción `dry-run` ni los tres casos de ejemplo exigidos: resolución sin log, estado distinto del último cambio y log duplicado o incoherente. No hay un comando de diagnóstico ejecutable que pueda documentarse aún.

Al implementar esa tarea se añadirán aquí los comandos reales, las consultas, su justificación, la política de reparación y los casos que requieren revisión manual. Las incidencias, sus estados, comentarios e historial también siguen pendientes.

## Decisiones técnicas

- Laravel con Inertia y React, TypeScript y Tailwind; Wayfinder para los enlaces y acciones del frontend. Sin API separada ni Next.js.
- MySQL como fuente de datos; tablas transaccionales InnoDB y collation compatible con el servidor 5.7.44 comprobado.
- Base de pruebas independiente, configuración local no versionada y comprobación del destino antes de cualquier reinicio de tablas.
- Se conserva la caché de infraestructura del starter kit en base de datos. No se ha añadido caché de incidencias ni Redis.
- Identificadores y comentarios de código en inglés; documentación e interfaz del dominio en español.

## Verificación y limitaciones

Última ejecución de pruebas registrada el 16 de octubre de 2026, al preparar el commit de configuración MySQL y pruebas aisladas:

- Prueba de humo: 1 aprobada.
- Suite completa: 21 aprobadas, 3 omitidas por 2FA desactivado y 53 aserciones.
- Tablas de pruebas verificadas en MySQL 5.7.44 con InnoDB y `utf8mb4_unicode_ci`.
- Protección comprobada con un nombre de base inválido: rechazo antes de conectar o migrar.
- Pint y PHPStan sobre `tests/TestCase.php`: correctos.
- Análisis estático general: pendiente de corregir un problema previo en `database/factories/UserFactory.php:49`; `withTwoFactor()` declara un retorno pero está vacío. El análisis necesitó `--memory-limit=512M` al superar el límite local de 128 MB. No se ha modificado esa funcionalidad.

La documentación se contrastó con las versiones, scripts y ayudas locales, y se comprobaron sus enlaces y el diff. Antes del commit de configuración se verificó de nuevo la base efectiva `prueba_optimaretail_test` en MySQL 5.7.44 y se repitieron la suite, la validación de sintaxis y Pint con éxito. No se ejecutaron instalaciones, seeders ni compilaciones, ni operaciones de escritura sobre la base de desarrollo. La instalación completa en otro equipo y la revisión manual del usuario siguen pendientes.
