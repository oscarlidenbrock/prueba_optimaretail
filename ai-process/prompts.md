# Registro de peticiones

## 2026-10-16 — Pruebas aisladas en MySQL 5.7.44

- **Resumen fiel del prompt:** configurar las pruebas en la base independiente `prueba_optimaretail_test`; guardar conexión y credenciales únicamente en `.env.testing`, excluido de Git; retirar las imposiciones SQLite de `phpunit.xml`; comprobar la base efectiva antes de ejecutar; no migrar, limpiar ni probar sobre `prueba_optimaretail`; ejecutar la suite y documentar sin credenciales.
- **Cambios y decisiones:** creada la base de pruebas con `utf8mb4_unicode_ci`; archivo local con permisos `600`; protección en `TestCase` antes de `RefreshDatabase` mediante validación de configuración y `SELECT DATABASE()` sobre lectura y escritura. Sin cambios en `.env`, `.env.example`, los requisitos ni las funcionalidades de autenticación. Documentación en README, desarrollo y proceso de IA.
- **Comprobaciones:** destino real verificado como `prueba_optimaretail_test`, servidor 5.7.44, tablas InnoDB; prueba de humo aprobada; suite final con 21 aprobadas, 3 omitidas por 2FA desactivado y 53 aserciones. Nombre de base incorrecto rechazado antes de conectar. Git confirma `.env.testing` ignorado y no versionado. Pint y PHPStan específico correctos tras corregir la comprobación de un posible `false` en PDO.
- **Incidencias y límites:** el sandbox bloqueó inicialmente PHPStan y el límite local de 128 MB resultó insuficiente; con 512 MB el análisis general señaló el método previo vacío `UserFactory::withTwoFactor()`. No se corrigió por estar fuera del alcance. CI y pruebas paralelas no verificadas.
- **Revisión manual:** pendiente de confirmación del usuario.

## 2026-10-16 — Completar README y versionar el alcance

- **Resumen fiel del prompt:** comprobar y completar el README según las carencias señaladas; corregir referencias desactualizadas en `AGENTS.md`; añadir README y documento de alcance a Git y realizar un commit.
- **Cambios y decisiones:** ampliadas las instrucciones y versiones, con instalación, configuración, migraciones y seeder para una base nueva, compilación, arranque, usuario de demostración y pruebas; diagnóstico marcado pendiente. Corregidas también las referencias obsoletas que estaban en el alcance y recogidas en `AGENTS.md` las decisiones ya acordadas. Commit limitado a documentación, instrucciones y registros; no incluye cambios previos de configuración o código de pruebas.
- **Comprobaciones:** requisitos de plataforma Composer correctos; PHP, Composer, Node, npm, `pdo_mysql` y MySQL comprobados; scripts y ayudas revisados; seis documentos sin enlaces locales rotos ni bloques de código sin cerrar; diff sin errores de espacios; no quedan las referencias obsoletas buscadas. Sin instalaciones, migraciones, seeders, pruebas ni compilaciones ejecutadas en esta tarea.
- **Revisión manual:** pendiente del usuario; instalación completa en otro equipo pendiente.
