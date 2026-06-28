<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ChevronLeft, Sparkles, CheckCircle, XCircle, Loader, Clock,
    Camera, Video, Mic, Image, Users, MapPin, Play, RefreshCw, Trash2
} from '@lucide/vue'

const props = defineProps({
    result:         Object,
    previewRenders: Array,
    characters:     Array,
    cameraStyles:   Array,
    visualStyles:   Array,
    locations:      Array,
})

const expandedScenes  = ref({})
const preRendering    = ref(false)
const preRenderDone   = ref(false)
const localRenders    = ref([...(props.previewRenders ?? [])])

function toggleScene(index) {
    expandedScenes.value[index] = !expandedScenes.value[index]
}

function expandAll() {
    props.result.proposed_structure?.scenes?.forEach((_, i) => {
        expandedScenes.value[i] = true
    })
}

function shotRender(sceneIndex, shotIndex) {
    return localRenders.value.find(r => r.scene_index === sceneIndex && r.shot_index === shotIndex)
}

function hasPreviewRenders() {
    return localRenders.value.length > 0
}

function allRendersComplete() {
    return localRenders.value.length > 0 && localRenders.value.every(r => ['completed', 'failed'].includes(r.status))
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

async function preRenderAll() {
    preRendering.value = true
    try {
        const res  = await fetch(`/ai-director/${props.result.id}/pre-render`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
        const data = await res.json()
        if (data.ok) {
            // Arranca polling para ver imágenes aparecer
            pollRenders()
        }
    } catch (e) {
        console.error(e)
    } finally {
        preRendering.value = false
    }
}

let renderPollTimer = null

function pollRenders() {
    if (renderPollTimer) return
    renderPollTimer = setInterval(() => {
        router.reload({ only: ['previewRenders'], onSuccess: () => {
            localRenders.value = [...(props.previewRenders ?? [])]
            if (allRendersComplete()) {
                clearInterval(renderPollTimer)
                renderPollTimer = null
                preRenderDone.value = true
            }
        }})
    }, 4000)
}

// Polling cuando status es pending/processing
let pollTimer = null

onMounted(() => {
    if (['pending', 'processing'].includes(props.result.status)) {
        expandAll()
        pollTimer = setInterval(() => {
            router.reload({ only: ['result'] })
        }, 4000)
    } else {
        expandAll()
        // Si ya hay renders en progreso, retoma el polling
        if (hasPreviewRenders() && !allRendersComplete()) {
            pollRenders()
        }
    }
})

onBeforeUnmount(() => {
    clearInterval(pollTimer)
    clearInterval(renderPollTimer)
})

// Para cuando cambie a completed, limpiar polling
const isProcessing = computed(() => ['pending', 'processing'].includes(props.result.status))

function applyStructure() {
    if (!confirm(`¿Aplicar esta estructura? Se crearán ${props.result.proposed_structure?.total_scenes ?? '?'} escenas y ${props.result.proposed_structure?.total_shots ?? '?'} shots en el episodio.`)) return
    router.post(`/ai-director/${props.result.id}/apply`)
}

function deleteResult() {
    if (!confirm('¿Eliminar este análisis?')) return
    router.delete(`/ai-director/${props.result.id}`)
}

const SHOT_TYPE_ICON = { image: Image, video: Video, talking: Mic }
const SHOT_TYPE_COLOR = { image: 'text-amber', video: 'text-violet', talking: 'text-teal-400' }
const PURPOSE_COLORS = {
    narrative: 'bg-surface-2 text-text-muted',
    hero: 'bg-amber/10 text-amber',
    thumbnail: 'bg-violet/10 text-violet',
    social: 'bg-blue-500/10 text-blue-400',
    talking_dialogue: 'bg-teal-500/10 text-teal-400',
}
</script>

<template>
    <AppLayout>
        <Head :title="`AI Director — E${result.episode?.number}`" />

        <div class="max-w-4xl mx-auto px-6 py-8">
            <!-- Back + title -->
            <div class="flex items-center gap-3 mb-6">
                <Link href="/ai-director" class="text-text-muted hover:text-text-primary transition-colors">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div>
                    <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest">
                        {{ result.episode?.season?.project?.name }}
                    </p>
                    <h1 class="text-lg font-semibold text-text-primary">
                        AI Director — E{{ result.episode?.number }}: {{ result.episode?.title }}
                    </h1>
                </div>
            </div>

            <!-- Processing state -->
            <div v-if="isProcessing" class="text-center py-20">
                <Loader class="w-8 h-8 text-violet mx-auto mb-4 animate-spin" />
                <p class="text-text-primary font-medium">Analizando el episodio...</p>
                <p class="text-text-muted text-sm mt-1">Claude está leyendo el script y generando la estructura de producción.</p>
                <p class="text-text-muted text-xs mt-3 font-mono">Puede tomar 30-60 segundos</p>
            </div>

            <!-- Error state -->
            <div v-else-if="result.status === 'failed'" class="bg-danger/10 border border-danger/30 rounded-xl p-6 text-center">
                <XCircle class="w-8 h-8 text-danger mx-auto mb-3" />
                <p class="text-danger font-medium">El análisis falló</p>
                <p class="text-text-muted text-sm mt-1">{{ result.error_message }}</p>
                <div class="flex justify-center gap-3 mt-4">
                    <Link
                        :href="`/episodes/${result.episode_id}`"
                        class="px-4 py-2 bg-surface-2 text-text-secondary text-sm rounded-lg hover:text-text-primary transition-colors"
                    >
                        Volver al episodio
                    </Link>
                    <button
                        @click="router.post(`/episodes/${result.episode_id}/ai-director`)"
                        class="flex items-center gap-1.5 px-4 py-2 bg-violet text-white text-sm font-medium rounded-lg hover:bg-violet/90 transition-colors"
                    >
                        <RefreshCw class="w-3.5 h-3.5" />
                        Reintentar
                    </button>
                </div>
            </div>

            <!-- Applied state -->
            <div v-else-if="result.status === 'applied'" class="bg-evergreen/10 border border-evergreen/30 rounded-xl p-6 text-center mb-6">
                <CheckCircle class="w-8 h-8 text-evergreen mx-auto mb-2" />
                <p class="text-evergreen font-medium">Estructura aplicada</p>
                <p class="text-text-muted text-sm mt-1">
                    Las escenas y shots fueron creados en el episodio.
                </p>
                <Link
                    :href="`/episodes/${result.episode_id}`"
                    class="inline-block mt-3 text-violet text-sm hover:underline"
                >
                    Ver episodio →
                </Link>
            </div>

            <!-- Completed — review structure -->
            <template v-if="result.status === 'completed' || result.status === 'applied'">
                <!-- Summary -->
                <div class="bg-surface-1 border border-violet/30 rounded-xl p-4 mb-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <Sparkles class="w-4 h-4 text-violet" />
                                <span class="text-xs font-semibold text-violet uppercase tracking-widest">Propuesta del AI Director</span>
                            </div>
                            <p class="text-sm text-text-primary">{{ result.proposed_structure?.summary }}</p>
                            <div class="flex gap-4 mt-3">
                                <span class="text-xs text-text-muted">
                                    <span class="text-text-primary font-medium">{{ result.proposed_structure?.total_scenes }}</span> escenas
                                </span>
                                <span class="text-xs text-text-muted">
                                    <span class="text-text-primary font-medium">{{ result.proposed_structure?.total_shots }}</span> shots
                                </span>
                            </div>
                        </div>

                        <div v-if="result.status === 'completed'" class="flex gap-2 shrink-0">
                            <button
                                @click="deleteResult"
                                class="p-2 text-text-muted hover:text-danger transition-colors rounded-lg hover:bg-danger/10"
                                title="Descartar"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                            <!-- Pre-render si no hay renders aún -->
                            <button
                                v-if="!hasPreviewRenders()"
                                @click="preRenderAll"
                                :disabled="preRendering"
                                class="flex items-center gap-1.5 px-4 py-2 bg-amber/10 text-amber text-sm font-semibold rounded-lg hover:bg-amber/20 disabled:opacity-50 transition-colors"
                            >
                                <Sparkles v-if="!preRendering" class="w-3.5 h-3.5" />
                                <Loader v-else class="w-3.5 h-3.5 animate-spin" />
                                {{ preRendering ? 'Enviando...' : 'Ver renders' }}
                            </button>
                            <!-- Spinner mientras renderizan -->
                            <div v-else-if="hasPreviewRenders() && !allRendersComplete()" class="flex items-center gap-1.5 px-3 py-2 text-xs text-amber font-mono">
                                <Loader class="w-3.5 h-3.5 animate-spin" />
                                Renderizando...
                            </div>
                            <button
                                @click="applyStructure"
                                class="flex items-center gap-1.5 px-4 py-2 bg-violet text-white text-sm font-semibold rounded-lg hover:bg-violet/90 transition-colors"
                            >
                                <Play class="w-3.5 h-3.5" />
                                Aplicar estructura
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ghost Director profile used -->
                <div
                    v-if="result.ghost_profile_snapshot?.total_approved_renders > 0"
                    class="bg-surface-1 border border-border rounded-xl p-4 mb-6"
                >
                    <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-2">Estilo creativo aprendido (Ghost Director)</p>
                    <div class="flex flex-wrap gap-2">
                        <div v-if="Object.keys(result.ghost_profile_snapshot.top_camera_styles ?? {}).length">
                            <p class="text-[10px] text-text-muted mb-1">Cámara</p>
                            <div class="flex gap-1 flex-wrap">
                                <span
                                    v-for="(count, name) in result.ghost_profile_snapshot.top_camera_styles"
                                    :key="name"
                                    class="text-[10px] px-2 py-0.5 bg-amber/10 text-amber rounded"
                                >
                                    {{ name }} ({{ count }})
                                </span>
                            </div>
                        </div>
                        <div v-if="Object.keys(result.ghost_profile_snapshot.top_visual_styles ?? {}).length">
                            <p class="text-[10px] text-text-muted mb-1">Visual</p>
                            <div class="flex gap-1 flex-wrap">
                                <span
                                    v-for="(count, name) in result.ghost_profile_snapshot.top_visual_styles"
                                    :key="name"
                                    class="text-[10px] px-2 py-0.5 bg-violet/10 text-violet rounded"
                                >
                                    {{ name }} ({{ count }})
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scenes tree -->
                <div class="space-y-4">
                    <div
                        v-for="(scene, si) in result.proposed_structure?.scenes"
                        :key="si"
                        class="bg-surface-1 border border-border rounded-xl overflow-hidden"
                    >
                        <!-- Scene header -->
                        <button
                            @click="toggleScene(si)"
                            class="w-full flex items-center justify-between p-4 text-left hover:bg-surface-2 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] font-mono text-text-muted bg-surface-2 px-2 py-0.5 rounded">
                                    ESC {{ scene.number }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-text-primary">{{ scene.title }}</p>
                                    <div class="flex items-center gap-3 mt-0.5">
                                        <span v-if="scene.location_name" class="flex items-center gap-1 text-xs text-text-muted">
                                            <MapPin class="w-3 h-3" />{{ scene.location_name }}
                                        </span>
                                        <span class="text-xs text-text-muted capitalize">{{ scene.mood }}</span>
                                        <span class="text-xs text-text-muted">{{ scene.shots?.length ?? 0 }} shots</span>
                                    </div>
                                </div>
                            </div>
                            <ChevronLeft
                                class="w-4 h-4 text-text-muted transition-transform"
                                :class="expandedScenes[si] ? '-rotate-90' : 'rotate-180'"
                            />
                        </button>

                        <!-- Scene description -->
                        <div v-if="expandedScenes[si] && scene.description" class="px-4 pb-3 -mt-1">
                            <p class="text-xs text-text-muted bg-surface-0 rounded-lg px-3 py-2">
                                {{ scene.description }}
                            </p>
                        </div>

                        <!-- Shots -->
                        <div v-if="expandedScenes[si]" class="px-4 pb-4 space-y-2">
                            <div
                                v-for="(shot, shi) in scene.shots"
                                :key="shi"
                                class="bg-surface-0 border border-border rounded-lg overflow-hidden"
                            >
                                <!-- Preview render image -->
                                <div v-if="hasPreviewRenders()" class="relative w-full aspect-video bg-surface-2">
                                    <!-- Completed: show image -->
                                    <img
                                        v-if="shotRender(si, shi)?.status === 'completed' && shotRender(si, shi)?.url"
                                        :src="shotRender(si, shi).url"
                                        :alt="shot.description"
                                        class="w-full h-full object-cover"
                                    />
                                    <!-- Queued / processing: spinner -->
                                    <div
                                        v-else-if="['queued', 'processing'].includes(shotRender(si, shi)?.status)"
                                        class="absolute inset-0 flex flex-col items-center justify-center gap-2"
                                    >
                                        <Loader class="w-5 h-5 text-amber animate-spin" />
                                        <span class="text-[10px] font-mono text-text-muted">Renderizando...</span>
                                    </div>
                                    <!-- Failed or no render yet -->
                                    <div v-else class="absolute inset-0 flex items-center justify-center">
                                        <Image class="w-6 h-6 text-surface-3" />
                                    </div>
                                    <!-- Shot number badge -->
                                    <span class="absolute top-1.5 left-1.5 text-[10px] font-mono bg-black/60 text-white px-1.5 py-0.5 rounded">
                                        S{{ shot.number }}
                                    </span>
                                </div>

                                <div class="p-3">
                                <div class="flex items-start gap-3">
                                    <!-- Shot type icon -->
                                    <div class="flex items-center gap-1.5 shrink-0 pt-0.5">
                                        <component
                                            :is="SHOT_TYPE_ICON[shot.shot_type] ?? Image"
                                            class="w-3.5 h-3.5"
                                            :class="SHOT_TYPE_COLOR[shot.shot_type] ?? 'text-text-muted'"
                                        />
                                        <span v-if="!hasPreviewRenders()" class="text-[10px] font-mono text-text-muted">S{{ shot.number }}</span>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-text-primary mb-1.5">{{ shot.description }}</p>

                                        <!-- Metadata row -->
                                        <div class="flex flex-wrap gap-1.5">
                                            <span :class="['text-[10px] px-1.5 py-0.5 rounded', PURPOSE_COLORS[shot.purpose] ?? 'bg-surface-2 text-text-muted']">
                                                {{ shot.purpose }}
                                            </span>
                                            <span v-if="shot.camera_style_name" class="text-[10px] px-1.5 py-0.5 bg-amber/10 text-amber rounded">
                                                {{ shot.camera_style_name }}
                                            </span>
                                            <span v-if="shot.visual_style_name" class="text-[10px] px-1.5 py-0.5 bg-violet/10 text-violet rounded">
                                                {{ shot.visual_style_name }}
                                            </span>
                                            <span
                                                v-for="char in (shot.character_names ?? [])"
                                                :key="char"
                                                class="flex items-center gap-1 text-[10px] px-1.5 py-0.5 bg-blue-500/10 text-blue-400 rounded"
                                            >
                                                <Users class="w-2.5 h-2.5" />{{ char }}
                                            </span>
                                        </div>

                                        <!-- Director notes -->
                                        <p v-if="shot.director_notes" class="text-[10px] text-text-muted mt-1.5 italic">
                                            {{ shot.director_notes }}
                                        </p>

                                        <!-- Dialogue -->
                                        <p v-if="shot.dialogue_text" class="text-[10px] text-teal-400 mt-1.5 italic border-l-2 border-teal-400/40 pl-2">
                                            "{{ shot.dialogue_text }}"
                                        </p>
                                    </div>
                                </div>
                                </div><!-- /.p-3 -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom apply button -->
                <div v-if="result.status === 'completed'" class="flex justify-end mt-6">
                    <button
                        @click="applyStructure"
                        class="flex items-center gap-2 px-6 py-3 bg-violet text-white font-semibold rounded-xl hover:bg-violet/90 transition-colors"
                    >
                        <Play class="w-4 h-4" />
                        Aplicar estructura al episodio
                    </button>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
