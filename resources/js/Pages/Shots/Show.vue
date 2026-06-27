<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ArrowLeft, Wand2, Clock, Zap, Copy, Check,
    ChevronDown, History, UserPlus, UserMinus,
    RefreshCw, Save
} from '@lucide/vue'

const props = defineProps({
    shot: Object,
    backUrl: String,
    availableCharacters: Array,
    cameraStyles: Array,
    visualStyles: Array,
    formatPresets: Array,
})

// ────────────────────────────────────────────
// Quality tiers
// ────────────────────────────────────────────
const TIERS = [
    {
        key: 'draft',
        label: 'Draft',
        model: 'FLUX Schnell',
        cost: 0.005,
        time: '~20s',
        color: 'text-text-secondary',
        bg: 'bg-surface-2',
        border: 'border-border',
    },
    {
        key: 'standard',
        label: 'Standard',
        model: 'FLUX Dev',
        cost: 0.025,
        time: '~60s',
        color: 'text-amber',
        bg: 'bg-amber/5',
        border: 'border-amber/30',
    },
    {
        key: 'final',
        label: 'Final',
        model: 'FLUX Dev + Upscale 4×',
        cost: 0.08,
        time: '~4min',
        color: 'text-violet',
        bg: 'bg-violet/5',
        border: 'border-violet/30',
    },
]

const selectedTier = ref('draft')

// ────────────────────────────────────────────
// Section collapse state
// ────────────────────────────────────────────
const showPromptHistory = ref(false)
const showAddCharacter = ref(false)

// ────────────────────────────────────────────
// Edit forms
// ────────────────────────────────────────────
const editForm = useForm({
    description:      props.shot.description ?? '',
    shot_type:        props.shot.shot_type ?? 'image',
    purpose:          props.shot.purpose ?? 'narrative',
    dialogue_text:    props.shot.dialogue_text ?? '',
    director_notes:   props.shot.director_notes ?? '',
    duration_seconds: props.shot.duration_seconds ?? '',
    status:           props.shot.status ?? 'draft',
    camera_style_id:  props.shot.camera_style_id ?? '',
    visual_style_id:  props.shot.visual_style_id ?? '',
    format_preset_id: props.shot.format_preset_id ?? '',
})

const saveShot = () => {
    editForm.put(`/shots/${props.shot.id}`, { preserveScroll: true })
}

// ────────────────────────────────────────────
// Prompt manual edit
// ────────────────────────────────────────────
const activePrompt = computed(() => props.shot.prompts?.find(p => p.is_active) ?? null)
const editingPrompt = ref(false)
const promptForm = useForm({
    positive_prompt: activePrompt.value?.positive_prompt ?? '',
    negative_prompt: activePrompt.value?.negative_prompt ?? '',
})

const composePrompt = () => {
    router.post(`/shots/${props.shot.id}/compose-prompt`, {}, { preserveScroll: true })
}

const savePromptManual = () => {
    promptForm.post(`/shots/${props.shot.id}/save-prompt`, {
        preserveScroll: true,
        onSuccess: () => { editingPrompt.value = false },
    })
}

// ────────────────────────────────────────────
// Characters
// ────────────────────────────────────────────
const charForm = useForm({
    character_id: '',
    outfit_id:    '',
})

const selectedCharacterOutfits = computed(() => {
    if (!charForm.character_id) return []
    const ch = props.availableCharacters.find(c => String(c.id) === String(charForm.character_id))
    return ch?.outfits ?? []
})

const addCharacter = () => {
    charForm.post(`/shots/${props.shot.id}/characters`, {
        preserveScroll: true,
        onSuccess: () => {
            charForm.reset()
            showAddCharacter.value = false
        },
    })
}

const removeCharacter = (characterId) => {
    router.delete(`/shots/${props.shot.id}/characters/${characterId}`, { preserveScroll: true })
}

// ────────────────────────────────────────────
// Copy helper
// ────────────────────────────────────────────
const copied = ref(null)
const copyText = (text, key) => {
    navigator.clipboard.writeText(text)
    copied.value = key
    setTimeout(() => { copied.value = null }, 1500)
}

// ────────────────────────────────────────────
// Color map for prompt sources
// ────────────────────────────────────────────
const SOURCE_CLASSES = {
    lora:        'bg-violet/10 text-violet border-violet/20',
    character:   'bg-blue-500/10 text-blue-400 border-blue-400/20',
    outfit:      'bg-amber/10 text-amber border-amber/20',
    location:    'bg-green-500/10 text-green-400 border-green-400/20',
    lighting:    'bg-teal-500/10 text-teal-400 border-teal-400/20',
    camera:      'bg-orange-500/10 text-orange-400 border-orange-400/20',
    style:       'bg-purple-500/10 text-purple-400 border-purple-400/20',
    description: 'bg-amber/10 text-amber border-amber/20',
    dialogue:    'bg-pink-500/10 text-pink-400 border-pink-400/20',
    notes:       'bg-red-500/10 text-red-400 border-red-400/20',
    purpose:     'bg-surface-3 text-text-muted border-border',
}

const STATUS_LABELS = {
    draft:           'Borrador',
    prompt_ready:    'Prompt listo',
    rendering:       'Renderizando',
    audio_pending:   'Audio pendiente',
    lip_sync_pending:'Lip sync pendiente',
    completed:       'Completado',
    approved:        'Aprobado',
}

const STATUS_COLORS = {
    draft:           'bg-surface-3 text-text-muted',
    prompt_ready:    'bg-amber/10 text-amber',
    rendering:       'bg-violet/10 text-violet',
    audio_pending:   'bg-blue-500/10 text-blue-400',
    lip_sync_pending:'bg-orange-500/10 text-orange-400',
    completed:       'bg-green-500/10 text-green-400',
    approved:        'bg-green-600/20 text-green-300',
}
</script>

<template>
    <AppLayout>
        <!-- Header -->
        <div class="flex items-center gap-4 px-6 pt-6 pb-4 border-b border-border">
            <Link :href="backUrl" class="text-text-muted hover:text-text-primary transition-colors">
                <ArrowLeft class="w-4 h-4" />
            </Link>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs text-text-muted">Shot #{{ shot.number ?? shot.id }}</span>
                    <span
                        :class="['text-xs px-2 py-0.5 rounded-full font-medium', STATUS_COLORS[shot.status] ?? 'bg-surface-3 text-text-muted']"
                    >{{ STATUS_LABELS[shot.status] ?? shot.status }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-surface-2 text-text-secondary font-mono">{{ shot.shot_type }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-surface-2 text-text-secondary">{{ shot.purpose }}</span>
                </div>
                <p class="text-text-secondary text-sm truncate mt-0.5">{{ shot.description || 'Sin descripción' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_380px] gap-6 p-6">

            <!-- LEFT COLUMN: Prompt Engine -->
            <div class="space-y-4">

                <!-- Active prompt card -->
                <div class="bg-surface-1 border border-border rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h2 class="text-sm font-semibold text-text-primary flex items-center gap-2">
                            <Wand2 class="w-4 h-4 text-violet" />
                            Prompt Engine
                        </h2>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="activePrompt"
                                @click="copyText(activePrompt.positive_prompt, 'prompt')"
                                class="flex items-center gap-1.5 text-xs text-text-muted hover:text-text-primary transition-colors"
                            >
                                <Check v-if="copied === 'prompt'" class="w-3 h-3 text-green-400" />
                                <Copy v-else class="w-3 h-3" />
                                Copiar
                            </button>
                            <button
                                v-if="activePrompt && !editingPrompt"
                                @click="editingPrompt = true"
                                class="text-xs text-text-muted hover:text-amber transition-colors"
                            >Editar</button>
                            <button
                                @click="composePrompt"
                                class="flex items-center gap-1.5 text-xs bg-violet/10 text-violet hover:bg-violet/20 transition-colors px-2.5 py-1.5 rounded-md font-medium"
                            >
                                <RefreshCw class="w-3 h-3" />
                                {{ activePrompt ? 'Recomponer' : 'Componer prompt' }}
                            </button>
                        </div>
                    </div>

                    <!-- Prompt display / edit -->
                    <div class="p-4 space-y-3">
                        <!-- Sources preview (colored chips) -->
                        <div v-if="activePrompt?.sources?.length && !editingPrompt" class="flex flex-wrap gap-1.5">
                            <span
                                v-for="seg in activePrompt.sources"
                                :key="seg.key + seg.label"
                                :class="['inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded border font-mono', SOURCE_CLASSES[seg.key] ?? 'bg-surface-2 text-text-secondary border-border']"
                            >
                                <span class="font-semibold opacity-60">{{ seg.label }}</span>
                                <span class="opacity-80 truncate max-w-[120px]">{{ seg.text }}</span>
                            </span>
                        </div>

                        <!-- Full positive prompt text -->
                        <div v-if="activePrompt && !editingPrompt">
                            <p class="text-xs text-text-muted font-mono mb-1">Prompt positivo v{{ activePrompt.version }}</p>
                            <div class="text-sm text-text-primary bg-surface-0 rounded p-3 font-mono leading-relaxed border border-border">
                                {{ activePrompt.positive_prompt }}
                            </div>
                        </div>

                        <!-- Negative prompt -->
                        <div v-if="activePrompt && !editingPrompt && activePrompt.negative_prompt">
                            <p class="text-xs text-text-muted font-mono mb-1">Negativo</p>
                            <div class="text-xs text-text-muted bg-surface-0 rounded p-3 font-mono leading-relaxed border border-border">
                                {{ activePrompt.negative_prompt }}
                            </div>
                        </div>

                        <!-- Manual edit form -->
                        <div v-if="editingPrompt" class="space-y-3">
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Prompt positivo</label>
                                <textarea
                                    v-model="promptForm.positive_prompt"
                                    rows="5"
                                    class="w-full text-sm font-mono bg-surface-0 border border-border rounded p-3 text-text-primary resize-y focus:outline-none focus:border-violet/50"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-text-muted mb-1">Negativo</label>
                                <textarea
                                    v-model="promptForm.negative_prompt"
                                    rows="3"
                                    class="w-full text-sm font-mono bg-surface-0 border border-border rounded p-3 text-text-muted resize-y focus:outline-none focus:border-violet/50"
                                />
                            </div>
                            <div class="flex gap-2 justify-end">
                                <button @click="editingPrompt = false" class="text-xs text-text-muted hover:text-text-primary transition-colors px-3 py-1.5">
                                    Cancelar
                                </button>
                                <button
                                    @click="savePromptManual"
                                    :disabled="promptForm.processing"
                                    class="flex items-center gap-1.5 text-xs bg-surface-2 hover:bg-surface-3 text-text-primary transition-colors px-3 py-1.5 rounded-md"
                                >
                                    <Save class="w-3 h-3" />
                                    Guardar versión
                                </button>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-if="!activePrompt && !editingPrompt" class="text-center py-8">
                            <Wand2 class="w-8 h-8 text-text-muted mx-auto mb-2 opacity-40" />
                            <p class="text-sm text-text-muted">No hay prompt activo.</p>
                            <p class="text-xs text-text-muted mt-1">Asigna personajes, estilos y una locación, luego compón el prompt.</p>
                        </div>
                    </div>
                </div>

                <!-- Prompt history -->
                <div v-if="shot.prompts?.length > 1" class="bg-surface-1 border border-border rounded-lg overflow-hidden">
                    <button
                        @click="showPromptHistory = !showPromptHistory"
                        class="flex items-center justify-between w-full px-4 py-3 text-sm text-text-secondary hover:text-text-primary transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <History class="w-4 h-4" />
                            Historial de prompts ({{ shot.prompts.length }})
                        </span>
                        <ChevronDown :class="['w-4 h-4 transition-transform', showPromptHistory ? 'rotate-180' : '']" />
                    </button>
                    <div v-if="showPromptHistory" class="border-t border-border divide-y divide-border">
                        <div
                            v-for="p in shot.prompts"
                            :key="p.id"
                            :class="['px-4 py-3', p.is_active ? 'bg-violet/5' : '']"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-mono text-text-muted">v{{ p.version }}</span>
                                <span v-if="p.is_active" class="text-xs text-violet">activo</span>
                                <span class="text-xs text-text-muted ml-auto">{{ new Date(p.created_at).toLocaleDateString('es-MX') }}</span>
                            </div>
                            <p class="text-xs text-text-secondary font-mono line-clamp-2">{{ p.positive_prompt }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quality tier selector -->
                <div class="bg-surface-1 border border-border rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-text-primary mb-3 flex items-center gap-2">
                        <Zap class="w-4 h-4 text-amber" />
                        Calidad de render
                    </h3>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="tier in TIERS"
                            :key="tier.key"
                            @click="selectedTier = tier.key"
                            :class="[
                                'rounded-lg border p-3 text-left transition-all',
                                selectedTier === tier.key ? `${tier.bg} ${tier.border}` : 'bg-surface-0 border-border hover:border-border/60'
                            ]"
                        >
                            <p :class="['text-sm font-semibold', selectedTier === tier.key ? tier.color : 'text-text-secondary']">
                                {{ tier.label }}
                            </p>
                            <p class="text-xs text-text-muted mt-0.5 font-mono">{{ tier.model }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs font-mono text-text-primary">${{ tier.cost }}</span>
                                <span class="text-xs text-text-muted flex items-center gap-1">
                                    <Clock class="w-3 h-3" />
                                    {{ tier.time }}
                                </span>
                            </div>
                        </button>
                    </div>
                    <div class="mt-3 pt-3 border-t border-border flex items-center justify-between">
                        <p class="text-xs text-text-muted">
                            Costo estimado:
                            <span class="text-text-primary font-mono ml-1">${{ TIERS.find(t => t.key === selectedTier)?.cost }}</span>
                        </p>
                        <button
                            :disabled="!activePrompt"
                            :class="[
                                'flex items-center gap-2 text-xs px-4 py-2 rounded-md font-medium transition-all',
                                activePrompt
                                    ? 'bg-violet text-white hover:bg-violet/90'
                                    : 'bg-surface-2 text-text-muted cursor-not-allowed opacity-50'
                            ]"
                        >
                            <Zap class="w-3 h-3" />
                            Enviar a render
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Meta / Characters / Styles -->
            <div class="space-y-4">

                <!-- Shot info edit -->
                <div class="bg-surface-1 border border-border rounded-lg p-4 space-y-3">
                    <h3 class="text-sm font-semibold text-text-primary">Configuración del shot</h3>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Descripción</label>
                        <textarea
                            v-model="editForm.description"
                            rows="2"
                            class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary resize-none focus:outline-none focus:border-amber/50"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Tipo</label>
                            <select v-model="editForm.shot_type" class="w-full text-xs bg-surface-0 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-amber/50">
                                <option value="image">Imagen</option>
                                <option value="video">Video</option>
                                <option value="talking">Talking</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-text-muted mb-1">Estado</label>
                            <select v-model="editForm.status" class="w-full text-xs bg-surface-0 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-amber/50">
                                <option v-for="(label, key) in STATUS_LABELS" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Propósito</label>
                        <select v-model="editForm.purpose" class="w-full text-xs bg-surface-0 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-amber/50">
                            <option value="narrative">Narrativa</option>
                            <option value="hero">Hero</option>
                            <option value="carousel_frame">Carousel frame</option>
                            <option value="thumbnail">Thumbnail</option>
                            <option value="social">Social</option>
                            <option value="broker_portrait">Broker portrait</option>
                            <option value="property_hero">Property hero</option>
                            <option value="talking_dialogue">Talking / diálogo</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Estilo de cámara</label>
                        <select v-model="editForm.camera_style_id" class="w-full text-xs bg-surface-0 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-amber/50">
                            <option value="">— Sin asignar —</option>
                            <option v-for="cs in cameraStyles" :key="cs.id" :value="cs.id">{{ cs.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Estilo visual</label>
                        <select v-model="editForm.visual_style_id" class="w-full text-xs bg-surface-0 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-amber/50">
                            <option value="">— Sin asignar —</option>
                            <option v-for="vs in visualStyles" :key="vs.id" :value="vs.id">{{ vs.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Formato</label>
                        <select v-model="editForm.format_preset_id" class="w-full text-xs bg-surface-0 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-amber/50">
                            <option value="">— Sin asignar —</option>
                            <option v-for="fp in formatPresets" :key="fp.id" :value="fp.id">
                                {{ fp.name }} ({{ fp.width }}×{{ fp.height }})
                            </option>
                        </select>
                    </div>

                    <div v-if="editForm.shot_type === 'talking'">
                        <label class="block text-xs text-text-muted mb-1">Texto de diálogo</label>
                        <textarea
                            v-model="editForm.dialogue_text"
                            rows="2"
                            class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary resize-none focus:outline-none focus:border-amber/50"
                        />
                    </div>

                    <div>
                        <label class="block text-xs text-text-muted mb-1">Notas de director</label>
                        <textarea
                            v-model="editForm.director_notes"
                            rows="2"
                            class="w-full text-sm bg-surface-0 border border-border rounded px-3 py-2 text-text-primary resize-none focus:outline-none focus:border-amber/50"
                        />
                    </div>

                    <button
                        @click="saveShot"
                        :disabled="editForm.processing"
                        class="w-full flex items-center justify-center gap-2 text-xs bg-amber/10 text-amber hover:bg-amber/20 transition-colors px-3 py-2 rounded-md font-medium"
                    >
                        <Save class="w-3 h-3" />
                        Guardar cambios
                    </button>
                </div>

                <!-- Characters -->
                <div class="bg-surface-1 border border-border rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 class="text-sm font-semibold text-text-primary">
                            Personajes ({{ shot.characters?.length ?? 0 }})
                        </h3>
                        <button
                            @click="showAddCharacter = !showAddCharacter"
                            class="flex items-center gap-1 text-xs text-text-muted hover:text-violet transition-colors"
                        >
                            <UserPlus class="w-3.5 h-3.5" />
                            Añadir
                        </button>
                    </div>

                    <!-- Add character form -->
                    <div v-if="showAddCharacter" class="px-4 py-3 border-b border-border bg-surface-0 space-y-2">
                        <select
                            v-model="charForm.character_id"
                            class="w-full text-xs bg-surface-1 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-violet/50"
                        >
                            <option value="">— Selecciona personaje —</option>
                            <option
                                v-for="ch in availableCharacters"
                                :key="ch.id"
                                :value="String(ch.id)"
                                :disabled="shot.characters?.some(c => c.id === ch.id)"
                            >{{ ch.name }} {{ shot.characters?.some(c => c.id === ch.id) ? '(ya añadido)' : '' }}</option>
                        </select>

                        <select
                            v-if="selectedCharacterOutfits.length"
                            v-model="charForm.outfit_id"
                            class="w-full text-xs bg-surface-1 border border-border rounded px-2 py-1.5 text-text-primary focus:outline-none focus:border-violet/50"
                        >
                            <option value="">— Sin outfit específico —</option>
                            <option v-for="o in selectedCharacterOutfits" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>

                        <div class="flex gap-2">
                            <button
                                @click="addCharacter"
                                :disabled="!charForm.character_id || charForm.processing"
                                class="flex-1 text-xs bg-violet/10 text-violet hover:bg-violet/20 disabled:opacity-40 transition-colors px-3 py-1.5 rounded-md font-medium"
                            >Añadir al shot</button>
                            <button @click="showAddCharacter = false; charForm.reset()" class="text-xs text-text-muted hover:text-text-primary px-2">✕</button>
                        </div>
                        <p v-if="charForm.errors.character_id" class="text-xs text-red-400">{{ charForm.errors.character_id }}</p>
                    </div>

                    <!-- Character list -->
                    <div v-if="shot.characters?.length" class="divide-y divide-border">
                        <div v-for="ch in shot.characters" :key="ch.id" class="flex items-start gap-3 px-4 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-text-primary font-medium truncate">{{ ch.name }}</p>
                                <p class="text-xs text-text-muted font-mono">{{ ch.type }}</p>
                                <div v-if="ch.pivot?.outfit_id" class="mt-1">
                                    <span class="text-xs bg-amber/10 text-amber px-1.5 py-0.5 rounded font-mono">
                                        {{ ch.outfits?.find(o => o.id === ch.pivot.outfit_id)?.name ?? 'Outfit asignado' }}
                                    </span>
                                </div>
                                <p v-if="ch.lora_trigger_word" class="text-xs text-violet font-mono mt-1 truncate">{{ ch.lora_trigger_word }}</p>
                            </div>
                            <button
                                @click="removeCharacter(ch.id)"
                                class="shrink-0 text-text-muted hover:text-red-400 transition-colors mt-0.5"
                            >
                                <UserMinus class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div v-else-if="!showAddCharacter" class="px-4 py-4 text-center">
                        <p class="text-xs text-text-muted">Sin personajes asignados.</p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
