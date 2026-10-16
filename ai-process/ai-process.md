# Uso de IA

Este resumen explica cómo se utilizó IA para desarrollar el **Panel operativo de incidencias** y qué control se aplicó sobre sus resultados. No sustituye al enunciado ni al historial: las peticiones de programación y las verificaciones se mantienen en documentos separados. Se actualiza al cerrar fases relevantes, sin convertir cada tarea en una entrada cronológica.

## Herramientas de IA y de validación

Codex asistió en el análisis del enunciado, la implementación incremental, la preparación de pruebas y la documentación. Laravel Boost permitió consultar documentación, esquema y datos mediante lecturas; cuando fue necesario, se revisaron el código instalado, metadatos de paquetes y documentación oficial. Las propuestas de IA se contrastaron con el alcance y las convenciones del proyecto.

Se utilizaron PHP/Artisan, Pest, Pint, PHPStan, TypeScript, Vite Plus, Vitest, Composer/npm, SQL y Git. GitHub Actions proporcionó validación remota del commit identificado en el registro de desarrollo. Los resultados HTTP, el renderizado estático y la comprobación manual en navegador son evidencias distintas. No se incluyen secretos ni contenidos de archivos de entorno en estos registros.

## Contribuciones con asistencia de IA

La asistencia abarcó migraciones, modelos Eloquent, relaciones, enums, factories y pruebas de incidencias, logs y comentarios. También incluyó creación y listado con filtros combinables, validación mediante Form Requests, paginación y operaciones HTTP protegidas por la autenticación existente.

La operación de cambio de estado quedó encapsulada en una acción: consulta y bloqueo del registro dentro de una transacción, validación del estado actual y escritura conjunta de la incidencia y su auditoría. Las pruebas cubren transiciones válidas, inválidas, repetidas, instancias obsoletas y rollback.

En React se construyeron listado responsive, formulario de creación, filtros Inertia, detalle, historial, comentarios y cambios de estado. Wayfinder conserva las rutas tipadas; el servidor determina los destinos permitidos. La confirmación de bloqueo o resolución se comparte entre listado y detalle, sin duplicar la matriz de negocio.

Se añadieron tres fixtures de inconsistencias independientes, diagnóstico de solo lectura y evaluación conservadora de reparaciones. Las consultas, evidencia y límites están documentados. Los ejemplos ambiguos requieren revisión humana: no se fabrican acontecimientos ni se borran logs para aparentar una reparación.

## Decisiones humanas y verificaciones confirmadas

El usuario eligió Laravel, MySQL, Inertia + React, TypeScript y Tailwind; pidió identificadores en inglés e interfaz/documentación en español. Dirigió el desarrollo por fases, conservando la autenticación del starter kit y evitando dependencias, servicios o funcionalidades innecesarias.

También especificó estados y prioridades, almacenamiento VARCHAR compatible con MySQL 5.7, responsable textual opcional, metadata JSON nullable y logs con solo fecha de creación. Solicitó transiciones limitadas, actor autenticado, bloqueo previo y auditoría atómica. Los tamaños, índices, contratos y respuestas concretas fueron decisiones de implementación de Codex; no se atribuye al usuario una revisión exhaustiva de cada una.

El usuario confirmó la instalación limpia desde un clon independiente en el mismo Mac, usando `prueba_optimaretail_check`, con migraciones, seeder, build, login y creación correctos. Confirmó además las comprobaciones manuales de interfaz y responsive. Estas confirmaciones no se presentan como acciones ejecutadas por el navegador del agente, que había fallado al inicializarse, ni como pruebas de todos los dispositivos o de concurrencia real.

Para la seguridad del toolchain autorizó conservar las versiones funcionales y fijar Tinypool bajo Oxfmt mediante un override acotado. La compatibilidad se verificó primero en aislamiento y después en el proyecto original. El CI aprobado corresponde a `61120b7`; no acredita los cambios posteriores todavía no publicados.

## Errores detectados y correcciones

Se distinguen defectos heredados del starter kit de errores o estimaciones del trabajo asistido:

- **Wayfinder:** la redirección heredada de ajustes aceptaba QUERY, incompatible con los métodos generados. Se limitó a GET/HEAD y se regeneraron las rutas, sin editar archivos generados ni ampliar tipos para ocultar el problema.
- **UserFactory:** `withTwoFactor()` estaba vacío pese a declarar retorno y seguir referenciado. Se implementó un estado real con credenciales cifradas, sin activar 2FA ni eliminar el test.
- **PDO:** PHPStan detectó que el código generado no comprobaba el posible retorno `false` de `PDO::query()`. Se añadió la comprobación antes de `fetchColumn()`.
- **Pruebas y consultas:** se corrigieron sintaxis, precisión temporal, cabeceras Inertia, búsqueda literal y consumo prematuro del flash, conservando las aserciones relevantes.
- **Dependencias:** actualizar solo Vite Plus no bastó: su alias permitió compilar, pero produjo ERESOLVE en instalación limpia. La alternativa autorizada mantiene el toolchain y corrige Tinypool; pasó instalación, audits y verificaciones frontend. No se declaró compatible únicamente por ser una versión patch.

## Referencias al historial

- [Prompts](prompts.md): programación, correcciones y arquitectura; se conservan íntegramente sus entradas.
- [Desarrollo](../docs/development.md): configuración, resultados, confirmaciones humanas, alternativas fallidas y limitaciones. La corrección de Tinypool se registra allí, no como programación.
- [README](../README.md): instalación, ejecución, diagnóstico y estado de verificación.
- [Alcance](technical-assessment-scope.md): requisitos y decisiones diferenciados de la implementación; el [PDF original](../docs/prueba_tecnica_mid_senior.pdf) sigue siendo la referencia principal.
