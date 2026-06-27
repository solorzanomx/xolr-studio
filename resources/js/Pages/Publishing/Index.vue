<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, computed, onBeforeUnmount } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ChevronLeft, Download, Trash2, FileText, Film, Package, Grid,
    Play, Pause, SkipBack, SkipForward, RefreshCw, Clock, Image,
    Mic, Video, CheckCircle, Loader, AlertCircle, ExternalLink
} from '@lucide/vue'

const props = defineProps({
    project:   Object,
    seasons:   Array,
    campaigns: Array,
    exports:   Array,
})

const activeTab = ref('animatic')

// ── Animatic Player ────────────────────────────────────────────
const selectedEpisodeId = ref(null)
const slides            = ref([])
const currentIndex      = ref(0)
const isPlaying         = ref(false)
const isLoading         = ref(false)
let slideTimer = null

const currentSlide = computed(() => slides.value[currentIndex.value] ?? null)
const totalDuration = computed(() => slides.value.reduce((sum, s) => sum + (s.duration_seconds ?? 3), 0))

function hasEpisodes() {
    return props.seasons.some(s => s.episodes?.length > 0)
}

const allEpisodes = computed(() => {
    const list = []
    props.seasons.forEach(s => {
        (s.episodes ?? []).forEach(e => {
            list.push({ ...e, seasonNumber: s.number })
        })
    })
    return list
})

async function loadAnimatic() {
    if (!selectedEpisodeId.value) return
    isLoading.value = true
    stop()
    try {
        const res = await fetch(`/publishing/episodes/${selectedEpisodeId.value}/animatic-slides`)
        const data = await res.json()
        slides.value     = data.slides ?? []
        currentIndex.value = 0
    } finally {
        isLoading.value = false
    }
}

function play() {
    if (!slides.value.length) return
    isPlaying.value = true
    scheduleNext()
}

function scheduleNext() {
    clearTimeout(slideTimer)
    const duration = (currentSlide.value?.duration_seconds ?? 3) * 1000
    slideTimer = setTimeout(() => {
        if (currentIndex.value < slides.value.length - 1) {
            currentIndex.value++
            scheduleNext()
        } else {
            isPlaying.value = false
        }
    }, duration)
}

function pause() {
    isPlaying.value = false
    clearTimeout(slideTimer)
}

function stop() {
    pause()
    currentIndex.value = 0
}

function prev() {
    pause()
    if (currentIndex.value > 0) currentIndex.value--
}

function next() {
    pause()
    if (currentIndex.value < slides.value.length - 1) currentIndex.value++
}

function togglePlay() {
    isPlaying.value ? pause() : play()
}

onBeforeUnmount(() => clearTimeout(slideTimer))

// Duration editing
const editingDuration = ref(null)

function saveDuration(slide, newValue) {
    slide.duration_seconds = Math.max(1, parseInt(newValue) || 3)
    editingDuration.value = null
}

// Save animatic
const animaticForm = useForm({ episode_id: null, shot_durations: {} })

function saveAnimatic() {
    if (!selectedEpisodeId.value || !slides.value.length) return
    animaticForm.episode_id = selectedEpisodeId.value
    animaticForm.shot_durations = Object.fromEntries(slides.value.map(s => [s.id, s.duration_seconds ?? 3]))
    animaticForm.post(`/projects/${props.project.id}/publishing/animatic`, {
        onSuccess: () => {},
    })
}

// ── ZIP Export ─────────────────────────────────────────────────
const zipForm = useForm({ campaign_id: '' })

function submitZip() {
    zipForm.post(`/projects/${props.project.id}/publishing/zip`)
}

// ── Exports list ───────────────────────────────────────────────
const EXPORT_TYPE_LABEL = {
    animatic:         'Animatic',
    pdf_book:         'Libro PDF',
    zip_campaign:     'ZIP Campaña',
    production_packet: 'Production Bible',
    csv:              'CSV',
}

const STATUS_COLOR = {
    pending:    'text-text-muted',
    generating: 'text-amber',
    completed:  'text-emerald-400',
    failed:     'text-danger',
}

function deleteExport(id) {
    if (!confirm('¿Eliminar esta exportación?')) return
    router.delete(`/publishing/exports/${id}`)
}

// ── Carousel Builder ───────────────────────────────────────────
const selectedCampaignId = ref(null)
const carouselSlots      = ref(Array.from({ length: 10 }, (_, i) => ({ index: i, shot: null })))

const selectedCampaign = computed(() => props.campaigns.find(c => c.id === parseInt(selectedCampaignId.value)))
</script>

<template>
    <AppLayout>
        <Head :title="`Publishing — ${project.name}`" />

        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link :href="`/projects/${project.id}`" class="text-text-muted hover:text-text-primary transition-colors">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div>
                    <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest">Publishing Engine</p>
                    <h1 class="text-lg font-semibold text-text-primary">{{ project.name }}</h1>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 border-b border-border mb-6">
                <button
                    v-for="[id, label, icon] in [
                        ['animatic',  'Animatic',         Film],
                        ['exports',   'Exportaciones',    Download],
                        ['carousel',  'Carousel Builder', Grid],
                    ]"
                    :key="id"
                    @click="activeTab = id"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                        activeTab === id ? 'border-amber text-text-primary' : 'border-transparent text-text-muted hover:text-text-secondary'
                    ]"
                >
                    <component :is="icon" class="w-4 h-4" />
                    {{ label }}
                </button>
            </div>

            <!-- ─── ANIMATIC TAB ─── -->
            <div v-if="activeTab === 'animatic'" class="space-y-5">

                <!-- Episode selector -->
                <div class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-semibold text-text-primary mb-3">Seleccionar episodio</p>
                    <div class="flex gap-3">
                        <select
                            v-model="selectedEpisodeId"
                            class="flex-1 bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                        >
                            <option value="" disabled>Elige un episodio</option>
                            <option v-for="ep in allEpisodes" :key="ep.id" :value="ep.id">
                                T{{ ep.seasonNumber }}E{{ ep.number }} — {{ ep.title }}
                            </option>
                        </select>
                        <button
                            @click="loadAnimatic"
                            :disabled="!selectedEpisodeId || isLoading"
                            class="flex items-center gap-2 px-4 py-2 bg-violet text-white text-sm font-medium rounded-lg hover:bg-violet/90 disabled:opacity-50 transition-colors"
                        >
                            <Loader v-if="isLoading" class="w-4 h-4 animate-spin" />
                            <Film v-else class="w-4 h-4" />
                            Cargar animatic
                        </button>
                    </div>
                </div>

                <!-- Player -->
                <div v-if="slides.length" class="space-y-4">
                    <!-- Main display -->
                    <div class="bg-surface-0 border border-border rounded-xl overflow-hidden aspect-video relative flex items-center justify-center">
                        <!-- Slide image -->
                        <template v-if="currentSlide">
                            <img
                                v-if="currentSlide.render_url && currentSlide.render_url.startsWith('http')"
                                :src="currentSlide.render_url"
                                :alt="currentSlide.description"
                                class="w-full h-full object-contain"
                                :key="currentSlide.id"
                            />
                            <div v-else class="flex flex-col items-center gap-2 text-text-muted">
                                <Image class="w-12 h-12 opacity-30" />
                                <p class="text-sm">{{ currentSlide.label }} — sin render</p>
                            </div>

                            <!-- Overlay info -->
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                                <div class="flex items-end justify-between">
                                    <div>
                                        <p class="text-white text-xs font-mono opacity-70">{{ currentSlide.label }}</p>
                                        <p class="text-white text-sm">{{ currentSlide.description ?? 'Sin descripción' }}</p>
                                        <div class="flex gap-1.5 mt-1">
                                            <span v-for="char in currentSlide.characters" :key="char" class="text-[10px] bg-white/20 text-white px-1.5 py-0.5 rounded">{{ char }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-white text-xs font-mono opacity-60">{{ currentIndex + 1 }} / {{ slides.length }}</p>
                                        <p class="text-white text-sm font-mono">{{ currentSlide.duration_seconds ?? 3 }}s</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Progress bar -->
                    <div class="flex gap-0.5">
                        <div
                            v-for="(slide, i) in slides"
                            :key="slide.id"
                            :style="{ flex: slide.duration_seconds ?? 3 }"
                            :class="[
                                'h-1 rounded-full transition-colors',
                                i === currentIndex ? 'bg-amber' : i < currentIndex ? 'bg-surface-3' : 'bg-surface-2'
                            ]"
                        />
                    </div>

                    <!-- Controls -->
                    <div class="flex items-center justify-between bg-surface-1 border border-border rounded-xl px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button @click="prev" :disabled="currentIndex === 0" class="p-1.5 text-text-muted hover:text-text-primary disabled:opacity-30 transition-colors rounded">
                                <SkipBack class="w-4 h-4" />
                            </button>
                            <button @click="togglePlay" class="w-8 h-8 flex items-center justify-center bg-amber text-surface-0 rounded-full hover:bg-amber/90 transition-colors">
                                <Pause v-if="isPlaying" class="w-4 h-4" />
                                <Play v-else class="w-4 h-4" />
                            </button>
                            <button @click="next" :disabled="currentIndex === slides.length - 1" class="p-1.5 text-text-muted hover:text-text-primary disabled:opacity-30 transition-colors rounded">
                                <SkipForward class="w-4 h-4" />
                            </button>
                            <button @click="stop" class="p-1.5 text-text-muted hover:text-text-primary transition-colors rounded">
                                <RefreshCw class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-text-muted font-mono">{{ totalDuration }}s total</span>
                            <span class="text-xs text-text-muted font-mono">{{ slides.length }} shots</span>
                            <button @click="saveAnimatic" :disabled="animaticForm.processing" class="px-3 py-1.5 bg-surface-2 text-text-secondary text-xs rounded-lg hover:text-text-primary transition-colors">
                                Guardar durations
                            </button>
                        </div>
                    </div>

                    <!-- Shot strip with duration editing -->
                    <div class="bg-surface-1 border border-border rounded-xl p-4">
                        <p class="text-xs font-semibold text-text-primary mb-3">Timeline — click para editar duración</p>
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            <div
                                v-for="(slide, i) in slides"
                                :key="slide.id"
                                @click="currentIndex = i; pause()"
                                :class="[
                                    'flex-shrink-0 w-20 cursor-pointer rounded-lg overflow-hidden border transition-all',
                                    i === currentIndex ? 'border-amber' : 'border-border hover:border-text-muted'
                                ]"
                            >
                                <div class="h-12 bg-surface-2 relative">
                                    <img
                                        v-if="slide.render_url && slide.render_url.startsWith('http')"
                                        :src="slide.render_url"
                                        :alt="slide.label"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <Image class="w-4 h-4 text-text-muted opacity-40" />
                                    </div>
                                </div>
                                <div class="px-1.5 py-1 bg-surface-1">
                                    <p class="text-[9px] font-mono text-text-muted">{{ slide.label }}</p>
                                    <!-- Editable duration -->
                                    <div v-if="editingDuration === slide.id" @click.stop>
                                        <input
                                            :value="slide.duration_seconds ?? 3"
                                            type="number"
                                            min="1"
                                            max="30"
                                            class="w-full text-[10px] font-mono bg-surface-0 border border-amber rounded px-1 py-0.5 text-center text-text-primary focus:outline-none"
                                            @change="saveDuration(slide, $event.target.value)"
                                            @blur="editingDuration = null"
                                            @keydown.enter="saveDuration(slide, $event.target.value)"
                                            autofocus
                                        />
                                    </div>
                                    <button
                                        v-else
                                        @click.stop="editingDuration = slide.id"
                                        class="flex items-center gap-0.5 text-[9px] text-text-muted hover:text-amber transition-colors"
                                    >
                                        <Clock class="w-2.5 h-2.5" />
                                        {{ slide.duration_seconds ?? 3 }}s
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="!isLoading && selectedEpisodeId" class="text-center py-10 text-text-muted text-sm">
                    No hay shots con renders aprobados en este episodio.
                </div>
            </div>

            <!-- ─── EXPORTS TAB ─── -->
            <div v-if="activeTab === 'exports'" class="space-y-6">

                <!-- Print views -->
                <div class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-4">Vistas de impresión (PDF via navegador)</p>
                    <div class="space-y-3">
                        <!-- Production Bible per project -->
                        <div class="flex items-center justify-between py-2.5 border-b border-border">
                            <div>
                                <p class="text-sm text-text-primary">Production Bible</p>
                                <p class="text-xs text-text-muted">Guía completa del proyecto con escenas y renders</p>
                            </div>
                            <a :href="`/projects/${project.id}/print/bible`" target="_blank"
                               class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-2 text-text-secondary text-xs rounded-lg hover:text-text-primary transition-colors">
                                <ExternalLink class="w-3.5 h-3.5" /> Abrir
                            </a>
                        </div>

                        <!-- Book per season -->
                        <template v-if="seasons.length">
                            <div v-for="season in seasons" :key="season.id" class="flex items-center justify-between py-2.5 border-b border-border last:border-0">
                                <div>
                                    <p class="text-sm text-text-primary">Libro — {{ season.title }}</p>
                                    <p class="text-xs text-text-muted">{{ season.episodes.length }} capítulos con renders como ilustraciones</p>
                                </div>
                                <a :href="`/seasons/${season.id}/print/book`" target="_blank"
                                   class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-2 text-text-secondary text-xs rounded-lg hover:text-text-primary transition-colors">
                                    <ExternalLink class="w-3.5 h-3.5" /> Abrir
                                </a>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- ZIP campaign export -->
                <div v-if="campaigns.length" class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-3">Exportar ZIP de campaña</p>
                    <form @submit.prevent="submitZip" class="flex gap-3">
                        <select v-model="zipForm.campaign_id" class="flex-1 bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="" disabled>Elige una campaña</option>
                            <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <button type="submit" :disabled="!zipForm.campaign_id || zipForm.processing"
                            class="flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                            <Package class="w-4 h-4" /> Generar ZIP
                        </button>
                    </form>
                </div>

                <!-- Exports history -->
                <div class="bg-surface-1 border border-border rounded-xl">
                    <div class="p-4 border-b border-border">
                        <p class="text-xs font-semibold text-text-primary">Historial de exportaciones</p>
                    </div>
                    <div v-if="!exports.length" class="p-8 text-center text-sm text-text-muted">
                        No hay exportaciones aún.
                    </div>
                    <div v-else class="divide-y divide-border">
                        <div v-for="exp in exports" :key="exp.id" class="flex items-center justify-between px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div>
                                    <p class="text-sm text-text-primary">{{ EXPORT_TYPE_LABEL[exp.type] ?? exp.type }}</p>
                                    <p class="text-xs font-mono" :class="STATUS_COLOR[exp.status]">{{ exp.status }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-text-muted font-mono">{{ new Date(exp.created_at).toLocaleDateString('es') }}</span>
                                <a v-if="exp.status === 'completed' && exp.file_path"
                                   :href="`/publishing/exports/${exp.id}/download`"
                                   class="p-1.5 text-text-muted hover:text-amber transition-colors rounded">
                                    <Download class="w-4 h-4" />
                                </a>
                                <Loader v-else-if="exp.status === 'generating'" class="w-4 h-4 text-amber animate-spin" />
                                <button @click="deleteExport(exp.id)" class="p-1.5 text-text-muted hover:text-danger transition-colors rounded">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── CAROUSEL BUILDER TAB ─── -->
            <div v-if="activeTab === 'carousel'" class="space-y-5">
                <div class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-3">Preview de feed Instagram (grid 3×3)</p>
                    <p class="text-xs text-text-muted mb-4">
                        Las campañas con shots asignados muestran los renders aprobados en orden de publicación.
                        Selecciona una campaña para previsualizar.
                    </p>
                    <select
                        v-model="selectedCampaignId"
                        class="w-full md:w-72 bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors mb-4"
                    >
                        <option value="" disabled>Elige una campaña</option>
                        <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <!-- Link to campaign for full carousel editing -->
                    <div v-if="selectedCampaignId" class="mt-2">
                        <Link
                            :href="`/campaigns/${selectedCampaignId}`"
                            class="flex items-center gap-1.5 text-sm text-violet hover:underline"
                        >
                            <ExternalLink class="w-3.5 h-3.5" />
                            Gestionar shots de esta campaña
                        </Link>
                    </div>
                </div>

                <!-- Grid preview placeholder -->
                <div class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-3">Vista previa de feed</p>
                    <div class="grid grid-cols-3 gap-0.5 max-w-xs">
                        <div
                            v-for="i in 9"
                            :key="i"
                            class="aspect-square bg-surface-2 rounded-sm flex items-center justify-center"
                        >
                            <Image class="w-5 h-5 text-text-muted opacity-30" />
                        </div>
                    </div>
                    <p class="text-xs text-text-muted mt-3">
                        Los renders aprobados de la campaña aparecen aquí en orden.
                        Agrega shots desde la <Link :href="`/campaigns`" class="text-violet hover:underline">página de Campañas</Link>.
                    </p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
