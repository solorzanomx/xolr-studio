<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Camera, Video, Mic, Image, Search, X, ChevronLeft, ChevronRight } from '@lucide/vue'

const props = defineProps({
    shots:    Object,
    projects: Array,
    filters:  Object,
})

const localFilters = ref({ ...props.filters })

let debounceTimer = null
watch(() => localFilters.value.search, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(applyFilters, 350)
})

function applyFilters() {
    router.get('/shots', localFilters.value, { preserveState: true, replace: true })
}

function resetFilters() {
    localFilters.value = {}
    router.get('/shots', {}, { preserveState: false })
}

function goToPage(url) {
    if (url) router.get(url, {}, { preserveState: true })
}

const SHOT_TYPE_ICON  = { image: Image, video: Video, talking: Mic }
const SHOT_TYPE_COLOR = { image: 'text-amber', video: 'text-violet', talking: 'text-teal-400' }
const SHOT_TYPE_LABEL = { image: 'Imagen', video: 'Video', talking: 'Talking' }

const STATUS_COLOR = {
    draft:             'bg-surface-3 text-text-muted',
    prompt_ready:      'bg-violet/10 text-violet',
    rendering:         'bg-amber/10 text-amber',
    audio_pending:     'bg-blue-500/10 text-blue-400',
    lip_sync_pending:  'bg-teal-500/10 text-teal-400',
    completed:         'bg-emerald-400/10 text-emerald-400',
    approved:          'bg-evergreen/10 text-evergreen',
}
const STATUS_LABEL = {
    draft:             'Borrador',
    prompt_ready:      'Prompt listo',
    rendering:         'Renderizando',
    audio_pending:     'Audio pendiente',
    lip_sync_pending:  'Lip-sync pendiente',
    completed:         'Completado',
    approved:          'Aprobado',
}

const hasFilters = () => Object.values(localFilters.value).some(v => v)
</script>

<template>
    <AppLayout>
        <Head title="Shots" />

        <div class="max-w-7xl mx-auto px-6 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Shots</h1>
                    <p class="text-sm text-text-muted mt-0.5">
                        {{ shots.total }} shots en total
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-text-muted pointer-events-none" />
                    <input
                        v-model="localFilters.search"
                        type="text"
                        placeholder="Buscar por descripción..."
                        class="pl-8 pr-3 py-2 bg-surface-1 border border-border text-sm text-text-primary placeholder:text-text-muted rounded-lg focus:outline-none focus:border-violet w-64"
                    />
                </div>

                <!-- Project -->
                <select
                    v-model="localFilters.project_id"
                    @change="applyFilters"
                    class="px-3 py-2 bg-surface-1 border border-border text-sm text-text-primary rounded-lg focus:outline-none focus:border-violet"
                >
                    <option value="">Todos los proyectos</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>

                <!-- Status -->
                <select
                    v-model="localFilters.status"
                    @change="applyFilters"
                    class="px-3 py-2 bg-surface-1 border border-border text-sm text-text-primary rounded-lg focus:outline-none focus:border-violet"
                >
                    <option value="">Todos los estados</option>
                    <option v-for="(label, val) in STATUS_LABEL" :key="val" :value="val">{{ label }}</option>
                </select>

                <!-- Type -->
                <select
                    v-model="localFilters.type"
                    @change="applyFilters"
                    class="px-3 py-2 bg-surface-1 border border-border text-sm text-text-primary rounded-lg focus:outline-none focus:border-violet"
                >
                    <option value="">Todos los tipos</option>
                    <option value="image">Imagen</option>
                    <option value="video">Video</option>
                    <option value="talking">Talking</option>
                </select>

                <!-- Reset -->
                <button
                    v-if="hasFilters()"
                    @click="resetFilters"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm text-text-muted hover:text-text-primary transition-colors"
                >
                    <X class="w-3.5 h-3.5" />
                    Limpiar
                </button>
            </div>

            <!-- Grid -->
            <div v-if="shots.data.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                <Link
                    v-for="shot in shots.data"
                    :key="shot.id"
                    :href="`/shots/${shot.id}`"
                    class="group bg-surface-1 border border-border rounded-xl overflow-hidden hover:border-violet/50 transition-colors"
                >
                    <!-- Thumbnail -->
                    <div class="relative aspect-video bg-surface-2">
                        <img
                            v-if="shot.thumb"
                            :src="shot.thumb"
                            :alt="shot.description"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        />
                        <div v-else class="absolute inset-0 flex items-center justify-center">
                            <component
                                :is="SHOT_TYPE_ICON[shot.shot_type] ?? Image"
                                class="w-6 h-6"
                                :class="SHOT_TYPE_COLOR[shot.shot_type] ?? 'text-surface-3'"
                            />
                        </div>
                        <!-- Status dot -->
                        <span
                            class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full"
                            :class="{
                                'bg-surface-3':    shot.status === 'draft',
                                'bg-violet':       shot.status === 'prompt_ready',
                                'bg-amber':        shot.status === 'rendering',
                                'bg-blue-400':     shot.status === 'audio_pending',
                                'bg-teal-400':     shot.status === 'lip_sync_pending',
                                'bg-emerald-400':  shot.status === 'completed',
                                'bg-evergreen':    shot.status === 'approved',
                            }"
                            :title="STATUS_LABEL[shot.status]"
                        />
                        <!-- Shot number -->
                        <span class="absolute bottom-1.5 left-1.5 text-[10px] font-mono bg-black/60 text-white px-1.5 py-0.5 rounded">
                            S{{ shot.number }}
                        </span>
                    </div>

                    <!-- Meta -->
                    <div class="p-2.5">
                        <p class="text-xs text-text-primary leading-snug line-clamp-2 mb-1.5">
                            {{ shot.description || '—' }}
                        </p>
                        <p v-if="shot.project" class="text-[10px] text-text-muted truncate">
                            {{ shot.project.name }}
                        </p>
                        <p class="text-[10px] text-text-muted truncate">{{ shot.context }}</p>
                    </div>
                </Link>
            </div>

            <!-- Empty -->
            <div v-else class="text-center py-20">
                <Camera class="w-10 h-10 text-surface-3 mx-auto mb-3" />
                <p class="text-text-muted text-sm">No hay shots que coincidan con los filtros.</p>
                <button v-if="hasFilters()" @click="resetFilters" class="text-violet text-sm mt-2 hover:underline">
                    Limpiar filtros
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="shots.last_page > 1" class="flex items-center justify-center gap-2 mt-8">
                <button
                    @click="goToPage(shots.prev_page_url)"
                    :disabled="!shots.prev_page_url"
                    class="p-2 rounded-lg border border-border text-text-muted hover:text-text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronLeft class="w-4 h-4" />
                </button>
                <span class="text-sm text-text-muted px-3">
                    {{ shots.current_page }} / {{ shots.last_page }}
                </span>
                <button
                    @click="goToPage(shots.next_page_url)"
                    :disabled="!shots.next_page_url"
                    class="p-2 rounded-lg border border-border text-text-muted hover:text-text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>
        </div>
    </AppLayout>
</template>
