<script setup>
defineProps({
    title:    { type: String, default: 'Preview' },
    subtitle: { type: String, default: null },
})
</script>

<template>
    <div class="min-h-screen bg-surface-0 text-text-primary">
        <!-- Minimal header -->
        <header class="border-b border-border bg-surface-1 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-amber font-mono font-bold text-lg">◈</span>
                <div>
                    <p class="text-sm font-semibold text-text-primary leading-none">{{ title }}</p>
                    <p v-if="subtitle" class="text-xs text-text-muted mt-0.5">{{ subtitle }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-text-muted px-2 py-1 bg-surface-2 border border-border rounded-lg font-mono">
                    Vista de cliente — solo lectura
                </span>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-10">
            <slot />
        </main>

        <!-- Watermark footer -->
        <footer class="border-t border-border px-6 py-4 text-center">
            <p class="text-xs text-text-muted">
                Generado con <span class="text-amber font-semibold">Xolr Studio</span> — Plataforma de producción audiovisual con IA
            </p>
        </footer>
    </div>
</template>
