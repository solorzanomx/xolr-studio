<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ChevronLeft, Sparkles, Loader, Eye, TrendingUp, Link2,
    Plus, X, Trash2, Save, CheckCircle, AlertCircle
} from '@lucide/vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'

const props = defineProps({
    chapter:     Object,
    allChapters: Array,
    episodes:    Array,
})

// ── Editor ────────────────────────────────────────────────────
const saveStatus = ref('saved') // saved | unsaved | saving
let   autoSaveTimer = null

const editor = useEditor({
    content: props.chapter.content ?? '',
    extensions: [StarterKit],
    onUpdate: () => {
        saveStatus.value = 'unsaved'
        clearTimeout(autoSaveTimer)
        autoSaveTimer = setTimeout(saveContent, 2000)
    },
})

async function saveContent() {
    if (saveStatus.value === 'saved') return
    saveStatus.value = 'saving'
    await fetch(`/historia/capitulos/${props.chapter.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ content: editor.value?.getHTML() }),
    })
    saveStatus.value = 'saved'
}

// ── Draft notes ───────────────────────────────────────────────
const draftNotes = ref(props.chapter.draft_notes ?? '')
let   draftTimer = null
watch(draftNotes, () => {
    clearTimeout(draftTimer)
    draftTimer = setTimeout(saveDraft, 1500)
})
async function saveDraft() {
    await fetch(`/historia/capitulos/${props.chapter.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ draft_notes: draftNotes.value }),
    })
}

// ── Status ────────────────────────────────────────────────────
const localStatus = ref(props.chapter.status)
async function updateStatus(status) {
    localStatus.value = status
    await fetch(`/historia/capitulos/${props.chapter.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ status }),
    })
}

// ── AI: Expand ────────────────────────────────────────────────
const expanding    = ref(false)
const expandError  = ref('')
let   expandTimer  = null

async function expandWithAI() {
    expanding.value  = true
    expandError.value = ''
    await saveDraft()
    const res  = await fetch(`/historia/capitulos/${props.chapter.id}/expand`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({}),
    })
    const data = await res.json()
    if (data.ok) pollExpand()
    else { expandError.value = 'Error al iniciar la expansión'; expanding.value = false }
}

function pollExpand() {
    expandTimer = setInterval(async () => {
        const res  = await fetch(`/historia/capitulos/${props.chapter.id}/expand/status`, { headers: { 'Accept': 'application/json' } })
        const data = await res.json()
        if (data.status === 'completed') {
            clearInterval(expandTimer)
            expanding.value = false
            editor.value?.commands.setContent(data.result)
            saveStatus.value = 'unsaved'
            await saveContent()
        } else if (data.status === 'failed') {
            clearInterval(expandTimer)
            expanding.value   = false
            expandError.value = data.error ?? 'Error al expandir'
        }
    }, 3000)
}

// ── AI: Market Intel ──────────────────────────────────────────
const analyzing      = ref(false)
const marketResult   = ref(props.chapter.market_intel ?? null)
const marketError    = ref('')
let   marketTimer    = null

async function analyzeMarket() {
    analyzing.value  = true
    marketError.value = ''
    const res  = await fetch(`/historia/capitulos/${props.chapter.id}/market-intel`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({}),
    })
    const data = await res.json()
    if (data.ok) pollMarket()
    else { marketError.value = 'Error'; analyzing.value = false }
}

function pollMarket() {
    marketTimer = setInterval(async () => {
        const res  = await fetch(`/historia/capitulos/${props.chapter.id}/market-intel/status`, { headers: { 'Accept': 'application/json' } })
        const data = await res.json()
        if (data.status === 'completed') {
            clearInterval(marketTimer)
            analyzing.value  = false
            marketResult.value = data.result
        } else if (data.status === 'failed') {
            clearInterval(marketTimer)
            analyzing.value   = false
            marketError.value = data.error ?? 'Error'
        }
    }, 3000)
}

// ── AI: Interlinking ──────────────────────────────────────────
const interlinking       = ref(false)
const interlinkResult    = ref(props.chapter.interlinks ?? null)

async function generateInterlinking() {
    interlinking.value = true
    const res  = await fetch(`/historia/capitulos/${props.chapter.id}/interlinking`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({}),
    })
    const data = await res.json()
    interlinking.value = false
    if (data.ok) interlinkResult.value = data.result
}

// ── Pistas ────────────────────────────────────────────────────
const localClues    = ref([...(props.chapter.clues ?? [])])
const suggestingClues = ref(false)
const clueError      = ref('')
const showClueForm   = ref(false)
const newClue        = ref({ book_secret: '', visual_element: '', placement: 'background', viewer_feeling: '', reader_payoff: '' })

async function suggestClues() {
    suggestingClues.value = true
    clueError.value = ''
    const res  = await fetch(`/historia/capitulos/${props.chapter.id}/suggest-clues`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({}),
    })
    const data = await res.json()
    suggestingClues.value = false
    if (data.ok && data.clues?.length) {
        suggestedClues.value = data.clues
    } else {
        clueError.value = 'El capítulo necesita más contenido para sugerir pistas'
    }
}

const suggestedClues = ref([])

async function adoptSuggestedClue(clue) {
    const res  = await fetch(`/historia/capitulos/${props.chapter.id}/clues`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(clue),
    })
    const data = await res.json()
    if (data.ok) {
        localClues.value.push(data.clue)
        suggestedClues.value = suggestedClues.value.filter(c => c !== clue)
    }
}

async function storeClue() {
    const res  = await fetch(`/historia/capitulos/${props.chapter.id}/clues`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(newClue.value),
    })
    const data = await res.json()
    if (data.ok) {
        localClues.value.push(data.clue)
        newClue.value    = { book_secret: '', visual_element: '', placement: 'background', viewer_feeling: '', reader_payoff: '' }
        showClueForm.value = false
    }
}

async function deleteClue(id) {
    await fetch(`/historia/clues/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
    })
    localClues.value = localClues.value.filter(c => c.id !== id)
}

// ── Tab ───────────────────────────────────────────────────────
const activeTab = ref('write')

// ── Helpers ───────────────────────────────────────────────────
function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

const STATUS_STEPS = ['idea', 'draft', 'written', 'ready', 'published']
const STATUS_COLOR = {
    idea: 'text-text-muted', draft: 'text-amber', written: 'text-violet',
    ready: 'text-evergreen', published: 'text-emerald-400',
}

const SCORE_COLOR = (s) => s >= 8 ? 'text-emerald-400' : s >= 6 ? 'text-amber' : 'text-danger'

onBeforeUnmount(() => {
    clearTimeout(autoSaveTimer)
    clearTimeout(draftTimer)
    clearInterval(expandTimer)
    clearInterval(marketTimer)
    editor.value?.destroy()
})
</script>

<template>
    <AppLayout>
        <Head :title="`Cap. ${chapter.sort_order} — ${chapter.title}`" />

        <div class="flex flex-col h-[calc(100vh-56px)]">
            <!-- Top bar -->
            <div class="flex items-center gap-4 px-6 py-3 border-b border-border bg-surface-1 shrink-0">
                <Link href="/historia" class="text-text-muted hover:text-text-primary transition-colors">
                    <ChevronLeft class="w-5 h-5" />
                </Link>

                <div class="flex items-center gap-2">
                    <span class="text-sm font-mono text-amber/60">{{ chapter.sort_order }}.</span>
                    <h1 class="text-sm font-semibold text-text-primary">{{ chapter.title }}</h1>
                </div>

                <!-- Status stepper -->
                <div class="flex items-center gap-1 ml-2">
                    <button
                        v-for="step in STATUS_STEPS" :key="step"
                        @click="updateStatus(step)"
                        :class="['text-[10px] px-2 py-0.5 rounded transition-colors', localStatus === step ? 'bg-amber text-black font-semibold' : 'text-text-muted hover:text-text-primary']"
                    >
                        {{ step }}
                    </button>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <!-- Save indicator -->
                    <span class="text-[11px] text-text-muted font-mono flex items-center gap-1">
                        <Loader v-if="saveStatus === 'saving'" class="w-3 h-3 animate-spin" />
                        <CheckCircle v-else-if="saveStatus === 'saved'" class="w-3 h-3 text-evergreen" />
                        <span v-else class="w-1.5 h-1.5 rounded-full bg-amber inline-block" />
                        {{ saveStatus === 'saving' ? 'Guardando...' : saveStatus === 'saved' ? 'Guardado' : 'Sin guardar' }}
                    </span>

                    <!-- Tabs -->
                    <div class="flex gap-1 bg-surface-0 rounded-lg p-0.5">
                        <button v-for="tab in ['write', 'notes', 'pistas', 'market', 'links']" :key="tab"
                            @click="activeTab = tab"
                            :class="['text-[11px] px-3 py-1 rounded transition-colors capitalize', activeTab === tab ? 'bg-surface-2 text-text-primary' : 'text-text-muted hover:text-text-primary']"
                        >
                            {{ tab === 'write' ? 'Escribir' : tab === 'notes' ? 'Notas' : tab === 'pistas' ? 'Pistas' : tab === 'market' ? 'Mercado' : 'Links' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content area -->
            <div class="flex-1 overflow-auto">

                <!-- WRITE TAB -->
                <div v-if="activeTab === 'write'" class="max-w-3xl mx-auto px-6 py-8">
                    <div class="flex items-center justify-between mb-6">
                        <div />
                        <button
                            @click="expandWithAI"
                            :disabled="expanding"
                            class="flex items-center gap-1.5 px-4 py-2 bg-violet/10 text-violet text-sm font-semibold rounded-lg hover:bg-violet/20 disabled:opacity-50 transition-colors"
                        >
                            <Loader v-if="expanding" class="w-3.5 h-3.5 animate-spin" />
                            <Sparkles v-else class="w-3.5 h-3.5" />
                            {{ expanding ? 'Escribiendo...' : 'Expandir con IA' }}
                        </button>
                    </div>
                    <p v-if="expandError" class="text-xs text-danger mb-4">{{ expandError }}</p>

                    <EditorContent
                        :editor="editor"
                        class="prose prose-invert max-w-none focus:outline-none min-h-[400px] text-text-primary"
                    />
                </div>

                <!-- NOTES TAB -->
                <div v-if="activeTab === 'notes'" class="max-w-3xl mx-auto px-6 py-8">
                    <p class="text-xs text-text-muted mb-3">Tus notas y borradores — la IA los usa para expandir el capítulo</p>
                    <textarea
                        v-model="draftNotes"
                        placeholder="Escribe aquí tus recuerdos, ideas sueltas, frases, momentos específicos... Sin filtros. Esto es materia prima."
                        rows="20"
                        class="w-full bg-surface-1 border border-border text-sm text-text-primary placeholder:text-text-muted rounded-xl px-4 py-3 resize-none focus:outline-none focus:border-amber font-mono leading-relaxed"
                    />
                </div>

                <!-- PISTAS TAB -->
                <div v-if="activeTab === 'pistas'" class="max-w-3xl mx-auto px-6 py-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-sm font-semibold text-text-primary flex items-center gap-1.5">
                                <Eye class="w-4 h-4 text-amber" />
                                Pistas visuales enterradas
                            </h2>
                            <p class="text-xs text-text-muted mt-0.5">Secretos del libro que aparecen de fondo en el video — sin protagonismo</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="suggestClues"
                                :disabled="suggestingClues"
                                class="flex items-center gap-1.5 px-3 py-2 bg-violet/10 text-violet text-sm rounded-lg hover:bg-violet/20 disabled:opacity-50 transition-colors"
                            >
                                <Loader v-if="suggestingClues" class="w-3.5 h-3.5 animate-spin" />
                                <Sparkles v-else class="w-3.5 h-3.5" />
                                Sugerir con IA
                            </button>
                            <button
                                @click="showClueForm = !showClueForm"
                                class="flex items-center gap-1.5 px-3 py-2 bg-amber/10 text-amber text-sm rounded-lg hover:bg-amber/20 transition-colors"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                Nueva pista
                            </button>
                        </div>
                    </div>
                    <p v-if="clueError" class="text-xs text-danger mb-4">{{ clueError }}</p>

                    <!-- AI Suggestions -->
                    <div v-if="suggestedClues.length" class="mb-6 space-y-3">
                        <p class="text-[10px] font-mono text-violet uppercase tracking-widest">Sugerencias de la IA</p>
                        <div v-for="(clue, i) in suggestedClues" :key="i"
                            class="bg-violet/5 border border-violet/20 rounded-xl p-4"
                        >
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <p class="text-[10px] text-text-muted mb-1">Secreto del libro</p>
                                    <p class="text-xs text-text-primary">{{ clue.book_secret }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-text-muted mb-1">Elemento visual en video</p>
                                    <p class="text-xs text-amber">{{ clue.visual_element }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <p class="text-[10px] text-text-muted mb-1">El espectador siente</p>
                                    <p class="text-xs text-text-secondary italic">{{ clue.viewer_feeling }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-text-muted mb-1">El lector siente</p>
                                    <p class="text-xs text-emerald-400 italic">{{ clue.reader_payoff }}</p>
                                </div>
                            </div>
                            <button @click="adoptSuggestedClue(clue)"
                                class="text-[11px] px-3 py-1 bg-violet text-white rounded-lg hover:bg-violet/90 transition-colors"
                            >
                                Adoptar esta pista
                            </button>
                        </div>
                    </div>

                    <!-- Manual form -->
                    <div v-if="showClueForm" class="bg-surface-1 border border-amber/30 rounded-xl p-4 mb-4">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="text-[10px] text-text-muted block mb-1">Secreto del libro *</label>
                                <textarea v-model="newClue.book_secret" rows="2" placeholder="Lo que el libro revela..."
                                    class="w-full bg-surface-0 border border-border text-xs text-text-primary rounded-lg px-3 py-2 resize-none focus:outline-none focus:border-amber" />
                            </div>
                            <div>
                                <label class="text-[10px] text-text-muted block mb-1">Elemento visual en video *</label>
                                <textarea v-model="newClue.visual_element" rows="2" placeholder="El jarrón de cerámica azul en la repisa..."
                                    class="w-full bg-surface-0 border border-border text-xs text-text-primary rounded-lg px-3 py-2 resize-none focus:outline-none focus:border-amber" />
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="text-[10px] text-text-muted block mb-1">Posición</label>
                                <select v-model="newClue.placement" class="w-full bg-surface-0 border border-border text-xs text-text-primary rounded-lg px-2 py-1.5">
                                    <option value="background">Fondo</option>
                                    <option value="subtle">Sutil</option>
                                    <option value="passing">De pasada</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] text-text-muted block mb-1">El espectador siente</label>
                                <input v-model="newClue.viewer_feeling" placeholder="Curiosidad, extrañeza..." class="w-full bg-surface-0 border border-border text-xs text-text-primary rounded-lg px-2 py-1.5 focus:outline-none focus:border-amber" />
                            </div>
                            <div>
                                <label class="text-[10px] text-text-muted block mb-1">El lector siente</label>
                                <input v-model="newClue.reader_payoff" placeholder="¡Ese es el jarrón!" class="w-full bg-surface-0 border border-border text-xs text-text-primary rounded-lg px-2 py-1.5 focus:outline-none focus:border-amber" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="storeClue" class="px-4 py-2 bg-amber text-black text-xs font-semibold rounded-lg">Guardar pista</button>
                            <button @click="showClueForm = false" class="px-3 py-2 text-xs text-text-muted hover:text-text-primary">Cancelar</button>
                        </div>
                    </div>

                    <!-- Clues list -->
                    <div class="space-y-3">
                        <div v-for="clue in localClues" :key="clue.id"
                            class="bg-surface-1 border border-border rounded-xl p-4 group"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-[10px] px-2 py-0.5 bg-surface-2 text-text-muted rounded font-mono">
                                    {{ clue.placement === 'background' ? 'FONDO' : clue.placement === 'subtle' ? 'SUTIL' : 'DE PASADA' }}
                                </span>
                                <button @click="deleteClue(clue.id)" class="opacity-0 group-hover:opacity-100 text-text-muted hover:text-danger transition-all">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] text-text-muted mb-1">📖 Libro revela</p>
                                    <p class="text-xs text-text-primary">{{ clue.book_secret }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-text-muted mb-1">🎥 Video muestra</p>
                                    <p class="text-xs text-amber">{{ clue.visual_element }}</p>
                                </div>
                                <div v-if="clue.viewer_feeling">
                                    <p class="text-[10px] text-text-muted mb-1">👁 Espectador</p>
                                    <p class="text-xs text-text-secondary italic">{{ clue.viewer_feeling }}</p>
                                </div>
                                <div v-if="clue.reader_payoff">
                                    <p class="text-[10px] text-text-muted mb-1">📚 Lector</p>
                                    <p class="text-xs text-emerald-400 italic">{{ clue.reader_payoff }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-if="!localClues.length && !suggestedClues.length" class="text-center text-sm text-text-muted py-8">
                            No hay pistas aún — usa "Sugerir con IA" o crea una manualmente
                        </p>
                    </div>
                </div>

                <!-- MARKET TAB -->
                <div v-if="activeTab === 'market'" class="max-w-3xl mx-auto px-6 py-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-sm font-semibold text-text-primary flex items-center gap-1.5">
                                <TrendingUp class="w-4 h-4 text-emerald-400" />
                                Análisis de mercado YouTube
                            </h2>
                            <p class="text-xs text-text-muted mt-0.5">Potencial del capítulo como video</p>
                        </div>
                        <button @click="analyzeMarket" :disabled="analyzing"
                            class="flex items-center gap-1.5 px-4 py-2 bg-emerald-400/10 text-emerald-400 text-sm rounded-lg hover:bg-emerald-400/20 disabled:opacity-50 transition-colors"
                        >
                            <Loader v-if="analyzing" class="w-3.5 h-3.5 animate-spin" />
                            <Sparkles v-else class="w-3.5 h-3.5" />
                            {{ analyzing ? 'Analizando...' : marketResult ? 'Re-analizar' : 'Analizar mercado' }}
                        </button>
                    </div>
                    <p v-if="marketError" class="text-xs text-danger mb-4">{{ marketError }}</p>

                    <div v-if="marketResult" class="space-y-4">
                        <!-- Score -->
                        <div class="bg-surface-1 border border-border rounded-xl p-4 flex items-center gap-4">
                            <div class="text-center">
                                <p :class="['text-4xl font-bold', SCORE_COLOR(marketResult.viability_score)]">
                                    {{ marketResult.viability_score }}<span class="text-lg text-text-muted">/10</span>
                                </p>
                                <p class="text-[10px] text-text-muted mt-1">Viabilidad</p>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-text-primary">{{ marketResult.viability_reason }}</p>
                                <p v-if="marketResult.trending_angle" class="text-xs text-amber mt-2 italic">
                                    ↗ {{ marketResult.trending_angle }}
                                </p>
                            </div>
                        </div>

                        <!-- SEO Titles -->
                        <div class="bg-surface-1 border border-border rounded-xl p-4">
                            <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-3">Títulos SEO sugeridos</p>
                            <div class="space-y-2">
                                <div v-for="(title, i) in marketResult.seo_titles" :key="i"
                                    class="flex items-center gap-2 bg-surface-0 rounded-lg px-3 py-2"
                                >
                                    <span class="text-[10px] font-mono text-text-muted">{{ i + 1 }}</span>
                                    <p class="text-sm text-text-primary">{{ title }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Keywords + Format -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-surface-1 border border-border rounded-xl p-4">
                                <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-2">Keywords</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="kw in marketResult.keywords" :key="kw"
                                        class="text-[11px] px-2 py-0.5 bg-surface-2 text-text-secondary rounded"
                                    >{{ kw }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-1 border border-border rounded-xl p-4">
                                <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-2">Formato recomendado</p>
                                <p class="text-sm font-semibold text-amber capitalize">{{ marketResult.best_format }}</p>
                                <p class="text-xs text-text-muted mt-1">{{ marketResult.format_reason }}</p>
                            </div>
                        </div>

                        <!-- Hook + Thumbnail -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-surface-1 border border-border rounded-xl p-4">
                                <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-2">Hook primeros 30 seg</p>
                                <p class="text-xs text-text-primary italic">"{{ marketResult.hook_first_30_seconds }}"</p>
                            </div>
                            <div class="bg-surface-1 border border-border rounded-xl p-4">
                                <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-2">Concepto de thumbnail</p>
                                <p class="text-xs text-text-primary">{{ marketResult.thumbnail_concept }}</p>
                            </div>
                        </div>

                        <div v-if="marketResult.competitor_gap" class="bg-evergreen/5 border border-evergreen/20 rounded-xl p-4">
                            <p class="text-[10px] font-mono text-evergreen uppercase tracking-widest mb-1">Oportunidad única</p>
                            <p class="text-sm text-text-primary">{{ marketResult.competitor_gap }}</p>
                        </div>
                    </div>
                    <div v-else class="text-center py-16 text-text-muted">
                        <TrendingUp class="w-10 h-10 mx-auto mb-3 opacity-30" />
                        <p class="text-sm">Analiza el potencial de este capítulo en YouTube</p>
                    </div>
                </div>

                <!-- LINKS TAB -->
                <div v-if="activeTab === 'links'" class="max-w-3xl mx-auto px-6 py-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-sm font-semibold text-text-primary flex items-center gap-1.5">
                                <Link2 class="w-4 h-4 text-blue-400" />
                                Interlinking narrativo
                            </h2>
                            <p class="text-xs text-text-muted mt-0.5">Referencias a otros capítulos que harán que la gente quiera ver más</p>
                        </div>
                        <button @click="generateInterlinking" :disabled="interlinking || allChapters.length < 1"
                            class="flex items-center gap-1.5 px-4 py-2 bg-blue-500/10 text-blue-400 text-sm rounded-lg hover:bg-blue-500/20 disabled:opacity-50 transition-colors"
                        >
                            <Loader v-if="interlinking" class="w-3.5 h-3.5 animate-spin" />
                            <Sparkles v-else class="w-3.5 h-3.5" />
                            {{ interlinking ? 'Generando...' : 'Generar con IA' }}
                        </button>
                    </div>

                    <div v-if="interlinkResult?.links?.length" class="space-y-4">
                        <p v-if="interlinkResult.narrative_thread" class="text-xs text-blue-400 italic border-l-2 border-blue-400/30 pl-3 mb-4">
                            {{ interlinkResult.narrative_thread }}
                        </p>
                        <div v-for="link in interlinkResult.links" :key="link.chapter_id"
                            class="bg-surface-1 border border-border rounded-xl p-4"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <p class="text-sm font-medium text-blue-400">→ {{ link.chapter_title }}</p>
                                <span class="text-[10px] px-2 py-0.5 bg-surface-2 text-text-muted rounded font-mono">{{ link.moment_in_video }}</span>
                            </div>
                            <blockquote class="border-l-2 border-blue-400/30 pl-3 my-2">
                                <p class="text-sm text-text-primary italic">"{{ link.reference_line }}"</p>
                            </blockquote>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xs text-text-muted">{{ link.emotion }}</p>
                                <span v-if="link.youtube_card_text" class="text-[10px] px-2 py-0.5 bg-red-500/10 text-red-400 rounded font-mono">
                                    Card: "{{ link.youtube_card_text }}"
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-16 text-text-muted">
                        <Link2 class="w-10 h-10 mx-auto mb-3 opacity-30" />
                        <p class="text-sm">
                            {{ allChapters.length < 1 ? 'Necesitas más capítulos escritos para generar interlinking' : 'Genera las referencias narrativas entre capítulos' }}
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style>
.ProseMirror {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1rem;
    line-height: 1.8;
    color: #E8E8EA;
    outline: none;
}
.ProseMirror p { margin-bottom: 1.2em; }
.ProseMirror h2 { font-size: 1.4rem; font-weight: 700; margin: 2em 0 0.8em; color: #F59E0B; }
.ProseMirror blockquote {
    border-left: 3px solid #F59E0B;
    padding-left: 1rem;
    margin: 1.5em 0;
    color: #9CA3AF;
    font-style: italic;
}
.ProseMirror em { color: #9CA3AF; }
</style>
