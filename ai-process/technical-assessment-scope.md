# Prueba técnica mid/senior: Panel operativo de incidencias

## Fuente y uso de este documento

Versión estructurada en Markdown del enunciado facilitado por la empresa. Conserva sus requisitos y alternativas; no es una transcripción literal. El PDF original es la referencia en caso de discrepancia. Conservar este documento en `ai-process/technical-assessment-scope.md` y el original en `docs/prueba_tecnica_mid_senior.pdf`; ambas rutas son relativas a la raíz del repositorio.

Las secciones 1 a 10 recogen el enunciado. La sección 11 identifica decisiones del proyecto y recomendaciones adicionales, que no deben confundirse con exigencias de la empresa.

Esta lista es una referencia de requisitos, no una declaración de funcionalidades implementadas. Registrar el progreso en el documento de desarrollo.

## 1. Objetivo y entrega

- Duración estimada: **90 minutos**.
- Aplicación: **Panel operativo de incidencias**.
- Crear el proyecto desde cero: no se proporciona un repositorio preparado.
- Entregar un repositorio de GitHub con instrucciones claras de ejecución.
- Construir una funcionalidad pequeña, realista, coherente, mantenible y razonada.
- Demostrar criterio en modelado, validación, relaciones, transacciones, trazabilidad, diagnóstico de inconsistencias y correcciones seguras.

## 2. Stack obligatorio

- PHP + Laravel.
- MySQL como base de datos.
- HTML y CSS.
- Bootstrap o Tailwind como apoyo visual.
- JavaScript para interacción de interfaz.
- Frontend con Blade/Livewire o Inertia + React, manteniendo una aproximación coherente.
- **No utilizar Next.js.**

El enunciado no fija versiones concretas. Se permite utilizar IA, siempre que se documente brevemente su uso y se demuestre comprensión, revisión y control del resultado.

## 3. Ejercicio 1: pantalla principal

La pantalla debe permitir consultar y operar incidencias e incluir:

- Cabecera con nombre de la aplicación y breve descripción funcional.
- Listado en tabla o tarjetas con prioridad, estado, fecha y responsable o solicitante.
- Indicadores visuales claros para abierta, en revisión, bloqueada y resuelta.
- Formulario de creación de incidencias.
- Acción visible para cambiar el estado.
- Acceso al historial de cambios.
- Diseño limpio, responsive y usable tanto en escritorio como en móvil.

La interfaz debe ser fácil de entender, mantener y ampliar. Evitar CSS caótico, componentes gigantes y markup innecesariamente acoplado.

## 4. Ejercicio 2: base de datos y modelo

Crear las migraciones necesarias para, como mínimo, estas entidades y campos:

| Tabla               | Campos mínimos                                                                                                  | Propósito                              |
| ------------------- | --------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| `incidents`         | `id`, `title`, `description`, `status`, `priority`, `requester_name`, `assigned_to`, `created_at`, `updated_at` | Incidencias registradas.               |
| `incident_logs`     | `id`, `incident_id`, `action`, `old_value`, `new_value`, `user_name`, `metadata`, `created_at`                  | Trazabilidad de cambios relevantes.    |
| `incident_comments` | `id`, `incident_id`, `author_name`, `body`, `created_at`, `updated_at`                                          | Comentarios operativos de seguimiento. |

Requisitos adicionales:

- Utilizar claves foráneas donde corresponda.
- Definir índices razonables para consultas habituales por estado, prioridad y fecha.
- Centralizar los estados para evitar strings mágicos dispersos.
- Validar la coherencia de los cambios de estado.
- Guardar los cambios críticos en `incident_logs`.
- Utilizar transacciones cuando una acción modifique la incidencia y su trazabilidad asociada.

El enunciado no especifica todos los tipos, longitudes, campos anulables, niveles de prioridad ni políticas de borrado. Deben decidirse con criterio y documentarse. Tampoco obliga a crear una tabla de usuarios para `assigned_to`.

## 5. Ejercicio 3: funcionalidad mínima

- Crear incidencias con validación de datos.
- Listar incidencias con filtros por estado, prioridad y texto libre.
- Cambiar estados respetando las transiciones permitidas.
- Registrar cada cambio de estado en `incident_logs`, incluyendo valor anterior, valor nuevo, usuario o actor y fecha.
- Mostrar historial de cambios y comentarios de una incidencia.
- Añadir comentarios operativos.
- Mostrar errores de validación de forma clara.

### Transiciones mínimas

| Estado inicial | Estado de destino |
| -------------- | ----------------- |
| Abierta        | En revisión       |
| En revisión    | Bloqueada         |
| En revisión    | Resuelta          |

Una incidencia resuelta no debe volver a abierta sin un log explícito de reapertura. El enunciado condiciona la reapertura a ese registro; no exige expresamente un botón de reapertura. Documentar las transiciones adicionales que se implementen.

## 6. Ejercicio 4: interacción de frontend

Implementar **al menos dos** de las siguientes funcionalidades:

1. Filtro rápido por estado sin recargar toda la página.
2. Confirmación antes de resolver o bloquear una incidencia.
3. Contador visual de incidencias por estado.
4. Resaltado de incidencias vencidas o bloqueadas.
5. Componente React o Livewire para filtrar, actualizar o resaltar incidencias.

No se exige una arquitectura frontend compleja, pero debe entenderse dónde vive el estado, dónde se dispara cada acción y cómo se refresca la interfaz.

Nota de interpretación: la fecha de vencimiento no es un campo mínimo obligatorio. No es necesario añadirla si se eligen otras interacciones o se resaltan únicamente las incidencias bloqueadas.

## 7. Ejercicio 5: diagnóstico y corrección de inconsistencias

Simular una situación de mantenimiento dentro del proyecto y documentar su corrección en el README.

### Datos de ejemplo obligatorios

Incluir al menos estos tres casos:

1. Incidencia resuelta sin log de resolución.
2. Incidencia cuyo estado no coincide con su último log.
3. Log duplicado o incoherente.

### Diagnóstico y tratamiento

- Detectar las inconsistencias mediante SQL o Eloquent.
- Proporcionar un mecanismo claro: comando Artisan, clase de servicio, script documentado o consulta claramente identificable.
- Corregir de forma segura, explicando si se crean logs faltantes, se ajustan estados o se marcan casos para revisión manual.
- Incluir una opción `dry-run` **o una explicación equivalente** que permita ver qué se corregiría antes de modificar datos.
- Documentar la consulta utilizada y explicar por qué detecta el problema.
- No corregir automáticamente los casos sin información suficiente. Explicar los que se dejan para revisión manual.

## 8. Uso de IA

Si se utiliza IA, crear **`/ai-process` en la raíz del repositorio** e incluir un archivo breve con:

- Herramientas utilizadas.
- Prompts principales o resumen de los prompts.
- Partes generadas con ayuda de IA.
- Cambios, correcciones o decisiones tomadas manualmente.
- Errores detectados en la salida de la IA y cómo se corrigieron.

No se exige un registro exhaustivo de todas las conversaciones. Basta con demostrar que se ha dirigido y revisado el proceso.

## 9. Entregables y README

El repositorio de GitHub debe incluir:

- Código fuente.
- Migraciones y seeders necesarios.
- README con instrucciones de instalación y ejecución.
- [`.env.example`](../.env.example) sin credenciales reales.
- Explicación de las inconsistencias simuladas y su diagnóstico/corrección.
- Documentación de uso de IA cuando corresponda.

El README debe permitir ejecutar el proyecto sin adivinar e incluir:

- Versiones necesarias de PHP, Composer, Node y MySQL.
- Pasos de instalación.
- Comandos para migrar y cargar datos de ejemplo.
- Comando para levantar la aplicación.
- Comando o pasos para ejecutar el diagnóstico.
- Usuario de prueba si se implementa autenticación, que es opcional.
- Notas sobre decisiones técnicas.

## 10. Alcance recomendado por la empresa

No es necesario implementar:

- Autenticación completa.
- Roles y permisos avanzados.
- Notificaciones.
- Tests extensos.
- Docker.
- Despliegue público.
- Integraciones externas.

Se valora una solución pequeña, consistente y ejecutable frente a una aplicación grande e incompleta. El criterio técnico tiene más importancia que la cantidad de pantallas.

## 11. Decisiones del proyecto y recomendaciones adicionales

Esta sección no amplía los requisitos obligatorios de la empresa.

### Decisiones ya acordadas

- Utilizar Inertia + React y Tailwind CSS.
- Utilizar MySQL de MAMP durante el desarrollo local.
- Escribir identificadores y comentarios de código en inglés; documentación e interfaz en español.
- Aplicar SOLID de forma proporcionada, KISS y YAGNI, evitando capas innecesarias.
- Documentar reglas y decisiones no evidentes sin exigir comentarios redundantes en cada función.
- Añadir pruebas útiles de las funcionalidades implementadas.
- Mantener un registro breve y fiel de peticiones y resultados en `/ai-process/prompts.md`.
- Aislar las pruebas de base de datos en MySQL `prueba_optimaretail_test`, con conexión y credenciales locales únicamente en `.env.testing`, excluido de Git, y comprobar el destino efectivo antes de migrar o limpiar tablas de pruebas.

### Recomendaciones sobre base de datos incorporadas a AGENTS.md

- Mantener MySQL como fuente de verdad del estado y del historial.
- Utilizar tablas transaccionales, relaciones y políticas de borrado explícitas; evitar borrar trazabilidad accidentalmente.
- Definir tipos, longitudes, nulabilidad y valores de prioridad antes de implementar los formularios.
- Mantener una codificación `utf8mb4` y una collation compatible con la versión real del servidor.
- Verificar la versión del servidor MySQL y no deducirla de `mysql --version`, que muestra la del cliente.
- Evitar SQL o restricciones que dependan de versiones no verificadas; documentar la compatibilidad efectivamente comprobada.
- Proteger los cambios de estado frente a escrituras concurrentes mediante bloqueo de fila dentro de la transacción u otra estrategia justificada.
- Ordenar los logs de forma determinista y distinguir logs de cambio de estado de otras acciones.
- Paginar listados y evitar consultas N+1. Justificar los índices según las consultas reales.
- Separar seeders normales de los datos deliberadamente inconsistentes.
- Usar una base de datos de pruebas independiente. No ejecutar resets sobre los datos de desarrollo sin autorización.
- Hacer las reparaciones seguras de repetir, conservar su trazabilidad y no inventar hechos históricos.

### Recomendaciones sobre caché incorporadas a AGENTS.md

- El enunciado no exige caché de datos ni Redis. No introducirlos inicialmente.
- Consultar directamente MySQL para listados, contadores, transiciones y diagnóstico mientras el volumen de datos sea pequeño.
- Distinguir la caché interna del framework de una caché funcional de incidencias. Revisar el driver ya configurado antes de cambiarlo.
- Si se utiliza el driver de caché de base de datos, incluir sus migraciones e instrucciones necesarias; no asumir que las tablas ya existen.
- No usar datos cacheados como autoridad para validar una transición o reparar inconsistencias.
- Si más adelante se justifica cachear consultas, documentar claves, filtros incluidos en ellas, TTL e invalidación tras confirmar la transacción. Evitar respuestas obsoletas después de cambios.

### Referencia operativa incorporada a AGENTS.md

Usar `ai-process/technical-assessment-scope.md` como referencia operativa de requisitos. Antes de implementar una funcionalidad, identificar las secciones aplicables; al terminar, comprobar los criterios correspondientes. Conservar el PDF como fuente original y resolver discrepancias explícitamente. No modificar los requisitos para hacerlos coincidir con la implementación.
