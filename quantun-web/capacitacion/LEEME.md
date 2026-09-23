# Capacitación CMS — Taxi Taxi

Módulo autónomo para la habilitación del equipo de **taxi-taxi.com.co** en la gestión de su WordPress.
Vive dentro de `quantun-web/` y se publica en `public_html/capacitacion/`.

**No toca nada del CRM ni de la base de datos.** Guarda todo en un único archivo JSON dentro de `data/`.

---

## URL final

| Vista | URL |
|---|---|
| Landing pública | `https://quantundigital.com/capacitacion/` |
| Acceso estudiantes | `https://quantundigital.com/capacitacion/index.php?v=acceso` |
| Panel del instructor | `https://quantundigital.com/capacitacion/index.php?v=admin` |

---

## Despliegue en Hostinger

El módulo está dentro de `quantun-web/`, así que el pipeline existente ya lo publica:

```
quantun-web/  →  public_html/          (rsync --delete, excluye crm/)
```

Al hacer push a `master`, `capacitacion/` aparece en `public_html/capacitacion/`.
**No se borra ni se modifica nada de lo que ya está en el hosting**: es una carpeta nueva.

Si prefieres subirlo a mano por el Administrador de archivos de hPanel:

1. Comprime la carpeta `capacitacion/` completa.
2. Súbela a `public_html/` y descomprímela.
3. Verifica que `data/` tenga permisos de escritura (`755` en la carpeta).
4. Entra a `https://quantundigital.com/capacitacion/index.php?v=admin` y crea la contraseña de administrador.

> La carpeta `data/` se crea sola en el primer uso si no existe.

---

## Primer uso

1. Abre `index.php?v=admin`. La primera vez pide **crear la contraseña del administrador**
   (mínimo 10 caracteres, se guarda con `password_hash`, no se puede recuperar).
2. En el panel, agrega a cada estudiante con **nombre, apellidos, correo y cargo**,
   y define **la contraseña** con la que entrará (botón *Generar* si quieres una sugerida).
3. Al guardar se muestran las credenciales **una sola vez**: cópialas y envíalas por canal
   privado junto al enlace de la landing. La contraseña se guarda cifrada con `password_hash`
   y no se puede volver a ver; si se pierde, defines una nueva con el botón de llave en la tabla.
4. El estudiante entra con **correo + contraseña**, **firma el acuerdo de uso** (8 cláusulas)
   y desde ahí puede marcar el temario.
5. Tú marcas la **asistencia** de cada clase en la pestaña *Asistencia*.
6. Cuando el estudiante tiene acuerdo + 5 asistencias + 100% del temario,
   se le habilita la **evaluación de 5 preguntas** (aprueba con 4, hasta 3 intentos).
7. Con la evaluación aprobada, en *Entrega de accesos* registras el **rol de WordPress**
   asignado y la fecha. **Nunca guardes contraseñas aquí.**

---

## Roles, tareas y reportes

Un solo login (correo + contraseña) para todos; el sistema detecta el rol.

| Rol | Qué puede hacer |
|---|---|
| **Administrador** (cuenta propia, *Mi cuenta*) | Todo: usuarios y roles, tareas, programa, asistencia, entrega de accesos, reportes, bitácora |
| **Supervisor** (se crea en *Usuarios*) | Crear, editar, reasignar y eliminar tareas; ver cumplimiento; ver usuarios (solo lectura); reportes |
| **Estudiante** | Su capacitación (acuerdo, temario, evaluación) y sus tareas: *Empezar* / *Marcar como completada* con nota |

Los permisos del supervisor se definen en `CAP_PERMISOS_SUPERVISOR` (`lib/config.php`) y se validan
también en `api.php`, no solo en la interfaz.

**Tareas:** título, descripción, prioridad y fecha límite opcional. Se asignan marcando estudiantes
(o *Todos*). Por cada asignado se registra: asignada, iniciada, completada, tiempo que tomó y nota.
Las no completadas después de la fecha límite se marcan como *Vencidas*. Una tarea completada se puede
reabrir para un estudiante. Al eliminar un usuario se limpian sus asignaciones.

**Reportes** (`reporte.php?tipo=tareas|evaluacion|general`): documento A4 con cumplimiento por estudiante,
detalle de cada tarea, resultado de la evaluación por estudiante, contenido completo de la evaluación
(preguntas, opciones, respuesta correcta y % de aciertos) y las respuestas de cada intento.
*Exportar PDF* abre el diálogo de impresión del navegador → *Guardar como PDF* (texto real, sin librerías).

---

## Programa (definido en `lib/config.php`)

5 clases repartidas en **2 jornadas de 2 horas** (4 horas en total), los dos viernes siguientes al alta del programa:

| Jornada | Fecha | Horario del día | Clase | Tema | Bloque |
|---|---|---|---|---|---|
| 1 | viernes 25 de septiembre de 2026 | 4:00 – 6:00 p.m. | 1 | Gestionar el panel | 4:00 – 4:35 p.m. |
| 1 | viernes 25 de septiembre de 2026 | 4:00 – 6:00 p.m. | 2 | Accesos y copias de seguridad | 4:35 – 5:15 p.m. |
| 1 | viernes 25 de septiembre de 2026 | 4:00 – 6:00 p.m. | 3 | Creación de contenido | 5:15 – 6:00 p.m. |
| 2 | viernes 2 de octubre de 2026 | 4:00 – 6:00 p.m. | 4 | Mantenimiento del CMS | 4:00 – 5:00 p.m. |
| 2 | viernes 2 de octubre de 2026 | 4:00 – 6:00 p.m. | 5 | Mantenimiento e informes | 5:00 – 6:00 p.m. |

29 temas en total, con check individual. Las fechas de las jornadas son relativas al momento en que se
configuró el programa — si cambian, edita `fecha` en cada clase dentro de `cap_sesiones()`.

### Cómo cambiar fechas, temas, cargos o preguntas

Todo está en **`lib/config.php`**, en un solo lugar:

- `cap_sesiones()` — títulos, fechas (`YYYY-MM-DD`), resumen y lista de temas.
- `CAP_HORA_INICIO` / `CAP_HORA_FIN` — horario que se muestra.
- `CAP_CARGOS` — opciones del selector de cargo.
- `CAP_CLAVE_MIN` — longitud mínima de la contraseña del estudiante (8 por defecto).
- `cap_consentimiento()` — cláusulas del acuerdo.
- `cap_quiz()` — preguntas, opciones, índice de la respuesta correcta (`c`, base 0) y clase de repaso (`ref`).
- `CAP_QUIZ_MINIMO` (4) y `CAP_QUIZ_MAX_INT` (3).

Si agregas o quitas temas de una clase, el progreso ya guardado se reajusta solo
(`cap_normalizar_estudiante()`); si cambias las preguntas del quiz, sube `CAP_CONSENT_VER`
o reinicia la evaluación del estudiante desde el panel.

---

## Estructura

```
capacitacion/
├── index.php        landing pública + login estudiante/admin + setup inicial
├── panel.php        panel del estudiante (progreso, temario, acuerdo, quiz, accesos)
├── admin.php        panel del instructor (estudiantes, asistencia, accesos, bitácora)
├── api.php          endpoints JSON (POST, CSRF, control de rol)
├── reporte.php      reportes imprimibles / exportables a PDF
├── assets/
│   ├── cap.css      design system QUANTUN aplicado al módulo
│   ├── reporte.css  hoja A4 de los reportes (pantalla + impresión)
│   └── cap.js       checklist en vivo, modal de confirmación, toasts, tabs
├── lib/
│   ├── config.php   PROGRAMA, TEMARIO, ACUERDO Y QUIZ  ← editar aquí
│   ├── store.php    persistencia JSON con bloqueo + métricas
│   ├── auth.php     sesión aislada, CSRF, límite de intentos
│   └── ui.php       head, nav, footer e iconos SVG
├── data/            capacitacion.json (se crea solo) — bloqueado por .htaccess
└── .htaccess        bloquea data/ y lib/, sin listado de directorios
```

---

## Seguridad aplicada

- Sesión propia (`QDCAPSESS`) con cookie `HttpOnly` + `SameSite=Lax`, aislada de la del CRM.
- CSRF en todas las acciones; toda escritura es POST.
- Límite de 6 intentos de ingreso cada 10 minutos.
- Contraseñas de administrador y de estudiantes con `password_hash` (bcrypt); nunca se almacenan en texto plano.
- El instructor asigna la contraseña de cada estudiante y solo la ve en el momento de crearla o rotarla.
- `data/` y `lib/` bloqueados por `.htaccess` y por `RewriteRule`; `Options -Indexes`.
- Todas las salidas escapadas con `htmlspecialchars`.
- `noindex, nofollow` en todas las páginas.
- Bitácora con fecha, actor, acción e IP de cada evento (ingreso, tema, acuerdo, evaluación, accesos).
- **El módulo nunca almacena credenciales de WordPress**, solo el rol asignado y la fecha de entrega.

---

## Respaldo

El estado completo vive en `data/capacitacion.json`. Para respaldar, descarga ese archivo.
Para reiniciar el módulo desde cero, bórralo: se regenera vacío y volverá a pedir
la creación de la contraseña de administrador.
