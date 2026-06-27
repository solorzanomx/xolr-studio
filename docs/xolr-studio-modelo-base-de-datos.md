# Xolr Studio — Modelo de Base de Datos
**Versión:** 3.0  
**Autor:** Alejandro Solórzano  
**Fecha:** Junio 2026  
**Estado:** Documento Maestro Activo

---

## 1. Principios del Modelo

- **Entity-first:** Personajes, locaciones y assets existen independientemente de proyectos.
- **Audio-visual unified:** Renders de imagen, video, audio y lip sync son todos assets de producción.
- **Trazabilidad total:** Cada render y cada audio guardan todos sus parámetros exactos.
- **Intelligence-ready:** Datos suficientes para que el Ghost Director y el Intelligence Engine aprendan.
- **SaaS-ready:** Diseño que soporta multi-tenant con `workspace_id` sin refactoring.

---

## 2. Mapa Completo de Entidades — v3

```
══════════════ IDENTIDAD & ACCESO ══════════════
users ── clients ── (futuro: workspaces)

══════════════ BIBLIOTECA GLOBAL ══════════════
characters ─┬─ character_versions ── voice_profiles ← NUEVO
            ├─ outfits
            └─ virtual_talents

locations / visual_styles / camera_styles
props / format_presets / render_templates

══════════════ PRODUCCIÓN NARRATIVA ══════════
projects ─┬─ seasons ─ episodes ─ scenes ─ shots
          ├─ universe_notes
          └─ character_relationships

══════════════ CAMPAÑAS Y CONTENIDO ══════════
properties ── campaigns ── (shots tipados)
video_concepts ── (shots thumbnail)
content_calendar_events

══════════════ RENDERIZADO VISUAL ════════════
shots ─┬─ prompts ─ renders (con quality_tier) ← ACTUALIZADO
       └─ render_annotations ← NUEVO

render_insights (Intelligence Engine)

══════════════ AUDIO STUDIO ← NUEVO ══════════
voice_profiles ── audio_assets ── talking_renders
sound_designs

══════════════ PUBLICACIÓN Y ANALYTICS ═══════
social_posts ← NUEVO
analytics_snapshots ← NUEVO

══════════════ NEGOCIO ════════════════════════
continuity_notes / exports / budgets
client_quotes ← NUEVO
```

---

## 3. Tablas de Identidad y Acceso

### `users`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(100) | |
| email | VARCHAR(255) UNIQUE | |
| password | VARCHAR(255) | bcrypt |
| role | ENUM('owner','admin','editor','viewer') | |
| avatar_path | VARCHAR(500) NULL | |
| timezone | VARCHAR(50) | Default: America/Mexico_City |
| preferences | JSON NULL | UI prefs, default project, etc. |
| ghost_director_profile | JSON NULL | Perfil de preferencias creativas aprendidas |
| last_active_at | TIMESTAMP NULL | |
| created_at / updated_at | TIMESTAMP | |

### `clients`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| project_id | BIGINT UNSIGNED FK (projects) | |
| name | VARCHAR(150) | |
| email | VARCHAR(255) | |
| company | VARCHAR(150) NULL | |
| access_token | VARCHAR(64) UNIQUE | UUID |
| token_expires_at | TIMESTAMP NULL | |
| can_download | BOOLEAN | Default: false |
| can_comment | BOOLEAN | Default: true |
| can_approve | BOOLEAN | Default: true |
| is_active | BOOLEAN | Default: true |
| last_accessed_at | TIMESTAMP NULL | |
| created_at / updated_at | TIMESTAMP | |

---

## 4. Tablas de Biblioteca Global

### `characters`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(150) | |
| slug | VARCHAR(150) UNIQUE | |
| type | ENUM('fictional','virtual_talent','mascot') | |
| description | TEXT NULL | |
| physical_description | TEXT NULL | |
| personality_traits | JSON NULL | |
| base_prompt | TEXT NULL | |
| negative_prompt | TEXT NULL | |
| lora_path | VARCHAR(500) NULL | |
| lora_trigger_word | VARCHAR(100) NULL | |
| lora_weight | DECIMAL(3,2) NULL | |
| lora_trained_at | TIMESTAMP NULL | |
| **dna_config** | JSON NULL | **PuLID params, preferred seeds, approval rates por template** |
| thumbnail_path | VARCHAR(500) NULL | |
| approval_rate | DECIMAL(5,2) NULL | Calculado por Intelligence Engine |
| avg_renders_to_approve | DECIMAL(4,2) NULL | |
| is_active | BOOLEAN | |
| created_by | BIGINT UNSIGNED FK (users) | |
| created_at / updated_at | TIMESTAMP | |

### `voice_profiles` ← NUEVO
Perfil de voz de ElevenLabs asociado a un personaje o Virtual Talent.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| character_id | BIGINT UNSIGNED FK (characters) | |
| name | VARCHAR(150) | Ej: "Voz de Elena — dramática" |
| elevenlabs_voice_id | VARCHAR(100) | ID en ElevenLabs |
| is_cloned | BOOLEAN | True si es voz clonada (vs sintética) |
| language | VARCHAR(10) | es, en, fr, etc. |
| default_stability | DECIMAL(3,2) | 0.00–1.00, default: 0.50 |
| default_similarity_boost | DECIMAL(3,2) | 0.00–1.00, default: 0.75 |
| default_style | DECIMAL(3,2) | 0.00–1.00, default: 0.00 |
| notes | TEXT NULL | Guías de dirección de voz |
| is_default | BOOLEAN | Voz default del personaje |
| created_at / updated_at | TIMESTAMP | |

### `virtual_talents`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| character_id | BIGINT UNSIGNED FK (characters) | |
| title | VARCHAR(100) | |
| specialties | JSON NULL | |
| bio_short | TEXT NULL | |
| bio_full | TEXT NULL | |
| communication_style | TEXT NULL | Guía de voz para Claude |
| signature_phrase | VARCHAR(255) NULL | |
| social_handle | VARCHAR(100) NULL | |
| brand_colors | JSON NULL | |
| is_public | BOOLEAN | |
| created_at / updated_at | TIMESTAMP | |

### `character_versions`, `outfits`, `character_relationships`
*(Sin cambios desde v2)*

### `locations`, `visual_styles`, `camera_styles`, `props`
*(Sin cambios desde v2)*

### `format_presets`, `render_templates`, `assets`
*(Sin cambios desde v2)*

---

## 5. Tablas de Producción

### `projects`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(255) | |
| slug | VARCHAR(255) UNIQUE | |
| type | ENUM('fiction_series','youtube_channel','real_estate','commercial','corporate','social_media','client') | |
| description / synopsis | TEXT NULL | |
| visual_style_id | BIGINT UNSIGNED FK NULL | |
| default_format_preset_id | BIGINT UNSIGNED FK NULL | |
| status | ENUM(...) | |
| brand_colors / brand_fonts | JSON NULL | |
| thumbnail_path / cover_path | VARCHAR(500) NULL | |
| monthly_budget_usd | DECIMAL(10,2) NULL | |
| **notion_database_id** | VARCHAR(100) NULL | **ID de BD Notion para sync** |
| **notion_sync_enabled** | BOOLEAN | **Default: false** |
| settings | JSON NULL | |
| created_by | BIGINT UNSIGNED FK (users) | |
| created_at / updated_at | TIMESTAMP | |

### `universe_notes`, `seasons`, `episodes`, `scenes`
*(Sin cambios desde v2)*

### `shots`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| scene_id / campaign_id / video_concept_id | BIGINT UNSIGNED NULL | |
| number | SMALLINT UNSIGNED | |
| description | TEXT NULL | |
| shot_type | ENUM('image','video','talking') | **'talking' = lip sync** |
| purpose | ENUM('narrative','hero','carousel_frame','thumbnail','social','broker_portrait','property_hero','talking_dialogue') | |
| format_preset_id | BIGINT UNSIGNED FK NULL | |
| camera_style_id / visual_style_id | BIGINT UNSIGNED FK NULL | |
| **dialogue_text** | TEXT NULL | **Texto del diálogo para talking shots** |
| duration_seconds | TINYINT NULL | |
| director_notes | TEXT NULL | |
| status | ENUM('draft','prompt_ready','rendering','audio_pending','lip_sync_pending','completed','approved') | |
| approved_render_id / approved_talking_render_id | BIGINT UNSIGNED NULL | |
| sort_order | SMALLINT UNSIGNED | |
| created_at / updated_at | TIMESTAMP | |

### `shot_characters`, `shot_props`
*(Sin cambios desde v2)*

---

## 6. Tablas de Renderizado Visual

### `prompts`
*(Sin cambios desde v2)*

### `renders`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| prompt_id | BIGINT UNSIGNED FK (prompts) | |
| shot_id | BIGINT UNSIGNED FK (shots) | |
| status | ENUM('queued','processing','completed','failed','cancelled') | |
| **quality_tier** | ENUM('draft','standard','final') | **NUEVO — tier del render** |
| gpu_service | ENUM('runpod','replicate','local','other') | |
| job_id | VARCHAR(255) NULL | |
| seed | BIGINT NULL | |
| file_path | VARCHAR(500) NULL | |
| file_type | ENUM('image','video') | |
| format_preset_id | BIGINT UNSIGNED FK NULL | |
| width / height | SMALLINT UNSIGNED NULL | |
| duration_ms | INT UNSIGNED NULL | |
| gpu_cost_usd | DECIMAL(8,6) NULL | |
| user_rating | TINYINT NULL | 1–5 |
| is_approved | BOOLEAN | |
| error_message | TEXT NULL | |
| retry_count | TINYINT UNSIGNED | Default: 0 |
| metadata | JSON NULL | |
| created_at / updated_at | TIMESTAMP | |

### `render_annotations` ← NUEVO
Comentarios con marcadores visuales sobre áreas específicas de un render.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| render_id | BIGINT UNSIGNED FK (renders) | |
| x_percent | DECIMAL(5,2) | Posición X en % del ancho (0–100) |
| y_percent | DECIMAL(5,2) | Posición Y en % del alto (0–100) |
| content | TEXT | El comentario/feedback |
| type | ENUM('issue','suggestion','approval','question') | |
| created_by | BIGINT UNSIGNED FK (users) NULL | NULL = comentario de cliente |
| client_id | BIGINT UNSIGNED FK (clients) NULL | |
| resolved | BOOLEAN | Default: false |
| created_at / updated_at | TIMESTAMP | |

### `render_insights`
*(Sin cambios desde v2)*

---

## 7. Tablas de Audio Studio ← TODAS NUEVAS

### `audio_assets`
Todos los archivos de audio generados o subidos a la plataforma.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| project_id | BIGINT UNSIGNED FK (projects) NULL | NULL = asset global |
| name | VARCHAR(255) | |
| type | ENUM('voice_over','dialogue','ambient','sfx','music','mixed') | |
| file_path | VARCHAR(500) NULL | S3 path al archivo |
| file_format | ENUM('mp3','wav','ogg') | |
| duration_seconds | DECIMAL(8,2) NULL | |
| transcript | TEXT NULL | Texto fuente del audio (para voice) |
| prompt_used | TEXT NULL | Prompt usado para ambientes/música |
| voice_profile_id | BIGINT UNSIGNED FK (voice_profiles) NULL | Si fue generado con ElevenLabs |
| service | ENUM('elevenlabs','suno','musicgen','audiogen','runpod','uploaded') | |
| service_job_id | VARCHAR(255) NULL | |
| generation_cost_usd | DECIMAL(8,6) NULL | |
| status | ENUM('pending','generating','completed','failed') | |
| audioable_type | VARCHAR(255) NULL | Polimórfico: Episode, Scene, Shot, Campaign |
| audioable_id | BIGINT NULL | |
| created_by | BIGINT UNSIGNED FK (users) | |
| created_at / updated_at | TIMESTAMP | |

### `talking_renders` ← NUEVO
Jobs de lip sync: un render de imagen + un audio = video del personaje hablando.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| shot_id | BIGINT UNSIGNED FK (shots) | |
| source_render_id | BIGINT UNSIGNED FK (renders) | La imagen fuente |
| audio_asset_id | BIGINT UNSIGNED FK (audio_assets) | El audio fuente |
| quality | ENUM('draft','production','premium') | draft=RunPod, production=D-ID, premium=HeyGen |
| service | ENUM('did','heygen','runpod_wav2lip','runpod_latentsync') | |
| service_job_id | VARCHAR(255) NULL | |
| status | ENUM('queued','processing','completed','failed') | |
| file_path | VARCHAR(500) NULL | Video MP4 resultante |
| duration_seconds | DECIMAL(8,2) NULL | |
| width / height | SMALLINT UNSIGNED NULL | |
| service_cost_usd | DECIMAL(8,6) NULL | |
| is_approved | BOOLEAN | Default: false |
| error_message | TEXT NULL | |
| created_at / updated_at | TIMESTAMP | |

### `sound_designs` ← NUEVO
Diseño sonoro de una escena: mix de diálogo + ambientes + música.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| scene_id | BIGINT UNSIGNED FK (scenes) | |
| name | VARCHAR(150) | Ej: "Bosque nocturno — S01E01 Sc01" |
| ambient_asset_id | BIGINT UNSIGNED FK (audio_assets) NULL | |
| music_asset_id | BIGINT UNSIGNED FK (audio_assets) NULL | |
| dialogue_volume | TINYINT UNSIGNED | 0–100, default: 100 |
| ambient_volume | TINYINT UNSIGNED | 0–100, default: 35 |
| music_volume | TINYINT UNSIGNED | 0–100, default: 55 |
| sfx_config | JSON NULL | [{asset_id, volume, offset_seconds}] |
| export_path | VARCHAR(500) NULL | Audio mix exportado |
| notes | TEXT NULL | |
| created_at / updated_at | TIMESTAMP | |

---

## 8. Tablas de Publicación y Analytics

### `social_posts` ← NUEVO
Publicaciones planificadas y ejecutadas en redes sociales.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| project_id | BIGINT UNSIGNED FK (projects) | |
| calendar_event_id | BIGINT UNSIGNED FK (content_calendar_events) NULL | |
| platform | ENUM('instagram','youtube','facebook','tiktok','linkedin') | |
| post_type | ENUM('post','carousel','story','reel','video','short') | |
| caption | TEXT NULL | |
| hashtags | TEXT NULL | |
| media_paths | JSON NULL | Array de S3 paths de los archivos |
| thumbnail_path | VARCHAR(500) NULL | Para YouTube |
| scheduled_for | DATETIME | |
| status | ENUM('draft','scheduled','publishing','published','failed') | |
| platform_post_id | VARCHAR(255) NULL | ID del post en la plataforma |
| published_at | TIMESTAMP NULL | |
| error_message | TEXT NULL | |
| created_at / updated_at | TIMESTAMP | |

### `analytics_snapshots` ← NUEVO
Métricas de YouTube e Instagram sincronizadas diariamente.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| social_post_id | BIGINT UNSIGNED FK (social_posts) NULL | |
| project_id | BIGINT UNSIGNED FK (projects) | |
| platform | ENUM('instagram','youtube','facebook') | |
| platform_post_id | VARCHAR(255) | |
| snapshot_date | DATE | Fecha de la medición |
| views | INT UNSIGNED NULL | YouTube: views / Instagram: impressions |
| reach | INT UNSIGNED NULL | Instagram: cuentas únicas alcanzadas |
| likes | INT UNSIGNED NULL | |
| comments | INT UNSIGNED NULL | |
| shares | INT UNSIGNED NULL | |
| saves | INT UNSIGNED NULL | Instagram saves |
| click_through_rate | DECIMAL(5,4) NULL | YouTube CTR del thumbnail |
| avg_watch_time_seconds | INT UNSIGNED NULL | YouTube |
| engagement_rate | DECIMAL(5,4) NULL | |
| subscribers_gained | INT NULL | YouTube: subs ganados por este video |
| created_at | TIMESTAMP | |

---

## 9. Tablas de Negocio

### `client_quotes` ← NUEVO
Cotizaciones generadas para proyectos de clientes externos.

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| project_id | BIGINT UNSIGNED FK (projects) NULL | |
| client_id | BIGINT UNSIGNED FK (clients) NULL | |
| quote_number | VARCHAR(50) UNIQUE | Ej: "XS-2026-0042" |
| items | JSON | [{description, hours, unit_cost, subtotal}] |
| render_cost_estimate | DECIMAL(10,2) NULL | Costo estimado de renders |
| production_fee | DECIMAL(10,2) NULL | Fee de producción |
| subtotal | DECIMAL(10,2) | |
| tax_rate | DECIMAL(4,2) NULL | |
| tax_amount | DECIMAL(10,2) NULL | |
| total | DECIMAL(10,2) | |
| currency | CHAR(3) | MXN, USD |
| status | ENUM('draft','sent','accepted','rejected','invoiced') | |
| valid_until | DATE NULL | |
| notes | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK (users) | |
| created_at / updated_at | TIMESTAMP | |

### `continuity_notes`, `budgets`, `exports`
*(Sin cambios desde v2)*

---

## 10. Migrations Order — v3

```
001–032  Igual que v2...

# NUEVAS en v3:
033_add_dna_config_to_characters
034_add_quality_tier_to_renders
035_add_dialogue_fields_to_shots
036_add_notion_fields_to_projects
037_add_ghost_director_to_users
038_create_voice_profiles_table
039_create_audio_assets_table
040_create_talking_renders_table
041_create_sound_designs_table
042_create_render_annotations_table
043_create_social_posts_table
044_create_analytics_snapshots_table
045_create_client_quotes_table
```

---

## 11. Reglas de Negocio — v3

1. **Seed obligatorio** en todo render completado.
2. **Quality tier no cambia** en un render existente — para subir de Draft a Final se crea un nuevo Render del mismo Shot.
3. **Talking render requiere** source_render_id con status='completed' y audio_asset_id con status='completed' — ambos deben existir antes de lanzar el job.
4. **Render annotations** de clientes son de solo lectura para otros clientes — solo el owner puede ver todas.
5. **Analytics snapshots** son inmutables — si el dato cambia al día siguiente, se crea un nuevo snapshot, no se edita el anterior.
6. **client_quotes** generan número de cotización secuencial al guardar (no al crear draft).
7. **sound_designs** tiene restricción: `dialogue_volume + ambient_volume + music_volume` no tiene suma fija — son niveles independientes, no porcentajes de un total.
8. **social_posts** en status 'scheduled' no pueden editarse si faltan menos de 15 minutos para scheduled_for.

---

*Documento vivo — actualizar al agregar entidades.*  
*Versión 3.0 — Junio 2026*
