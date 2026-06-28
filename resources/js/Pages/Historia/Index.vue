<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    BookOpen, Plus, Sparkles, Loader, X, Tag, CheckCircle,
    ChevronRight, FileText, Eye, TrendingUp, Lightbulb
} from '@lucide/vue'

const props = defineProps({
    book:     Object,
    chapters: Array,
    ideas:    Array,
    episodes: Array,
})

// ── Ideas ──────────────────────────────────────────────────────
const newIdea    = ref('')
const newTag     = ref('')
const savingIdea = ref(false)
const localIdeas = ref([...props.ideas])

const TAGS = ['infancia', 'trabajo', 'amor', 'crisis', 'logro', 'familia', 'viaje', 'aprendizaje', 'otro']

async function submitIdea() {
    if (!newIdea.value.trim()) return
    savingIdea.value = true
    try {
        const res  = await fetch('/historia/ideas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({ content: newIdea.value, tag: newTag.value }),
        })
        const data = await res.json()
        if (data.ok) {
            localIdeas.value.unshift(data.idea)
            newIdea.value = ''
            newTag.value  = ''
        }
    } finally {
        savingIdea.value = false
    }
}

async function deleteIdea(id) {
    await fetch(`/historia/ideas/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
    })
    localIdeas.value = localIdeas.value.filter(i => i.id !== id)
}

// ── Organizar con IA ──────────────────────────────────────────
const organizing    = ref(false)
const organizeResult = ref(null)
const organizeError  = ref('')
let   organizePollTimer = null

async function organizeIdeas() {
    organizing.value    = true
    organizeError.value = ''
    try {
        const res  = await fetch('/historia/organize', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
        const data = await res.json()
        if (data.ok) pollOrganize()
    } catch (e) {
        organizeError.value = 'Error al conectar'
        organizing.value = false
    }
}

function pollOrganize() {
    if (organizePollTimer) clearInterval(organizePollTimer)
    organizePollTimer = setInterval(async () => {
        const res  = await fetch('/historia/organize/status', { headers: { 'Accept': 'application/json' } })
        const data = await res.json()
        if (data.status === 'completed') {
            clearInterval(organizePollTimer)
            organizePollTimer = null
            organizing.value  = false
            organizeResult.value = data.result
        } else if (data.status === 'failed') {
            clearInterval(organizePollTimer)
            organizePollTimer = null
            organizing.value  = false
            organizeError.value = data.error ?? 'Error al organizar'
        }
    }, 3000)
}

// ── Nuevo capítulo ────────────────────────────────────────────
const newChapterTitle = ref('')
const showNewChapter  = ref(false)

function createChapter() {
    if (!newChapterTitle.value.trim()) return
    router.post('/historia/capitulos', { title: newChapterTitle.value })
}

// ── Helpers ───────────────────────────────────────────────────
function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

const STATUS_COLOR = {
    idea:      'bg-surface-3 text-text-muted',
    draft:     'bg-amber/10 text-amber',
    written:   'bg-violet/10 text-violet',
    ready:     'bg-evergreen/10 text-evergreen',
    published: 'bg-emerald-400/10 text-emerald-400',
}
const STATUS_LABEL = {
    idea: 'Idea', draft: 'Borrador', written: 'Escrito', ready: 'Listo', published: 'Publicado',
}

const unconvertedIdeas = computed(() => localIdeas.value.filter(i => !i.converted))
</script>

<template>
    <AppLayout>
        <Head title="Historia" />

        <div class="max-w-5xl mx-auto px-6 py-8">
            <!-- Header -->
            <div class="flex items-start justify-between mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <BookOpen class="w-5 h-5 text-amber" />
                        <h1 class="text-xl font-semibold text-text-primary">{{ book.title }}</h1>
                    </div>
                    <p v-if="book.logline" class="text-sm text-text-muted">{{ book.logline }}</p>
                    <p v-else class="text-sm text-text-muted italic">Sin logline — edita el libro para agregar uno</p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="showNewChapter = true"
                        class="flex items-center gap-1.5 px-4 py-2 bg-amber text-black text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        Nuevo capítulo
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- LEFT: Ideas Board -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-surface-1 border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-semibold text-text-primary flex items-center gap-1.5">
                                <Lightbulb class="w-4 h-4 text-amber" />
                                Ideas
                                <span class="text-[10px] font-mono text-text-muted ml-1">{{ unconvertedIdeas.length }}</span>
                            </h2>
                            <button
                                v-if="unconvertedIdeas.length >= 3"
                                @click="organizeIdeas"
                                :disabled="organizing"
                                class="flex items-center gap-1 text-[11px] px-2.5 py-1 bg-violet/10 text-violet rounded-lg hover:bg-violet/20 disabled:opacity-50 transition-colors"
                            >
                                <Loader v-if="organizing" class="w-3 h-3 animate-spin" />
                                <Sparkles v-else class="w-3 h-3" />
                                {{ organizing ? 'Organizando...' : 'Organizar con IA' }}
                            </button>
                        </div>

                        <!-- Input -->
                        <div class="mb-3">
                            <textarea
                                v-model="newIdea"
                                @keydown.meta.enter="submitIdea"
                                placeholder="Escribe un recuerdo, momento, frase... (⌘+Enter para guardar)"
                                rows="3"
                                class="w-full bg-surface-0 border border-border text-sm text-text-primary placeholder:text-text-muted rounded-lg px-3 py-2 resize-none focus:outline-none focus:border-amber"
                            />
                            <div class="flex items-center justify-between mt-2">
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="tag in TAGS" :key="tag"
                                        @click="newTag = newTag === tag ? '' : tag"
                                        :class="['text-[10px] px-2 py-0.5 rounded-full border transition-colors', newTag === tag ? 'bg-amber text-black border-amber' : 'border-border text-text-muted hover:border-amber/50']"
                                    >
                                        {{ tag }}
                                    </button>
                                </div>
                                <button
                                    @click="submitIdea"
                                    :disabled="!newIdea.trim() || savingIdea"
                                    class="px-3 py-1 bg-amber text-black text-xs font-semibold rounded-lg disabled:opacity-40 transition-colors"
                                >
                                    Guardar
                                </button>
                            </div>
                        </div>

                        <!-- Ideas list -->
                        <div class="space-y-2 max-h-[420px] overflow-y-auto">
                            <div
                                v-for="idea in unconvertedIdeas" :key="idea.id"
                                class="group flex items-start gap-2 bg-surface-0 border border-border rounded-lg p-2.5 hover:border-amber/30 transition-colors"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-text-primary leading-relaxed">{{ idea.content }}</p>
                                    <span v-if="idea.tag" class="inline-block mt-1 text-[10px] px-1.5 py-0.5 bg-amber/10 text-amber rounded">
                                        {{ idea.tag }}
                                    </span>
                                </div>
                                <button @click="deleteIdea(idea.id)" class="shrink-0 opacity-0 group-hover:opacity-100 text-text-muted hover:text-danger transition-all">
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <p v-if="!unconvertedIdeas.length" class="text-xs text-text-muted text-center py-4">
                                Escribe tu primera idea arriba
                            </p>
                        </div>
                    </div>

                    <!-- Organize result -->
                    <div v-if="organizeResult" class="bg-violet/5 border border-violet/20 rounded-xl p-4">
                        <p class="text-[10px] font-mono text-violet uppercase tracking-widest mb-3">Propuesta de estructura IA</p>
                        <p v-if="organizeResult.narrative_spine" class="text-xs text-text-secondary italic mb-3 border-l-2 border-violet/30 pl-2">
                            {{ organizeResult.narrative_spine }}
                        </p>
                        <div class="space-y-2">
                            <div
                                v-for="(ch, i) in organizeResult.chapters" :key="i"
                                class="bg-surface-1 rounded-lg p-3"
                            >
                                <p class="text-xs font-semibold text-text-primary">{{ ch.title }}</p>
                                <p class="text-[11px] text-text-muted mt-1">{{ ch.premise }}</p>
                                <p v-if="ch.hook" class="text-[11px] text-amber mt-1.5 italic">"{{ ch.hook }}"</p>
                                <button
                                    @click="router.post('/historia/capitulos', { title: ch.title })"
                                    class="mt-2 text-[10px] px-2 py-1 bg-violet/10 text-violet rounded hover:bg-violet/20 transition-colors"
                                >
                                    + Crear capítulo
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-if="organizeError" class="text-xs text-danger">{{ organizeError }}</p>
                </div>

                <!-- RIGHT: Chapters -->
                <div class="lg:col-span-3">
                    <h2 class="text-sm font-semibold text-text-primary mb-3 flex items-center gap-1.5">
                        <FileText class="w-4 h-4 text-text-muted" />
                        Capítulos
                        <span class="text-[10px] font-mono text-text-muted ml-1">{{ chapters.length }}</span>
                    </h2>

                    <!-- New chapter form -->
                    <div v-if="showNewChapter" class="mb-3 flex gap-2">
                        <input
                            v-model="newChapterTitle"
                            @keydown.enter="createChapter"
                            @keydown.escape="showNewChapter = false"
                            placeholder="Título del capítulo..."
                            class="flex-1 bg-surface-1 border border-amber text-sm text-text-primary placeholder:text-text-muted rounded-lg px-3 py-2 focus:outline-none"
                            autofocus
                        />
                        <button @click="createChapter" class="px-4 py-2 bg-amber text-black text-sm font-semibold rounded-lg">
                            Crear
                        </button>
                        <button @click="showNewChapter = false" class="p-2 text-text-muted hover:text-text-primary">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Chapters list -->
                    <div class="space-y-2">
                        <Link
                            v-for="chapter in chapters" :key="chapter.id"
                            :href="`/historia/capitulos/${chapter.id}`"
                            class="group flex items-center gap-4 bg-surface-1 border border-border rounded-xl p-4 hover:border-amber/40 transition-colors"
                        >
                            <span class="text-2xl font-display text-amber/40 shrink-0 w-8 text-center leading-none">
                                {{ chapter.sort_order }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-text-primary">{{ chapter.title }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span :class="['text-[10px] px-1.5 py-0.5 rounded', STATUS_COLOR[chapter.status] ?? 'bg-surface-3 text-text-muted']">
                                        {{ STATUS_LABEL[chapter.status] }}
                                    </span>
                                    <span v-if="chapter.clues_count" class="flex items-center gap-1 text-[10px] text-text-muted">
                                        <Eye class="w-2.5 h-2.5" />
                                        {{ chapter.clues_count }} pistas
                                    </span>
                                    <span v-if="chapter.market_intel?.viability_score" class="flex items-center gap-1 text-[10px] text-emerald-400">
                                        <TrendingUp class="w-2.5 h-2.5" />
                                        {{ chapter.market_intel.viability_score }}/10 mercado
                                    </span>
                                </div>
                            </div>
                            <ChevronRight class="w-4 h-4 text-text-muted group-hover:text-amber transition-colors shrink-0" />
                        </Link>

                        <button
                            v-if="!chapters.length"
                            @click="showNewChapter = true"
                            class="w-full flex flex-col items-center gap-2 py-12 border border-dashed border-border rounded-xl text-text-muted hover:border-amber/40 hover:text-amber transition-colors"
                        >
                            <BookOpen class="w-8 h-8" />
                            <p class="text-sm">Crea tu primer capítulo</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
