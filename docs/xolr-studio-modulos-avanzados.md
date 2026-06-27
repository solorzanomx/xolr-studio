# Xolr Studio — Módulos Avanzados y Diferenciadores
**Versión:** 2.0  
**Autor:** Alejandro Solórzano  
**Fecha:** Junio 2026  
**Estado:** Documento Maestro Activo

---

## 1. Audio Studio

El módulo de producción sonora completo dentro de Xolr Studio. Todo lo que necesita una producción audiovisual — voces, ambientes, música, efectos — generado, dirigido y mezclado sin salir de la plataforma.

### 1.1 Voice Generation

Cada personaje, broker o presenter tiene un Voice Profile que almacena su voz en ElevenLabs.

**Configuración del Voice Profile:**
```
voice_id: "eleven_labs_id_xxxxx"
stability: 0.75          # consistencia de la voz
similarity_boost: 0.85   # parecido al original clonado
style: 0.45              # expresividad
use_speaker_boost: true
is_cloned: false         # true si es clon de voz real
```

**Flujo de generación:**
1. Texto en el campo de diálogo del shot
2. Seleccionar voice profile del personaje
3. Abrir Voice Director
4. Generar → ElevenLabs API
5. Preview con WaveSurfer.js
6. Confirmar → guardado en audio_assets

### 1.2 Voice Director

La herramienta de dirección de actuación de voz. Convierte texto plano en actuaciones dirigidas.

El Voice Director presenta el texto del diálogo dividido en líneas. Para cada línea el usuario define:

**Parámetros disponibles:**
- **Emoción:** neutral | alegre | triste | enojada | aterrorizada | irónica | esperanzada | determinada | contenida | desconectada
- **Ritmo:** muy lento | lento | normal | rápido | muy rápido
- **Énfasis:** palabras subrayadas en el editor — se convierten en SSML `<emphasis>` tags
- **Pausa previa:** 0 | 0.3s | 0.5s | 1s | 1.5s
- **Pausa posterior:** 0 | 0.3s | 0.5s

**Ejemplo práctico — Elena confronta a Kael:**
```
Línea 1: "Sé lo que hiciste, Kael."
  Emoción: contenida / fría
  Ritmo: lento
  Énfasis: "hiciste"
  Pausa previa: 0.5s
  → Elena suena amenazante y absolutamente en control

Línea 2: "Toda tu operación. Lo sé todo."
  Emoción: determinada
  Ritmo: normal
  Énfasis: "todo"
  Pausa previa: 0.3s
  → La segunda línea llega como una sentencia
```

La plataforma ensambla las líneas en el orden correcto con las pausas incluidas y las envía como una sola llamada optimizada a ElevenLabs.

### 1.3 Ambient Sound Generation

Texto descriptivo → archivo de audio de ambiente.

**Ejemplos de prompts y resultados:**
- "Bosque nocturno templado, lluvia ligera, viento entre pinos, búho distante a 300 metros" → loop de 2 minutos para loop seamless
- "Lobby de edificio corporativo, aire acondicionado, pasos en mármol, ascensor en fondo" → ambiente de 90 segundos
- "Mercado mexicano al mediodía, voces, vendedores, radio a distancia, motos pasando" → soundscape de 3 minutos

**Servicio:** ElevenLabs Sound Effects API (principal). Fallback: AudioGen (Meta) en RunPod para mayor duración o cuando ElevenLabs no esté disponible.

**Formato de output:** WAV 44.1kHz, entregado en loop seamless si la duración lo permite.

### 1.4 Music Generation

Música original para escenas, episodios y campañas.

**Parámetros de generación:**
- Mood: tenso | relajado | esperanzador | oscuro | épico | íntimo | aspiracional | urgente
- Instrumentación: orquestal | electrónico | acústico | híbrido | minimalista
- Tempo: 60–140 BPM
- Duración: 30s | 60s | 90s | 2min | 3min | loop continuo
- Prompt libre: "cuerdas minimalistas con electrónica fría, como Hans Zimmer en película de espías"

**Servicio:** Suno API con licencia comercial. Fallback: MusicGen (Meta) en RunPod para proyectos sin budget o para drafts.

**Music Themes (Leitmotifs):**
Cada personaje de Strange Light tiene un tema musical asignado. Cuando el AI Director procesa una escena con ese personaje, sugiere reproducir su leitmotif en el Sound Design. Los temas están almacenados en la Audio Library del proyecto como assets permanentes.

### 1.5 Sound Design por Escena

Vista de mixer para componer el paisaje sonoro completo. Cada escena tiene su propio Sound Design.

**Canales del mixer:**
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

- Cada canal muestra el audio asignado y permite ajustar el volumen con slider
- Preview del mix completo en tiempo real con WaveSurfer.js
- Export WAV del mix final listo para la edición de video

### 1.6 Subtitle Generator

Cualquier audio de la plataforma puede convertirse en subtítulos.

1. Click en un audio_asset → "Generar subtítulos"
2. Whisper API (OpenAI) procesa el audio
3. Resultado: archivo .srt con timestamps exactos (precisión de 10ms)
4. Preview del .srt en la plataforma
5. Descarga o envío directo al Content Calendar de la publicación

**Idiomas soportados:** Los que soporta Whisper — español, inglés, francés, italiano y 96 más con detección automática.

---

## 2. Lip Sync Engine

Personajes que hablan. El módulo que conecta el render visual con el audio generado.

### 2.1 Arquitectura del flujo

```
Shot (talking) → [Render aprobado] + [Audio generado] 
                          ↓
              Seleccionar calidad de lip sync
                          ↓
              ┌──────────────────────────────┐
              │ Draft    → RunPod Wav2Lip    │ ~$0.004/seg
              │ Production → D-ID API        │ ~$0.015/seg  
              │ Premium  → HeyGen API        │ ~$0.025/seg
              └──────────────────────────────┘
                          ↓
              Job asíncrono → WebSocket update
                          ↓
              Video MP4 en R2 → Preview → Aprobación
```

### 2.2 Routing inteligente

La plataforma sugiere el nivel de calidad según el contexto:

| Contexto | Calidad sugerida | Por qué |
|---|---|---|
| Shots de revisión / bocetos | Draft | Verificar timing, no calidad |
| Diálogos de Strange Light | Production | Calidad cinematográfica a costo razonable |
| Videos de broker de HDV | Premium | La cara de Sofía o Diego ES la marca |
| Intros/outros de TWVG | Draft | Solo 3–5 segundos, low stakes |
| Entrega a cliente externo | Premium | Producto final pagado |

### 2.3 Thumbnail Animado

Micro-feature para YouTube: versión animada de 3 segundos del thumbnail. El presenter hace un pequeño gesto — un guiño, una sonrisa, señalar algo. Se usa como preview animado en el feed de YouTube o como Shorts de bienvenida de video. Costo-eficiente porque usa Wav2Lip Draft en un clip muy corto.

---

## 3. Ghost Director

El AI Director que aprende el estilo creativo del usuario con el tiempo.

### 3.1 Qué aprende

El Ghost Director analiza todos los renders aprobados y rechazados del usuario y construye un perfil creativo por proyecto.

**Dimensiones del aprendizaje:**

| Dimensión | Qué analiza | Ejemplo de conclusión |
|---|---|---|
| Encuadre | Shot type más aprobado por mood de escena | "En escenas tensas prefieres close-up (78%)" |
| Iluminación | Tags de prompt con mayor aprobación | "Moonlight gana sobre artificial light en exteriores nocturnos" |
| Color | Paletas más seleccionadas | "Para Strange Light: desaturados fríos. Para HDV: cálidos y limpios" |
| Ritmo | Shots por escena promedio | "Tus escenas de acción tienen 5 shots, tus diálogos tienen 3" |
| Hora del día | Preferencia en prompts | "80% de tus renders de Strange Light son noche o atardecer" |
| Estilo visual | Palabras de prompt más aprobadas | "cinematic, volumetric light, shallow depth of field" |

### 3.2 Perfil de Ghost Director

Vista "Tu Estilo Creativo" en el Intelligence Engine:

```
STRANGE LIGHT — Perfil Ghost Director
───────────────────────────────────────
Encuadre preferido:    Close-up (41%) · Medium (35%) · Wide (24%)
Iluminación favorita:  Moonlight (38%) · Neon azul (29%) · Emergency red (20%)
Profundidad de campo:  Shallow DOF (72% de renders aprobados)
Paleta dominante:      Desaturada fría · Azul-gris · Negro profundo
Hora del día:          Noche (61%) · Atardecer (27%) · Día (12%)
Velocidad de producción: 4.2 renders aprobados/sesión
Eficiencia:            68% de renders aprobados en primer intento
───────────────────────────────────────
Basado en: 147 renders aprobados · 89 rechazados
Última actualización:  Hace 2 horas
```

### 3.3 Cuándo activa el Ghost Director

- El botón "Dirigir con AI" cambia a "Dirigir con Ghost Director ✦" cuando hay 20+ renders aprobados en el proyecto
- Las decisiones del Ghost Director llevan badge violeta ✦
- El usuario puede aceptar o rechazar cada sugerencia — cada respuesta retroalimenta el modelo

### 3.4 Separación por proyecto

El Ghost Director mantiene perfiles independientes por proyecto. El estilo de Strange Light no contamina las sugerencias de HDV — son mundos creativos distintos. Un mismo usuario puede ser director de cine noir y diseñador de marketing aspiracional.

---

## 4. Intelligence Engine

El cerebro analítico de la plataforma.

### 4.1 Character DNA System

Cada personaje tiene un registro de ADN creativo que se actualiza automáticamente con cada render:

```json
{
  "character_id": "elena_voss",
  "preferred_seeds": [42891, 73012, 15934],
  "approval_rate_by_template": {
    "close_up_dramatic": 0.87,
    "medium_shot_neutral": 0.71,
    "wide_environmental": 0.44
  },
  "pulid_config": {
    "face_weight": 1.4,
    "id_consistency": 0.9
  },
  "lora_optimal_weight": 0.85,
  "approved_renders_count": 147,
  "last_updated": "2026-06-25T14:30:00Z"
}
```

El sistema sugiere automáticamente los seeds con más alta aprobación histórica. El usuario puede fijar seeds que han funcionado excepcionalmente bien.

### 4.2 Continuity Canvas

Mapa visual interactivo de todos los renders aprobados del proyecto.

**Estructura:**
- Eje Y: personajes principales (Elena, Kael, Marcus)
- Eje X: timeline narrativo (S01E01 Sc01, S01E01 Sc02, S01E01 Sc03...)
- Cada celda: thumbnail 60×60px del render aprobado del personaje en esa escena
- Celdas vacías: escenas sin render aprobado aún

**Utilidad práctica:**
- Ver si Elena mantiene consistencia visual a lo largo de 10 episodios sin revisar uno por uno
- Detectar un cambio de look no intencional entre el episodio 3 y el 5
- Planificar los próximos renders con contexto visual completo de la temporada
- Para HDV: ver si Sofía y Diego mantienen su look en todas las campañas del mes

**Interacción:**
- Click en cualquier thumbnail → abre render completo con su metadata
- Hover → tooltip con información del shot: fecha, seed, modelo, costo
- Click en celda vacía → abre directamente el shot correspondiente para producir

### 4.3 Visual Continuity Checker

IA que analiza automáticamente los renders de una escena y detecta inconsistencias antes de que lleguen al editor.

**Qué detecta:**
- Cambio de look del personaje entre shots consecutivos (cabello, ropa, accesorios)
- Inconsistencia de iluminación entre shots de la misma escena
- Prop que aparece y desaparece
- Diferencia significativa de rasgos faciales (el LoRA no es el mismo en todos los renders)
- Cambio de fondo inconsistente con la locación establecida

**Alertas en el storyboard:**
```
⚠ Alerta de continuidad detectada
Escena: S01E01 Sc03
Entre Shot 2 y Shot 4: posible cambio de iluminación

[Ver comparativa lado a lado]  [Descartar]  [Marcar para re-render]
```

**Nivel de confianza:** Cada alerta tiene un porcentaje de confianza. Las alertas > 70% se muestran en naranja, las > 90% en rojo. Las < 40% se ocultan por default (evitar falsos positivos).

### 4.4 Analytics Inverso

Las métricas de las plataformas de publicación fluyen de regreso a Xolr Studio.

**YouTube Analytics:**
- Views por video (curva de 28 días)
- CTR por thumbnail
- Average Watch Time y curva de retención
- Suscriptores ganados por video
- Revenue si está monetizado

**Instagram Insights:**
- Reach y impressions por post
- Engagement rate (likes + comments + saves / reach)
- Profile visits generadas por post
- Story completion rate
- Reach de reels

**Cron:** Sincronización diaria a las 3:00 AM. Los datos quedan disponibles en el dashboard al despertar.

**Insights generados por Intelligence Engine:**
La IA procesa los datos históricos y genera insights accionables en lenguaje natural:
- "Los thumbnails de TWVG con el host de frente tienen 31% más CTR que los de perfil"
- "Los posts de HDV publicados martes 7–9 AM tienen 2.3x más engagement"
- "Los episodios de Strange Light de 10+ shots retienen 18% más tiempo promedio"

Estos insights alimentan directamente las sugerencias del Ghost Director.

---

## 5. Animatic Generator

Pre-visualización del episodio completo antes de la edición.

### El flujo

1. Ir a vista de episodio → todos los shots con renders aprobados
2. Click "Generar Animatic"
3. Por cada shot: definir duración en segundos (o aceptar sugerencia basada en longitud del diálogo)
4. Opcional: seleccionar música de fondo de la Audio Library
5. La plataforma ensambla renders en secuencia con transiciones simples → video MP4
6. El animatic muestra el ritmo narrativo del episodio completo
7. Descarga para revisión o presentación

### Casos de uso reales

**Strange Light:** Ver si el ritmo del episodio funciona narrativamente antes de iniciar la edición final. Identificar que la escena 3 dura demasiado o que la transición del laboratorio al bosque necesita un shot extra.

**Home del Valle:** Revisar la secuencia de un carrusel antes de publicarlo. Ver el orden de las imágenes con la música elegida.

**The Walking Video Guy:** Pre-ver la estructura del video con los thumbnails de sección antes de grabar o editar.

---

## 6. Render Quality Tiers

### La filosofía del ahorro inteligente

La mayoría del gasto en renders se desperdicia en renders de exploración que nunca llegan a publicarse. Los tiers resuelven esto con una disciplina de producción clara:

```
EXPLORAR → Draft   ($0.005)   ~20 seg   "¿Funciona el concepto?"
APROBAR  → Standard ($0.025)  ~60 seg   "¿Está listo para revisión?"
PUBLICAR → Final   ($0.08)    ~4 min    "Este va al cliente / la audiencia"
```

**Ahorro estimado:** Un proyecto con 50 renders finales típicamente requiere 200 renders de exploración. Sin tiers: 250 × $0.08 = $20. Con tiers: 150 × $0.005 + 50 × $0.025 + 50 × $0.08 = $0.75 + $1.25 + $4 = **$6 total (70% de ahorro).**

### Reglas de transición entre tiers

- **Draft → Standard:** El usuario selecciona explícitamente. No hay upgrade automático.
- **Standard → Final:** Al aprobar un render Standard, aparece banner: *"¿Subir a Final? Costo adicional: ~$0.055"* con botón de confirmación.
- **Downgrade:** No existe. Un render Final no se puede bajar de tier — se produce un nuevo render.

---

## 7. Auto-Post y Publicación

### Auto-Post

La plataforma publica automáticamente en las plataformas conectadas en el horario definido.

**Plataformas:**
- Instagram: post individual, carrusel (hasta 10 imágenes), story, reel
- YouTube: video con thumbnail, descripción completa, playlist asignada, chapters automáticos

**Proceso:**
1. Asset aprobado + fecha/hora definida en Content Calendar
2. 5 minutos antes: verificación de que todos los assets están en R2
3. En el momento exacto: llamada a la API de la plataforma
4. Éxito: log con platform_post_id + notificación
5. Fallo: reintento automático (×2 con 15 min de diferencia) + alerta al usuario

### Content Calendar

Vista de calendario mensual con todos los posts programados. Drag and drop para mover posts a otras fechas. Color coding por proyecto (Strange Light = azul, HDV = ámbar, TWVG = verde).

---

## 8. Client Portal

Acceso externo controlado para clientes de producción.

### Cómo funciona

El usuario genera un token de acceso para un proyecto específico. El cliente recibe un link y accede sin necesidad de cuenta en Xolr Studio.

**Lo que ve el cliente:**
- Los renders y talking renders marcados como "disponibles para revisión"
- Opciones: Aprobar, Solicitar revisión, Preguntar
- En "Solicitar revisión": campo de texto + opción de anotar el render (Render Annotations)

**Lo que NO ve el cliente:**
- Renders en Draft
- Costos de producción
- Configuración técnica
- Otros proyectos del estudio

### Flujo de revisión con cliente

```
Xolr Studio produce → marca renders como "En revisión"
        ↓
Cliente recibe email con link al portal
        ↓
Cliente revisa renders → aprueba, anota, o solicita cambios
        ↓
Notificación a Alejandro en Xolr Studio
        ↓
Ajustes → nuevo render → vuelve al portal
        ↓
Aprobación final → Production Packet generado
```

---

## 9. Pricing Calculator y Production Packet

### Pricing Calculator

Generador de cotizaciones para proyectos de clientes externos.

**Wizard de 4 pasos:**
1. Datos del cliente (nombre, empresa, email)
2. Items del proyecto: descripción, horas/unidades, precio unitario
3. Costo estimado de renders (calculado por la plataforma según tier y cantidad)
4. Fee de producción + impuestos + total

**Resultado:** PDF profesional con número de cotización XS-2026-XXXX, listo para enviar.

### Production Packet

El paquete de entrega final al cliente.

**Contenido:**
- Renders finales (Final tier) organizados por escena/campaña
- Talking renders en MP4
- Captions optimizados por plataforma
- Subtítulos .srt
- Guía de uso: qué archivo va dónde, dimensiones, formatos
- Cronograma de publicación sugerido
- Notas de producción si aplica

**Formato:** ZIP descargable con estructura de carpetas clara. También disponible en el Client Portal para descarga directa del cliente.

---

## 10. Xolr OS — La Plataforma como Negocio

Xolr Studio está construido desde la primera línea para ser escalable hacia un producto SaaS.

### Lo que ya está contemplado en la arquitectura

La arquitectura multi-tenant en Laravel permite que múltiples "estudios" usen la misma instalación. Cada usuario vive en su propio espacio aislado. Los recursos compartidos (RunPod, ElevenLabs, Suno) se facturan por uso real.

**El modelo de negocio futuro:**

| Plan | Target | Precio est. | Límites |
|---|---|---|---|
| **Creator** | Creadores individuales | $29/mes | 50 renders/mes, 2 proyectos, 10 audio jobs |
| **Studio** | Productoras pequeñas | $89/mes | 200 renders/mes, 10 proyectos, 50 audio jobs |
| **Agency** | Agencias de marketing | $249/mes | Renders ilimitados, proyectos ilimitados, multi-usuario |
| **Enterprise** | Estudios grandes | Custom | White-label, SLA, soporte dedicado |

**Economía del modelo:** El costo real de GPU (RunPod) y APIs (ElevenLabs, Suno, D-ID) se paga por uso. El plan cobra el acceso a la plataforma + un markup razonable sobre los costos de API. En escala, el margen es significativo porque la plataforma no es el compute — es la inteligencia encima del compute.

### Diferenciadores competitivos de Xolr OS

**Nadie más hace esto integrado:**

| Capacidad | Xolr Studio | MidJourney | RunwayML | ElevenLabs | Canva |
|---|---|---|---|---|---|
| Gestión de personajes persistentes | ✅ | ❌ | ❌ | ❌ | ❌ |
| Render farm propio (FLUX) | ✅ | ❌ | Partial | ❌ | ❌ |
| Audio completo (voz + ambient + música) | ✅ | ❌ | ❌ | Partial | ❌ |
| Lip sync integrado | ✅ | ❌ | ❌ | ❌ | ❌ |
| Sound Design mixer | ✅ | ❌ | ❌ | ❌ | ❌ |
| AI Director narrativo | ✅ | ❌ | ❌ | ❌ | ❌ |
| Ghost Director (aprendizaje de estilo) | ✅ | ❌ | ❌ | ❌ | ❌ |
| Continuity Canvas | ✅ | ❌ | ❌ | ❌ | ❌ |
| Auto-Post + Analytics Inverso | ✅ | ❌ | ❌ | ❌ | Partial |
| Client Portal | ✅ | ❌ | ❌ | ❌ | ❌ |
| Production Score gamificado | ✅ | ❌ | ❌ | ❌ | ❌ |

**La propuesta de valor:** No es una herramienta de generación de imágenes. Es el sistema operativo completo de una casa de producción audiovisual con IA.

---

## 11. Production Score

Métrica gamificada del estado de salud de una producción.

| Componente | Peso | Cómo se mide |
|---|---|---|
| Tasa de aprobación | 30% | Renders aprobados / renders totales del período |
| Consistencia visual | 25% | Alertas de continuity sin resolver (penaliza) |
| Eficiencia de costo | 20% | Costo por render aprobado vs. promedio del proyecto |
| Ritmo de producción | 15% | Shots producidos en los últimos 7 días vs. objetivo |
| Cobertura de audio | 10% | Shots con sound design / total de shots aprobados |

**Score 0–100. Badges:**
- 🏆 **Producción Élite** (95–100): Consistencia y eficiencia excepcionales
- ⚡ **Producción Eficiente** (85–94): Ritmo excelente, pocos ajustes necesarios
- ✅ **En Ritmo** (70–84): Producción saludable
- ⚠ **Necesita Atención** (50–69): Hay áreas que mejorar
- 🔴 **Producción Detenida** (<50): Algo va mal — el dashboard muestra qué

El Production Score es visible en el dashboard del proyecto y en el Dashboard global como comparativa entre proyectos activos.

---

## 12. Notion Sync

Sincronización automática bidireccional-lite con el workspace de Notion del usuario.

### Qué se sincroniza

| Dato en Xolr Studio | Destino en Notion |
|---|---|
| Estado de episodios (título, status, fecha estimada) | Database "Episodios" |
| Estado de campañas (nombre, status, fecha) | Database "Campañas" |
| Renders completados hoy | Log diario de producción |
| Calendario de publicación | Calendar view |
| Costo mensual acumulado | Tabla de presupuesto |
| Production Score por proyecto | Dashboard ejecutivo |

**Configuración única:** El usuario mapea las databases de Notion a los tipos de dato de Xolr Studio. La sincronización ocurre cada hora de forma automática.

**Dirección:** Xolr → Notion (unidireccional). Evita conflictos. El estado master siempre vive en Xolr Studio.

---

*Documento vivo — versión 2.0 — Junio 2026*
