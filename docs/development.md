# Desarrollo

## 2026-10-16 — Aislamiento de las pruebas en MySQL

- Creada la base independiente `prueba_optimaretail_test` y verificado mediante la conexión real el servidor MySQL Community 5.7.44. Las tablas generadas utilizan InnoDB y `utf8mb4_unicode_ci`.
- Conexión y credenciales de pruebas únicamente en `.env.testing`, excluido de Git y con permisos `600`. Eliminadas las variables de base de datos de `phpunit.xml` para que no imponga SQLite ni sobrescriba la conexión local.
- Añadida protección en `tests/TestCase.php`, anterior a `RefreshDatabase`: exige configuración sin caché, `.env.testing`, entorno `testing`, driver MySQL y base configurada y efectiva `prueba_optimaretail_test`, tanto en lectura como en escritura. Un nombre distinto fue rechazado antes de conectar.
- Comprobada la base efectiva antes de ejecutar la prueba de humo y la suite. No se ejecutaron migraciones, limpiezas ni tests sobre `prueba_optimaretail`.
- Verificación final: 21 pruebas aprobadas, 3 omitidas por 2FA desactivado y 53 aserciones; prueba de humo previa aprobada. Pint y PHPStan del archivo modificado correctos.
- Análisis estático general: el sandbox bloqueó inicialmente el socket de PHPStan; con acceso permitido, se agotó el límite de 128 MB. Con `--memory-limit=512M` se detectó un error previo: `UserFactory::withTwoFactor()` no devuelve el tipo declarado. Se conserva sin cambios por estar fuera de esta tarea.
- Documentadas las instrucciones y resultados en el README. CI y ejecución paralela no se han configurado ni verificado. Las funcionalidades de incidencias continúan pendientes. Revisión manual del usuario pendiente.

## 2026-10-16 — Instalación documentada y referencias de alcance

- Ampliado el README con versiones comprobadas de PHP, Composer, Node, npm y MySQL; instalación con los lockfiles; configuración local, migraciones y seeder del starter kit para una base nueva; acceso de demostración; compilación, arranque y preparación de pruebas. El diagnóstico y las funciones de incidencias permanecen explícitamente pendientes.
- Corregidas las rutas y las referencias a recomendaciones ya incorporadas en el documento de alcance. Actualizado `AGENTS.md` para indicar la ubicación de cada documento, corregir la referencia a las reglas superiores y recoger el aislamiento de pruebas ya solicitado. No se alteraron los requisitos de la empresa ni el PDF original.
- Comprobaciones: versiones locales y versión real del servidor MySQL; `composer check-platform-reqs` correcto; extensión `pdo_mysql` presente; ayudas y listas de comandos consultadas; seis documentos con enlaces locales y bloques de código correctos; `git diff --check` sin incidencias. La búsqueda de referencias obsoletas no encontró coincidencias.
- No se ejecutaron instalaciones, migraciones, seeders, pruebas ni compilaciones en esta tarea documental. Se conservan los resultados anteriores identificados como tales. Instalación completa en otro equipo y revisión manual del usuario pendientes.
- Alcance del commit documental: README, alcance, instrucciones y registros de desarrollo e IA. Los cambios previos de configuración y código de pruebas quedan fuera de este commit.

## 2026-10-16 — Versionar la configuración MySQL pendiente

- A petición del usuario, se incluyen los cuatro archivos que quedaron fuera del commit documental: `.env.example` con MySQL y credenciales vacías, `.gitignore` para excluir `.env.testing`, `phpunit.xml` sin imposiciones SQLite y `tests/TestCase.php` con la comprobación previa a `RefreshDatabase`. El README deja de indicar que esos cambios están pendientes de versionar.
- Antes de repetir la suite se verificó `SELECT DATABASE()` sobre la conexión real: `prueba_optimaretail_test`, servidor MySQL 5.7.44. Resultado: 21 aprobadas, 3 omitidas por 2FA desactivado y 53 aserciones; sintaxis PHP, Pint y diff correctos.
- `.env` y `.env.testing` permanecen ignorados y fuera del commit. No se realizaron migraciones, limpiezas ni tests sobre `prueba_optimaretail`. No se repitió el análisis estático general; su limitación anterior continúa documentada. Revisión manual del usuario pendiente.
