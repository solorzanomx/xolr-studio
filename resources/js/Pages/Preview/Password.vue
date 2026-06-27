<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import PreviewLayout from '@/Layouts/PreviewLayout.vue'
import { Lock } from '@lucide/vue'

const props = defineProps({ token: String })

const form = useForm({ password: '' })

function submit() {
    form.post(`/preview/${props.token}/auth`)
}
</script>

<template>
    <PreviewLayout title="Preview protegido">
        <Head title="Preview — Acceso protegido" />

        <div class="flex items-center justify-center min-h-[60vh]">
            <div class="bg-surface-1 border border-border rounded-2xl p-8 w-full max-w-sm text-center">
                <div class="w-12 h-12 rounded-full bg-amber/10 flex items-center justify-center mx-auto mb-4">
                    <Lock class="w-6 h-6 text-amber" />
                </div>
                <h2 class="text-lg font-semibold text-text-primary mb-1">Preview protegido</h2>
                <p class="text-sm text-text-muted mb-6">Ingresa la contraseña para ver este contenido.</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="Contraseña"
                        autofocus
                        class="w-full bg-surface-2 border border-border rounded-lg px-4 py-2.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors"
                        :class="form.errors.password ? 'border-danger' : ''"
                    />
                    <p v-if="form.errors.password" class="text-xs text-danger text-left">{{ form.errors.password }}</p>
                    <button type="submit" :disabled="form.processing"
                        class="w-full py-2.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                        Acceder
                    </button>
                </form>
            </div>
        </div>
    </PreviewLayout>
</template>
