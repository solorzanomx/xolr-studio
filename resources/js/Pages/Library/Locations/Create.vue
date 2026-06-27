<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft } from '@lucide/vue'

const form = useForm({
    name: '',
    type: 'interior',
    description: '',
    base_prompt: '',
    lighting_by_time: {
        morning: '',
        day: '',
        golden_hour: '',
        night: '',
    },
    is_active: true,
})

function submit() {
    form.post('/library/locations')
}
</script>

<template>
    <Head title="Nueva locación" />
    <AppLayout>
        <div class="max-w-2xl">
            <Link href="/library/locations" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" />
                Locaciones
            </Link>

            <h1 class="text-xl font-semibold text-text-primary mb-6">Nueva locación</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Nombre *</label>
                        <input v-model="form.name" type="text" placeholder="Ej: Oficina corporativa" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" :class="{ 'border-danger': form.errors.name }" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5">Tipo *</label>
                        <select v-model="form.type" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors">
                            <option value="interior">Interior</option>
                            <option value="exterior">Exterior</option>
                            <option value="mixed">Mixto</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Descripción</label>
                    <textarea v-model="form.description" rows="3" placeholder="Descripción general del escenario..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Prompt base</label>
                    <textarea v-model="form.base_prompt" rows="4" placeholder="Prompt de generación para esta locación..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>

                <!-- Lighting by time of day -->
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-3">Iluminación por momento del día</label>
                    <div class="space-y-2">
                        <div v-for="(label, key) in { morning: 'Mañana', day: 'Día', golden_hour: 'Hora dorada', night: 'Noche' }" :key="key" class="flex items-center gap-3">
                            <span class="w-24 text-xs text-text-muted shrink-0">{{ label }}</span>
                            <input
                                v-model="form.lighting_by_time[key]"
                                type="text"
                                :placeholder="`Prompt de iluminación ${label.toLowerCase()}...`"
                                class="flex-1 bg-surface-1 border border-border rounded-lg px-3 py-1.5 text-xs font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                            />
                        </div>
                    </div>
                </div>

                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input v-model="form.is_active" type="checkbox" class="accent-amber w-4 h-4" />
                    <span class="text-sm text-text-secondary">Locación activa</span>
                </label>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Guardando...' : 'Crear locación' }}
                    </button>
                    <Link href="/library/locations" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
