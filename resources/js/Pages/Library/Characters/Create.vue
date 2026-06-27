<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Plus, X } from '@lucide/vue'
import { ref } from 'vue'

const form = useForm({
    name: '',
    type: 'fictional',
    description: '',
    physical_description: '',
    personality_traits: [],
    base_prompt: '',
    negative_prompt: '',
    lora_trigger_word: '',
    lora_weight: '',
    is_active: true,
})

const traitInput = ref('')

function addTrait() {
    const t = traitInput.value.trim()
    if (t && !form.personality_traits.includes(t)) {
        form.personality_traits.push(t)
    }
    traitInput.value = ''
}

function removeTrait(i) {
    form.personality_traits.splice(i, 1)
}

function submit() {
    form.post('/library/characters')
}
</script>

<template>
    <Head title="Nuevo personaje" />
    <AppLayout>
        <div class="max-w-2xl">
            <!-- Back -->
            <Link href="/library/characters" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" />
                Personajes
            </Link>

            <h1 class="text-xl font-semibold text-text-primary mb-6">Nuevo personaje</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Name + Type -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Nombre *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Nombre del personaje"
                            class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                            :class="{ 'border-danger': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Tipo *</label>
                        <select
                            v-model="form.type"
                            class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                        >
                            <option value="fictional">Ficción</option>
                            <option value="virtual_talent">Talent Virtual</option>
                            <option value="mascot">Mascota</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Descripción</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Descripción general del personaje..."
                        class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none"
                    />
                </div>

                <!-- Physical description -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Descripción física</label>
                    <textarea
                        v-model="form.physical_description"
                        rows="3"
                        placeholder="Características físicas detalladas..."
                        class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none"
                    />
                </div>

                <!-- Personality traits -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Rasgos de personalidad</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span
                            v-for="(t, i) in form.personality_traits"
                            :key="i"
                            class="inline-flex items-center gap-1 text-xs bg-surface-2 text-text-secondary border border-border rounded-full px-2.5 py-1"
                        >
                            {{ t }}
                            <button type="button" @click="removeTrait(i)" class="text-text-muted hover:text-danger transition-colors">
                                <X class="w-3 h-3" />
                            </button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input
                            v-model="traitInput"
                            @keydown.enter.prevent="addTrait"
                            type="text"
                            placeholder="Añadir rasgo y Enter..."
                            class="flex-1 bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                        />
                        <button type="button" @click="addTrait" class="p-2 bg-surface-2 border border-border rounded-lg text-text-secondary hover:text-text-primary transition-colors">
                            <Plus class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Base prompt -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Prompt base</label>
                    <textarea
                        v-model="form.base_prompt"
                        rows="4"
                        placeholder="Prompt de generación de imagen para este personaje..."
                        class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none"
                    />
                </div>

                <!-- Negative prompt -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Prompt negativo</label>
                    <textarea
                        v-model="form.negative_prompt"
                        rows="2"
                        placeholder="Elementos a evitar en la generación..."
                        class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none"
                    />
                </div>

                <!-- LoRA -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">LoRA trigger word</label>
                        <input
                            v-model="form.lora_trigger_word"
                            type="text"
                            placeholder="Ej: sks person"
                            class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">LoRA weight (0–2)</label>
                        <input
                            v-model="form.lora_weight"
                            type="number"
                            min="0"
                            max="2"
                            step="0.05"
                            placeholder="1.0"
                            class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                        />
                    </div>
                </div>

                <!-- Active -->
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input v-model="form.is_active" type="checkbox" class="accent-amber w-4 h-4" />
                    <span class="text-sm text-text-secondary">Personaje activo</span>
                </label>

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Guardando...' : 'Crear personaje' }}
                    </button>
                    <Link href="/library/characters" class="text-sm text-text-muted hover:text-text-primary transition-colors">
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
