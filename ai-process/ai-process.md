# Uso de IA

Este documento resume la asistencia de IA en el proyecto y se actualiza al finalizar fases relevantes. Las peticiones de programación y los resultados operativos se conservan en los registros detallados enlazados al final, sin reproducir aquí su cronología.

## Herramientas de IA y herramientas de validación utilizadas

- **Asistencia de IA:** Codex para analizar requisitos, proponer cambios, generar código y pruebas y mantener documentación. Laravel Boost como apoyo para consultar documentación, esquema, versión real de MySQL y datos mediante consultas de lectura; también se consultaron el código instalado y documentación oficial.
- **Validación:** PHP/Artisan, Pest, Pint, PHPStan, TypeScript, scripts de formato y lint, compilación con Vite, Composer/npm, consultas SQL y revisión de diferencias con Git. Las respuestas HTTP se comprobaron por separado del intento de revisión visual con el navegador integrado.

Los resultados concretos y las limitaciones de cada comprobación están en el registro de desarrollo. No se registran credenciales ni contenidos de archivos de entorno.

## Partes del proyecto desarrolladas con asistencia de IA

- Preparación del entorno MySQL y aislamiento de pruebas, incluida la protección anterior a las migraciones de tests.
- Correcciones de la navegación de ajustes y de una factory del starter kit, con pruebas de regresión y sin ampliar la autenticación.
- Configuración de GitHub Actions con un servicio MySQL y credenciales exclusivas de CI, revisión de instalación y documentación del proyecto.
- Primera fase del modelo de datos: migraciones, modelos Eloquent, relaciones, enums, factories y tests de incidencias, logs y comentarios.
- Lógica interna de transiciones: reglas centralizadas, acción de cambio de estado con bloqueo y auditoría transaccional y tests de integridad, estados desactualizados y rollback.
- Backend de creación y listado: controlador, Form Requests, rutas autenticadas, filtros Eloquent y pruebas HTTP, con contrato Inertia reutilizado por la pantalla principal.
- Endpoint HTTP de cambio de estado: reutilización de la acción transaccional, actor autenticado y distinción entre validación y conflicto, con respuestas y flash compatibles con Inertia.
- Consulta de detalle e historial y creación de comentarios: relaciones ordenadas, contrato Inertia sin pantalla React, validación y autor autenticado, con pruebas HTTP.
- Pantalla principal React: componentes tipados, tabla/tarjetas, filtros y paginación Inertia, formulario de creación y navegación Wayfinder, reutilizando layout, componentes y confirmaciones del starter kit.

Edición, eliminación, seeders de incidencias, pantalla de detalle, comentarios y cambios de estado desde la interfaz y diagnóstico de inconsistencias permanecen pendientes.

## Decisiones técnicas y manuales confirmadas por el usuario

- Laravel, MySQL, Inertia + React, TypeScript y Tailwind; identificadores y comentarios en inglés e interfaz y documentación en español. Desarrollo incremental, conservando la autenticación del starter kit sin ampliarla.
- Pruebas exclusivamente en `prueba_optimaretail_test`, compatibles con MySQL 5.7, con credenciales locales en `.env.testing` excluido de Git. Se autorizaron migraciones normales en desarrollo tras comprobar destino y pendientes; siguen prohibidos allí los tests, la limpieza y los reinicios destructivos sin autorización expresa.
- Para la navegación de ajustes, limitar la ruta a GET/HEAD conservando destino y comportamiento. Para `UserFactory`, se autorizó implementar un estado real si las referencias existentes justificaban conservarlo; Codex eligió esa alternativa tras encontrar su uso.
- En el modelo de datos: estados y prioridades especificados por el usuario, persistencia VARCHAR, responsable como nombre opcional, metadata JSON nullable, valores auditados nullable y logs con solo `created_at`.
- En las transiciones: únicamente abierta a revisión y revisión a bloqueada o resuelta, rechazando repeticiones; bloqueo de fila y auditoría en una misma transacción, con propagación de errores y rollback. La validación del nombre del actor y el contrato de retorno son decisiones de implementación pendientes de revisión manual.
- En creación/listado: campos requeridos, estado inicial impuesto por servidor, filtros combinables, orden por fecha e ID y acceso autenticado sin ampliar la autenticación. Preparar el contrato Inertia sin crear una página React, los límites adicionales y mantener solo auditoría de cambios de estado son decisiones de implementación pendientes de revisión manual.
- En el endpoint de estado: ruta PATCH protegida con el acceso actual, validación mediante Form Request, reutilización de la acción existente, actor obtenido únicamente de la sesión y errores diferenciados. La excepción de dominio, los códigos JSON y el flash de conflicto en redirecciones Inertia son decisiones de implementación pendientes de revisión manual.

Los tamaños de campos, defaults, índices y política restrictiva de claves foráneas fueron decisiones de implementación de Codex, documentadas para revisión. No se consideran revisadas manualmente por el usuario. Tampoco se atribuyen al usuario comprobaciones visuales o de instalación limpia que no se hayan realizado.

Para detalle y comentarios, el usuario confirmó los órdenes temporales, el autor exclusivamente autenticado y la preparación del contrato sin componentes React. La estructura de props, el límite de contenido y las respuestas concretas son decisiones de implementación pendientes de revisión, detalladas en el registro de prompts y el README.

Para la pantalla principal, el usuario solicitó React 19, TypeScript y Tailwind 4, reutilizar el starter kit y el backend y limitar la interfaz a listado, filtros y creación. El diseño concreto, los breakpoints y los componentes extraídos son decisiones de implementación pendientes de revisión visual/manual; no se ha atribuido esa revisión al usuario.

## Errores detectados en el código asistido por IA y correcciones realizadas

Se distinguen los defectos heredados del starter kit de los errores introducidos durante el trabajo asistido:

- **PDO:** PHPStan detectó que el código generado usaba el resultado de `PDO::query()` sin comprobar su posible valor `false`. Se añadió la comprobación antes de llamar a `fetchColumn()`.
- **Wayfinder:** la ruta heredada de redirección aceptaba QUERY, ausente del tipo de métodos generado. Se corrigió la definición de la ruta para navegación GET/HEAD y se regeneró Wayfinder, sin editar archivos generados ni ampliar tipos para ocultar el fallo.
- **UserFactory:** el método heredado `withTwoFactor()` estaba vacío pese a declarar un retorno y seguía referenciado. Se implementó un estado con credenciales cifradas y confirmación, sin habilitar 2FA ni eliminar el test que lo utilizaba.
- **Tests del modelo de datos:** se corrigieron un cierre sobrante y comparaciones con microsegundos frente a timestamps de segundos, ajustando el reloj de prueba con `freezeSecond()`.
- **Transiciones:** se corrigió la consulta para obtener un único modelo sin ambigüedad de tipos y se tomaron los snapshots de pruebas desde MySQL para comparar atributos con el mismo orden, sin ocultar errores del análisis ni debilitar las aserciones.
- **Creación/listado:** se corrigieron las cabeceras de versión de assets de los tests Inertia y la búsqueda literal de caracteres especiales en MySQL 5.7, sin desactivar middleware ni debilitar comprobaciones. Detalles y resultados en el registro de desarrollo.

## Referencias a los registros detallados

- [Registro de prompts](prompts.md): peticiones cronológicas de programación, correcciones, refactorizaciones y arquitectura, con decisiones y revisiones pendientes.
- [Registro de desarrollo](../docs/development.md): configuración, verificaciones, resultados operativos, errores encontrados y limitaciones de cada fase, incluida la revisión visual, la instalación limpia y la ejecución remota de CI pendientes.
- [README](../README.md): instalación, estado funcional, decisiones del modelo de datos y comandos disponibles.
- [Alcance de la prueba](technical-assessment-scope.md): requisitos y decisiones del proyecto, diferenciados de la implementación.
