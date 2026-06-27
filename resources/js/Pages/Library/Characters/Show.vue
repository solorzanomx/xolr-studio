<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Pencil, Trash2, Plus, X, Users, Shirt, Dna, Mic, GitBranch } from '@lucide/vue'

const props = defineProps({ character: Object })

const activeTab = ref('overview')

const typeColor = {
    fictional: 'text-amber bg-amber/10',
    virtual_talent: 'text-violet bg-violet/10',
    mascot: 'text-info bg-info/10',
}
const typeLabel = { fictional: 'Ficción', virtual_talent: 'Talent Virtual', mascot: 'Mascota' }

function deleteCharacter() {
    if (!confirm(`¿Eliminar "${props.character.name}"?`)) return
    router.delete(`/library/characters/${props.character.id}`)
}

// Outfit form
const showOutfitForm = ref(false)
const outfitForm = useForm({
    name: '',
    description: '',
    prompt_fragment: '',
    context: 'casual',
    is_active: true,
})

function storeOutfit() {
    outfitForm.post(`/library/characters/${props.character.id}/outfits`, {
        onSuccess: () => {
            showOutfitForm.value = false
            outfitForm.reset()
        },
    })
}

function deleteOutfit(outfitId) {
    if (!confirm('¿Eliminar este outfit?')) return
    router.delete(`/library/outfits/${outfitId}`)
}

const outfitContextLabel = {
    formal: 'Formal', casual: 'Casual', action: 'Acción',
    historical: 'Histórico', fantasy: 'Fantasía', uniform: 'Uniforme', other: 'Otro',
}
</script>

<template>
    <Head :title="character.name" />
    <AppLayout>
        <!-- Back + actions -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <Link href="/library/characters" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-3 transition-colors">
                    <ChevronLeft class="w-4 h-4" />
                    Personajes
                </Link>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-text-primary">{{ character.name }}</h1>
                    <span :class="['text-xs font-mono px-2 py-0.5 rounded', typeColor[character.type]]">
                        {{ typeLabel[character.type] }}
                    </span>
                    <span :class="['w-2 h-2 rounded-full', character.is_active ? 'bg-success' : 'bg-surface-3']" />
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    :href="`/library/characters/${character.id}/edit`"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors"
                >
                    <Pencil class="w-4 h-4" />
                    Editar
                </Link>
                <button
                    @click="deleteCharacter"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-surface-1 border border-border rounded-lg text-sm text-danger hover:bg-danger/10 transition-colors"
                >
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-border mb-6">
            <button
                v-for="tab in [
                    { id: 'overview', label: 'Overview', icon: Users },
                    { id: 'outfits', label: 'Outfits', icon: Shirt, count: character.outfits?.length },
                    { id: 'voice', label: 'Voz', icon: Mic, count: character.voice_profiles?.length },
                    { id: 'dna', label: 'DNA Config', icon: Dna },
                ]"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === tab.id
                        ? 'border-amber text-text-primary'
                        : 'border-transparent text-text-muted hover:text-text-secondary'
                ]"
            >
                <component :is="tab.icon" class="w-4 h-4" />
                {{ tab.label }}
                <span v-if="tab.count" class="text-[10px] font-mono bg-surface-2 text-text-muted rounded-full px-1.5 py-0.5">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- Overview tab -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: thumbnail + stats -->
            <div class="space-y-4">
                <div class="aspect-square bg-surface-1 border border-border rounded-xl overflow-hidden">
                    <img v-if="character.thumbnail_path" :src="character.thumbnail_path" :alt="character.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <Users class="w-16 h-16 text-text-muted" />
                    </div>
                </div>
                <!-- Stats -->
                <div class="bg-surface-1 border border-border rounded-xl p-4 space-y-3">
                    <div v-if="character.approval_rate" class="flex justify-between text-sm">
                        <span class="text-text-muted">Tasa de aprobación</span>
                        <span class="font-mono text-success">{{ character.approval_rate }}%</span>
                    </div>
                    <div v-if="character.avg_renders_to_approve" class="flex justify-between text-sm">
                        <span class="text-text-muted">Renders promedio</span>
                        <span class="font-mono text-text-secondary">{{ character.avg_renders_to_approve }}</span>
                    </div>
                    <div v-if="character.lora_trigger_word" class="flex justify-between text-sm">
                        <span class="text-text-muted">Trigger word</span>
                        <span class="font-mono text-violet text-xs">{{ character.lora_trigger_word }}</span>
                    </div>
                    <div v-if="character.lora_weight" class="flex justify-between text-sm">
                        <span class="text-text-muted">LoRA weight</span>
                        <span class="font-mono text-violet">{{ character.lora_weight }}</span>
                    </div>
                    <div v-if="character.lora_trained_at" class="flex justify-between text-sm">
                        <span class="text-text-muted">LoRA entrenado</span>
                        <span class="font-mono text-text-secondary text-xs">{{ new Date(character.lora_trained_at).toLocaleDateString() }}</span>
                    </div>
                </div>
                <!-- Personality traits -->
                <div v-if="character.personality_traits?.length" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Rasgos</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="trait in character.personality_traits" :key="trait"
                            class="text-xs bg-surface-2 text-text-secondary border border-border rounded-full px-2.5 py-1">
                            {{ trait }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: descriptions + prompts -->
            <div class="lg:col-span-2 space-y-4">
                <div v-if="character.description" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Descripción</p>
                    <p class="text-sm text-text-secondary leading-relaxed">{{ character.description }}</p>
                </div>
                <div v-if="character.physical_description" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Descripción física</p>
                    <p class="text-sm text-text-secondary leading-relaxed">{{ character.physical_description }}</p>
                </div>
                <div v-if="character.base_prompt" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Prompt base</p>
                    <pre class="text-xs font-mono text-text-secondary whitespace-pre-wrap leading-relaxed">{{ character.base_prompt }}</pre>
                </div>
                <div v-if="character.negative_prompt" class="bg-surface-1 border border-border rounded-xl p-4">
                    <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-2">Prompt negativo</p>
                    <pre class="text-xs font-mono text-danger/70 whitespace-pre-wrap leading-relaxed">{{ character.negative_prompt }}</pre>
                </div>
            </div>
        </div>

        <!-- Outfits tab -->
        <div v-if="activeTab === 'outfits'">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">Outfits ({{ character.outfits?.length ?? 0 }})</h2>
                <button
                    @click="showOutfitForm = !showOutfitForm"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-surface-1 border border-border rounded-lg text-sm text-text-secondary hover:text-text-primary transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    Añadir outfit
                </button>
            </div>

            <!-- Outfit creation form -->
            <div v-if="showOutfitForm" class="bg-surface-1 border border-amber/30 rounded-xl p-4 mb-4 space-y-3">
                <h3 class="text-sm font-medium text-text-primary">Nuevo outfit</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Nombre *</label>
                        <input v-model="outfitForm.name" type="text" placeholder="Ej: Traje de gala" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors" />
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Contexto</label>
                        <select v-model="outfitForm.context" class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="formal">Formal</option>
                            <option value="casual">Casual</option>
                            <option value="action">Acción</option>
                            <option value="historical">Histórico</option>
                            <option value="fantasy">Fantasía</option>
                            <option value="uniform">Uniforme</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-text-muted mb-1">Prompt fragment</label>
                    <textarea v-model="outfitForm.prompt_fragment" rows="2" placeholder="Fragmento de prompt para este outfit..." class="w-full bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>
                <div class="flex items-center gap-3">
                    <button @click="storeOutfit" :disabled="outfitForm.processing" class="px-4 py-1.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                        Guardar
                    </button>
                    <button @click="showOutfitForm = false; outfitForm.reset()" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                </div>
            </div>

            <!-- Outfit list -->
            <div v-if="!character.outfits?.length" class="bg-surface-1 border border-border rounded-xl p-10 text-center">
                <Shirt class="w-8 h-8 text-text-muted mx-auto mb-2" />
                <p class="text-sm text-text-muted">Sin outfits aún</p>
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div v-for="outfit in character.outfits" :key="outfit.id"
                    class="bg-surface-1 border border-border rounded-xl p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-sm font-medium text-text-primary">{{ outfit.name }}</p>
                            <span class="text-[10px] font-mono text-text-muted">{{ outfitContextLabel[outfit.context] }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span :class="['w-1.5 h-1.5 rounded-full', outfit.is_active ? 'bg-success' : 'bg-surface-3']" />
                            <button @click="deleteOutfit(outfit.id)" class="ml-1 text-text-muted hover:text-danger transition-colors">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                    <p v-if="outfit.prompt_fragment" class="text-xs font-mono text-text-muted line-clamp-2">{{ outfit.prompt_fragment }}</p>
                </div>
            </div>
        </div>

        <!-- Voice tab -->
        <div v-if="activeTab === 'voice'">
            <div class="bg-surface-1 border border-border rounded-xl p-10 text-center">
                <Mic class="w-8 h-8 text-text-muted mx-auto mb-2" />
                <p class="text-sm text-text-muted">Perfiles de voz — próximamente</p>
            </div>
        </div>

        <!-- DNA tab -->
        <div v-if="activeTab === 'dna'">
            <div class="bg-surface-1 border border-border rounded-xl p-4">
                <p class="text-xs font-medium text-text-muted uppercase tracking-wider mb-3">Configuración DNA</p>
                <pre v-if="character.dna_config" class="text-xs font-mono text-text-secondary whitespace-pre-wrap leading-relaxed">{{ JSON.stringify(character.dna_config, null, 2) }}</pre>
                <p v-else class="text-sm text-text-muted">Sin configuración DNA definida.</p>
            </div>
        </div>
    </AppLayout>
</template>
