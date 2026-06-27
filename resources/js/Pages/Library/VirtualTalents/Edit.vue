<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, Sparkles, Plus, X, Loader } from '@lucide/vue'
import axios from 'axios'

const props = defineProps({
    character: Object,
    talent: Object,
})

const form = useForm({
    title:               props.talent?.title ?? '',
    specialties:         props.talent?.specialties ?? [],
    bio_short:           props.talent?.bio_short ?? '',
    bio_full:            props.talent?.bio_full ?? '',
    communication_style: props.talent?.communication_style ?? '',
    signature_phrase:    props.talent?.signature_phrase ?? '',
    social_handle:       props.talent?.social_handle ?? '',
    brand_colors:        props.talent?.brand_colors ?? [],
    is_public:           props.talent?.is_public ?? false,
})

const specialtyInput = ref('')
const colorInput = ref('#FFFFFF')
const generating = ref(false)
const generateError = ref('')

function addSpecialty() {
    const s = specialtyInput.value.trim()
    if (s && !form.specialties.includes(s)) form.specialties.push(s)
    specialtyInput.value = ''
}

function removeSpecialty(i) {
    form.specialties.splice(i, 1)
}

function addColor() {
    if (!form.brand_colors.includes(colorInput.value)) {
        form.brand_colors.push(colorInput.value)
    }
}

function removeColor(i) {
    form.brand_colors.splice(i, 1)
}

async function generateBio() {
    generating.value = true
    generateError.value = ''
    try {
        const res = await axios.post(`/library/virtual-talents/${props.character.id}/generate-bio`)
        if (res.data.success) {
            const d = res.data.data
            form.bio_short           = d.bio_short ?? form.bio_short
            form.bio_full            = d.bio_full ?? form.bio_full
            form.communication_style = d.communication_style ?? form.communication_style
            form.signature_phrase    = d.signature_phrase ?? form.signature_phrase
        } else {
            generateError.value = res.data.message ?? 'Error al generar'
        }
    } catch (e) {
        generateError.value = 'No se pudo conectar con la IA. Verifica ANTHROPIC_API_KEY.'
    } finally {
        generating.value = false
    }
}

function submit() {
    form.put(`/library/virtual-talents/${props.character.id}`)
}
</script>

<template>
    <Head :title="`Editar perfil — ${character.name}`" />
    <AppLayout>
        <div class="max-w-2xl">
            <Link :href="`/library/virtual-talents/${character.id}`" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" />
                {{ character.name }}
            </Link>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Perfil de Talent Virtual</h1>
                    <p class="text-sm text-text-muted mt-0.5">{{ character.name }}</p>
                </div>
                <!-- Generate with AI button -->
                <button
                    type="button"
                    @click="generateBio"
                    :disabled="generating"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-violet text-white text-sm font-semibold rounded-lg hover:bg-violet/90 disabled:opacity-60 transition-colors"
                >
                    <Loader v-if="generating" class="w-4 h-4 animate-spin" />
                    <Sparkles v-else class="w-4 h-4" />
                    {{ generating ? 'Generando...' : 'Generar con IA' }}
                </button>
            </div>

            <!-- AI error -->
            <div v-if="generateError" class="bg-danger/10 border border-danger/30 text-danger text-sm rounded-lg px-4 py-3 mb-5">
                {{ generateError }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Title + Social handle -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Título / rol *</label>
                        <input v-model="form.title" type="text" placeholder="Ej: Creadora de contenido de moda" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-violet transition-colors" :class="{ 'border-danger': form.errors.title }" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-danger">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Handle de redes</label>
                        <input v-model="form.social_handle" type="text" placeholder="@sofianavarro" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-violet transition-colors" />
                    </div>
                </div>

                <!-- Specialties -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Especialidades</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span v-for="(s, i) in form.specialties" :key="i"
                            class="inline-flex items-center gap-1 text-xs bg-violet/10 text-violet border border-violet/20 rounded-full px-2.5 py-1">
                            {{ s }}
                            <button type="button" @click="removeSpecialty(i)" class="text-violet/60 hover:text-danger transition-colors">
                                <X class="w-3 h-3" />
                            </button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="specialtyInput" @keydown.enter.prevent="addSpecialty" type="text" placeholder="Añadir especialidad y Enter..." class="flex-1 bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-violet transition-colors" />
                        <button type="button" @click="addSpecialty" class="p-2 bg-surface-2 border border-border rounded-lg text-text-secondary hover:text-text-primary transition-colors">
                            <Plus class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Signature phrase -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">
                        Frase icónica
                        <span class="text-violet ml-1">← IA la genera</span>
                    </label>
                    <input v-model="form.signature_phrase" type="text" placeholder="Su frase definitoria..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-violet transition-colors" />
                </div>

                <!-- Bio short -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">
                        Bio corta (redes sociales)
                        <span class="text-violet ml-1">← IA la genera</span>
                    </label>
                    <textarea v-model="form.bio_short" rows="2" placeholder="160 caracteres para perfil de Instagram..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-violet transition-colors resize-none" />
                    <p class="text-[10px] text-text-muted mt-1 text-right">{{ form.bio_short.length }} / 300</p>
                </div>

                <!-- Bio full -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">
                        Bio completa
                        <span class="text-violet ml-1">← IA la genera</span>
                    </label>
                    <textarea v-model="form.bio_full" rows="8" placeholder="Bio completa de 3-4 párrafos..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-violet transition-colors resize-none" />
                </div>

                <!-- Communication style -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">
                        Guía de comunicación y estilo
                        <span class="text-violet ml-1">← IA la genera</span>
                    </label>
                    <textarea v-model="form.communication_style" rows="5" placeholder="Tono de voz, vocabulario, emojis, cómo interactúa con su audiencia..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-violet transition-colors resize-none" />
                </div>

                <!-- Brand colors -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Colores de marca</label>
                    <div class="flex items-center gap-2 mb-2">
                        <input v-model="colorInput" type="color" class="w-10 h-9 rounded-lg border border-border bg-surface-1 cursor-pointer" />
                        <button type="button" @click="addColor" class="px-3 py-1.5 bg-surface-2 border border-border rounded-lg text-xs text-text-secondary hover:text-text-primary transition-colors">
                            Añadir color
                        </button>
                        <div class="flex gap-2 flex-wrap">
                            <div v-for="(color, i) in form.brand_colors" :key="i" class="relative group">
                                <div class="w-8 h-8 rounded-lg border border-border" :style="{ backgroundColor: color }" :title="color" />
                                <button type="button" @click="removeColor(i)"
                                    class="absolute -top-1 -right-1 w-4 h-4 bg-danger rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <X class="w-2.5 h-2.5 text-white" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Is public -->
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input v-model="form.is_public" type="checkbox" class="accent-violet w-4 h-4" />
                    <span class="text-sm text-text-secondary">Perfil público</span>
                </label>

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-violet text-white text-sm font-semibold rounded-lg hover:bg-violet/90 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Guardando...' : 'Guardar perfil' }}
                    </button>
                    <Link :href="`/library/virtual-talents/${character.id}`" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
