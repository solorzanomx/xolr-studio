<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { CheckCircle, XCircle, ExternalLink } from '@lucide/vue'

const props = defineProps({
    integrations: Object,
})
</script>

<template>
    <Head title="Configuraci&#243;n" />

    <AppLayout>
        <div class="max-w-2xl">
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-text-primary">Configuraci&#243;n del Studio</h1>
                <p class="text-sm text-text-muted mt-0.5">Integraciones y API keys del sistema</p>
            </div>

            <!-- Integrations -->
            <section>
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide mb-3">Integraciones</h2>
                <div class="bg-surface-1 border border-border rounded-xl divide-y divide-border">
                    <div
                        v-for="(integration, key) in integrations"
                        :key="key"
                        class="flex items-center gap-4 p-4"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-text-primary">{{ integration.label }}</span>
                                <CheckCircle v-if="integration.configured" class="w-3.5 h-3.5 text-success" />
                                <XCircle v-else class="w-3.5 h-3.5 text-text-muted" />
                            </div>
                            <p class="text-xs text-text-muted mt-0.5">{{ integration.description }}</p>
                        </div>
                        <span
                            :class="[
                                'text-xs font-mono px-2 py-0.5 rounded',
                                integration.configured
                                    ? 'bg-success/10 text-success'
                                    : 'bg-surface-2 text-text-muted'
                            ]"
                        >
                            {{ integration.configured ? 'conectado' : 'sin configurar' }}
                        </span>
                    </div>
                </div>
                <p class="text-xs text-text-muted mt-3 flex items-center gap-1">
                    <ExternalLink class="w-3 h-3" />
                    Configura las API keys en el archivo <code class="font-mono text-text-secondary">.env</code> del servidor.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
