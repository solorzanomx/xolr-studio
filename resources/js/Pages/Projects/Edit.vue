<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft, X } from '@lucide/vue'

const props = defineProps({ project: Object, visualStyles: Array })

const form = useForm({
    name:               props.project.name,
    type:               props.project.type,
    description:        props.project.description ?? '',
    synopsis:           props.project.synopsis ?? '',
    visual_style_id:    props.project.visual_style_id ?? null,
    status:             props.project.status,
    brand_colors:       props.project.brand_colors ?? [],
    monthly_budget_usd: props.project.monthly_budget_usd ?? '',
})

const colorInput = ref('#F59E0B')

function addColor() {
    if (!form.brand_colors.includes(colorInput.value)) form.brand_colors.push(colorInput.value)
}
function removeColor(i) { form.brand_colors.splice(i, 1) }

function submit() { form.put(`/projects/${props.project.id}`) }
</script>

<template>
    <Head title="Editar proyecto" />
    <AppLayout>
        <div class="max-w-2xl">
            <Link :href="`/projects/${project.id}`" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" /> {{ project.name }}
            </Link>
            <h1 class="text-xl font-semibold text-text-primary mb-6">Editar proyecto</h1>
            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Nombre *</label>
                    <input v-model="form.name" type="text" placeholder="Ej: Strange Light" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" :class="{ 'border-danger': form.errors.name }" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Tipo *</label>
                        <select v-model="form.type" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="fiction_series">Serie de ficción</option>
                            <option value="youtube_channel">Canal YouTube</option>
                            <option value="real_estate">Inmobiliario</option>
                            <option value="commercial">Comercial</option>
                            <option value="corporate">Corporativo</option>
                            <option value="social_media">Social media</option>
                            <option value="client">Cliente externo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Estado *</label>
                        <select v-model="form.status" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="development">Desarrollo</option>
                            <option value="pre_production">Pre-producción</option>
                            <option value="production">Producción</option>
                            <option value="post_production">Post-producción</option>
                            <option value="completed">Terminado</option>
                            <option value="archived">Archivado</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Descripción</label>
                    <textarea v-model="form.description" rows="2" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Sinopsis</label>
                    <textarea v-model="form.synopsis" rows="4" placeholder="Premisa y sinopsis general del proyecto..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Estilo visual default</label>
                        <select v-model="form.visual_style_id" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option :value="null">Sin estilo</option>
                            <option v-for="s in visualStyles" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Presupuesto mensual (USD)</label>
                        <input v-model="form.monthly_budget_usd" type="number" min="0" step="0.01" placeholder="0.00" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-2">Colores de marca</label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input v-model="colorInput" type="color" class="w-10 h-9 rounded border border-border bg-surface-1 cursor-pointer" />
                        <button type="button" @click="addColor" class="px-3 py-1.5 bg-surface-2 border border-border rounded-lg text-xs text-text-secondary hover:text-text-primary transition-colors">Añadir</button>
                        <div v-for="(c, i) in form.brand_colors" :key="i" class="relative group">
                            <div class="w-8 h-8 rounded-lg border border-border" :style="{ backgroundColor: c }" />
                            <button type="button" @click="removeColor(i)" class="absolute -top-1 -right-1 w-4 h-4 bg-danger rounded-full items-center justify-center hidden group-hover:flex">
                                <X class="w-2.5 h-2.5 text-white" />
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                    </button>
                    <Link :href="`/projects/${project.id}`" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
