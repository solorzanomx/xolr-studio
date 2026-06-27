# Xolr Studio — Arquitectura Técnica
**Versión:** 3.0  
**Autor:** Alejandro Solórzano  
**Fecha:** Junio 2026  
**Estado:** Documento Maestro Activo

---

## 1. Visión de Plataforma

Xolr Studio es el **sistema operativo para estudios de producción audiovisual con IA**. No es una herramienta de renders ni un wrapper de modelos — es la infraestructura completa para una casa de producción digital que gestiona proyectos, personajes, renders, audio, lip sync, campañas y publicación desde una sola plataforma.

**Tagline:** *Where Imagination Renders Reality*

**Proyectos que opera:**
- **Strange Light** — Serie de ficción AI-first (temporadas, episodios, diálogos con lip sync, libro, YouTube)
- **Home del Valle** — Marketing inmobiliario con Virtual Talents y campañas completas
- **The Walking Video Guy** — Canal de YouTube (thumbnails, conceptos, voice cloning, narración)
- **Proyectos externos** — Clientes bajo contrato con Client Portal y Production Packet

---

## 2. Principios de Arquitectura

| Principio | Descripción |
|---|---|
| **Entity-first** | Personajes, locaciones y assets son entidades persistentes |
| **Model-agnostic** | Modelos de IA intercambiables sin cambiar la plataforma |
| **Audio-visual unified** | Visual + audio + lip sync gestionados como un solo pipeline |
| **Data permanence** | Cada render guarda seed, modelo, workflow, costo — reproducible forever |
| **API-first** | Toda la lógica expuesta via API interna para futuro mobile/SaaS |
| **Intelligence-driven** | La plataforma aprende y mejora con cada producción |
| **Production-feel** | El usuario piensa en personajes y escenas, nunca en parámetros técnicos |
| **Xolr OS** | Arquitectura preparada desde el inicio para ser multi-tenant y vendible |

---

## 3. Stack Tecnológico

### Backend
| Componente | Tecnología | Versión | Rol |
|---|---|---|---|
| Framework | Laravel | 11.x | Core de la aplicación |
| Lenguaje | PHP | 8.3 | Tipos estrictos, rendimiento |
| Base de Datos | MySQL | 8.0 | Datos relacionales |
| Caché / Colas | Redis | 7.x | Jobs, sesiones, caché |
| Queue Monitor | Laravel Horizon | Latest | Workers de renders y audio |
| WebSockets | Laravel Reverb | Latest | Tiempo real: renders, lip sync, audio |
| Monitoreo App | Laravel Pulse | Latest | Performance en producción |
| Auth | Laravel Sanctum | Latest | Sesiones + API tokens |
| Búsqueda | Laravel Scout + MeiliSearch | Latest | Búsqueda en biblioteca |
| PDF | Laravel Snappy | Latest | Production Bible, exportaciones |

### Frontend
| Componente | Tecnología | Rol |
|---|---|---|
| Bridge SPA | Inertia.js 2.x | SPA sin API REST separada |
| Framework UI | Vue 3 | Componentes reactivos |
| Build Tool | Vite | HMR, bundling |
| Estilos | Tailwind CSS 3.x | Dark theme profesional |
| Componentes | shadcn-vue | UI accesible |
| Estado Global | Pinia | Store del studio |
| Editor | Tiptap | Scripts, outlines, Universe Bible |
| Drag & Drop | Vue Draggable | Storyboard, timeline de audio |
| Gráficas | Chart.js + Vue-ChartJS | Analytics, costos, Intelligence |
| Audio Player | WaveSurfer.js | Preview de audios generados |
| Iconos | Lucide Vue | Set de iconos |

### Infraestructura VPS
| Componente | Tecnología | Detalle |
|---|---|---|
| OS | Ubuntu 22.04 LTS | |
| Web | Nginx | Reverse proxy + gzip + SSL |
| PHP | PHP-FPM 8.3 | |
| Search | MeiliSearch | Motor de búsqueda local |
| Process Manager | Supervisor | Horizon + Reverb + workers especializados |
| SSL | Let's Encrypt | Auto-renovación |
| CI/CD | GitHub Actions | Deploy automático |
| Backups | Cron + Rclone → R2 | MySQL + archivos diarios |

### Almacenamiento
| Tipo | Storage | Notas |
|---|---|---|
| Renders imagen/video | Cloudflare R2 | Sin egress cost |
| Audio generado (voice, SFX, música) | Cloudflare R2 | Todos los assets de audio |
| Talking renders (lip sync output) | Cloudflare R2 | Videos de personajes hablando |
| LoRA files (.safetensors) | Cloudflare R2 + VPS cache | Acceso rápido a workers |
| Workflows ComfyUI | Cloudflare R2 | Versionados |
| Exports (PDF, ZIP, Production Packet) | Cloudflare R2 | Descarga directa |
| Thumbnails web | Cloudflare Images | WebP automático, resize on-the-fly |

---

## 4. Integraciones Externas — Mapa Completo

### Render Farm (Visual)
| Servicio | Uso | Endpoint interno |
|---|---|---|
| RunPod Serverless | Generación de imagen (FLUX) | `/api/webhooks/runpod` |
| RunPod Serverless | Generación de video (WAN) | `/api/webhooks/runpod` |
| RunPod Serverless | Upscaling (RealESRGAN) | `/api/webhooks/runpod` |
| RunPod Serverless | Lip Sync (Wav2Lip/LatentSync) | `/api/webhooks/runpod` |

### Audio (ElevenLabs)
| Función | API | Notas |
|---|---|---|
| Text-to-Speech | `/v1/text-to-speech/{voice_id}` | Genera voice-over desde script |
| Voice Cloning | `/v1/voices/add` | Crear clon de voz del usuario |
| Sound Effects | `/v1/sound-generation` | Ambientes y SFX desde texto |
| Voice Library | `/v1/voices` | Gestión de voice_ids |

### Lip Sync (Multi-provider)
| Proveedor | Calidad | Uso recomendado | Costo est. |
|---|---|---|---|
| **D-ID API** | Alta | Strange Light diálogos, HDV broker videos | ~$0.015/seg |
| **HeyGen API** | Muy alta | Videos de presentación de propiedades HDV | ~$0.025/seg |
| **Wav2Lip / LatentSync en RunPod** | Media-alta | TWVG intros, bocetos, volumen alto | ~$0.004/seg |

Estrategia: el usuario selecciona calidad al crear el Talking Render. La plataforma enruta al proveedor correcto automáticamente.

### Música y Sound Design
| Servicio | Uso | Licencia |
|---|---|---|
| Suno API | Generación de música original | Comercial (plan Pro) |
| MusicGen (Meta) en RunPod | Alternativa open source, más barata | MIT |
| AudioGen (Meta) en RunPod | Ambient sounds y SFX | MIT |

### Subtítulos
| Servicio | Uso |
|---|---|
| OpenAI Whisper API | Audio generado → SRT automático |

### IA Creativa (Claude API)
| Función | Módulo |
|---|---|
| Script desde outline | Script Generator |
| Escenas y shots desde script | AI Director |
| Continuity notes | Continuity Manager |
| Bio y voz de Virtual Talents | Virtual Talent System |
| Conceptos de video YouTube | Content Machine |
| SEO titles, descriptions, tags | SEO Assistant |
| Captions por plataforma | Social Media Planner |
| Análisis de render history | Ghost Director + Intelligence Engine |
| Pricing de proyectos cliente | Pricing Calculator |

### Publicación y Distribución
| Plataforma | API | Funciones |
|---|---|---|
| YouTube Data API v3 | Upload, metadata, playlist, thumbnail | |
| YouTube Analytics API | Métricas de videos: views, CTR, watch time | Analytics inverso |
| Instagram Graph API | Publicar posts, carruseles, stories, reels | Auto-Post |
| Instagram Insights API | Métricas: reach, engagement, saves | Analytics inverso |
| Notion API | Sync de proyectos, episodios, campañas | Notion Sync |

---

## 5. Render Quality Tiers

Sistema de tres niveles que permite iterar rápido y barato, subiendo a calidad final solo cuando el shot está aprobado.

| Tier | Modelo | Velocidad | Costo est. | Uso |
|---|---|---|---|---|
| **Draft** | FLUX.1 Schnell | ~20 seg | ~$0.005 | Exploración, mood boards, iteración de prompts |
| **Standard** | FLUX.1 Dev | ~60 seg | ~$0.025 | Producción regular, aprobaciones |
| **Final** | FLUX.1 Dev + Upscale 4x | ~4 min | ~$0.08 | Render final para publicación |

**Regla de oro:** 80% del trabajo ocurre en Draft. Solo los shots aprobados en Standard pasan a Final. Ahorro estimado: 60% del costo mensual de renders.

**Aplicación automática:** Al aprobar un render en Standard, la plataforma sugiere "¿Upscale a Final?" con el costo estimado visible antes de confirmar.

---

## 6. Character DNA System

Más allá del LoRA, el Character DNA captura parámetros numéricos de identidad visual usando PuLID. Esto garantiza consistencia incluso cuando el modelo de base cambia.

```json
{
  "lora_path": "s3://xolr/loras/elena-voss-v2.safetensors",
  "lora_trigger": "elena_voss",
  "lora_weight": 0.82,
  "pulid_config": {
    "reference_images": ["s3://xolr/references/elena-ref-01.png"],
    "face_weight": 1.0,
    "id_weight": 0.8
  },
  "preferred_seeds": [145823, 167234, 189012],
  "optimal_cfg": 7.0,
  "optimal_steps": 28,
  "approval_rate_by_template": {
    "portrait_hdr": 0.89,
    "portrait_natural": 0.76,
    "full_body": 0.61
  }
}
```

El `dna_config` se almacena en el campo JSON del character y se alimenta automáticamente al `PromptEngine` al construir renders. El Intelligence Engine actualiza `preferred_seeds` y `approval_rate_by_template` con cada render.

---

## 7. Audio Pipeline — Arquitectura Completa

### Flujo de Voice-Over
```
Script / Diálogo
    ↓
AudioService::generateVoice(text, voice_profile)
    ↓
ElevenLabs API (POST /v1/text-to-speech/{voice_id})
    ↓
Audio MP3/WAV → subido a R2
    ↓
AudioAsset creado en BD (type: voice_over)
    ↓
Disponible en biblioteca de audio del proyecto
```

### Flujo de Lip Sync (Talking Render)
```
Render aprobado (imagen PNG del personaje)
    +
AudioAsset (voz generada)
    ↓
LipSyncService::submit(render, audio_asset, quality)
    ↓
    ├── quality: 'production' → D-ID API
    ├── quality: 'premium'    → HeyGen API
    └── quality: 'draft'     → RunPod (Wav2Lip/LatentSync)
    ↓
TalkingRender creado en BD (status: processing)
    ↓
Webhook recibido al completar
    ↓
Video MP4 → guardado en R2
    ↓
TalkingRender::update(status: completed, file_path)
    ↓
Broadcast Reverb → UI muestra talking render
```

### Flujo de Sound Design por Escena
```
Scene tiene mood: "tense"
    ↓
SoundDesign creado para la escena:
  - ambient_asset_id: "bosque nocturno lluvioso"
  - music_asset_id: "cuerdas tensas minimalistas"
  - dialogue_volume: 100%
  - ambient_volume: 35%
  - music_volume: 55%
    ↓
Preview en la plataforma con mixer de volúmenes
    ↓
Export: audio mix como archivo WAV listo para edición
```

### Flujo de Subtítulos
```
AudioAsset (voice-over completo del episodio)
    ↓
SubtitleService::generate(audio_asset)
    ↓
OpenAI Whisper API (POST /v1/audio/transcriptions)
    → response_format: srt
    ↓
Archivo .srt guardado en R2
    ↓
Asociado al episodio para descarga junto con el video
```

---

## 8. Ghost Director — Arquitectura de Aprendizaje

El Ghost Director es una capa sobre el AI Director que incorpora las preferencias creativas del usuario aprendidas de la historia de producción.

### Datos que recolecta
- Cada render aprobado: qué camera_style, qué time_of_day, qué mood, qué visual_style
- Cada render rechazado: los mismos campos
- Notas de director escritas manualmente en shots aprobados
- Preferencias de iluminación por tipo de escena
- Estilos de cámara más usados por tipo de shot

### Cómo influye en el AI Director
Cuando el AI Director procesa un nuevo script, antes de generar las decisiones de producción, consulta el `GhostDirectorProfile` del proyecto:

```php
// GhostDirectorService::getProfileForProject(Project $project): array
// Retorna: {
//   preferred_camera_styles_by_mood: {tense: 'close_up', action: 'wide_shot'},
//   preferred_time_of_day_for_conflict: 'night',
//   avg_shots_per_scene: 4.2,
//   favorite_visual_style: 'cinematic_dark',
//   lighting_preferences: {exterior_night: 'moonlight_cold_blue'}
// }
```

Este perfil se inyecta en el prompt de Claude como contexto adicional: "El director prefiere close-ups en escenas tensas, lighting azul frío en exteriores nocturnos, y promedia 4 shots por escena."

### Evolución
Con las primeras 5 producciones el perfil es básico. Con 20 producciones, el Ghost Director toma decisiones casi idénticas a las que tomaría el usuario manualmente. Es un sistema que mejora sin que el usuario tenga que configurarlo.

---

## 9. Auto-Post y Analytics Inverso

### Auto-Post
La plataforma publica directamente en Instagram y YouTube en el horario definido en el Content Calendar.

```
Evento de calendario (status: ready, scheduled_for: futuro)
    ↓
Cron job cada 5 minutos verifica eventos pendientes
    ↓
AutoPostJob dispatched cuando scheduled_for <= now
    ↓
    ├── type: instagram_post → InstagramService::post(image, caption, hashtags)
    ├── type: instagram_carousel → InstagramService::postCarousel(images[], caption)
    ├── type: instagram_story → InstagramService::postStory(image)
    └── type: youtube_video → YouTubeService::upload(video, metadata, thumbnail)
    ↓
SocialPost::update(status: published, platform_post_id, published_at)
```

### Analytics Inverso
Las métricas de las plataformas fluyen de vuelta a Xolr Studio cada 24 horas.

```
Cron diario → AnalyticsService::sync()
    ↓
YouTube Analytics API: views, watch_time, CTR, subscribers_gained
Instagram Insights API: reach, impressions, engagement_rate, saves
    ↓
AnalyticsSnapshot creado en BD por post/video
    ↓
Intelligence Engine procesa: ¿qué tipo de thumbnail tuvo más CTR?
    ↿ Alimenta Ghost Director y recomendaciones futuras
```

---

## 10. Notion Sync

El usuario ya tiene Notion conectado. La plataforma sincroniza automáticamente con workspaces de Notion seleccionados.

**Datos que sincroniza:**
- Estado de episodios → Database en Notion
- Nuevas campañas creadas → Página en Notion
- Calendario de publicación → Calendar view en Notion
- Renders completados → Galería en Notion
- Presupuesto del mes → Tabla en Notion

**Configuración:** El usuario mapea qué base de datos de Notion corresponde a cada tipo de dato. La sincronización es unidireccional (Xolr → Notion) para evitar conflictos.

---

## 11. Estructura de Directorios — v3

```
xolr-studio/
├── app/
│   ├── Services/
│   │   ├── Audio/
│   │   │   ├── AudioService.php          # ElevenLabs TTS + SFX
│   │   │   ├── MusicService.php          # Suno + MusicGen
│   │   │   ├── LipSyncService.php        # D-ID / HeyGen / RunPod
│   │   │   ├── SubtitleService.php       # Whisper → SRT
│   │   │   └── SoundDesignService.php    # Mix de audio por escena
│   │   ├── Intelligence/
│   │   │   ├── GhostDirectorService.php  # Aprende preferencias creativas
│   │   │   ├── IntelligenceService.php   # Insights de render history
│   │   │   └── ContinuityCheckerService.php # Detecta inconsistencias
│   │   ├── Publishing/
│   │   │   ├── AutoPostService.php       # Instagram + YouTube auto-post
│   │   │   ├── AnalyticsService.php      # Sync métricas inversas
│   │   │   ├── NotionSyncService.php     # Sync con Notion
│   │   │   └── ProductionPacketService.php # Genera paquete para cliente
│   │   ├── Client/
│   │   │   ├── ClientPortalService.php
│   │   │   └── PricingCalculatorService.php
│   │   ├── Production/
│   │   │   ├── AnimaticService.php       # Renders → video pre-viz
│   │   │   └── ContinuityCanvasService.php
│   │   └── [otros servicios existentes]
│   │
│   ├── Jobs/
│   │   ├── Audio/
│   │   │   ├── GenerateVoiceJob.php
│   │   │   ├── GenerateAmbientJob.php
│   │   │   ├── GenerateMusicJob.php
│   │   │   ├── SubmitLipSyncJob.php
│   │   │   └── GenerateSubtitlesJob.php
│   │   ├── Publishing/
│   │   │   ├── AutoPostJob.php
│   │   │   ├── SyncAnalyticsJob.php
│   │   │   └── SyncNotionJob.php
│   │   └── [otros jobs existentes]
│   │
│   └── Console/Commands/
│       ├── CheckScheduledPosts.php   # Cron: verifica auto-posts pendientes
│       ├── SyncAnalytics.php         # Cron: sync métricas diarias
│       └── SyncNotion.php            # Cron: sync Notion
│
├── resources/js/
│   ├── Pages/
│   │   ├── Audio/                    # Audio Studio module
│   │   │   ├── VoiceProfiles.vue
│   │   │   ├── AudioLibrary.vue
│   │   │   └── SoundDesigner.vue
│   │   ├── Intelligence/
│   │   │   ├── Dashboard.vue
│   │   │   ├── ContinuityCanvas.vue
│   │   │   └── GhostDirector.vue
│   │   └── [otras páginas existentes]
│   │
│   └── Components/
│       ├── Audio/
│       │   ├── VoiceDirector.vue     # Dirección por línea de diálogo
│       │   ├── AudioPlayer.vue       # Preview WaveSurfer.js
│       │   ├── SoundMixer.vue        # Mixer de diálogo+ambient+música
│       │   └── TalkingRenderCard.vue
│       ├── Intelligence/
│       │   ├── InsightCard.vue
│       │   └── ContinuityAlert.vue
│       └── [otros componentes existentes]
```

---

## 12. Variables de Entorno — v3

```env
# App
APP_NAME="Xolr Studio"
APP_ENV=production
APP_URL=https://xolrstudio.com

# Database, Redis, Queue (igual que v2)

# Storage — Cloudflare R2
FILESYSTEM_DISK=s3
AWS_BUCKET=xolr-studio-assets
AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com
AWS_URL=https://assets.xolrstudio.com

# RunPod (Renders + Audio ML en RunPod)
RUNPOD_API_KEY=
RUNPOD_ENDPOINT_IMAGE=
RUNPOD_ENDPOINT_VIDEO=
RUNPOD_ENDPOINT_UPSCALE=
RUNPOD_ENDPOINT_LIPSYNC=        # Wav2Lip / LatentSync
RUNPOD_ENDPOINT_MUSICGEN=       # MusicGen open source
RUNPOD_WEBHOOK_SECRET=

# Audio — ElevenLabs
ELEVENLABS_API_KEY=
ELEVENLABS_DEFAULT_MODEL=eleven_multilingual_v2

# Lip Sync — D-ID
DID_API_KEY=
DID_API_URL=https://api.d-id.com

# Lip Sync — HeyGen
HEYGEN_API_KEY=

# Música — Suno
SUNO_API_KEY=
SUNO_PLAN=pro  # Licencia comercial requerida

# Subtítulos — Whisper
OPENAI_API_KEY=    # Solo para Whisper, no para Claude

# Claude AI
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-sonnet-4-6

# YouTube
YOUTUBE_CLIENT_ID=
YOUTUBE_CLIENT_SECRET=
YOUTUBE_REDIRECT_URI=https://xolrstudio.com/oauth/youtube/callback

# Instagram
INSTAGRAM_APP_ID=
INSTAGRAM_APP_SECRET=
INSTAGRAM_REDIRECT_URI=https://xolrstudio.com/oauth/instagram/callback

# Notion
NOTION_API_KEY=
NOTION_WORKSPACE_ID=

# MeiliSearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=

# Presupuesto y Alertas
RENDER_BUDGET_MONTHLY_USD=200
RENDER_BUDGET_ALERT_THRESHOLD=0.80

# Auto-Post
AUTOPOST_CHECK_INTERVAL_MINUTES=5

# Analytics Sync
ANALYTICS_SYNC_HOUR=3  # 3 AM diario

# Xolr OS (SaaS futuro)
MULTI_TENANT_ENABLED=false
```

---

## 13. Cron Schedule

```php
// app/Console/Kernel.php
$schedule->command('posts:check-scheduled')->everyFiveMinutes();
$schedule->command('analytics:sync')->dailyAt('03:00');
$schedule->command('notion:sync')->hourly();
$schedule->command('intelligence:compute-insights')->dailyAt('04:00');
$schedule->command('budget:check-alerts')->dailyAt('08:00');
$schedule->command('renders:poll-status')->everyTenMinutes(); // fallback
```

---

## 14. Escalabilidad — Visión Xolr OS

La arquitectura actual soporta un solo usuario (Alejandro) con sus proyectos. Pero está diseñada para escalar a multi-tenant sin refactoring mayor:

**Hoy:** Un workspace, todos los proyectos del mismo owner.

**En 18 meses:** Multi-tenant con `workspace_id` en todas las tablas. Cada creador tiene su propio espacio aislado. Subscription plans con límites de renders/mes y features. Panel de admin para gestionar workspaces.

**Valor de mercado:** Cualquier creador que produce contenido con IA (series, marketing, YouTube) necesita exactamente esto. No existe una plataforma integrada en el mercado que combine entity-first + render farm + audio + lip sync + publishing + intelligence.

---

*Documento vivo — actualizar cuando cambien decisiones de arquitectura.*  
*Versión 3.0 — Junio 2026*
