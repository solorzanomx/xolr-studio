# Xolr Studio — Roadmap de Desarrollo
**Versión:** 3.0  
**Autor:** Alejandro Solórzano  
**Fecha:** Junio 2026  
**Estado:** Documento Maestro Activo

---

## Estado General

| | |
|---|---|
| **Fase actual** | Fase 0 — Planificación y documentación |
| **Progreso global** | 3% — documentación maestra completa |
| **Stack** | Laravel 11 + Inertia.js + Vue 3 + MySQL + Redis |
| **Documentos maestros** | ✅ 6 documentos completos v3 |
| **Inicio de código** | Pendiente |

---

## Resumen de Fases

| Fase | Nombre | Estado |
|---|---|---|
| 0 | Planificación y Documentación | 🔵 En curso |
| 1 | Infraestructura y Setup | ⬜ |
| 2 | Core y Modelo de Datos | ⬜ |
| 3 | Studio Dashboard y Auth | ⬜ |
| 4 | Biblioteca Global | ⬜ |
| 5 | Virtual Talent System | ⬜ |
| 6 | Gestión de Proyectos y Producción | ⬜ |
| 7 | Campaign Manager (Home del Valle) | ⬜ |
| 8 | Content Machine (The Walking Video Guy) | ⬜ |
| 9 | Prompt Engine + Render Quality Tiers | ⬜ |
| 10 | Render Farm Integration | ⬜ |
| 11 | Audio Studio | ⬜ |
| 12 | Lip Sync Engine | ⬜ |
| 13 | AI Director + Ghost Director + Script Generator | ⬜ |
| 14 | Intelligence Engine + Continuity Canvas | ⬜ |
| 15 | Publishing Engine + Animatic | ⬜ |
| 16 | Auto-Post + Analytics Inverso + Notion Sync | ⬜ |
| 17 | Client Portal + Pricing Calculator | ⬜ |
| 18 | Content Calendar + Social Media Planner | ⬜ |
| 19 | Optimización, Pulido y Producción Real | ⬜ |
| 20 | Xolr OS — Arquitectura SaaS | ⬜ |

**Leyenda:** ✅ Completado · 🔄 En progreso · 🔵 En curso · ⬜ Pendiente

---

## FASE 0 — Planificación y Documentación
**Estado:** 🔵 En curso

- [x] Documento: Arquitectura Técnica v3
- [x] Documento: Modelo de Base de Datos v3
- [x] Documento: Roadmap de Desarrollo v3
- [x] Documento: Especificación de Producto v3
- [x] Documento: Proyectos Flagship v2
- [x] Documento: Módulos Avanzados v2
- [ ] Contratar dominio xolrstudio.com
- [ ] Contratar VPS
- [ ] Crear repositorio en GitHub

---

## FASE 1 — Infraestructura y Setup

### VPS y Servidor
- [ ] Contratar VPS (4 vCPU, 8GB RAM, 100GB SSD — Ubuntu 22.04 LTS)
- [ ] Nginx + PHP 8.3 FPM + MySQL 8 + Redis 7 + Node 20
- [ ] MeiliSearch + Supervisor
- [ ] Certbot SSL + UFW firewall
- [ ] Nginx virtual host con dominio

### Repositorio y CI/CD
- [ ] Repositorio privado `xolr-studio` en GitHub
- [ ] Ramas: `main` (prod), `develop`, `feature/*`
- [ ] GitHub Actions: deploy automático en push a `main`
- [ ] GitHub Secrets con todas las variables de entorno

### Proyecto Laravel Base
- [ ] `laravel new xolr-studio`
- [ ] Inertia.js + Vue 3 + Tailwind CSS + shadcn-vue
- [ ] Laravel Horizon + Reverb + Pulse + Scout
- [ ] Pinia + Tiptap + Vue Draggable + WaveSurfer.js + Chart.js
- [ ] Cloudflare R2 configurado y verificado
- [ ] App corriendo en dominio con HTTPS ✅

**Milestone:** Laravel en vivo en el dominio, HTTPS, queue workers activos, R2 funcionando.

---

## FASE 2 — Core y Modelo de Datos

### Migraciones (45 en total — en orden del doc de BD)
- [ ] 001–013: Biblioteca global (users, styles, locations, characters, assets...)
- [ ] 014–032: Producción, campañas, contenido, renderizado, negocio
- [ ] 033–045: Nuevas v3 (DNA, quality tier, audio, talking renders, social posts, analytics, quotes)

### Modelos Eloquent
- [ ] Todos los modelos con relaciones, scopes y casts JSON
- [ ] Polimórficos: assets, audio_assets, annotations

### Seeders
- [ ] `FormatPresetSeeder` (10 presets del sistema)
- [ ] `CameraStyleSeeder` (8 estilos)
- [ ] `VisualStyleSeeder` (5 estilos)
- [ ] `RenderTemplateSeeder` (5 templates ComfyUI)
- [ ] `AdminUserSeeder`
- [ ] `DemoProjectSeeder` (Strange Light + HDV + TWVG vacíos)

**Milestone:** `php artisan migrate:fresh --seed` sin errores. Todas las relaciones verificadas.

---

## FASE 3 — Studio Dashboard y Autenticación

- [ ] Laravel Breeze con Inertia + Vue (dark theme personalizado)
- [ ] Login page con identidad Xolr Studio
- [ ] Layout: sidebar colapsable + header + content area
- [ ] Header: indicador de renders activos + costo del día + usuario
- [ ] Dashboard: proyectos activos, renders en proceso (Reverb), métricas, actividad reciente
- [ ] Configuración del Studio: API keys (RunPod, ElevenLabs, Anthropic, D-ID, Suno)

**Milestone:** Login funciona, dashboard carga datos reales, WebSockets activos.

---

## FASE 4 — Biblioteca Global

### Character Library
- [ ] Grid con filtros (tipo: fictional/virtual_talent, activo, con LoRA, con voz)
- [ ] Búsqueda MeiliSearch
- [ ] CRUD completo + upload LoRA + upload imágenes de referencia
- [ ] Vista de personaje: datos, DNA config, galería de renders, versiones, outfits, métricas
- [ ] Character Versions CRUD
- [ ] Outfits CRUD

### Demás módulos de biblioteca
- [ ] Locations CRUD con iluminación por hora del día
- [ ] Visual Styles + Camera Styles + Props CRUD
- [ ] Format Presets (lectura de los del sistema + custom)
- [ ] Render Templates: CRUD, editor de workflow JSON

**Milestone:** Personaje con LoRA, outfits, locación con iluminación completa — todo reutilizable.

---

## FASE 5 — Virtual Talent System

- [ ] CRUD de Virtual Talents (extensión de characters)
- [ ] Generador de bio con Claude API
- [ ] Communication style guide integrado con caption generator
- [ ] Vista de perfil: looks por contexto, galería de renders
- [ ] Panel de especialidades y signature phrase

**Milestone:** Sofía Navarro creada — bio, communication style, 3 outfits, 5 renders de referencia.

---

## FASE 6 — Gestión de Proyectos y Producción

### Proyectos y Estructura
- [ ] Lista y CRUD de proyectos por tipo
- [ ] Brand Identity por proyecto (colores, fuente, estilo visual default)
- [ ] CRUD Temporadas + Episodios
- [ ] Editor de script con Tiptap + guardado automático cada 30 seg
- [ ] Universe Bible module (categorías, editor, vinculación con characters/locations)
- [ ] Character Relationships: CRUD + visualización de red

### Escenas y Shots
- [ ] Vista de Storyboard: grid horizontal con drag & drop
- [ ] CRUD de Escenas con location + time_of_day + mood
- [ ] CRUD de Shots: tipo (image/video/talking), purpose, formato, personajes, outfits, props, diálogo

**Milestone:** Strange Light S01E01 estructurado — outline, script, 3 escenas, 10 shots.

---

## FASE 7 — Campaign Manager

- [ ] CRUD de Properties (inmuebles)
- [ ] CRUD de Campaigns con asset checklist configurable
- [ ] Auto-creación de shots desde checklist (formato correcto por tipo)
- [ ] Progress tracker de campaña (X/Y assets completados)
- [ ] Export: ZIP con naming convention + captions

**Milestone:** Primera campaña de HDV completa — 10 assets producidos y listos.

---

## FASE 8 — Content Machine (The Walking Video Guy)

- [ ] Ideas Bank con sistema de rating (1–5 estrellas)
- [ ] CRUD de Video Concepts con generador de IA (hook, estructura, tips, CTA)
- [ ] SEO Assistant: título, descripción, tags optimizados para YouTube
- [ ] Thumbnail Studio: shot especializado landscape 16:9 con comparador de variantes
- [ ] Series Manager: agrupar videos en series temáticas
- [ ] Banco de hooks reutilizables por categoría

**Milestone:** 10 conceptos de video con SEO + 5 thumbnails renderizados para TWVG.

---

## FASE 9 — Prompt Engine + Render Quality Tiers

- [ ] `PromptEngine::compose(Shot)` — composición automática completa
- [ ] Preview del prompt con secciones coloreadas por origen
- [ ] Historial de versiones con diff visual
- [ ] Comparador A vs B de prompts
- [ ] Biblioteca de snippets de prompt
- [ ] **Render Quality Tier selector** en el shot (Draft / Standard / Final)
- [ ] Estimación de costo antes de renderizar según tier

**Milestone:** Shot complejo (Elena + Outfit + Noche + Close-up) genera prompt coherente. Tier selector funciona con estimación de costo.

---

## FASE 10 — Render Farm Integration

### Backend
- [ ] `RenderFarmService` + `RunPodAdapter` (abstracción)
- [ ] Jobs: `SubmitRenderJob`, `PollRenderStatus`
- [ ] Webhook `POST /api/webhooks/runpod` con HMAC
- [ ] Batch rendering: encolar múltiples shots a la vez con priorización
- [ ] Smart retry: reintento automático con seed diferente si falla
- [ ] Watermark automático en renders de Draft y Standard para cliente
- [ ] Horizon workers configurados en Supervisor

### Frontend
- [ ] Botón "Renderizar" con tier selector + estimación de costo
- [ ] Estado en tiempo real (Reverb): queued → processing → completed
- [ ] Galería de renders por shot (approved + variaciones)
- [ ] Render Farm Monitor: todos los jobs activos, historial, costo acumulado
- [ ] **Render Annotations:** comentarios con marcadores visuales sobre el render
- [ ] Indicador de costo en header con alerta visual al 80% del presupuesto

**Milestone:** Render real de Elena — Draft ($0.005), aprobado → Final ($0.08). Costo registrado. Annotations funcionando.

---

## FASE 11 — Audio Studio

### Backend
- [ ] `AudioService`: ElevenLabs TTS (voice-over + diálogo)
- [ ] `AudioService`: ElevenLabs Sound Effects (ambientes + SFX)
- [ ] `MusicService`: Suno API + fallback MusicGen en RunPod
- [ ] `SubtitleService`: Whisper API → SRT
- [ ] Jobs: `GenerateVoiceJob`, `GenerateAmbientJob`, `GenerateMusicJob`, `GenerateSubtitlesJob`
- [ ] Voice Profiles CRUD + asociación con characters

### Frontend
- [ ] Voice Profiles: gestión de voice_ids por personaje
- [ ] Audio Library: todos los assets de audio del proyecto organizados
- [ ] Generador de voice-over desde texto con Voice Director (emoción, ritmo, énfasis por línea)
- [ ] Generador de ambientes/SFX desde descripción de texto
- [ ] Generador de música: prompt + duración + mood
- [ ] Audio Player: preview con WaveSurfer.js en la plataforma
- [ ] Sound Design por escena: mixer visual (diálogo + ambient + música con sliders de volumen)
- [ ] Subtitle Generator: audio → .srt automático

**Milestone:** Elena habla una línea de diálogo con emoción dirigida. Bosque de Ashveil tiene ambiente sonoro completo. Escena tiene sound design mezclado.

---

## FASE 12 — Lip Sync Engine

### Backend
- [ ] `LipSyncService` con enrutamiento por calidad (D-ID / HeyGen / RunPod)
- [ ] Job: `SubmitLipSyncJob`
- [ ] Webhook handler para D-ID y HeyGen
- [ ] TalkingRender CRUD en BD
- [ ] Costo registrado por proveedor

### Frontend
- [ ] Shot tipo "talking": selector de render fuente + audio fuente + calidad de lip sync
- [ ] Preview comparativo: imagen original vs talking render
- [ ] Galería de talking renders por shot
- [ ] Indicador de costo estimado antes de lanzar el job
- [ ] Aprobación de talking render (igual que renders normales)

**Milestone:** Elena dice su primera línea en Strange Light con labios sincronizados. Sofía presenta una propiedad de HDV en video con lip sync de calidad producción.

---

## FASE 13 — AI Director + Ghost Director + Script Generator

### AI Director
- [ ] `AIDirectorService` + `ProcessAIDirector` Job
- [ ] Análisis de script → JSON estructurado (escenas, shots, asignaciones)
- [ ] Vista de revisión: tabla de decisiones antes de aplicar
- [ ] Creación masiva en BD al confirmar
- [ ] Pre-cálculo de prompts para todos los shots

### Ghost Director
- [ ] `GhostDirectorService`: análisis de historial de aprobaciones/rechazos
- [ ] Perfil de preferencias creativas por proyecto (camera, lighting, mood preferences)
- [ ] Inyección del perfil en el prompt del AI Director
- [ ] Vista: "Tu estilo creativo" — resumen del perfil aprendido

### Script Generator
- [ ] Generar script desde outline (Claude API con Universe Bible como contexto)
- [ ] Generar capítulo del libro desde script
- [ ] Continuity check: Claude verifica coherencia con episodios anteriores

**Milestone:** AI Director + Ghost Director procesan S01E01 completo — 3 escenas, 12 shots, con el estilo creativo del usuario incorporado.

---

## FASE 14 — Intelligence Engine + Continuity Canvas

- [ ] `IntelligenceService`: análisis de render history
- [ ] Insights por personaje: seeds óptimos, tasa de aprobación, degradación de LoRA
- [ ] Dashboard de inteligencia con gráficas (Chart.js)
- [ ] Alertas: presupuesto, LoRA degradado, workflow lento
- [ ] `ContinuityCheckerService`: análisis de renders de una escena con IA
- [ ] Alertas de continuity: "Elena cambió de look entre Shot 3 y Shot 5"
- [ ] **Continuity Canvas**: mapa visual de todos los renders por personaje × timeline
- [ ] Production Score: métrica gamificada de calidad de producción

**Milestone:** Intelligence Engine activo con 50+ renders. Continuity Canvas de S01E01 completo.

---

## FASE 15 — Publishing Engine + Animatic Generator

### Animatic Generator
- [ ] `AnimaticService`: combina renders aprobados en video de pre-visualización
- [ ] Timeline: definir duración de cada shot
- [ ] Output: video MP4 del animatic con música de fondo opcional
- [ ] Export del animatic por episodio

### Publishing
- [ ] PDF libro (temporada completa con renders como ilustraciones)
- [ ] Export ZIP campaña con naming convention
- [ ] Carousel Builder: compositor visual + preview del feed
- [ ] Production Bible PDF: auto-generado de todos los datos del proyecto
- [ ] YouTube Publishing: OAuth2 + upload + metadata + playlist

**Milestone:** Animatic de S01E01. PDF libro de T1. Production Bible de Strange Light.

---

## FASE 16 — Auto-Post + Analytics Inverso + Notion Sync

### Auto-Post
- [ ] Instagram Graph API: post, carousel, story, reel
- [ ] YouTube auto-upload con metadata
- [ ] Cron: `CheckScheduledPosts` cada 5 minutos
- [ ] Notificación al publicar exitosamente

### Analytics Inverso
- [ ] YouTube Analytics API: views, CTR, watch time
- [ ] Instagram Insights API: reach, engagement, saves
- [ ] Cron: `SyncAnalytics` diario a las 3 AM
- [ ] Dashboard de analytics en la plataforma con gráficas
- [ ] A/B testing de thumbnails: comparar CTR de variantes

### Notion Sync
- [ ] Notion API: sync de episodios, campañas, calendario, renders
- [ ] Configuración por proyecto: qué DB de Notion corresponde a qué datos
- [ ] Cron: `SyncNotion` cada hora

**Milestone:** Post de HDV publicado automáticamente en Instagram. Métricas de YouTube visibles en Xolr Studio. Notion sincronizado con el estado del proyecto.

---

## FASE 17 — Client Portal + Pricing Calculator

### Client Portal
- [ ] URL única por cliente: `/client/{token}`
- [ ] Vista del cliente: renders, talking renders, estado, aprobaciones con comentarios
- [ ] Render Annotations: el cliente puede marcar áreas específicas de un render
- [ ] Notificación por email cuando hay nuevos renders
- [ ] Descarga de assets aprobados (configurable)
- [ ] Revocación de acceso

### Pricing Calculator
- [ ] Wizard de cotización: items, horas, costo de renders, margen
- [ ] Número de cotización secuencial automático
- [ ] Vista de cotización en formato profesional
- [ ] Export PDF de la cotización
- [ ] Estados: borrador → enviada → aceptada → facturada
- [ ] **Production Packet**: export completo del proyecto para entrega al cliente

**Milestone:** Cliente externo accede a su portal, aprueba renders con comentarios, recibe Production Packet. Cotización generada y exportada en PDF.

---

## FASE 18 — Content Calendar + Social Media Planner

- [ ] Vista mensual y semanal del calendario
- [ ] Crear eventos desde renders, campañas o episodios aprobados
- [ ] Caption Generator integrado (Claude) por plataforma y tono
- [ ] Checklist de publicación por evento
- [ ] Vista de pipeline semanal: qué falta para publicar
- [ ] Historial de publicaciones pasadas con métricas

**Milestone:** Calendario de HDV con 4 semanas de contenido planificado, captions generados y listos.

---

## FASE 19 — Optimización y Producción Real

- [ ] Auditoría de N+1 queries con Debugbar
- [ ] Caché Redis en listados grandes (biblioteca, renders)
- [ ] Lazy loading de imágenes en galerías
- [ ] Optimización de assets: WebP via Cloudflare Images
- [ ] Tests de carga en render farm y audio pipeline
- [ ] Revisión completa de seguridad
- [ ] Primer episodio completo de Strange Light producido en la plataforma
- [ ] Primera campaña completa de HDV producida y publicada
- [ ] 10 thumbnails de TWVG producidos y publicados

**Milestone:** La plataforma está en producción real operando los 3 proyectos flagship.

---

## FASE 20 — Xolr OS — Arquitectura SaaS

- [ ] Multi-tenant: `workspace_id` en todas las tablas principales
- [ ] Registro y onboarding de nuevos usuarios
- [ ] Planes de suscripción: Starter / Pro / Studio
- [ ] Panel de admin: gestión de workspaces, métricas globales
- [ ] Límites por plan: renders/mes, proyectos, usuarios, storage
- [ ] Billing: integración con Stripe
- [ ] Landing page de Xolr Studio como producto

**Milestone:** Xolr Studio disponible como SaaS para otros creadores.

---

## Milestones por Proyecto Flagship

### Strange Light
| ID | Milestone | Fase |
|---|---|---|
| SL-1 | S01E01 estructurado (outline, script, escenas, shots) | 6 |
| SL-2 | Personajes con LoRA + voces (Elena, Kael, Marcus) | 11 |
| SL-3 | AI Director procesa S01E01 con Ghost Director | 13 |
| SL-4 | Renders visuales de S01E01 completados y aprobados | 10 |
| SL-5 | Talking renders de diálogos clave de S01E01 | 12 |
| SL-6 | Animatic de S01E01 generado | 15 |
| SL-7 | PDF libro de Temporada 1 exportado | 15 |
| SL-8 | S01E01 publicado en YouTube | 15 |

### Home del Valle
| ID | Milestone | Fase |
|---|---|---|
| HDV-1 | Sofía y Diego — LoRA + voces + galería completa | 5 + 11 |
| HDV-2 | Primera campaña de propiedad producida en < 4 horas | 7 + 10 |
| HDV-3 | Sofía presenta propiedad en video con lip sync | 12 |
| HDV-4 | Auto-post funcionando en Instagram | 16 |
| HDV-5 | Analytics de Instagram en la plataforma | 16 |

### The Walking Video Guy
| ID | Milestone | Fase |
|---|---|---|
| WVG-1 | Ideas Bank con 50 ideas + 10 conceptos con SEO | 8 |
| WVG-2 | 10 thumbnails producidos con comparador A/B | 8 + 10 |
| WVG-3 | Voice cloning del presenter configurado | 11 |
| WVG-4 | Primer video con thumbnail de la plataforma publicado | 16 |
| WVG-5 | CTR analytics de thumbnails en la plataforma | 16 |

---

*Actualizar el estado de cada fase conforme avanza el desarrollo.*  
*Versión 3.0 — Junio 2026*
