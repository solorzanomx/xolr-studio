<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Mic, Music, Volume2, Play, Download, Loader, CheckCircle, XCircle, Zap, Plus, Trash2 } from '@lucide/vue'

const props = defineProps({
    assets:   Object,
    projects: Array,
    filters:  Object,
})

// ── Filtros ──────────────────────────────────
const filter = ref({ ...props.filters })
const applyFilter = () => {
    router.get('/audio', filter.value, { preserveScroll: true, preserveState: true })
}

// ── Tipos de audio ───────────────────────────
const TYPES = [
    { key: 'voice_over', label: 'Voice-over',  icon: Mic,     color: 'text-violet' },
    { key: 'dialogue',   label: 'Diálogo',     icon: Mic,     color: 'text-blue-400' },
    { key: 'ambient',    label: 'Ambiente',    icon: Volume2, color: 'text-green-400' },
    { key: 'sfx',        label: 'SFX',         icon: Zap,     color: 'text-amber' },
    { key: 'music',      label: 'Música',      icon: Music,   color: 'text-pink-400' },
]

const STATUS_MAP = {
    pending:    { label: 'Pendiente',   class: 'text-text-muted bg-surface-3' },
    generating: { label: 'Generando',  class: 'text-amber bg-amber/10' },
    completed:  { label: 'Listo',      class: 'text-green-400 bg-green-400/10' },
    failed:     { label: 'Fallido',    class: 'text-red-400 bg-red-400/10' },
}

const MOOD_OPTIONS = ['neutral', 'epic', 'melancholic', 'tense', 'hopeful', 'mysterious', 'upbeat', 'dramatic']

// ── Formulario de nuevo asset ─────────────────
const showForm = ref(false)
const form = useForm({
    project_id:        filter.value.projectId ?? '',
    name:              '',
    type:              'voice_over',
    transcript:        '',
    prompt_used:       '',
    voice_profile_id:  '',
    duration_seconds:  10,
    mood:              'neutral',
})

const isVoice = computed(() => ['voice_over', 'dialogue'].includes(form.type))
const isAmbientOrSfx = computed(() => ['ambient', 'sfx'].includes(form.type))
const isMusic = computed(() => form.type === 'music')

const submit = () => {
    form.post('/audio/assets', {
        preserveScroll: true,
        onSuccess: () => { form.reset(); showForm.value = false },
    })
}

// ── Player ───────────────────────────────────
const playingId = ref(null)
const audioEl   = ref(null)

const togglePlay = (asset) => {
    if (! asset.file_path) return
    const url = asset.file_path.startsWith('http') ? asset.file_path : `/storage/${asset.file_path}`
    if (playingId.value === asset.id) {
        audioEl.value?.pause()
        playingId.value = null
    } else {
        if (audioEl.value) audioEl.value.pause()
        audioEl.value       = new Audio(url)
        audioEl.value.play()
        playingId.value     = asset.id
        audioEl.value.onended = () => { playingId.value = null }
    }
}

// ── Acciones ──────────────────────────────────
const deleteAsset = (id) => router.delete(`/audio/assets/${id}`, { preserveScroll: true })
const generateSubtitles = (id) => router.post(`/audio/assets/${id}/subtitles`, {}, { preserveScroll: true })

const typeIcon = (type) => TYPES.find(t => t.key === type)?.icon ?? Mic
const typeColor = (type) => TYPES.find(t => t.key === type)?.color ?? 'text-text-muted'
const typeLabel = (type) => TYPES.find(t => t.key === type)?.label ?? type
const formatDuration = (s) => s ? `${Math.floor(s / 60)}:${String(Math.round(s % 60)).padStart(2, '0')}` : '—'
</script>

<template>
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary flex items-center gap-2">
                        <Mic class="w-5 h-5 text-violet" />
                        Audio Studio
                    </h1>
                    <p class="text-sm text-text-muted mt-0.5">Voice-overs, ambientes, SFX y música generados con IA</p>
                </div>
                <button
                    @click="showForm = !showForm"
                    class="flex items-center gap-2 bg-violet/10 text-violet hover:bg-violet/20 transition-colors px-4 py-2 rounded-lg text-sm font-medium"
                >
                    <Plus class="w-4 h-4" />
                    Generar audio
                </button>
            </div>

            <!-- Formulario de generación -->
            <div v-if="showForm" class="bg-surface-1 border border-violet/20 rounded-xl p-5 space-y-4">
                <h2 class="text-sm font-semibold text-text-primary">Nuevo asset de audio</h2>

                <!-- Tipo -->
                <div>
                    <label class="block text-xs text-text-muted mb-2">Tipo</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="t in TYPES" :key="t.key"
                            @click="form.type = t.key"
                            :class="[
                                'flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border transition-all',
                                form.type === t.key
                                    ? 'border-violet/40 bg-violet/10 text-violet'
                                    : 'border-border bg-surface-0 text-text-secondary hover:text-text-primary'
                            ]"
                        >
                            <component :is="t.icon" class="w-3.5 h-3.5" />
                            {{ t.label }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Nombre</label>
                        <input v-model="form.name" type="text" placeholder="Ej: Elena intro S01E01"
                            class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary focus:outline-none focus:border-violet/50" />
                        <p v-if="form.errors.name" class="text-xs text-red-400 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Proyecto</label>
                        <select v-model="form.project_id" class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary focus:outline-none focus:border-violet/50">
                            <option value="">— Sin proyecto —</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Voice: texto a sintetizar -->
                <div v-if="isVoice">
                    <label class="block text-xs text-text-muted mb-1">Texto a sintetizar</label>
                    <textarea v-model="form.transcript" rows="3" placeholder="Escribe el diálogo o voice-over aquí…"
                        class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary resize-none focus:outline-none focus:border-violet/50" />
                </div>

                <!-- Ambient / SFX / Music: prompt -->
                <div v-if="isAmbientOrSfx || isMusic">
                    <label class="block text-xs text-text-muted mb-1">
                        {{ isMusic ? 'Descripción de la música' : 'Descripción del sonido / ambiente' }}
                    </label>
                    <textarea v-model="form.prompt_used" rows="2"
                        :placeholder="isMusic ? 'Ej: cinematic orchestral, tension building, dark fantasy' : 'Ej: bosque nocturno con grillos, viento suave, hojas cayendo'"
                        class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary resize-none focus:outline-none focus:border-violet/50" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Duración (seg)</label>
                        <input v-model.number="form.duration_seconds" type="number" min="1" max="600"
                            class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary focus:outline-none focus:border-violet/50" />
                    </div>
                    <div v-if="isMusic">
                        <label class="block text-xs text-text-muted mb-1">Mood</label>
                        <select v-model="form.mood" class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary focus:outline-none focus:border-violet/50">
                            <option v-for="m in MOOD_OPTIONS" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 pt-1 justify-end">
                    <button @click="showForm = false; form.reset()" class="text-sm text-text-muted hover:text-text-primary px-4 py-2 transition-colors">
                        Cancelar
                    </button>
                    <button @click="submit" :disabled="form.processing"
                        class="flex items-center gap-2 bg-violet text-white hover:bg-violet/90 disabled:opacity-50 transition-colors px-5 py-2 rounded-lg text-sm font-medium">
                        <Loader v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
                        <Zap v-else class="w-3.5 h-3.5" />
                        {{ form.processing ? 'Encolando…' : 'Generar' }}
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex items-center gap-3 flex-wrap">
                <select v-model="filter.projectId" @change="applyFilter"
                    class="text-xs bg-surface-1 border border-border rounded px-3 py-1.5 text-text-secondary focus:outline-none">
                    <option value="">Todos los proyectos</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <select v-model="filter.type" @change="applyFilter"
                    class="text-xs bg-surface-1 border border-border rounded px-3 py-1.5 text-text-secondary focus:outline-none">
                    <option value="">Todos los tipos</option>
                    <option v-for="t in TYPES" :key="t.key" :value="t.key">{{ t.label }}</option>
                </select>
                <select v-model="filter.status" @change="applyFilter"
                    class="text-xs bg-surface-1 border border-border rounded px-3 py-1.5 text-text-secondary focus:outline-none">
                    <option value="">Todos los estados</option>
                    <option value="generating">Generando</option>
                    <option value="completed">Listos</option>
                    <option value="failed">Fallidos</option>
                </select>
                <span class="text-xs text-text-muted ml-auto">{{ assets.total }} assets</span>
            </div>

            <!-- Grid de assets -->
            <div v-if="assets.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="asset in assets.data"
                    :key="asset.id"
                    class="bg-surface-1 border border-border rounded-xl p-4 space-y-3 group"
                >
                    <!-- Tipo + status -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <component :is="typeIcon(asset.type)" :class="['w-4 h-4', typeColor(asset.type)]" />
                            <span class="text-xs text-text-secondary">{{ typeLabel(asset.type) }}</span>
                        </div>
                        <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', STATUS_MAP[asset.status]?.class ?? 'text-text-muted bg-surface-3']">
                            {{ STATUS_MAP[asset.status]?.label ?? asset.status }}
                        </span>
                    </div>

                    <!-- Nombre -->
                    <p class="text-sm text-text-primary font-medium truncate" :title="asset.name">{{ asset.name }}</p>

                    <!-- Transcript / prompt preview -->
                    <p v-if="asset.transcript || asset.prompt_used" class="text-xs text-text-muted line-clamp-2">
                        {{ asset.transcript || asset.prompt_used }}
                    </p>

                    <!-- Meta: duración + costo -->
                    <div class="flex items-center gap-3 text-xs text-text-muted font-mono">
                        <span v-if="asset.duration_seconds">{{ formatDuration(asset.duration_seconds) }}</span>
                        <span v-if="asset.generation_cost_usd">${{ Number(asset.generation_cost_usd).toFixed(4) }}</span>
                    </div>

                    <!-- Spinner si generando -->
                    <div v-if="asset.status === 'generating'" class="flex items-center gap-1.5 text-xs text-amber">
                        <Loader class="w-3.5 h-3.5 animate-spin" />
                        Generando…
                    </div>

                    <!-- Controles: play + acciones -->
                    <div v-if="asset.status === 'completed'" class="flex items-center gap-2">
                        <button
                            @click="togglePlay(asset)"
                            :class="[
                                'flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg font-medium transition-all flex-1 justify-center',
                                playingId === asset.id
                                    ? 'bg-violet text-white'
                                    : 'bg-surface-2 text-text-secondary hover:text-text-primary'
                            ]"
                        >
                            <Play class="w-3 h-3" />
                            {{ playingId === asset.id ? 'Pausar' : 'Play' }}
                        </button>
                        <a
                            v-if="asset.file_path"
                            :href="asset.file_path.startsWith('http') ? asset.file_path : `/storage/${asset.file_path}`"
                            download
                            class="p-1.5 text-text-muted hover:text-text-primary transition-colors"
                        >
                            <Download class="w-3.5 h-3.5" />
                        </a>
                        <button
                            v-if="['voice_over','dialogue'].includes(asset.type)"
                            @click="generateSubtitles(asset.id)"
                            class="p-1.5 text-text-muted hover:text-violet transition-colors"
                            title="Generar subtítulos SRT"
                        >
                            <CheckCircle class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Delete -->
                    <div class="pt-1 border-t border-border">
                        <button @click="deleteAsset(asset.id)"
                            class="text-xs text-text-muted hover:text-red-400 transition-colors flex items-center gap-1">
                            <Trash2 class="w-3 h-3" />
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-16">
                <Mic class="w-10 h-10 text-text-muted mx-auto mb-3 opacity-30" />
                <p class="text-text-muted text-sm">No hay assets de audio.</p>
                <p class="text-xs text-text-muted mt-1">Genera voice-overs, ambientes, SFX o música con el botón de arriba.</p>
            </div>

            <!-- Paginación -->
            <div v-if="assets.last_page > 1" class="flex justify-center gap-2">
                <a v-for="page in assets.last_page" :key="page"
                    :href="`/audio?page=${page}`"
                    :class="[
                        'text-xs px-3 py-1.5 rounded border transition-colors',
                        page === assets.current_page
                            ? 'bg-violet/10 text-violet border-violet/30'
                            : 'border-border text-text-muted hover:text-text-primary'
                    ]"
                >{{ page }}</a>
            </div>
        </div>
    </AppLayout>
</template>
