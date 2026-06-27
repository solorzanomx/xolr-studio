<script setup>
import { Head } from '@inertiajs/vue3'
import PreviewLayout from '@/Layouts/PreviewLayout.vue'
import { Clock } from '@lucide/vue'
</script>

<template>
    <PreviewLayout title="Preview expirado">
        <Head title="Preview — Enlace expirado" />

        <div class="flex items-center justify-center min-h-[60vh]">
            <div class="text-center">
                <div class="w-14 h-14 rounded-full bg-surface-2 flex items-center justify-center mx-auto mb-4">
                    <Clock class="w-7 h-7 text-text-muted" />
                </div>
                <h2 class="text-xl font-semibold text-text-primary mb-2">Enlace expirado</h2>
                <p class="text-sm text-text-muted max-w-xs mx-auto">
                    Este link de preview ya no está disponible. Contacta al equipo de producción para obtener un nuevo enlace.
                </p>
            </div>
        </div>
    </PreviewLayout>
</template>
