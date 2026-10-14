# Prueba Técnica — Desarrollador/a Junior PHP

**MisterPlan** — Empresa de software turístico con 20+ años en el mercado español.

---

Vas a construir una pequeña aplicación de gestión de reservas: una API en PHP (sin framework), páginas renderizadas con **Twig** y una interfaz con **HTML + JavaScript (vanilla / jQuery)**. Es, en pequeño, lo que hacemos cada día.

Nos interesa ver cómo trabajas con los fundamentos que usamos a diario: PHP, SQL, HTML, Twig y JavaScript, sin apoyarte en un framework.

---

## Qué construyes

Una app de "Reservas de alojamiento" con:

1. **API en PHP nativo** (sin Laravel, Symfony ni similar) que gestione reservas.
2. **Vistas con Twig** que pinten la lista y el detalle de una reserva.
3. **Interfaz con JS** (vanilla o jQuery) que consuma la API: filtrar, crear y cancelar sin recargar la página.

---

## PARTE 1: BACKEND — PHP nativo (peso principal)

Una API que gestione reservas de alojamiento. **Sin framework.** PHP puro.

**Base de datos: MySQL/MariaDB (preferido).** Es lo que usamos en todo el core. Puedes montarlo gratis en local con MAMP o WAMP. Se acepta MongoDB como alternativa si lo dominas mejor, pero MySQL suma.

**Importante:** incluye en el repo un fichero `.sql` con la creación de las tablas (DDL) **y datos de ejemplo** (INSERTs), para que podamos levantar tu BD con un solo import. Si usas MongoDB, incluye el script equivalente de creación y poblado.

### Requisitos

1. Endpoints para:
   - Listar reservas (con filtros, ver abajo)
   - Ver el detalle de una reserva
   - Crear una reserva
   - Cambiar el estado de una reserva (confirmar / cancelar)
2. Filtros combinables en el listado: por estado, por rango de fechas y búsqueda por nombre de huésped.
3. **Validación de los datos de entrada** en el servidor (fechas coherentes, email válido, campos obligatorios).
4. Organiza el código en **clases con una responsabilidad clara**: separa la gestión de la reserva del acceso a datos, por ejemplo.
5. Respuestas JSON con los **códigos de estado HTTP correctos** (200, 201, 400, 404...).
6. Ten cuidado con la seguridad. Es una API que recibe datos de fuera; piensa en ello. Lo revisamos.

### Modelo de datos

- **reserva**: id, nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado (`pendiente` | `confirmada` | `cancelada`), importe, notas, fecha_creacion
- **reserva_evento** (historial): id, reserva_id, tipo, descripcion, fecha_creacion
  - Cada vez que se crea o cambia de estado una reserva, se añade una fila aquí.

### Bonus backend (suma, no obligatorio)

- Separar la capa de acceso a datos de la lógica de negocio.
- Datos de ejemplo variados y realistas en el `.sql` (reservas en distintos estados, para que los filtros se vean funcionar).
- Algún test (con PHPUnit, Pest o lo que conozcas). Prioriza unos pocos tests con sentido sobre una cobertura amplia.
- Manejo de errores centralizado.

---

## PARTE 2: VISTAS — Twig (peso medio)

Usa **Twig** para renderizar al menos dos vistas en servidor:

1. **Listado de reservas**: tabla con las reservas y sus filtros.
2. **Detalle de una reserva**: datos de la reserva + su historial de eventos (timeline).

### Técnico

- Usa **plantilla base + herencia** (`{% extends %}`, `{% block %}`). No repitas el `<html><head>...` en cada vista.
- Aprovecha el **autoescaping** de Twig. Si en algún punto usas `|raw`, explica en el README por qué era seguro.
- Filtros/formato en la propia plantilla donde tenga sentido (fechas, importes).

---

## PARTE 3: FRONTEND — HTML + JavaScript (peso medio)

Sobre las vistas Twig, añade interacción con **JavaScript (vanilla o jQuery, lo que domines)** que consuma tu API:

### Requisitos

1. **Filtrar el listado** llamando a la API (sin recargar la página) y repintar la tabla.
2. **Formulario para crear una reserva**: validación en cliente + mostrar los errores que devuelva el servidor.
3. **Cancelar una reserva** desde el listado o el detalle (con confirmación).
4. **Feedback visual**: estado de "cargando" y aviso de éxito/error (un simple mensaje vale, no hace falta librería de toasts).

### Técnico

- HTML semántico y legible.
- JS organizado (evita lógica inline en el HTML).
- Maneja los errores de red (qué pasa si la API devuelve 400 o 500).
- CSS básico y responsive (no buscamos diseño espectacular, sí que se entienda y no se rompa en móvil).

---

## PARTE 4: README

- **Cómo levantarlo en local**: pasos concretos (crear BD, configurar conexión, arrancar).
- **Decisiones**: por qué organizaste las clases así, por qué elegiste esa BD.
- **Qué harías diferente con más tiempo.**
- **Escalabilidad**: si este servicio recibiera 1000 reservas/hora, ¿qué cambiarías?

---

## ENTREGA

- **Repositorio Git** con commits pequeños y con sentido (miramos el historial, no un único commit "primera versión").
- **Fichero `.sql`** en el repo con el DDL (crear tablas) + datos de ejemplo, listo para importar en MySQL (MAMP/WAMP).
- La app **desplegada y funcionando** en algún sitio accesible por URL (hay hostings gratuitos; busca cuál te encaja — también lo evaluamos).
- README con el enlace al deploy + instrucciones de setup local (incluyendo cómo importar el `.sql`).
- **Tiempo: sin límite.** Entregas cuando esté lista.
- Si no llegas a todo, **prioriza el backend**. Un backend sólido y seguro sin frontend pulido vale más que ambas cosas a medias.

---

## QUÉ EVALUAMOS

| Aspecto | Qué buscamos |
|---------|--------------|
| PHP y organización | Clases con responsabilidad clara, código legible |
| Seguridad | Que tengas presente que entra datos de fuera. Lo revisamos a fondo |
| Validación | Comprueba los datos en el servidor, no se fía del cliente |
| Diseño de la API | URLs y códigos HTTP coherentes, JSON limpio |
| Twig | Herencia de plantillas, autoescaping, no repetir HTML |
| JavaScript | Consume la API, maneja errores y estados de carga, código ordenado |
| README y Git | Explica decisiones, piensa en producción, commits con historia |

## QUÉ NO EVALUAMOS

- Diseño visual espectacular.
- Que uses un framework (de hecho, **no queremos** que uses uno aquí).
- Cobertura de tests del 100%.
- Infraestructura compleja.

---

## CONSEJO

Preferimos poco y bien hecho a mucho y a medias. Si entregas solo el backend, pero está limpio, seguro y bien pensado, nos interesas. Tómate el tiempo que necesites y entrega algo de lo que estés orgulloso/a.
