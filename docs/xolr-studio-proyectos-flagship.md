# Xolr Studio — Proyectos Flagship
**Versión:** 2.0  
**Autor:** Alejandro Solórzano  
**Fecha:** Junio 2026  
**Estado:** Documento Maestro Activo

---

## Introducción

Xolr Studio nace a partir de tres proyectos reales en producción activa. Estos proyectos no son ejemplos hipotéticos — son las producciones que definen los requerimientos de la plataforma, que validan cada módulo y que serán los primeros beneficiarios del sistema completo.

Cada proyecto usa Xolr Studio de manera distinta, y juntos cubren el espectro completo de las capacidades de la plataforma: narrativa de ficción serializada, marketing inmobiliario con personajes virtuales, y contenido de viajes para YouTube con presencia de host animado.

---

## Proyecto 1: Strange Light (Ficción — Serie)

### Concepto
Serie de ciencia ficción que sigue a Elena Voss, científica cognitiva, mientras investiga fenómenos anómalos en el Instituto de Investigación Cognitiva de Ashveil. Cada episodio es visualmente cinematográfico, producido íntegramente con IA bajo la dirección creativa del estudio.

**Tipo de proyecto:** Series / Ficción  
**Formato:** Serie de temporadas con episodios de 8–12 shots por episodio  
**Audiencia objetivo:** Fanáticos de ciencia ficción, comunidad de AI art, plataformas de YouTube y Webtoon

### Universo y Lore

**Ashveil Research Institute (ARI):** Instalación subterránea de cinco niveles donde se estudian fenómenos cognitivos no explicados. Fundado en 1987. Fachada pública: neurociencia aplicada. Actividad real: investigación de anomalías de consciencia.

**El Umbral:** Fenómeno central de la serie. Barrera entre la consciencia normal y un estado de percepción expandida descubierto accidentalmente en el Nivel 5. Los sujetos expuestos al Umbral ganan acceso a información que no debería ser accesible — pero a un costo.

**La Directiva:** Organización que monitorea y suprime el conocimiento del Umbral. Infiltrada en el ARI. Elena no sabe que trabaja rodeada de sus agentes.

### Personajes Principales

#### Elena Voss — Protagonista
- **Rol:** Científica cognitiva, Nivel 3 de acceso al ARI, investigadora independiente
- **Descripción:** Mujer 30–35 años, cabello castaño oscuro, mirada analítica, siempre bajo control excepto cuando el Umbral interfiere
- **LoRA:** `lora_elena_voss_v3.safetensors` — entrenado en 150+ renders aprobados
- **Arco:** De creyente en la ciencia pura a alguien que no puede ignorar lo que ha visto

**Voice Profile — Elena:**
- Voz principal: Inglés (narración y diálogo)
- Carácter vocal: profunda, controlada, con pausas calculadas
- Voz alternativa "Umbral": más lenta, levemente distorsionada (modificación de similarity_boost)
- Emotion range: neutral (80%), determinada (40%), aterrorizada (10%), desconectada/Umbral (5%)

**Talking Render Strategy:**
- Calidad: D-ID Production para todos los diálogos
- Expresiones preferidas para close-up de diálogo: directa a cámara o 3/4 perfil
- Voice Director default: contenida, ritmo moderado-lento, énfasis en palabras técnicas

#### Kael — Antagonista ambiguo
- **Rol:** Agente de La Directiva, asignado al ARI como "consultor de seguridad"
- **Descripción:** Hombre 35–40 años, apariencia seria, sombría, presencia imponente
- **LoRA:** `lora_kael_v2.safetensors`

**Voice Profile — Kael:**
- Voz: grave, directa, sin inflexiones emocionales visibles
- Emotion range: neutral (90%), amenazante-sutil (10%)
- Voice Director default: monotonía controlada, ritmo pausado

#### Marcus — Aliado
- **Rol:** Técnico del Nivel 5, el único que sabe más de lo que dice sobre el Umbral
- **Descripción:** Hombre 45–50 años, desgastado, nervioso, genuinamente asustado
- **LoRA:** `lora_marcus_v1.safetensors`

**Voice Profile — Marcus:**
- Voz: algo ronca, rápida cuando está nervioso, baja cuando revela información
- Emotion range: nervioso (50%), cómplice (30%), aterrorizado (20%)

### Locaciones

| Locación | Descripción | LoRA de Ambiente |
|---|---|---|
| **Laboratorio Nivel 3** | Iluminación fría de neón azul, equipos de monitoreo cognitivo, ventanas con vista a pasillos vacíos | `env_lab_n3_v2` |
| **Pasillo del Nivel 5** | Luz de emergencia roja, paredes de concreto, silencio pesado | `env_corridor_n5_v1` |
| **Bosque de Ashveil** | Bosque templado, niebla baja, luz filtrada entre árboles en momentos de transición | `env_ashveil_forest_v1` |
| **Oficina de la Directiva** | Arquitectura corporativa fría, ningún detalle personal, ventanales a una ciudad no identificada | `env_directive_office_v1` |
| **Apartamento de Elena** | Caos ordenado de investigadora: papeles, pizarrones, luz cálida de noche | `env_elena_apartment_v1` |

### Producción por Temporada

**Temporada 1 — "El Umbral"**

| Episodio | Título | Shots | Estado |
|---|---|---|---|
| S01E01 | "Primera Anomalía" | 10 | En producción |
| S01E02 | "Nivel 5" | 12 | Planeado |
| S01E03 | "La Directiva" | 10 | Planeado |
| S01E04 | "Kael" | 8 | Planeado |
| S01E05 | "El Costo" | 12 | Planeado |
| S01E06 | "Sin Retorno" | 14 | Planeado |

**Meta de producción T1:** 66 shots narrativos + 66 talking renders de diálogos clave

### Pipeline de Audio — Strange Light

**Música original por episodio:**
- Tema de apertura: cuerdas minimalistas + electrónica fría, 45 segundos
- Leitmotif de Elena: piano solo, melancólico, 20–30 segundos (se usa en momentos de introspección)
- Leitmotif de La Directiva: cuerdas oscuras y bajos, amenazante, 20 segundos
- Score de escena de acción: percusión electrónica + sintetizadores, generado por escena
- Todos generados con Suno API (licencia comercial)

**Sound design por escena:**
- Laboratorio: zumbido eléctrico bajo (40%), aire acondicionado institucional (20%), pasos en cerámica
- Pasillo N5: silencio casi total (solo HVAC a 10%), reverb largo en pasos
- Bosque: viento entre árboles (35%), pájaros distantes (15%), hojas al caminar
- Todos los ambientes generados con ElevenLabs Sound Effects

**Subtítulos:** Todos los episodios con .srt generado por Whisper para distribución

### Analytics Target — Strange Light
| Métrica | Meta a 6 meses |
|---|---|
| Visualizaciones por episodio | 5,000+ |
| Suscriptores de YouTube | 1,500+ |
| Comentarios promedio por episodio | 50+ |
| Episodios publicados | 6 (T1 completa) |

### Milestones de Producción
- **SL-1:** T1E01 completo y publicado (10 shots + renders aprobados)
- **SL-2:** Talking renders de diálogos clave de T1E01 completados
- **SL-3:** Sound design de T1E01 completo (diálogo + ambient + música)
- **SL-4:** Animatic de T1E01 aprobado
- **SL-5:** T1E02 en producción simultánea mientras T1E01 se edita
- **SL-6:** Pipeline completo funcionando para producción continua
- **SL-7:** T1 completa publicada (6 episodios)
- **SL-8:** T1 en Webtoon/plataforma adicional

---

## Proyecto 2: Home del Valle (Marketing Inmobiliario)

### Concepto
Campaña permanente de marketing de contenido para Home del Valle, la empresa de bienes raíces de Alejandro. El diferenciador estratégico: dos brokers virtuales de IA — Sofía Navarro y Diego Montoya — que presentan propiedades, responden preguntas, explican procesos inmobiliarios y forman parte del equipo público de la empresa.

**Tipo de proyecto:** Campaign / Inmobiliario  
**Formato:** Posts de Instagram, reels, carruseles informativos, videos de presentación  
**Audiencia objetivo:** Compradores y arrendadores de bienes raíces en México, segmento aspiracional

### Virtual Talents

#### Sofía Navarro — Broker Virtual Principal
**Perfil completo de identidad:**
- **Nombre:** Sofía Navarro
- **Título:** Asesora de Bienes Raíces | Home del Valle
- **Edad aparente:** 30 años
- **Personalidad:** Cálida, directa, aspiracional, apasionada por el diseño arquitectónico
- **Especialidad:** Propiedades residenciales de lujo y mid-range en desarrollos nuevos
- **Frase de cierre:** *"Tu espacio ideal ya existe. Solo hay que encontrarlo."*

**Visual Identity:**
- Look: profesional contemporánea, colores neutros y tierra, accesible y sofisticada
- Locaciones de preferencia: interiores de propiedades, lobbies de desarrollos, rooftops urbanos
- LoRA: `lora_sofia_hdv_v4.safetensors` — entrenado con 200+ renders aprobados
- Seed favorito documentado en Character DNA para consistencia

**Voice Profile — Sofía:**
- Voz: cálida, conversacional, femenina sin ser forzada
- Idioma: Español mexicano
- Voice Director default: ritmo conversacional-natural, tono esperanzador
- Casos de uso: videos de presentación de propiedades, stories con tips, reels de recomendación
- Calidad de lip sync: HeyGen Premium (videos de presentación), D-ID Production (reels cortos)

**Content que genera Sofía:**
- "Presentación de propiedad" — video de 60 seg donde Sofía describe el inmueble
- "Tip de la semana" — 30 seg con consejo de compra o arrendamiento
- "Respuesta a pregunta frecuente" — 45 seg respondiendo dudas del proceso
- "Bienvenida al desarrollo" — video de apertura para desarrollos nuevos

---

#### Diego Montoya — Broker Virtual Especialista
**Perfil completo de identidad:**
- **Nombre:** Diego Montoya
- **Título:** Especialista en Inversión Inmobiliaria | Home del Valle
- **Edad aparente:** 38 años
- **Personalidad:** Analítico, directo, sofisticado, habla con datos y cifras
- **Especialidad:** Inversión inmobiliaria, rendimientos, plusvalía, comparativas de mercado
- **Frase de cierre:** *"No compres una propiedad. Compra un patrimonio."*

**Visual Identity:**
- Look: traje oscuro, presencia ejecutiva, fondos de arquitectura moderna o cityscape
- LoRA: `lora_diego_hdv_v3.safetensors`

**Voice Profile — Diego:**
- Voz: grave, confiada, pausada, transmite autoridad financiera
- Idioma: Español mexicano (con precisión técnica)
- Voice Director default: ritmo moderado, énfasis en cifras y porcentajes
- Calidad de lip sync: HeyGen Premium para videos de inversión, D-ID para contenido regular

**Content que genera Diego:**
- "Análisis de mercado" — video mensual de 90 seg con cifras de tendencia
- "Por qué invertir aquí" — argumentario de inversión para un desarrollo específico
- "Comparativa de opciones" — ayuda a decidir entre propiedades
- "Preguntas del inversor" — responde las 5 preguntas que todo comprador tiene

---

### Asset Pack por Propiedad/Campaña

Al activar una campaña de propiedad en Xolr Studio, se genera automáticamente este checklist de assets:

**Formato de Campaign:**
```
Campaña: [Nombre del Desarrollo]
Canal: Home del Valle
Virtual Talent: Sofía Navarro (o Diego Montoya)

ASSETS A GENERAR:
□ Render de fachada exterior (Standard)
□ 4 renders de interiores (Standard)
□ Foto de perfil del broker en la propiedad (Standard)
□ Video de presentación 60 seg — broker talking render (HeyGen Premium)
□ 6 imágenes para carrusel (Standard)
□ Story vertical 9:16 con broker (Standard + talking render)
□ Thumbnail de reel (Final)
□ Caption con hashtags (AI Generator)
□ Subtítulos .srt para el video (Whisper)
□ Post programado para publicación
```

### Tipos de Campaña

| Tipo | Broker | Duración | Assets principales |
|---|---|---|---|
| **Lanzamiento de desarrollo** | Sofía | 4 semanas | Video 60 seg, carrusel, 4 stories, 1 reel |
| **Propiedad disponible** | Sofía/Diego | 2 semanas | Video 30 seg, 4 posts, story de precio |
| **Tips informativos** | Sofía | Permanente | 1 reel/semana con tip de 30 seg |
| **Análisis de mercado** | Diego | Mensual | Video 90 seg, infographic carrusel, post de datos |
| **Cierre exitoso** | Cualquiera | Puntual | Post de celebración, story de testimonio |

### Pipeline de Audio — Home del Valle

**Voice Videos:**
- Todos los videos de broker con audio Voice Director → Voice Generation → Lip Sync
- Sofía: tono cálido, ritmo conversacional, idioma español
- Diego: tono analítico, ritmo pausado, datos y cifras con énfasis

**Música de fondo:**
- Videos de propiedades residenciales: música aspiracional suave, piano y cuerdas, 60 BPM
- Videos de inversión: música corporativa moderna, minimalista
- Generada con Suno API, 60 segundos de loop seamless

**Ambiente:**
- Interiores de lujo: silencio con leve HVAC, reverb de espacio abierto
- Exterior urbano: ambiente de ciudad baja intensidad, tráfico distante

### Analytics Target — Home del Valle
| Métrica | Meta a 6 meses |
|---|---|
| Seguidores de Instagram | +2,000 |
| Engagement rate | >4% |
| Alcance promedio por post | 5,000+ |
| Consultas generadas | 20/mes |
| Videos de broker publicados | 24 (1/semana) |

### Milestones de Producción
- **HDV-1:** Perfiles de Sofía y Diego aprobados (renders + voice profiles)
- **HDV-2:** Primera campaña de propiedad completa (todos los assets del checklist)
- **HDV-3:** Primer video de broker con lip sync publicado en Instagram
- **HDV-4:** Pipeline de campaña documentado y replicable en < 2 horas
- **HDV-5:** Analytics Inverso activo — métricas de Instagram en el dashboard

---

## Proyecto 3: The Walking Video Guy (YouTube — Viajes)

### Concepto
Canal de YouTube de walking tours — videos POV (point of view) caminando por ciudades, mercados, playas y destinos en México y el mundo. La propuesta de valor: inmersión local auténtica con producción elevada. Xolr Studio potencia el canal con thumbnails consistentes, concepto de video estructurado con IA, presencia de host animado, y análisis de rendimiento de contenido.

**Tipo de proyecto:** Content Machine / YouTube  
**Formato:** Videos de walking tour 15–30 min, thumbnails, shorts  
**Audiencia objetivo:** Amantes de viajes, expats, comunidad de caminatas urbanas

### El Host — Walking Guy

**Concepto del presenter:**
Un "alter ego visual" del canal — el conductor animado que aparece en los thumbnails, en intros de 3–5 segundos y en pantallas finales. No reemplaza al creador — lo amplifica con producción consistente.

- **Aspecto:** Casual-explorador, mochila, lentes de sol ocasionales, look de viajero auténtico
- **LoRA:** `lora_walking_guy_v2.safetensors`
- Expresión base para thumbnails: sorpresa auténtica, asombro, señalando algo fuera de frame

**Estrategia de uso:**
- Thumbnails: el host reacciona al destino (expresión de asombro + location de fondo)
- Intro animada: 3 segundos del host caminando (talking render con frase de bienvenida)
- Pantalla final de YouTube: host señalando los videos sugeridos
- Shorts thumbnail: host en vertical 9:16

### Thumbnails

**Template estándar de thumbnail:**
```
[FOTO/RENDER DEL DESTINO — background]
[HOST en primer plano — expresión de asombro/sorpresa]
[TÍTULO grande: "CIUDAD INCREÍBLE - Walking Tour 4K"]
[Badge de duración: "20 MINUTES"]
```

**Proceso en Xolr Studio:**
1. Subir foto referencia del destino (o usar un render de locación)
2. Generar render del host con expresión correcta en ese ambiente
3. Seleccionar template, definir título y badge
4. Generar 3 variaciones de thumbnail para A/B testing
5. El Analytics Inverso identifica qué variación tiene mejor CTR
6. El Ghost Director incorpora los patrones aprendidos en futuras sugerencias

**Expresiones catalogadas por CTR:**
- Asombro genuino (boca abierta, ojos amplios): CTR base
- Señalando hacia arriba/adelante: +12% vs. base (según datos reales del canal)
- Sorpresa + mano en mejilla: +8%
- Expresión de "no lo vas a creer": +15% (se usa para destinos inesperados)

### Estructura de Contenido por Video

El AI Script Generator produce para cada video de TWVG:

**Concepto de Video (generado):**
- Título principal + variaciones para SEO
- Descripción de YouTube (primera sección antes del "más") con keywords
- Estructura de secciones del walk: intro, sección 1–5, outro
- Timestamp markers con nombre de cada sección
- Tags para YouTube SEO
- Hashtags para shorts

**Tips del Destino (generado):**
- 5 datos culturales o históricos interesantes del lugar visitado
- Horarios de visita recomendados
- Qué comer en la zona (3 opciones)
- Cómo llegar (transporte principal)
- Qué no hacer (locales vs. turistas)

Estos tips aparecen como cards en pantalla durante el video o como descripción expandida en YouTube.

### Pipeline de Audio — The Walking Video Guy

**Voz en off del host:**
- Narración de 30–60 segundos para la intro de cada video
- Voice profile del Walking Guy: voz masculina, entusiasta, conversacional
- Voice Director: ritmo animado, énfasis en nombres del destino y datos curiosos
- Idioma: Español (con posible expansión a inglés para el canal internacional)
- Generado con ElevenLabs

**Talking renders del host:**
- Intro de 3–5 segundos: "Hoy caminamos por [destino]" — Wav2Lip Draft (costo-eficiente)
- Pantalla final de YouTube: host señalando los videos sugeridos con lip sync

**Música de fondo:**
- Cada destino tiene un mood musical distinto:
  - Ciudad de México: música urbana / indie electrónico, 120 BPM
  - Mercados tradicionales: marimba ambient, 85 BPM
  - Playas: guitarra relajada, 70 BPM
- Generada con Suno API, loopeable, 30 minutos de duración para videos largos

**Ambient Sound (para recursos de producción):**
- La plataforma genera el ambiente "de referencia" del destino antes de filmar
- "Mercado de Oaxaca al mediodía — mucho ruido, voces, música de radio"
- Este audio ayuda a definir el tone del video antes de la edición

### SEO y Análisis de Canal

**Keywords tracking en plataforma:**
- Por video: keyword principal + 4 secundarias
- CTR objetivo por tipo de destino (basado en historial del canal)

**Analytics Inverso — TWVG:**
- Views por video en el tiempo (curva de crecimiento vs. decaimiento)
- Click-Through Rate por thumbnail variant
- Average Watch Time (qué secciones retienen mejor)
- Suscriptores ganados por video
- Trending searches de destinos de travel (para planificar contenido futuro)

**Intelligence Engine insights específicos:**
- "Los videos de playas tienen 2.1x más retención promedio que ciudades"
- "Videos de 18–22 minutos outperforman los de 25+ min en Watch Time"
- "Los thumbnails publicados viernes 4 PM tienen el mayor CTR inicial"

### Analytics Target — The Walking Video Guy
| Métrica | Meta a 6 meses |
|---|---|
| Suscriptores | +5,000 |
| Views totales | 150,000 |
| Average Watch Time | >40% |
| Videos publicados | 24 |
| CTR promedio | >5.5% |
| Shorts publicados | 20 |

### Milestones de Producción
- **WVG-1:** Voice profile del Walking Guy aprobado
- **WVG-2:** Template de thumbnail establecido y probado con 3 videos
- **WVG-3:** Primeros talking renders de intro funcionando (Wav2Lip Draft)
- **WVG-4:** Analytics Inverso activo — YouTube Analytics en dashboard
- **WVG-5:** A/B testing de thumbnails funcionando — datos de CTR retroalimentando Ghost Director

---

## Resumen Cross-Project

| Proyecto | Tipo | Cadencia | Audio | Lip Sync | Auto-Post |
|---|---|---|---|---|---|
| Strange Light | Ficción / Serie | 1 episodio/mes | Voz + score + ambient | D-ID Production | YouTube |
| Home del Valle | Marketing | 4–6 posts/semana | Voz + música | HeyGen Premium | Instagram |
| The Walking Video Guy | Content / YouTube | 4 videos/mes | Narración + música | RunPod Draft | YouTube |

Los tres proyectos alimentan el aprendizaje del Ghost Director. Los patrones visuales de Strange Light (cinematográfico, oscuro) se mantienen separados de los de HDV (luminoso, aspiracional) y TWVG (auténtico, dinámico) — el Ghost Director mantiene perfiles creativos por proyecto.

---

*Documento vivo — versión 2.0 — Junio 2026*
