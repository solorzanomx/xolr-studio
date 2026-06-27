<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ArrowLeft, Star, Sparkles, Pencil, Trash2, Plus, Image,
    Video, Tag, Clock, ChevronDown, ChevronUp, Check, Copy,
    Loader2
} from '@lucide/vue'

const props = defineProps({
    concept:        Object,
    thumbnailShots: Array,
    series:         Array,
})

// ── Meta form (title, status, rating, series) ─────────────────
const editingMeta = ref(false)
const metaForm = useForm({
    project_id:      props.concept.project_id,
    video_series_id: props.concept.video_series_id ?? '',
    title:           props.concept.title,
    hook:            props.concept.hook ?? '',
    status:          props.concept.status,
    rating:          props.concept.rating ?? null,
    structure:       props.concept.structure ?? null,
    youtube_seo:     props.concept.youtube_seo ?? null,
})

function setRating(n) {
    metaForm.rating = metaForm.rating === n ? null : n
}

function saveMeta() {
    metaForm.put(`/content-machine/concepts/${props.concept.id}`, {
        preserveState: true,
        onSuccess: () => { editingMeta.value = false },
    })
}

// ── AI Generation ─────────────────────────────────────────────
const generating = ref(false)
const generateForm = useForm({})

function generate() {
    generating.value = true
    generateForm.post(`/content-machine/concepts/${props.concept.id}/generate`, {
        preserveState: false,
        onFinish: () => { generating.value = false },
    })
}

// ── Structure display ─────────────────────────────────────────
const structure  = computed(() => props.concept.structure ?? null)
const seo        = computed(() => props.concept.youtube_seo ?? null)

const showStructure = ref(true)
const showSEO       = ref(true)
const showThumbnail = ref(true)

function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
}

// ── Thumbnail shots ────────────────────────────────────────────
const thumbForm = useForm({ description: '' })
const addingThumb = ref(false)

function addThumbnail() {
    thumbForm.post(`/content-machine/concepts/${props.concept.id}/thumbnail-shots`, {
        preserveState: false,
        onSuccess: () => { thumbForm.reset(); addingThumb.value = false },
    })
}

function deleteShot(id) {
    if (!confirm('¿Eliminar este thumbnail?')) return
    router.delete(`/shots/${id}`, { preserveState: false })
}

const statusColors = {
    idea:        'bg-surface-2 text-text-muted',
    scripted:    'bg-violet/20 text-violet',
    production:  'bg-amber/20 text-amber',
    published:   'bg-success/20 text-success',
}
const statusLabels = { idea: 'Idea', scripted: 'Guionizado', production: 'Producción', published: 'Publicado' }
</script>

<template>
    <Head :title="concept.title" />
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-start gap-4">
                <Link href="/content-machine" class="mt-1 p-1.5 text-text-muted hover:text-text-primary transition-colors shrink-0">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-full', statusColors[concept.status]]">
                            {{ statusLabels[concept.status] }}
                        </span>
                        <span v-if="concept.series" class="text-[11px] text-violet font-mono">{{ concept.series.name }}</span>
                        <span v-if="concept.project" class="text-[11px] text-text-muted">{{ concept.project.name }}</span>
                    </div>
                    <h1 class="text-2xl font-bold text-text-primary leading-tight">{{ concept.title }}</h1>
                    <!-- Star rating -->
                    <div class="flex items-center gap-0.5 mt-1.5">
                        <Star v-for="n in 5" :key="n" :class="['w-4 h-4', (concept.rating ?? 0) >= n ? 'text-amber fill-amber' : 'text-border']" />
                        <span v-if="concept.rating" class="ml-1.5 text-xs text-text-muted">{{ concept.rating }}/5</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        @click="generate"
                        :disabled="generating"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-violet text-white text-sm font-semibold rounded-lg hover:bg-violet/90 transition-colors disabled:opacity-50"
                    >
                        <Loader2 v-if="generating" class="w-4 h-4 animate-spin" />
                        <Sparkles v-else class="w-4 h-4" />
                        {{ generating ? 'Generando...' : 'Generar con IA' }}
                    </button>
                    <button @click="editingMeta = !editingMeta" class="p-2 text-text-muted hover:text-text-primary bg-surface-1 border border-border rounded-lg transition-colors">
                        <Pencil class="w-4 h-4" />
                    </button>
                    <Link :href="`/content-machine/concepts/${concept.id}/edit`" class="p-2 text-text-muted hover:text-danger bg-surface-1 border border-border rounded-lg transition-colors">
                        <Trash2 class="w-4 h-4" />
                    </Link>
                </div>
            </div>

            <!-- Flash success -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-2 px-4 py-3 bg-success/10 border border-success/20 rounded-xl text-sm text-success">
                <Check class="w-4 h-4 shrink-0" />
                {{ $page.props.flash.success }}
            </div>

            <!-- AI error -->
            <div v-if="$page.props.errors?.ai" class="px-4 py-3 bg-danger/10 border border-danger/20 rounded-xl text-sm text-danger">
                {{ $page.props.errors.ai }}
            </div>

            <!-- Meta Edit Panel -->
            <div v-if="editingMeta" class="bg-surface-1 border border-amber/30 rounded-xl p-5 space-y-4">
                <h3 class="text-sm font-semibold text-text-primary">Editar concepto</h3>
                <div>
                    <label class="block text-xs text-text-muted mb-1">Título</label>
                    <input v-model="metaForm.title" type="text" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                </div>
                <div>
                    <label class="block text-xs text-text-muted mb-1">Hook</label>
                    <textarea v-model="metaForm.hook" rows="2" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Serie</label>
                        <select v-model="metaForm.video_series_id" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="">Sin serie</option>
                            <option v-for="s in series" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Estado</label>
                        <select v-model="metaForm.status" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="idea">Idea</option>
                            <option value="scripted">Guionizado</option>
                            <option value="production">Producción</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Valoración</label>
                        <div class="flex items-center gap-0.5 mt-1">
                            <button v-for="n in 5" :key="n" type="button" @click="setRating(n)"
                                :class="['p-0.5 transition-colors', (metaForm.rating ?? 0) >= n ? 'text-amber' : 'text-border hover:text-amber/50']">
                                <Star :class="['w-4 h-4', (metaForm.rating ?? 0) >= n ? 'fill-amber' : '']" />
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="editingMeta = false" class="px-3 py-1.5 text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                    <button @click="saveMeta" :disabled="metaForm.processing" class="px-4 py-1.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors disabled:opacity-50">
                        {{ metaForm.processing ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </div>

            <!-- HOOK card -->
            <div v-if="concept.hook" class="bg-surface-1 border border-border rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Hook de apertura</p>
                    <button @click="copyToClipboard(concept.hook)" class="p-1 text-text-muted hover:text-text-primary transition-colors">
                        <Copy class="w-3.5 h-3.5" />
                    </button>
                </div>
                <p class="text-base text-text-primary leading-relaxed italic">"{{ concept.hook }}"</p>
            </div>

            <!-- STRUCTURE section -->
            <div v-if="structure" class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                <button
                    @click="showStructure = !showStructure"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-surface-2 transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <Clock class="w-4 h-4 text-amber" />
                        <span class="text-sm font-semibold text-text-primary">Estructura del video</span>
                        <span class="text-xs text-text-muted font-mono">{{ structure.sections?.length ?? 0 }} secciones</span>
                    </div>
                    <ChevronUp v-if="showStructure" class="w-4 h-4 text-text-muted" />
                    <ChevronDown v-else class="w-4 h-4 text-text-muted" />
                </button>

                <div v-if="showStructure" class="px-5 pb-5 space-y-4 border-t border-border">
                    <!-- Intro -->
                    <div v-if="structure.intro" class="pt-4">
                        <p class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-1.5">Intro</p>
                        <p class="text-sm text-text-secondary">{{ structure.intro }}</p>
                    </div>

                    <!-- Sections -->
                    <div v-if="structure.sections?.length">
                        <p class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">Secciones</p>
                        <div class="space-y-2">
                            <div
                                v-for="(section, i) in structure.sections"
                                :key="i"
                                class="flex gap-3 bg-surface-2 rounded-lg p-3"
                            >
                                <span class="w-6 h-6 rounded-full bg-amber/20 text-amber text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">{{ i + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-medium text-text-primary">{{ section.title }}</p>
                                        <span class="text-xs font-mono text-text-muted shrink-0">{{ section.duration_min }}min</span>
                                    </div>
                                    <p class="text-xs text-text-muted mt-0.5">{{ section.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div v-if="structure.tips?.length">
                        <p class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">Tips del destino</p>
                        <div class="grid grid-cols-1 gap-1.5">
                            <div v-for="(tip, i) in structure.tips" :key="i" class="flex gap-2 text-sm">
                                <span class="text-amber font-bold shrink-0 mt-0.5">✦</span>
                                <span><span class="font-medium text-text-primary">{{ tip.title }}:</span> <span class="text-text-secondary">{{ tip.content }}</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div v-if="structure.cta" class="bg-violet/10 border border-violet/20 rounded-lg p-3">
                        <p class="text-xs font-semibold text-violet uppercase tracking-wider mb-1">CTA final</p>
                        <p class="text-sm text-text-primary">{{ structure.cta }}</p>
                    </div>
                </div>
            </div>

            <!-- No structure yet -->
            <div v-else class="bg-surface-1 border border-dashed border-border rounded-xl p-8 text-center">
                <Sparkles class="w-8 h-8 text-text-muted mx-auto mb-2" />
                <p class="text-sm text-text-muted mb-3">Sin estructura generada</p>
                <button @click="generate" :disabled="generating" class="inline-flex items-center gap-2 px-4 py-2 bg-violet text-white text-sm font-semibold rounded-lg hover:bg-violet/90 transition-colors disabled:opacity-50">
                    <Sparkles class="w-3.5 h-3.5" />
                    Generar con IA
                </button>
            </div>

            <!-- SEO Assistant section -->
            <div v-if="seo" class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                <button
                    @click="showSEO = !showSEO"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-surface-2 transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <Video class="w-4 h-4 text-danger" />
                        <span class="text-sm font-semibold text-text-primary">SEO Assistant</span>
                        <span class="text-xs font-mono text-success">✓ generado</span>
                    </div>
                    <ChevronUp v-if="showSEO" class="w-4 h-4 text-text-muted" />
                    <ChevronDown v-else class="w-4 h-4 text-text-muted" />
                </button>

                <div v-if="showSEO" class="px-5 pb-5 space-y-4 border-t border-border">
                    <!-- Title + variants -->
                    <div class="pt-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Título optimizado</p>
                            <button @click="copyToClipboard(seo.title)" class="p-1 text-text-muted hover:text-text-primary transition-colors"><Copy class="w-3 h-3" /></button>
                        </div>
                        <p class="text-sm font-semibold text-text-primary bg-surface-2 rounded-lg px-3 py-2">{{ seo.title }}</p>
                        <div v-if="seo.title_variants?.length" class="mt-2 space-y-1">
                            <p class="text-xs text-text-muted">Variantes:</p>
                            <p v-for="(v, i) in seo.title_variants" :key="i" class="text-xs text-text-secondary bg-surface-2 rounded px-3 py-1.5">{{ v }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="seo.description">
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Descripción</p>
                            <button @click="copyToClipboard(seo.description)" class="p-1 text-text-muted hover:text-text-primary transition-colors"><Copy class="w-3 h-3" /></button>
                        </div>
                        <p class="text-sm text-text-secondary bg-surface-2 rounded-lg px-3 py-2.5 leading-relaxed whitespace-pre-wrap">{{ seo.description }}</p>
                    </div>

                    <!-- Tags -->
                    <div v-if="seo.tags?.length">
                        <div class="flex items-center gap-2 mb-2">
                            <Tag class="w-3.5 h-3.5 text-text-muted" />
                            <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Tags ({{ seo.tags.length }})</p>
                            <button @click="copyToClipboard(seo.tags.join(', '))" class="p-1 text-text-muted hover:text-text-primary transition-colors ml-auto"><Copy class="w-3 h-3" /></button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="tag in seo.tags" :key="tag" class="text-xs bg-surface-2 border border-border rounded-full px-2.5 py-0.5 text-text-secondary">{{ tag }}</span>
                        </div>
                    </div>

                    <!-- Hashtags -->
                    <div v-if="seo.hashtags?.length">
                        <p class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">Hashtags</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="h in seo.hashtags" :key="h" class="text-xs text-violet bg-violet/10 border border-violet/20 rounded-full px-2.5 py-0.5">{{ h }}</span>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div v-if="seo.timestamps?.length">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Timestamps</p>
                            <button @click="copyToClipboard(seo.timestamps.map(t => t.time + ' ' + t.label).join('\n'))" class="p-1 text-text-muted hover:text-text-primary transition-colors"><Copy class="w-3 h-3" /></button>
                        </div>
                        <div class="space-y-1">
                            <div v-for="ts in seo.timestamps" :key="ts.time" class="flex items-center gap-3 text-sm">
                                <span class="font-mono text-amber text-xs w-10 shrink-0">{{ ts.time }}</span>
                                <span class="text-text-secondary">{{ ts.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thumbnail Studio section -->
            <div class="bg-surface-1 border border-border rounded-xl overflow-hidden">
                <button
                    @click="showThumbnail = !showThumbnail"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-surface-2 transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <Image class="w-4 h-4 text-amber" />
                        <span class="text-sm font-semibold text-text-primary">Thumbnail Studio</span>
                        <span class="text-xs text-text-muted font-mono">{{ thumbnailShots.length }} variante{{ thumbnailShots.length !== 1 ? 's' : '' }}</span>
                    </div>
                    <ChevronUp v-if="showThumbnail" class="w-4 h-4 text-text-muted" />
                    <ChevronDown v-else class="w-4 h-4 text-text-muted" />
                </button>

                <div v-if="showThumbnail" class="px-5 pb-5 border-t border-border">
                    <div class="pt-4 grid grid-cols-2 gap-3">
                        <!-- Existing thumbnail shots -->
                        <div
                            v-for="shot in thumbnailShots"
                            :key="shot.id"
                            class="bg-surface-2 border border-border rounded-xl p-3 group"
                        >
                            <div class="aspect-video bg-surface-3 rounded-lg mb-2 flex items-center justify-center">
                                <Image class="w-6 h-6 text-text-muted" />
                            </div>
                            <p class="text-xs text-text-secondary line-clamp-2">{{ shot.description }}</p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-[10px] font-mono text-text-muted">16:9 · Draft</span>
                                <button @click="deleteShot(shot.id)" class="p-0.5 text-text-muted hover:text-danger opacity-0 group-hover:opacity-100 transition-all">
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </div>
                        </div>

                        <!-- Add new thumbnail -->
                        <div v-if="!addingThumb">
                            <button
                                @click="addingThumb = true"
                                class="w-full aspect-video border-2 border-dashed border-border rounded-xl flex flex-col items-center justify-center gap-1.5 hover:border-amber hover:bg-amber/5 transition-colors"
                            >
                                <Plus class="w-5 h-5 text-text-muted" />
                                <span class="text-xs text-text-muted">Añadir variante</span>
                            </button>
                        </div>

                        <div v-else class="bg-surface-2 border border-amber/30 rounded-xl p-3">
                            <p class="text-xs font-medium text-text-secondary mb-2">Descripción del thumbnail</p>
                            <textarea
                                v-model="thumbForm.description"
                                rows="3"
                                placeholder="Ej: Host con expresión de asombro, mercado de Oaxaca al fondo, luz dorada"
                                class="w-full bg-surface-1 border border-border rounded-lg px-2.5 py-2 text-xs text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none"
                                autofocus
                            />
                            <div class="flex gap-1.5 mt-2">
                                <button @click="addThumbnail" :disabled="thumbForm.processing" class="flex-1 bg-amber text-surface-0 text-xs font-semibold rounded-lg py-1.5 hover:bg-amber/90 transition-colors disabled:opacity-50">
                                    Añadir
                                </button>
                                <button @click="addingThumb = false; thumbForm.reset()" class="flex-1 bg-surface-1 text-text-muted text-xs rounded-lg py-1.5 hover:text-text-primary transition-colors">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-text-muted">Los thumbnails se renderizan en Fase 10 con la Render Farm. Aquí se definen las variantes para A/B testing.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
