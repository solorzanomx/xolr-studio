<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, onBeforeUnmount } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Pencil, FileText, Camera, Plus, Trash2, Image, Video, Mic } from '@lucide/vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
const props = defineProps({
    episode: Object,
    locations: Array,
})

const activeTab = ref('info')

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
            <Link :href="`/episodes/${episode.id}/edit`" class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors">
                <Pencil class="w-4 h-4" /> Editar
            </Link>
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
        </div>

        <!-- STORYBOARD TAB -->
        <div v-if="activeTab === 'storyboard'">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs text-text-muted">{{ episode.scenes?.length ?? 0 }} escenas · {{ episode.scenes?.reduce((a, s) => a + (s.shots?.length ?? 0), 0) ?? 0 }} shots</p>
                <button @click="showSceneForm = !showSceneForm" class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber text-surface-0 text-xs font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                    <Plus class="w-3.5 h-3.5" /> Añadir escena
                </button>
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
                    <div class="p-3">
                        <div v-if="!scene.shots?.length" class="text-center py-4 text-xs text-text-muted">
                            Sin shots — click "+ shot" para añadir
                        </div>
                        <div v-else class="flex gap-2 overflow-x-auto pb-1">
                            <div
                                v-for="shot in scene.shots"
                                :key="shot.id"
                                :class="['shrink-0 w-36 bg-surface-2 border rounded-lg p-2.5 relative group transition-colors', editingShot === shot.id ? 'border-amber' : 'border-border']"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-[10px] font-mono text-amber">S{{ String(shot.number).padStart(2, '0') }}</span>
                                    <div class="flex items-center gap-1">
                                        <component :is="shotTypeIcon[shot.shot_type]" class="w-3 h-3 text-text-muted" />
                                        <span :class="['w-1.5 h-1.5 rounded-full bg-current', shotStatusColor[shot.status]]" />
                                    </div>
                                </div>
                                <p class="text-[11px] text-text-secondary line-clamp-3 leading-tight">
                                    {{ shot.description || shot.dialogue_text || shot.purpose }}
                                </p>
                                <span v-if="shot.duration_seconds" class="text-[9px] font-mono text-text-muted mt-1 block">{{ shot.duration_seconds }}s</span>
                                <!-- Botones hover -->
                                <div class="absolute top-1 right-1 flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link :href="`/shots/${shot.id}`" class="p-0.5 text-text-muted hover:text-violet transition-colors" title="Abrir shot">
                                        <Camera class="w-3 h-3" />
                                    </Link>
                                    <button @click="openEditShot(shot)" class="p-0.5 text-text-muted hover:text-amber transition-colors">
                                        <Pencil class="w-3 h-3" />
                                    </button>
                                    <button @click="deleteShot(shot.id)" class="p-0.5 text-text-muted hover:text-danger transition-colors">
                                        <Trash2 class="w-3 h-3" />
                                    </button>
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
    </AppLayout>
</template>
