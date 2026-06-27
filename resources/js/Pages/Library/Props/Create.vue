<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ChevronLeft } from '@lucide/vue'

const form = useForm({
    name: '',
    description: '',
    prompt_fragment: '',
    is_active: true,
})

function submit() {
    form.post('/library/props')
}
</script>

<template>
    <Head title="Nuevo prop" />
    <AppLayout>
        <div class="max-w-2xl">
            <Link href="/library/props" class="inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors">
                <ChevronLeft class="w-4 h-4" />
                Props
            </Link>
            <h1 class="text-xl font-semibold text-text-primary mb-6">Nuevo prop</h1>
            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Nombre *</label>
                    <input v-model="form.name" type="text" placeholder="Ej: Maletín ejecutivo" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" :class="{ 'border-danger': form.errors.name }" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Descripción</label>
                    <textarea v-model="form.description" rows="2" class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1.5">Prompt fragment</label>
                    <textarea v-model="form.prompt_fragment" rows="3" placeholder="Fragmento de prompt para incluir este prop..." class="w-full bg-surface-1 border border-border rounded-lg px-3 py-2 text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none" />
                </div>
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input v-model="form.is_active" type="checkbox" class="accent-amber w-4 h-4" />
                    <span class="text-sm text-text-secondary">Prop activo</span>
                </label>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Guardando...' : 'Crear prop' }}
                    </button>
                    <Link href="/library/props" class="text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
