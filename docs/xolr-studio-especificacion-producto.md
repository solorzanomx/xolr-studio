# Xolr Studio — Especificación de Producto
**Versión:** 3.0  
**Autor:** Alejandro Solórzano  
**Fecha:** Junio 2026  
**Estado:** Documento Maestro Activo

---

## 1. Definición del Producto

**Xolr Studio** es el sistema operativo para casas de producción audiovisual con IA. Gestiona proyectos completos — series de ficción, marketing inmobiliario, canales de YouTube, producción para clientes — desde la conceptualización hasta la publicación, integrando generación visual, audio, lip sync, inteligencia creativa y distribución en una sola plataforma.

**Tagline:** *Where Imagination Renders Reality*

**Filosofía de diseño:** El usuario es un director creativo, no un ingeniero de IA. Piensa en personajes, escenas, campañas y canciones — nunca en seeds, CFG scale o parámetros técnicos. La plataforma oculta la complejidad y expone creatividad.

---

## 2. Módulos de la Plataforma

### 2.1 Studio Dashboard

Pantalla inicial post-login. Vista de control de todo el estudio.

- Proyectos activos con thumbnail y barra de progreso
- Renders en proceso en tiempo real (WebSockets — sin recargar)
- Talking renders (lip sync) en proceso
- Audio jobs en proceso
- Métricas: renders del día, costo acumulado del mes, Production Score
- Feed de actividad reciente
- Alertas: presupuesto al 80%, LoRA con baja aprobación, post programado hoy
- Accesos rápidos a acciones frecuentes

---

### 2.2 Biblioteca Global

#### Characters y Virtual Talents
*(Definición completa en Especificación anterior — sin cambios)*

**Nuevo en v3: Character DNA**
Cada personaje tiene un panel de DNA que muestra: seeds preferidos, approval rate por tipo de render template, PuLID config, peso óptimo de LoRA. El Intelligence Engine actualiza estos datos automáticamente con cada render. El usuario puede fijar manualmente valores que han funcionado bien.

#### Voice Profiles (por character/talent)
Cada personaje puede tener uno o varios perfiles de voz en ElevenLabs:
- Voz principal (ej: "Voz natural de Elena")
- Voz alternativa (ej: "Elena bajo control mental — más monótona")
- El usuario puede escuchar un preview de la voz antes de guardarla
- El perfil incluye valores default de stability, similarity y style
- Se puede asociar el voice_id a un clon de voz real o a una voz de la biblioteca de ElevenLabs

---

### 2.3 Proyectos y Producción
*(Definición completa en v2 — sin cambios estructurales)*

**Nuevo en v3: Universe Bible integrado con IA**
El AI Director y el Script Generator consultan el Universe Bible automáticamente. Cuando el AI Director procesa S01E05, ya sabe las reglas del Umbral, las relaciones entre personajes y los eventos de los episodios anteriores. No es un documento suelto — es contexto vivo que alimenta la producción.

---

### 2.4 Render Quality Tiers

Tres niveles de calidad para renders de imagen:

| Tier | Modelo | Tiempo | Costo est. | Cuándo usar |
|---|---|---|---|---|
| **Draft** | FLUX.1 Schnell | ~20 seg | ~$0.005 | Explorar prompts, crear mood boards, pruebas de composición |
| **Standard** | FLUX.1 Dev | ~60 seg | ~$0.025 | Producción regular, shots para aprobación |
| **Final** | FLUX.1 Dev + Upscale 4x | ~4 min | ~$0.08 | Render final para publicación o entrega al cliente |

**UX:** Al seleccionar el tier, la plataforma muestra el costo estimado y el tiempo antes de confirmar. Al aprobar un render en Standard, aparece un banner: *"¿Subir este render a Final? (~$0.055 adicionales)"* con botón de confirmación.

**Regla de ahorro:** Producir en Draft hasta que el prompt y la composición estén correctos. Solo pasar a Standard cuando el shot está listo para aprobación. Final solo para el render definitivo. Ahorro estimado: 60% del gasto mensual.

---

### 2.5 Render Annotations

Sistema de comentarios visuales sobre renders — como comentarios en Figma pero para imágenes de producción.

**Cómo funciona:**
- Click en cualquier área de un render → se crea un marcador numerado
- Escribir el comentario: "El pelo de Elena debería ser más oscuro aquí", "Este ángulo no funciona para la continuidad"
- Tipo de comentario: issue, suggestion, approval, question
- Los clientes del portal pueden también anotar renders

**Para el flujo de revisión:**
- Los marcadores son visibles para todos con acceso
- Al resolver una anotación, se marca como resuelta (sin eliminarla — queda en historial)
- El Render Farm puede recibir anotaciones como instrucciones para el re-render

---

### 2.6 Audio Studio

**El pipeline de sonorización completo dentro de Xolr Studio.**

#### Voice Generation — Generación de Voz
Desde cualquier texto en la plataforma (diálogo de shot, script de episodio, narración de campaña), generar audio con la voz del personaje asignado.

**Flujo:**
1. Shot tipo "talking" — el campo `dialogue_text` contiene el texto
2. Seleccionar voice profile del personaje
3. Abrir el **Voice Director** para dirigir la actuación (ver abajo)
4. Click "Generar voz" → ElevenLabs API
5. Audio generado y guardado en la Audio Library del proyecto
6. Preview con WaveSurfer.js en la plataforma

#### Voice Director
La herramienta de dirección de actuaciones de voz. No es suficiente generar voz — hay que dirigirla.

Por cada línea o segmento del texto, el usuario puede definir:
- **Emoción:** neutral, alegre, triste, enojada, aterrorizada, irónica, esperanzada, determinada
- **Ritmo:** lento y pesado, normal, rápido y nervioso, muy rápido
- **Énfasis:** qué palabras llevar arriba (subrayadas en el editor)
- **Pausa:** insertar pausa antes o después de la línea

Estos parámetros se traducen en SSML tags y configuraciones de ElevenLabs que producen actuaciones reales, no solo lecturas planas.

**Ejemplo:**
```
Línea: "Sé lo que hiciste, Kael."
  Emoción: contenida / fría
  Ritmo: lento
  Énfasis: "hiciste"
  Pausa antes: 0.5 seg
→ Elena suena amenazante y controlada
```

#### Ambient Sound — Sonido de Ambiente
Describir en texto el ambiente de una locación y generar el audio:
- "Bosque nocturno con lluvia ligera, viento entre los árboles, búho distante"
- "Bullicio de mercado al mediodía, voces lejanas, pasos en adoquín"
- "Lobby de edificio de lujo, aire acondicionado, pasos en mármol"

Servicio: ElevenLabs Sound Effects API (principal) + AudioGen en RunPod (fallback económico).

#### Music Generation — Generación de Música
Generar música original para escenas, episodios o campañas:
- Definir: mood, instrumentación, tempo, duración
- "Cuerdas minimalistas y tensas, 90 BPM, 45 segundos, para escena de confrontación"
- "Música aspiracional y moderna, piano y beats suaves, 60 segundos, para video de propiedad de lujo"

Servicio: Suno API (con licencia comercial). Fallback: MusicGen de Meta en RunPod.

**Music Themes — Leitmotifs:**
Cada personaje de Strange Light puede tener un tema musical propio. Cuando aparece Elena en una escena, su leitmotif puede reproducirse como parte del sound design.

#### Sound Design por Escena
Vista de mixer para componer el paisaje sonoro completo de una escena:

```
┌─ SOUND DESIGN: Bosque de Ashveil — S01E01 Sc01 ─────────────────┐
│                                                                    │
│  DIÁLOGO    ████████████████████████████████████ 100%  [▶ Elena] │
│  AMBIENTE   █████████████░░░░░░░░░░░░░░░░░░░░░░  40%  [▶ Bosq.] │
│  MÚSICA     ████████████████████░░░░░░░░░░░░░░░  55%  [▶ Tema]  │
│  SFX        █████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  15%  [▶ Rama]  │
│                                                                    │
│  [▶ Preview mix]  [Export WAV]  [Guardar]                         │
└───────────────────────────────────────────────────────────────────┘
```

El export es un archivo WAV del mix completo listo para usar en edición de video.

#### Subtitle Generator
Cualquier audio de la plataforma puede convertirse en subtítulos:
- Click en un audio_asset → "Generar subtítulos"
- OpenAI Whisper API procesa el audio
- Resultado: archivo .srt con timestamps exactos
- Descargable para subir a YouTube o usar en edición

---

### 2.7 Lip Sync Engine

**Talking Renders — personajes que hablan.**

#### El flujo completo
1. Shot tipo "talking" con `dialogue_text` escrito
2. Render aprobado del personaje (imagen fuente)
3. Audio generado con Voice Director (audio fuente)
4. Seleccionar calidad de lip sync:
   - **Draft:** Wav2Lip/LatentSync en RunPod (~$0.004/seg) — bocetos rápidos
   - **Production:** D-ID API (~$0.015/seg) — calidad de producción
   - **Premium:** HeyGen API (~$0.025/seg) — máxima calidad, para HDV y presentaciones
5. Click "Sincronizar labios" → job asíncrono
6. Video MP4 del personaje hablando guardado en R2
7. Preview en la plataforma, aprobación igual que renders visuales

#### Estrategia por proyecto
- **Strange Light:** Production (D-ID) para todos los diálogos de personajes
- **Home del Valle:** Premium (HeyGen) para videos de presentación de brokers
- **The Walking Video Guy:** Draft (RunPod) para intros/outros del presenter animadas

#### Thumbnail animado (micro-feature)
Para YouTube, generar una versión animada de 3 segundos del thumbnail — el presenter hace un pequeño gesto de bienvenida. Aumenta CTR en las vistas del feed sin ser un video completo.

---

### 2.8 AI Director + Ghost Director

#### AI Director
*(Definición completa en v2 — sin cambios)*

**Nuevo: Integración con Universe Bible y continuity notes previas**
Antes de generar las decisiones, el AI Director carga:
- Universe Bible del proyecto (lore, reglas, geografía)
- Notas de continuidad de episodios anteriores
- Relaciones entre personajes involucrados en el episodio

Esto garantiza que el episodio 5 sea coherente con lo establecido en los episodios 1–4.

#### Ghost Director
El AI Director aprende el estilo creativo del usuario con el tiempo.

**Datos que aprende:**
- Estilos de cámara por tipo de mood (¿qué plano usas en escenas tensas?)
- Hora del día preferida para ciertos tipos de escena
- Promedio de shots por escena
- Estilos visuales favoritos por tipo de proyecto
- Patrones de iluminación (¿prefieres moonlight o artificial light en exteriores nocturnos?)

**Cómo se ve en la UI:**
- Vista "Tu Estilo Creativo" — radar chart con las preferencias detectadas
- Botón "Dirigir con AI" cambia a "Dirigir con Ghost Director" cuando el perfil tiene suficiente data (20+ renders aprobados)
- Las decisiones del Ghost Director aparecen con badge púrpura ✦ para distinguirlas de las del AI Director base

---

### 2.9 Intelligence Engine + Continuity Systems

#### Continuity Canvas
Mapa visual interactivo de todos los renders aprobados de un proyecto.

**Dos ejes:**
- Eje X: timeline narrativo (episodios y escenas en orden)
- Eje Y: personajes principales

Cada celda muestra el thumbnail del render aprobado del personaje en esa escena. Las celdas vacías indican escenas sin render aprobado aún. Al hacer click en cualquier render se abre en pantalla completa con su metadata.

**Utilidad:** Ver de un vistazo si Elena mantiene consistencia visual a lo largo de 10 episodios, detectar saltos abruptos de look, y planificar los próximos renders con contexto visual completo.

#### Visual Continuity Checker
IA que analiza los renders de una escena y detecta inconsistencias.

**Lo que detecta:**
- Cambio de look del personaje entre shots (cabello, ropa, accesorios)
- Inconsistencia de iluminación (shot 1 es de día, shot 3 parece de noche)
- Prop que aparece y desaparece entre shots consecutivos
- Diferencia significativa de expresión del LoRA (el personaje "no parece el mismo")

**Alertas en la UI:**
- Banner amarillo en el storyboard de la escena: "⚠ Posible inconsistencia detectada entre Shot 2 y Shot 4"
- Click en la alerta → vista lado a lado de los renders problemáticos
- El usuario puede descartar la alerta o marcarla para re-render

#### Production Score
Métrica gamificada del estado de calidad de la producción. Visible en el dashboard del proyecto.

| Componente | Peso | Descripción |
|---|---|---|
| Tasa de aprobación de renders | 30% | % de renders aprobados en el período |
| Consistencia visual | 25% | Alertas de continuity no resueltas |
| Eficiencia de costo | 20% | Costo por render aprobado vs promedio |
| Ritmo de producción | 15% | Shots producidos por semana |
| Cobertura de audio | 10% | Shots con sound design vs total |

Score de 0–100. Badges: Producción Eficiente (>85), En Ritmo (>70), Necesita Atención (<50).

---

### 2.10 Animatic Generator

**Pre-visualización del episodio antes de la edición final.**

1. Ir a vista de episodio → todos los shots con renders aprobados
2. Click "Generar Animatic"
3. Por cada shot, definir duración en segundos (o aceptar la sugerencia basada en la longitud del diálogo)
4. Seleccionar música de fondo opcional (de la Audio Library)
5. La plataforma ensambla los renders en secuencia → video MP4
6. El animatic muestra el ritmo narrativo del episodio completo
7. Descargable para compartir o usar como referencia en la edición

**Utilidad real:** Ver si el ritmo del episodio funciona antes de invertir tiempo en la edición final. Identificar shots que duran demasiado o muy poco. Presentar el concepto a colaboradores o clientes.

---

### 2.11 Auto-Post y Analytics Inverso

#### Auto-Post
La plataforma publica automáticamente en Instagram y YouTube en el horario definido en el Content Calendar.

- Instagram: posts individuales, carruseles, stories, reels
- YouTube: video completo con thumbnail, descripción, playlist
- Notificación push/email al publicar exitosamente o al fallar
- Log de todos los posts con timestamp y platform_post_id

#### Analytics Inverso
Las métricas de las plataformas fluyen de regreso a Xolr Studio cada 24 horas.

**Dashboard de Analytics:**
- Gráfica de rendimiento por post (línea temporal)
- Comparativa de CTR entre thumbnails de TWVG (A/B automático)
- Top 5 posts por engagement del mes
- Tendencia de views y suscriptores de YouTube

**Inteligencia que genera:**
- "Los thumbnails con el presenter de frente tienen 31% más CTR que los de perfil"
- "Los posts de HDV publicados martes 7–9 AM tienen 2.3x más engagement"
- Estos insights se muestran en el Intelligence Engine y alimentan al Ghost Director

---

### 2.12 Pricing Calculator y Production Packet

#### Pricing Calculator
Para proyectos de clientes externos.

**Wizard de cotización:**
1. Seleccionar cliente o crear nuevo
2. Agregar items: descripción, horas/unidades, costo unitario
3. La plataforma calcula automáticamente el costo estimado de renders del proyecto
4. Definir fee de producción (trabajo creativo)
5. Agregar impuestos si aplica
6. Generar número de cotización secuencial (XS-2026-0042)
7. Preview de la cotización en formato profesional
8. Export PDF listo para enviar al cliente

#### Production Packet
El paquete completo que se entrega al cliente al terminar el proyecto.

**Contenido del packet:**
- Todos los renders aprobados en resolución final (Final tier)
- Todos los talking renders aprobados
- Captions generados por plataforma y canal
- Guía de uso: qué archivo va en qué plataforma, dimensiones, formatos
- Cronograma de publicación sugerido
- Notas de producción (continuity notes para el cliente, si aplica)

**Todo en un ZIP** con estructura de carpetas clara, descargable desde el Client Portal o desde el dashboard.

---

### 2.13 Notion Sync

La plataforma sincroniza automáticamente con el workspace de Notion del usuario.

**Datos sincronizados por proyecto:**
| Dato en Xolr Studio | Destino en Notion |
|---|---|
| Episodios (título, estado, fecha) | Database de episodios |
| Campañas (nombre, estado, property) | Database de campañas |
| Renders completados hoy | Registro diario de producción |
| Calendario de publicación | Calendar view |
| Presupuesto del mes | Tabla de costos |

**Configuración:** El usuario mapea una vez qué base de datos de Notion corresponde a cada tipo de dato. La sincronización es cada hora, automática.

---

## 3. UX y Diseño — v3

### Identidad Visual de Xolr Studio
- **Tema:** Dark profesional (default permanente)
- **Negros:** `#08080A` (fondo) · `#111113` (superficies) · `#1E1E22` (cards)
- **Texto:** `#E8E8EA` (principal) · `#9CA3AF` (secundario) · `#4B5563` (muted)
- **Amber:** `#F59E0B` — CTAs, renders aprobados, acento de marca
- **Violeta:** `#7C3AED` — todo lo generado por IA (badges, highlights, Ghost Director)
- **Verde:** `#10B981` — éxito, publicado, aprobado (render anotado resuelto)
- **Rojo:** `#EF4444` — error, fallido, alerta
- **Naranja:** `#F97316` — warning, alertas de presupuesto
- **Fuente:** Inter (UI) + JetBrains Mono (metadata técnica, seeds, costos)

### Indicadores Permanentes en Header
```
[◈ Xolr Studio]  [Strange Light ▾]  ... nav ...  [⚡ 3 renders]  [$2.14 hoy]  [👤 Alejandro]
```
- `⚡ 3 renders` — jobs activos, click abre Render Farm Monitor
- `$2.14 hoy` — costo acumulado del día, rojo si supera el 80% del presupuesto diario
- Spinner animado si hay lip sync o audio jobs activos

### Nuevos Patrones de UI

**Voice Director UI:** Editor de texto donde cada línea tiene controles de dirección desplegables (emoción, ritmo, énfasis). Parece una partitura de actuación.

**Audio Mixer:** Sliders horizontales para diálogo, ambient, música y SFX. Botón de preview reproductor del mix en tiempo real con WaveSurfer.js.

**Continuity Canvas:** Grid scrollable. Personajes en el eje Y, escenas en el eje X. Thumbnails de 60×60px en cada celda. Hover muestra el render completo en tooltip.

**Render Annotation:** Al pasar el mouse sobre un render en review mode, el cursor cambia a crosshair. Click crea un punto de anotación numerado con popover para escribir el comentario.

**Quality Tier Selector:** Tres botones con precio estimado visible: `[Draft $0.005]` `[Standard $0.025]` `[Final $0.08]`. El seleccionado tiene border amber.

**Production Score Widget:** Gauge circular en el dashboard del proyecto mostrando el score 0–100 con color según rango.

---

## 4. Reglas de Negocio — v3

**Renders:**
- Quality tier no es editable en un render existente. Para cambiar de tier, se crea un nuevo render del mismo shot.
- El upscale de Draft a Final requiere confirmación con costo visible.
- Smart retry: al fallar un render, reintento automático con seed diferente (máx 2 reintentos automáticos).

**Audio:**
- Un Talking Render requiere que tanto el source_render como el audio_asset estén en status 'completed' antes de poder lanzarse.
- Los audios generados son permanentes en R2 — no se eliminan aunque el shot asociado cambie.

**Lip Sync:**
- Los Talking Renders de calidad Draft no se muestran en el Client Portal — solo Production y Premium.
- Aprobar un Talking Render establece automáticamente el shot a status 'approved'.

**Auto-Post:**
- Un evento de calendario solo puede auto-publicarse si todos los assets asociados están en status 'approved'.
- Si la publicación falla, el sistema reintenta 2 veces con 15 minutos de diferencia antes de notificar error.

**Notion Sync:**
- Sync es unidireccional (Xolr → Notion) para evitar conflictos.
- Si Notion devuelve error, el sync se reintenta en la siguiente hora sin alerta al usuario (solo log interno).

**Analytics:**
- Los analytics_snapshots son inmutables — si el dato cambia, se crea un nuevo snapshot.
- Los insights del Intelligence Engine se re-calculan cada 24 horas.

---

## 5. Flujos de Usuario Completos — v3

### Flujo E — Producir un diálogo con lip sync (Strange Light)
1. Shot tipo "talking" en la escena
2. Escribir el diálogo: "Sé lo que hiciste, Kael."
3. Abrir Voice Director → definir: emoción contenida, ritmo lento, énfasis en "hiciste"
4. Seleccionar voice profile: "Voz de Elena — dramática"
5. Click "Generar voz" → preview del audio en WaveSurfer.js
6. Si está bien: confirmar audio
7. Seleccionar render aprobado de Elena como imagen fuente
8. Seleccionar calidad: Production (D-ID)
9. Click "Sincronizar labios" → job asíncrono
10. 2 minutos después: talking render listo en la plataforma
11. Preview del video → aprobar
12. ✅ Elena habla con emoción controlada y labios perfectamente sincronizados

### Flujo F — Crear video de broker para HDV
1. Campaign nueva para "Torre Versalles"
2. Asset checklist → activar "Broker video 60 seg" (story 9:16)
3. Shot creado con purpose: 'broker_portrait', shot_type: 'talking'
4. Escribir el script de Sofía (o generar con Claude en su voz)
5. Voice Director: tono cálido y directo, ritmo conversacional
6. Generar voz con voice_profile de Sofía
7. Seleccionar render de Sofía frente a la propiedad
8. Calidad Premium (HeyGen) → lip sync
9. Aprobar talking render
10. Agregar al Content Calendar para publicar en Instagram Story el martes 7 AM
11. ✅ Sofía presenta la Torre Versalles en video de 60 segundos

### Flujo G — Analizar el rendimiento de thumbnails (TWVG)
1. Analytics → YouTube Analytics
2. Ver comparativa de CTR por thumbnail del último mes
3. Intelligence Engine: "Los thumbnails con expresión de sorpresa tienen 28% más CTR"
4. Abrir Ideas Bank → marcar los próximos videos para usar expresión de sorpresa
5. Ghost Director actualiza el perfil: +1 preferencia por "expresión sorpresa en thumbnails"
6. Próximo thumbnail generado → el prompt sugiere automáticamente "expresión de sorpresa auténtica"

---

## 6. Xolr OS — La Visión Mayor

Xolr Studio está diseñado desde el inicio para ser más que una herramienta personal. La arquitectura multi-tenant, el Client Portal, el Pricing Calculator, el Production Packet y los planes de suscripción ya están contemplados en el modelo de datos y la arquitectura.

**Lo que Xolr Studio puede llegar a ser:**
- El estándar de la industria para producción audiovisual con IA
- Una plataforma vendible a otros creadores en formato SaaS
- Un ecosistema: marketplace de LoRAs, estilos y workflows compartidos entre usuarios

**El mercado:** Cualquier creador que produce contenido con IA necesita exactamente esto — entity-first + render farm + audio + lip sync + publishing + intelligence. No existe hoy una plataforma que lo integre todo.

---

## 7. Backlog Futuro

- Mobile app para revisión y aprobación de renders
- LoRA Trainer integrado (sin salir de la plataforma)
- Timeline de video básico para ensamble de clips
- Marketplace de estilos y workflows entre usuarios
- AI Voice-Over en múltiples idiomas desde el mismo voice profile
- Animación de stills con AnimateDiff integrado
- Location scouting con Street View / maps para TWVG
- White-label: clientes reciben portal con su propia marca

---

*Documento vivo — versión 3.0 — Junio 2026*
