<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, onBeforeUnmount, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Pencil, FileText, Camera, Plus, Trash2, Image, Video, Mic, Sparkles, BookOpen, CheckCheck, Wand2, X, AlertTriangle, Share2, Link2, Lock, Eye } from '@lucide/vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
const props = defineProps({
    episode:     Object,
    locations:   Array,
    shareTokens: Array,
})

// ── Share modal ──────────────────────────────────────────────────
const showShare = ref(false)
const copied    = ref(false)

const shareForm = useForm({
    label:      '',
    password:   '',
    expires_in: 'never',
})

function createShare() {
    shareForm.post(`/episodes/${props.episode.id}/share`, {
        onSuccess: () => shareForm.reset(),
        preserveScroll: true,
    })
}

function deleteShare(id) {
    router.delete(`/share/${id}`, { preserveScroll: true })
}

function copyLink(url) {
    navigator.clipboard.writeText(url)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}

const activeTab = ref('info')

// ── Flash / AI results ─────────────────────────────────────────
const page           = usePage()
const flash          = computed(() => page.props.flash ?? {})
const aiGenerating   = ref(null) // 'script' | 'chapter' | 'continuity'
const showChapter    = ref(false)
const showContinuity = ref(false)
const aiError        = ref('')
const chapterResult  = ref('')
const continuityResult = ref(null)

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

async function generateScript() {
    aiGenerating.value = 'script'
    aiError.value      = ''

    // Guarda el borrador actual antes de generar
    if (saveStatus.value === 'unsaved') {
        await new Promise((resolve) => {
            scriptForm.script = editor.value?.getHTML() ?? ''
            scriptForm.put(`/episodes/${props.episode.id}/script`, {
                preserveState: true, preserveScroll: true,
                onFinish: resolve,
            })
        })
    }

    try {
        const res  = await fetch(`/episodes/${props.episode.id}/generate-script`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
        const data = await res.json()

        if (data.ok && data.script) {
            editor.value?.commands.setContent(data.script)
            saveStatus.value = 'saved'
        } else {
            aiError.value = data.message ?? 'Error al generar el script'
        }
    } catch (e) {
        aiError.value = 'Error de conexión al generar el script'
    } finally {
        aiGenerating.value = null
    }
}

async function generateBookChapter() {
    aiGenerating.value = 'chapter'
    aiError.value      = ''

    try {
        const res  = await fetch(`/episodes/${props.episode.id}/generate-book-chapter`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
        const data = await res.json()

        if (data.ok && data.chapter) {
            chapterResult.value = data.chapter
            showChapter.value   = true
        } else {
            aiError.value = data.message ?? 'Error al generar el capítulo'
        }
    } catch (e) {
        aiError.value = 'Error de conexión al generar el capítulo'
    } finally {
        aiGenerating.value = null
    }
}

async function checkContinuity() {
    aiGenerating.value = 'continuity'
    aiError.value      = ''

    try {
        const res  = await fetch(`/episodes/${props.episode.id}/continuity-check`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
        const data = await res.json()

        if (data.ok && data.result) {
            continuityResult.value = data.result
            showContinuity.value   = true
        } else {
            aiError.value = data.message ?? 'Error al verificar continuidad'
        }
    } catch (e) {
        aiError.value = 'Error de conexión al verificar continuidad'
    } finally {
        aiGenerating.value = null
    }
}

// ── Script editor ──────────────────────────────────────────────
const saveStatus = ref('saved') // 'saved' | 'saving' | 'unsaved' | 'error'
const saveError  = ref('')
let autoSaveTimer    = null
let periodicTimer    = null

const scriptForm = useForm({ script: '' })

const editor = useEditor({
    content: props.episode.script ?? '',
    extensions: [StarterKit],
    onUpdate: () => {
        saveStatus.value = 'unsaved'
        clearTimeout(autoSaveTimer)
        autoSaveTimer = setTimeout(saveScript, 5000)
    },
})

function saveScript() {
    if (saveStatus.value === 'saving') return
    saveStatus.value = 'saving'
    saveError.value  = ''
    scriptForm.script = editor.value?.getHTML() ?? ''
    scriptForm.put(`/episodes/${props.episode.id}/script`, {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => { saveStatus.value = 'saved' },
        onError:   (e) => { saveStatus.value = 'error'; saveError.value = Object.values(e)[0] ?? 'Error al guardar' },
        onFinish:  () => { if (saveStatus.value === 'saving') saveStatus.value = 'unsaved' },
    })
}

// Guardar cada 60 segundos aunque se siga escribiendo
periodicTimer = setInterval(() => {
    if (saveStatus.value === 'unsaved') saveScript()
}, 60000)

onBeforeUnmount(() => {
    clearTimeout(autoSaveTimer)
    clearInterval(periodicTimer)
    editor.value?.destroy()
})

// ── Scenes ─────────────────────────────────────────────────────
const showSceneForm = ref(false)
const editingScene  = ref(null)

const sceneForm = useForm({
    title: '', description: '', location_id: null,
    time_of_day: 'unspecified', mood: 'calm',
})

const editSceneForm = useForm({
    title: '', description: '', location_id: null,
    time_of_day: 'unspecified', mood: 'calm',
})

function storeScene() {
    sceneForm.post(`/episodes/${props.episode.id}/scenes`, {
        onSuccess: () => { showSceneForm.value = false; sceneForm.reset() },
    })
}

function openEditScene(scene) {
    editingScene.value          = scene.id
    editSceneForm.title         = scene.title ?? ''
    editSceneForm.description   = scene.description ?? ''
    editSceneForm.location_id   = scene.location_id ?? null
    editSceneForm.time_of_day   = scene.time_of_day
    editSceneForm.mood          = scene.mood
}

function updateScene() {
    editSceneForm.put(`/scenes/${editingScene.value}`, {
        preserveScroll: true,
        onSuccess: () => { editingScene.value = null; editSceneForm.reset() },
    })
}

function deleteScene(id) {
    if (!confirm('¿Eliminar escena?')) return
    router.delete(`/scenes/${id}`, { preserveScroll: true })
}

// ── Shots ──────────────────────────────────────────────────────
const activeShotForm = ref(null) // scene id con form de creación abierto
const editingShot    = ref(null) // shot id siendo editado

const shotForm = useForm({
    description: '', shot_type: 'image', purpose: 'narrative',
    dialogue_text: '', director_notes: '', duration_seconds: '',
})

const editShotForm = useForm({
    description: '', shot_type: 'image', purpose: 'narrative',
    dialogue_text: '', director_notes: '', duration_seconds: '', status: 'draft',
})

function storeShot(sceneId) {
    shotForm.post(`/scenes/${sceneId}/shots`, {
        onSuccess: () => { activeShotForm.value = null; shotForm.reset() },
    })
}

function openEditShot(shot) {
    editingShot.value = shot.id
    editShotForm.description     = shot.description ?? ''
    editShotForm.shot_type       = shot.shot_type
    editShotForm.purpose         = shot.purpose
    editShotForm.dialogue_text   = shot.dialogue_text ?? ''
    editShotForm.director_notes  = shot.director_notes ?? ''
    editShotForm.duration_seconds = shot.duration_seconds ?? ''
    editShotForm.status          = shot.status
}

function updateShot() {
    editShotForm.put(`/shots/${editingShot.value}`, {
        preserveScroll: true,
        onSuccess: () => { editingShot.value = null; editShotForm.reset() },
    })
}

function deleteShot(id) {
    if (!confirm('¿Eliminar shot?')) return
    router.delete(`/shots/${id}`, { preserveScroll: true })
}

// ── Labels & colors ────────────────────────────────────────────
const moodColor = {
    tense:'text-danger', action:'text-warning', dramatic:'text-violet',
    calm:'text-success', mysterious:'text-violet', romantic:'text-pink-400',
    comedic:'text-amber', horror:'text-danger', other:'text-text-muted',
}
const timeLabel = {
    morning:'Mañana', day:'Día', golden_hour:'Hora dorada', night:'Noche', unspecified:'Sin especificar',
}
const shotTypeIcon = { image: Image, video: Video, talking: Mic }

function shotRenderUrl(shot) {
    const render = shot.approved_render ?? shot.renders?.[0]
    if (!render?.file_path) return null
    const p = render.file_path
    return p.startsWith('http') ? p : `/storage/${p}`
}
const shotStatusColor = {
    draft:'text-text-muted', prompt_ready:'text-info', rendering:'text-violet',
    audio_pending:'text-warning', lip_sync_pending:'text-warning',
    completed:'text-success', approved:'text-success',
}

const epStatusLabel = {
    concept:'Concepto', outline:'Outline', scripted:'Scriptado',
    production:'Producción', completed:'Completado', published:'Publicado',
}
</script>

<template>
    <Head :title="episode.title" />
    <AppLayout>
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-text-muted mb-4">
            <Link href="/projects" class="hover:text-text-primary transition-colors">Proyectos</Link>
            <span>/</span>
            <Link :href="`/projects/${episode.season.project.id}`" class="hover:text-text-primary transition-colors">{{ episode.season.project.name }}</Link>
            <span>/</span>
            <Link :href="`/projects/${episode.season.project.id}/seasons/${episode.season.id}`" class="hover:text-text-primary transition-colors">T{{ episode.season.number }}</Link>
            <span>/</span>
            <span class="text-text-primary">EP{{ String(episode.number).padStart(2, '0') }}</span>
        </div>

        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">{{ episode.title }}</h1>
                <p v-if="episode.logline" class="text-sm text-text-muted mt-0.5 italic">{{ episode.logline }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="showShare = true"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary hover:border-amber transition-colors">
                    <Share2 class="w-4 h-4" />
                    <span class="hidden sm:inline">Compartir</span>
                    <span v-if="shareTokens?.length" class="text-[10px] font-mono text-amber">{{ shareTokens.length }}</span>
                </button>
                <Link :href="`/episodes/${episode.id}/edit`" class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors">
                    <Pencil class="w-4 h-4" /> Editar
                </Link>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-border mb-6">
            <button v-for="tab in [
                { id: 'info', label: 'Info', icon: FileText },
                { id: 'script', label: 'Script', icon: FileText },
                { id: 'storyboard', label: 'Storyboard', icon: Camera, count: episode.scenes?.length },
            ]" :key="tab.id" @click="activeTab = tab.id"
            :class="['flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                activeTab === tab.id ? 'border-amber text-text-primary' : 'border-transparent text-text-muted hover:text-text-secondary']">
                <component :is="tab.icon" class="w-4 h-4" />
                {{ tab.label }}
                <span v-if="tab.count" class="text-[10px] font-mono bg-surface-2 text-text-muted rounded-full px-1.5 py-0.5">{{ tab.count }}</span>
            </button>
        </div>

        <!-- INFO TAB -->
        <div v-if="activeTab === 'info'" class="max-w-2xl space-y-4">
            <div class="bg-surface-1 border border-border rounded-xl p-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-text-muted mb-1">Estado</p>
                    <p class="text-text-primary font-medium">{{ epStatusLabel[episode.status] }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-muted mb-1">Número</p>
                    <p class="text-text-primary font-mono">EP{{ String(episode.number).padStart(2, '0') }}</p>
                </div>
            </div>
            <div v-if="episode.synopsis" class="bg-surface-1 border border-border rounded-xl p-4">
                <p class="text-xs text-text-muted uppercase tracking-wider mb-2">Sinopsis</p>
                <p class="text-sm text-text-secondary leading-relaxed">{{ episode.synopsis }}</p>
            </div>
        </div>

        <!-- SCRIPT TAB -->
        <div v-if="activeTab === 'script'">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs text-text-muted">Autoguardado a los 5s de parar de escribir · cada 60s mientras escribes</p>
                <div class="flex items-center gap-3">
                    <span v-if="saveStatus === 'error'" class="text-xs font-mono text-danger" :title="saveError">Error al guardar</span>
                    <span v-else-if="saveStatus === 'saving'" class="text-xs font-mono text-amber animate-pulse">Guardando...</span>
                    <span v-else-if="saveStatus === 'saved'" class="text-xs font-mono text-success">✓ Guardado</span>
                    <span v-else class="text-xs font-mono text-text-muted">Sin guardar</span>
                    <button @click="saveScript" :disabled="scriptForm.processing" class="px-3 py-1.5 bg-amber text-surface-0 text-xs font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                        Guardar ahora
                    </button>
                </div>
            </div>

            <!-- Tiptap toolbar -->
            <div v-if="editor" class="flex gap-1 bg-surface-1 border border-border rounded-t-xl px-3 py-2">
                <button @click="editor.chain().focus().toggleBold().run()" :class="['px-2 py-1 text-xs rounded', editor.isActive('bold') ? 'bg-surface-2 text-text-primary' : 'text-text-muted hover:text-text-primary']" type="button">B</button>
                <button @click="editor.chain().focus().toggleItalic().run()" :class="['px-2 py-1 text-xs rounded italic', editor.isActive('italic') ? 'bg-surface-2 text-text-primary' : 'text-text-muted hover:text-text-primary']" type="button">I</button>
                <div class="w-px bg-border mx-1" />
                <button @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="['px-2 py-1 text-xs rounded', editor.isActive('heading', { level: 1 }) ? 'bg-surface-2 text-text-primary' : 'text-text-muted hover:text-text-primary']" type="button">H1</button>
                <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="['px-2 py-1 text-xs rounded', editor.isActive('heading', { level: 2 }) ? 'bg-surface-2 text-text-primary' : 'text-text-muted hover:text-text-primary']" type="button">H2</button>
                <div class="w-px bg-border mx-1" />
                <button @click="editor.chain().focus().toggleBulletList().run()" :class="['px-2 py-1 text-xs rounded', editor.isActive('bulletList') ? 'bg-surface-2 text-text-primary' : 'text-text-muted hover:text-text-primary']" type="button">• Lista</button>
                <button @click="editor.chain().focus().toggleBlockquote().run()" :class="['px-2 py-1 text-xs rounded', editor.isActive('blockquote') ? 'bg-surface-2 text-text-primary' : 'text-text-muted hover:text-text-primary']" type="button">" Cita</button>
                <div class="w-px bg-border mx-1" />
                <button @click="editor.chain().focus().undo().run()" class="px-2 py-1 text-xs text-text-muted hover:text-text-primary rounded" type="button">↩</button>
                <button @click="editor.chain().focus().redo().run()" class="px-2 py-1 text-xs text-text-muted hover:text-text-primary rounded" type="button">↪</button>
            </div>

            <EditorContent
                :editor="editor"
                class="bg-surface-1 border border-border border-t-0 rounded-b-xl p-6 min-h-96 prose prose-invert prose-sm max-w-none focus:outline-none [&_.ProseMirror]:outline-none [&_.ProseMirror]:min-h-80"
            />

            <!-- AI Script Tools -->
            <div class="mt-4 bg-surface-1 border border-violet/20 rounded-xl p-4">
                <p class="text-[10px] font-mono text-violet uppercase tracking-widest mb-3 flex items-center gap-1.5">
                    <Sparkles class="w-3 h-3" /> Herramientas IA
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        @click="generateScript"
                        :disabled="aiGenerating !== null"
                        class="flex items-center gap-1.5 px-3 py-2 bg-violet/10 text-violet text-xs font-medium rounded-lg hover:bg-violet/20 disabled:opacity-50 transition-colors"
                    >
                        <Wand2 v-if="aiGenerating !== 'script'" class="w-3.5 h-3.5" />
                        <Sparkles v-else class="w-3.5 h-3.5 animate-spin" />
                        {{ aiGenerating === 'script' ? 'Generando...' : 'Generar script con IA' }}
                    </button>
                    <button
                        @click="generateBookChapter"
                        :disabled="aiGenerating !== null || !episode.script"
                        class="flex items-center gap-1.5 px-3 py-2 bg-surface-2 text-text-secondary text-xs rounded-lg hover:text-text-primary hover:bg-surface-3 disabled:opacity-40 transition-colors"
                    >
                        <BookOpen v-if="aiGenerating !== 'chapter'" class="w-3.5 h-3.5" />
                        <Sparkles v-else class="w-3.5 h-3.5 animate-spin" />
                        {{ aiGenerating === 'chapter' ? 'Generando...' : 'Capítulo del libro' }}
                    </button>
                    <button
                        @click="checkContinuity"
                        :disabled="aiGenerating !== null || !episode.script"
                        class="flex items-center gap-1.5 px-3 py-2 bg-surface-2 text-text-secondary text-xs rounded-lg hover:text-text-primary hover:bg-surface-3 disabled:opacity-40 transition-colors"
                    >
                        <CheckCheck v-if="aiGenerating !== 'continuity'" class="w-3.5 h-3.5" />
                        <Sparkles v-else class="w-3.5 h-3.5 animate-spin" />
                        {{ aiGenerating === 'continuity' ? 'Analizando...' : 'Verificar continuidad' }}
                    </button>
                </div>

                <!-- AI error banner -->
                <div v-if="aiError" class="mt-3 flex items-start gap-2 px-3 py-2.5 bg-danger/10 border border-danger/20 rounded-lg text-xs text-danger">
                    <AlertTriangle class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                    <span>{{ aiError }}</span>
                    <button @click="aiError = ''" class="ml-auto shrink-0 opacity-60 hover:opacity-100">
                        <X class="w-3 h-3" />
                    </button>
                </div>
            </div>

            <!-- Book chapter result -->
            <div v-if="showChapter && chapterResult" class="mt-4 bg-surface-1 border border-border rounded-xl">
                <div class="flex items-center justify-between p-4 border-b border-border">
                    <div class="flex items-center gap-2">
                        <BookOpen class="w-4 h-4 text-amber" />
                        <p class="text-sm font-medium text-text-primary">Capítulo del libro generado</p>
                    </div>
                    <button @click="showChapter = false" class="text-text-muted hover:text-text-primary transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-4 max-h-80 overflow-y-auto">
                    <pre class="text-xs text-text-secondary whitespace-pre-wrap font-sans leading-relaxed">{{ chapterResult }}</pre>
                </div>
            </div>

            <!-- Continuity check result -->
            <div v-if="showContinuity && continuityResult" class="mt-4 bg-surface-1 border border-border rounded-xl">
                <div class="flex items-center justify-between p-4 border-b border-border">
                    <div class="flex items-center gap-2">
                        <CheckCheck class="w-4 h-4 text-violet" />
                        <p class="text-sm font-medium text-text-primary">Verificación de continuidad</p>
                    </div>
                    <button @click="showContinuity = false" class="text-text-muted hover:text-text-primary transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <div v-if="!continuityResult.has_issues" class="flex items-center gap-2 text-sm text-evergreen">
                        <CheckCheck class="w-4 h-4" />
                        Sin problemas de continuidad detectados.
                    </div>
                    <div v-else>
                        <div
                            v-for="issue in continuityResult.issues"
                            :key="issue.description"
                            class="flex gap-2 text-xs p-3 bg-danger/5 border border-danger/20 rounded-lg mb-2"
                        >
                            <AlertTriangle class="w-3.5 h-3.5 text-danger shrink-0 mt-0.5" />
                            <div>
                                <p class="text-text-primary font-medium capitalize">{{ issue.type }}</p>
                                <p class="text-text-muted mt-0.5">{{ issue.description }}</p>
                                <p class="text-violet mt-1">→ {{ issue.suggestion }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="continuityResult.suggestions?.length" class="space-y-1">
                        <p class="text-[10px] text-text-muted uppercase tracking-wider">Sugerencias</p>
                        <p v-for="s in continuityResult.suggestions" :key="s" class="text-xs text-text-secondary">• {{ s }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- STORYBOARD TAB -->
        <div v-if="activeTab === 'storyboard'">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs text-text-muted">{{ episode.scenes?.length ?? 0 }} escenas · {{ episode.scenes?.reduce((a, s) => a + (s.shots?.length ?? 0), 0) ?? 0 }} shots</p>
                <div class="flex gap-2">
                    <button
                        @click="router.post(`/episodes/${episode.id}/ai-director`)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet/10 text-violet text-xs font-medium rounded-lg hover:bg-violet/20 transition-colors"
                    >
                        <Sparkles class="w-3.5 h-3.5" /> AI Director
                    </button>
                    <button @click="showSceneForm = !showSceneForm" class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber text-surface-0 text-xs font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                        <Plus class="w-3.5 h-3.5" /> Añadir escena
                    </button>
                </div>
            </div>

            <!-- Scene form -->
            <div v-if="showSceneForm" class="bg-surface-1 border border-amber/30 rounded-xl p-4 mb-4 space-y-3">
                <h3 class="text-sm font-medium text-text-primary">Nueva escena</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Título</label>
                        <input v-model="sceneForm.title" type="text" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Momento del día</label>
                        <select v-model="sceneForm.time_of_day" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="morning">Mañana</option>
                            <option value="day">Día</option>
                            <option value="golden_hour">Hora dorada</option>
                            <option value="night">Noche</option>
                            <option value="unspecified">Sin especificar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Mood</label>
                        <select v-model="sceneForm.mood" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="calm">Calma</option>
                            <option value="tense">Tensión</option>
                            <option value="action">Acción</option>
                            <option value="dramatic">Dramático</option>
                            <option value="mysterious">Misterioso</option>
                            <option value="romantic">Romántico</option>
                            <option value="comedic">Cómico</option>
                            <option value="horror">Terror</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-text-muted mb-1">Locación</label>
                    <select v-model="sceneForm.location_id" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option :value="null">Sin locación</option>
                        <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button @click="storeScene" :disabled="sceneForm.processing" class="px-4 py-1.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">Guardar</button>
                    <button @click="showSceneForm = false; sceneForm.reset()" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                </div>
            </div>

            <!-- Scenes + Shots -->
            <div v-if="!episode.scenes?.length" class="bg-surface-1 border border-border rounded-xl p-10 text-center">
                <Camera class="w-8 h-8 text-text-muted mx-auto mb-2" />
                <p class="text-sm text-text-muted">Sin escenas aún</p>
            </div>

            <div v-else class="space-y-4">
                <div v-for="scene in episode.scenes" :key="scene.id" class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                    <!-- Scene header -->
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-border bg-surface-2">
                        <span class="text-xs font-mono text-amber font-bold">ESC {{ scene.number }}</span>
                        <span v-if="scene.title" class="text-sm font-medium text-text-primary">{{ scene.title }}</span>
                        <span v-if="scene.location" class="text-xs text-text-muted">· {{ scene.location.name }}</span>
                        <span class="text-xs text-text-muted">· {{ timeLabel[scene.time_of_day] }}</span>
                        <span :class="['text-xs font-mono ml-auto', moodColor[scene.mood]]">{{ scene.mood }}</span>
                        <button @click="activeShotForm = activeShotForm === scene.id ? null : scene.id" class="text-xs text-amber hover:underline ml-2">
                            + shot
                        </button>
                        <button @click="openEditScene(scene)" class="text-text-muted hover:text-amber transition-colors">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                        <button @click="deleteScene(scene.id)" class="text-text-muted hover:text-danger transition-colors">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Scene edit form -->
                    <div v-if="editingScene === scene.id" class="px-4 py-3 border-b border-border bg-surface-0 space-y-2">
                        <p class="text-xs font-medium text-amber">Editando escena</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Título</label>
                                <input v-model="editSceneForm.title" type="text" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors" />
                            </div>
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Locación</label>
                                <select v-model="editSceneForm.location_id" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                    <option :value="null">Sin locación</option>
                                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Momento del día</label>
                                <select v-model="editSceneForm.time_of_day" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                    <option value="morning">Mañana</option>
                                    <option value="day">Día</option>
                                    <option value="golden_hour">Hora dorada</option>
                                    <option value="night">Noche</option>
                                    <option value="unspecified">Sin especificar</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Mood</label>
                                <select v-model="editSceneForm.mood" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                    <option value="calm">Calma</option>
                                    <option value="tense">Tensión</option>
                                    <option value="action">Acción</option>
                                    <option value="dramatic">Dramático</option>
                                    <option value="mysterious">Misterioso</option>
                                    <option value="romantic">Romántico</option>
                                    <option value="comedic">Cómico</option>
                                    <option value="horror">Terror</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button @click="updateScene" :disabled="editSceneForm.processing" class="px-3 py-1 bg-amber text-surface-0 text-xs font-semibold rounded hover:bg-amber/90 disabled:opacity-50 transition-colors">Guardar</button>
                            <button @click="editingScene = null; editSceneForm.reset()" class="text-xs text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                        </div>
                    </div>

                    <!-- Shot form -->
                    <div v-if="activeShotForm === scene.id" class="px-4 py-3 border-b border-border bg-surface-0 space-y-2">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Tipo</label>
                                <select v-model="shotForm.shot_type" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                    <option value="image">Imagen</option>
                                    <option value="video">Video</option>
                                    <option value="talking">Talking</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Propósito</label>
                                <select v-model="shotForm.purpose" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                    <option value="narrative">Narrativo</option>
                                    <option value="hero">Hero</option>
                                    <option value="thumbnail">Thumbnail</option>
                                    <option value="social">Social</option>
                                    <option value="talking_dialogue">Diálogo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Duración (seg)</label>
                                <input v-model="shotForm.duration_seconds" type="number" min="1" placeholder="5" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs font-mono text-text-primary focus:outline-none focus:border-amber transition-colors" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Descripción</label>
                            <input v-model="shotForm.description" type="text" placeholder="Qué ocurre en este shot..." class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                        </div>
                        <div v-if="shotForm.shot_type === 'talking'">
                            <label class="block text-xs text-text-muted mb-1">Diálogo</label>
                            <textarea v-model="shotForm.dialogue_text" rows="2" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors resize-none" />
                        </div>
                        <div class="flex gap-2">
                            <button @click="storeShot(scene.id)" :disabled="shotForm.processing" class="px-3 py-1 bg-amber text-surface-0 text-xs font-semibold rounded hover:bg-amber/90 disabled:opacity-50 transition-colors">Guardar shot</button>
                            <button @click="activeShotForm = null; shotForm.reset()" class="text-xs text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                        </div>
                    </div>

                    <!-- Shots grid -->
                    <div class="p-4">
                        <div v-if="!scene.shots?.length" class="text-center py-8 text-xs text-text-muted">
                            Sin shots — click "+ shot" para añadir
                        </div>
                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                            <div
                                v-for="shot in scene.shots"
                                :key="shot.id"
                                :class="['bg-surface-2 border rounded-xl overflow-hidden relative group transition-all hover:border-amber/40', editingShot === shot.id ? 'border-amber' : 'border-border']"
                            >
                                <!-- Imagen del render -->
                                <div class="relative aspect-video bg-surface-3 overflow-hidden">
                                    <img
                                        v-if="shotRenderUrl(shot)"
                                        :src="shotRenderUrl(shot)"
                                        :alt="`Shot ${shot.number}`"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1.5">
                                        <component :is="shotTypeIcon[shot.shot_type]" class="w-5 h-5 text-text-muted/40" />
                                        <span class="text-[9px] text-text-muted/40 font-mono uppercase">Sin render</span>
                                    </div>

                                    <!-- Número de shot overlay -->
                                    <div class="absolute top-1.5 left-1.5">
                                        <span class="text-[10px] font-mono font-bold text-white bg-black/60 backdrop-blur-sm px-1.5 py-0.5 rounded">
                                            S{{ String(shot.number).padStart(2, '0') }}
                                        </span>
                                    </div>

                                    <!-- Status dot -->
                                    <div class="absolute top-1.5 right-1.5">
                                        <span :class="['w-2 h-2 rounded-full block ring-1 ring-black/30', {
                                            'bg-text-muted': shot.status === 'draft',
                                            'bg-info': shot.status === 'prompt_ready',
                                            'bg-violet animate-pulse': shot.status === 'rendering',
                                            'bg-warning': ['audio_pending','lip_sync_pending'].includes(shot.status),
                                            'bg-success': shot.status === 'completed',
                                            'bg-amber': shot.status === 'approved',
                                        }]" :title="shot.status" />
                                    </div>

                                    <!-- Tipo + duración overlay bottom -->
                                    <div class="absolute bottom-0 inset-x-0 flex items-center justify-between px-1.5 py-1 bg-gradient-to-t from-black/70 to-transparent">
                                        <component :is="shotTypeIcon[shot.shot_type]" class="w-3 h-3 text-white/70" />
                                        <span v-if="shot.duration_seconds" class="text-[9px] font-mono text-white/60">{{ shot.duration_seconds }}s</span>
                                    </div>

                                    <!-- Hover actions overlay -->
                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link :href="`/shots/${shot.id}`" class="p-1.5 bg-white/10 hover:bg-violet/60 text-white rounded-lg transition-colors backdrop-blur-sm" title="Abrir shot">
                                            <Camera class="w-3.5 h-3.5" />
                                        </Link>
                                        <button @click="openEditShot(shot)" class="p-1.5 bg-white/10 hover:bg-amber/60 text-white rounded-lg transition-colors backdrop-blur-sm">
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                        <button @click="deleteShot(shot.id)" class="p-1.5 bg-white/10 hover:bg-danger/60 text-white rounded-lg transition-colors backdrop-blur-sm">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Info del shot -->
                                <div class="p-2">
                                    <p class="text-[11px] text-text-secondary line-clamp-2 leading-snug">
                                        {{ shot.description || shot.dialogue_text || shot.purpose }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de edición inline -->
                        <div v-if="editingShot && scene.shots?.some(s => s.id === editingShot)" class="mt-3 bg-surface-0 border border-amber/40 rounded-lg p-3 space-y-2">
                            <p class="text-xs font-medium text-amber mb-2">Editando shot</p>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-xs text-text-muted mb-1">Tipo</label>
                                    <select v-model="editShotForm.shot_type" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                        <option value="image">Imagen</option>
                                        <option value="video">Video</option>
                                        <option value="talking">Talking</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-text-muted mb-1">Propósito</label>
                                    <select v-model="editShotForm.purpose" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                        <option value="narrative">Narrativo</option>
                                        <option value="hero">Hero</option>
                                        <option value="thumbnail">Thumbnail</option>
                                        <option value="social">Social</option>
                                        <option value="talking_dialogue">Diálogo</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-text-muted mb-1">Estado</label>
                                    <select v-model="editShotForm.status" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors">
                                        <option value="draft">Borrador</option>
                                        <option value="prompt_ready">Prompt listo</option>
                                        <option value="rendering">Renderizando</option>
                                        <option value="audio_pending">Audio pendiente</option>
                                        <option value="lip_sync_pending">Lip sync pendiente</option>
                                        <option value="completed">Completado</option>
                                        <option value="approved">Aprobado</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Descripción</label>
                                <input v-model="editShotForm.description" type="text" placeholder="Qué ocurre en este shot..." class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-text-muted mb-1">Notas de dirección</label>
                                    <input v-model="editShotForm.director_notes" type="text" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-xs text-text-muted mb-1">Duración (seg)</label>
                                    <input v-model="editShotForm.duration_seconds" type="number" min="1" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs font-mono text-text-primary focus:outline-none focus:border-amber transition-colors" />
                                </div>
                            </div>
                            <div v-if="editShotForm.shot_type === 'talking'">
                                <label class="block text-xs text-text-muted mb-1">Diálogo</label>
                                <textarea v-model="editShotForm.dialogue_text" rows="2" class="w-full bg-surface-2 border border-border rounded px-2 py-1.5 text-xs text-text-primary focus:outline-none focus:border-amber transition-colors resize-none" />
                            </div>
                            <div class="flex gap-2 pt-1">
                                <button @click="updateShot" :disabled="editShotForm.processing" class="px-3 py-1 bg-amber text-surface-0 text-xs font-semibold rounded hover:bg-amber/90 disabled:opacity-50 transition-colors">Guardar</button>
                                <button @click="editingShot = null; editShotForm.reset()" class="text-xs text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Share Modal -->
        <div v-if="showShare" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showShare = false">
            <div class="bg-surface-1 border border-border rounded-2xl p-6 w-full max-w-md mx-4 shadow-2xl">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-text-primary flex items-center gap-2">
                        <Share2 class="w-4 h-4 text-amber" /> Compartir preview
                    </h3>
                    <button @click="showShare = false" class="text-text-muted hover:text-text-primary transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Create new link -->
                <form @submit.prevent="createShare" class="space-y-3 mb-5">
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Etiqueta (opcional)</label>
                        <input v-model="shareForm.label" type="text" placeholder="Ej: Review cliente — semana 3"
                            class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Contraseña (opcional)</label>
                            <input v-model="shareForm.password" type="password" placeholder="Sin contraseña"
                                class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                        </div>
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Expira en</label>
                            <select v-model="shareForm.expires_in"
                                class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                                <option value="never">Nunca</option>
                                <option value="7">7 días</option>
                                <option value="30">30 días</option>
                                <option value="90">90 días</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" :disabled="shareForm.processing"
                        class="w-full py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                        <Link2 class="w-4 h-4" /> Generar link
                    </button>
                </form>

                <!-- Existing tokens -->
                <div v-if="shareTokens?.length" class="border-t border-border pt-4 space-y-2">
                    <p class="text-xs text-text-muted mb-2">Links activos</p>
                    <div v-for="t in shareTokens" :key="t.id"
                        class="flex items-center gap-2 bg-surface-2 rounded-lg px-3 py-2"
                        :class="t.expired ? 'opacity-50' : ''">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-medium text-text-primary truncate">{{ t.label || 'Sin etiqueta' }}</span>
                                <Lock v-if="t.protected" class="w-3 h-3 text-text-muted shrink-0" />
                                <span v-if="t.expired" class="text-[9px] text-danger">expirado</span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] text-text-muted font-mono truncate">{{ t.url }}</span>
                                <span class="text-[9px] text-text-muted flex items-center gap-0.5 shrink-0">
                                    <Eye class="w-2.5 h-2.5" />{{ t.view_count }}
                                </span>
                                <span v-if="t.expires_at" class="text-[9px] text-text-muted shrink-0">exp. {{ t.expires_at }}</span>
                            </div>
                        </div>
                        <button @click="copyLink(t.url)"
                            class="text-[10px] px-2 py-1 bg-surface-1 border border-border rounded text-text-muted hover:text-amber hover:border-amber transition-colors shrink-0">
                            {{ copied ? '✓' : 'Copiar' }}
                        </button>
                        <button @click="deleteShare(t.id)"
                            class="p-1 text-text-muted hover:text-danger transition-colors shrink-0">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
