<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Image, CheckCircle, XCircle, Clock, Loader, AlertTriangle,
    Download, Trash2, Check, Filter, DollarSign, BarChart2,
    ChevronLeft, ChevronRight, Star, Eye
} from '@lucide/vue'

const props = defineProps({
    renders:  Object,   // paginated
    projects: Array,
    stats:    Object,
    filters:  Object,
})

// --- Filters ---
const localFilters = ref({ ...props.filters })

function applyFilters() {
    router.get('/renders', localFilters.value, { preserveState: true, replace: true })
}

function resetFilters() {
    localFilters.value = {}
    router.get('/renders', {}, { preserveState: false })
}

// --- Selection ---
const selected = ref(new Set())

function toggleSelect(id) {
    if (selected.value.has(id)) selected.value.delete(id)
    else selected.value.add(id)
    selected.value = new Set(selected.value)
}

function selectAll() {
    const all = props.renders.data.map(r => r.id)
    if (selected.value.size === all.length) selected.value = new Set()
    else selected.value = new Set(all)
}

const allSelected = computed(() =>
    props.renders.data.length > 0 && selected.value.size === props.renders.data.length
)

// --- Actions ---
function bulkApprove() {
    if (!selected.value.size) return
    router.post('/renders/bulk-approve', { ids: [...selected.value] }, {
        onSuccess: () => { selected.value = new Set() },
        preserveScroll: true,
    })
}

function approveRender(id) {
    router.post(`/renders/${id}/approve`, {}, { preserveScroll: true })
}

function deleteRender(id) {
    if (!confirm('¿Eliminar este render?')) return
    router.delete(`/renders/${id}`, { preserveScroll: true })
}

// --- Lightbox ---
const lightbox = ref(null)

function openLightbox(render) {
    lightbox.value = render
}

// --- Helpers ---
const STATUS_COLOR = {
    queued:     'text-text-muted bg-surface-3',
    processing: 'text-violet bg-violet/10',
    completed:  'text-emerald-400 bg-emerald-400/10',
    failed:     'text-danger bg-danger/10',
    cancelled:  'text-text-muted bg-surface-3',
}

const TIER_COLOR = {
    draft:    'text-text-muted bg-surface-3',
    standard: 'text-amber bg-amber/10',
    final:    'text-emerald-400 bg-emerald-400/10',
}

const STATUS_ICON = {
    queued:     Clock,
    processing: Loader,
    completed:  CheckCircle,
    failed:     AlertTriangle,
    cancelled:  XCircle,
}

function fmt(n) {
    if (!n && n !== 0) return '—'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

function fmtCost(v) {
    if (!v) return '—'
    return '$' + parseFloat(v).toFixed(4)
}
</script>

<template>
    <AppLayout>
        <Head title="Renders" />

        <div class="max-w-7xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Renders</h1>
                    <p class="text-sm text-text-muted">Galería global de imágenes y videos generados</p>
                </div>
                <div v-if="selected.size > 0" class="flex items-center gap-2">
                    <span class="text-xs text-text-muted">{{ selected.size }} seleccionados</span>
                    <button @click="bulkApprove"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-400/10 text-emerald-400 text-sm rounded-lg hover:bg-emerald-400/20 transition-colors">
                        <Check class="w-4 h-4" /> Aprobar seleccionados
                    </button>
                </div>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-6 gap-3 mb-6">
                <div class="bg-surface-1 border border-border rounded-xl p-4 col-span-1">
                    <p class="text-[10px] text-text-muted mb-1 flex items-center gap-1"><Image class="w-3 h-3" /> Total</p>
                    <p class="text-xl font-mono font-bold text-text-primary">{{ fmt(stats.total) }}</p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4 col-span-1">
                    <p class="text-[10px] text-text-muted mb-1 flex items-center gap-1"><CheckCircle class="w-3 h-3" /> Aprobados</p>
                    <p class="text-xl font-mono font-bold text-emerald-400">{{ fmt(stats.approved) }}</p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4 col-span-1">
                    <p class="text-[10px] text-text-muted mb-1 flex items-center gap-1"><BarChart2 class="w-3 h-3" /> Completados</p>
                    <p class="text-xl font-mono font-bold text-text-primary">{{ fmt(stats.completed) }}</p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4 col-span-1">
                    <p class="text-[10px] text-text-muted mb-1 flex items-center gap-1"><AlertTriangle class="w-3 h-3" /> Fallidos</p>
                    <p class="text-xl font-mono font-bold text-danger">{{ fmt(stats.failed) }}</p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4 col-span-1">
                    <p class="text-[10px] text-text-muted mb-1 flex items-center gap-1"><DollarSign class="w-3 h-3" /> Costo GPU</p>
                    <p class="text-xl font-mono font-bold text-amber">${{ stats.total_cost }}</p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4 col-span-1">
                    <p class="text-[10px] text-text-muted mb-2">Por tier</p>
                    <div class="space-y-0.5">
                        <div v-for="(cnt, tier) in stats.by_tier" :key="tier" class="flex items-center justify-between">
                            <span class="text-[10px] px-1.5 py-0.5 rounded" :class="TIER_COLOR[tier]">{{ tier }}</span>
                            <span class="text-xs font-mono text-text-primary">{{ cnt }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-surface-1 border border-border rounded-xl p-4 mb-6">
                <div class="flex items-center gap-3 flex-wrap">
                    <Filter class="w-4 h-4 text-text-muted shrink-0" />

                    <select v-model="localFilters.project_id" @change="applyFilters"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Todos los proyectos</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>

                    <select v-model="localFilters.status" @change="applyFilters"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Todos los estados</option>
                        <option value="queued">Encolado</option>
                        <option value="processing">Procesando</option>
                        <option value="completed">Completado</option>
                        <option value="failed">Fallido</option>
                    </select>

                    <select v-model="localFilters.quality_tier" @change="applyFilters"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Todos los tiers</option>
                        <option value="draft">Draft</option>
                        <option value="standard">Standard</option>
                        <option value="final">Final</option>
                    </select>

                    <select v-model="localFilters.file_type" @change="applyFilters"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Imagen y video</option>
                        <option value="image">Solo imágenes</option>
                        <option value="video">Solo videos</option>
                    </select>

                    <select v-model="localFilters.approved" @change="applyFilters"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                        <option value="">Todos</option>
                        <option value="1">Solo aprobados</option>
                        <option value="0">Sin aprobar</option>
                    </select>

                    <button v-if="Object.values(localFilters).some(v => v)" @click="resetFilters"
                        class="text-xs text-text-muted hover:text-danger transition-colors px-2 py-1.5">
                        Limpiar filtros
                    </button>

                    <!-- Select all toggle -->
                    <div class="ml-auto flex items-center gap-2">
                        <button @click="selectAll"
                            class="text-xs text-text-muted hover:text-text-primary transition-colors">
                            {{ allSelected ? 'Deselect all' : 'Select all' }}
                        </button>
                        <span class="text-xs text-text-muted font-mono">{{ renders.total }} renders</span>
                    </div>
                </div>
            </div>

            <!-- Grid -->
            <div v-if="renders.data.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
                <div
                    v-for="render in renders.data"
                    :key="render.id"
                    class="group relative bg-surface-1 border rounded-xl overflow-hidden transition-all duration-200"
                    :class="selected.has(render.id) ? 'border-amber ring-1 ring-amber' : 'border-border hover:border-border-hover'"
                >
                    <!-- Thumbnail -->
                    <div class="aspect-square bg-surface-2 relative cursor-pointer" @click="openLightbox(render)">
                        <img
                            v-if="render.url && render.file_type === 'image'"
                            :src="render.url"
                            :alt="render.shot?.label ?? 'render'"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        />
                        <video
                            v-else-if="render.url && render.file_type === 'video'"
                            :src="render.url"
                            class="w-full h-full object-cover"
                            muted
                            loop
                            @mouseenter="$event.target.play()"
                            @mouseleave="$event.target.pause()"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <component :is="STATUS_ICON[render.status]" class="w-8 h-8"
                                :class="render.status === 'processing' ? 'animate-spin text-violet' : 'text-text-muted'" />
                        </div>

                        <!-- Approved overlay -->
                        <div v-if="render.is_approved"
                            class="absolute top-1.5 right-1.5 p-0.5 bg-emerald-400/90 rounded-full">
                            <Check class="w-2.5 h-2.5 text-surface-0" />
                        </div>

                        <!-- Selection checkbox -->
                        <button
                            @click.stop="toggleSelect(render.id)"
                            class="absolute top-1.5 left-1.5 w-5 h-5 rounded border border-border bg-surface-0/80 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                            :class="selected.has(render.id) ? '!opacity-100 bg-amber border-amber' : ''"
                        >
                            <Check v-if="selected.has(render.id)" class="w-3 h-3 text-surface-0" />
                        </button>

                        <!-- Hover actions -->
                        <div class="absolute inset-x-0 bottom-0 p-1.5 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity bg-gradient-to-t from-black/70 to-transparent">
                            <button
                                v-if="!render.is_approved && render.status === 'completed'"
                                @click.stop="approveRender(render.id)"
                                class="text-[10px] px-2 py-0.5 bg-emerald-400/90 text-surface-0 rounded font-semibold"
                            >
                                Aprobar
                            </button>
                            <div class="ml-auto flex items-center gap-1">
                                <a v-if="render.url" :href="render.url" target="_blank" download
                                    @click.stop
                                    class="p-1 bg-black/50 text-white rounded hover:bg-black/80 transition-colors">
                                    <Download class="w-3 h-3" />
                                </a>
                                <button @click.stop="deleteRender(render.id)"
                                    class="p-1 bg-black/50 text-white rounded hover:bg-danger/80 transition-colors">
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="p-2 space-y-1">
                        <p class="text-[10px] text-text-primary font-medium truncate">{{ render.shot?.label ?? 'Shot #' + render.id }}</p>
                        <p class="text-[9px] text-text-muted truncate">{{ render.shot?.project?.name ?? '—' }}</p>
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="text-[9px] px-1 py-0.5 rounded" :class="TIER_COLOR[render.quality_tier]">{{ render.quality_tier }}</span>
                            <span class="text-[9px] px-1 py-0.5 rounded" :class="STATUS_COLOR[render.status]">{{ render.status }}</span>
                        </div>
                        <p class="text-[9px] font-mono text-text-muted">{{ fmtCost(render.gpu_cost_usd) }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="bg-surface-1 border border-border rounded-xl p-16 text-center">
                <Image class="w-10 h-10 text-text-muted mx-auto mb-3" />
                <p class="text-sm text-text-secondary">No hay renders con estos filtros.</p>
                <p class="text-xs text-text-muted mt-1">Los renders aparecen aquí cuando se encolan desde un Shot.</p>
            </div>

            <!-- Pagination -->
            <div v-if="renders.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                <Link
                    v-if="renders.prev_page_url"
                    :href="renders.prev_page_url"
                    class="p-2 bg-surface-1 border border-border rounded-lg text-text-muted hover:text-text-primary transition-colors"
                >
                    <ChevronLeft class="w-4 h-4" />
                </Link>
                <span class="text-xs text-text-muted font-mono px-3">
                    {{ renders.current_page }} / {{ renders.last_page }}
                </span>
                <Link
                    v-if="renders.next_page_url"
                    :href="renders.next_page_url"
                    class="p-2 bg-surface-1 border border-border rounded-lg text-text-muted hover:text-text-primary transition-colors"
                >
                    <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
        </div>

        <!-- Lightbox -->
        <div v-if="lightbox" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90" @click.self="lightbox = null">
            <div class="relative max-w-4xl max-h-screen w-full mx-4">
                <button @click="lightbox = null" class="absolute -top-10 right-0 text-white/60 hover:text-white transition-colors">
                    <XCircle class="w-6 h-6" />
                </button>

                <img v-if="lightbox.url && lightbox.file_type === 'image'"
                    :src="lightbox.url" :alt="lightbox.shot?.label"
                    class="max-h-[85vh] mx-auto rounded-xl object-contain" />
                <video v-else-if="lightbox.url && lightbox.file_type === 'video'"
                    :src="lightbox.url" controls autoplay
                    class="max-h-[85vh] mx-auto rounded-xl w-full" />

                <!-- Meta panel -->
                <div class="mt-3 flex items-center justify-between px-1">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ lightbox.shot?.label ?? 'Render #' + lightbox.id }}</p>
                        <p class="text-xs text-white/50">{{ lightbox.shot?.project?.name }} — {{ lightbox.shot?.episode?.title }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] px-1.5 py-0.5 rounded" :class="TIER_COLOR[lightbox.quality_tier]">{{ lightbox.quality_tier }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded" :class="STATUS_COLOR[lightbox.status]">{{ lightbox.status }}</span>
                            <span v-if="lightbox.width" class="text-[10px] font-mono text-white/40">{{ lightbox.width }}×{{ lightbox.height }}</span>
                            <span class="text-[10px] font-mono text-amber">{{ fmtCost(lightbox.gpu_cost_usd) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="!lightbox.is_approved && lightbox.status === 'completed'"
                            @click="approveRender(lightbox.id); lightbox = null"
                            class="px-3 py-1.5 bg-emerald-400/90 text-surface-0 text-sm font-semibold rounded-lg hover:bg-emerald-400 transition-colors"
                        >
                            Aprobar
                        </button>
                        <span v-else-if="lightbox.is_approved" class="flex items-center gap-1 text-emerald-400 text-sm">
                            <CheckCircle class="w-4 h-4" /> Aprobado
                        </span>
                        <a v-if="lightbox.url" :href="lightbox.url" target="_blank" download
                            class="px-3 py-1.5 bg-white/10 text-white text-sm rounded-lg hover:bg-white/20 transition-colors flex items-center gap-1.5">
                            <Download class="w-4 h-4" /> Descargar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
