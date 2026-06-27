<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Sparkles, Plus, Trash2, Star, Copy } from '@lucide/vue'

const props = defineProps({ hooks: Array, filters: Object })

const CATEGORIES = [
    { value: 'curiosity', label: 'Curiosidad',   color: 'text-amber' },
    { value: 'shock',     label: 'Impacto',       color: 'text-danger' },
    { value: 'question',  label: 'Pregunta',      color: 'text-violet' },
    { value: 'challenge', label: 'Reto',          color: 'text-success' },
    { value: 'story',     label: 'Historia',      color: 'text-copper' },
    { value: 'data',      label: 'Dato/Cifra',    color: 'text-amber' },
    { value: 'other',     label: 'Otro',          color: 'text-text-muted' },
]

const activeCategory = ref(props.filters?.category ?? '')

const form = useForm({
    project_id: null,
    category:   'curiosity',
    content:    '',
    rating:     null,
})

const showForm = ref(false)

function setRating(n) { form.rating = form.rating === n ? null : n }

function saveHook() {
    form.post('/content-machine/hooks', {
        preserveState: false,
        onSuccess: () => { form.reset('content', 'rating'); showForm.value = false },
    })
}

function deleteHook(id) {
    if (!confirm('¿Eliminar este hook?')) return
    router.delete(`/content-machine/hooks/${id}`, { preserveState: false })
}

function copyHook(content) {
    navigator.clipboard.writeText(content)
}

const filteredHooks = (cat) => props.hooks.filter(h => !cat || h.category === cat)
</script>

<template>
    <Head title="Banco de Hooks — Content Machine" />
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary flex items-center gap-2">
                        <Sparkles class="w-5 h-5 text-violet" />
                        Banco de Hooks
                    </h1>
                    <p class="text-sm text-text-muted mt-0.5">Ganchos de apertura reutilizables, organizados por tipo</p>
                </div>
                <button
                    @click="showForm = !showForm"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    Nuevo hook
                </button>
            </div>

            <!-- Add hook form -->
            <div v-if="showForm" class="bg-surface-1 border border-amber/30 rounded-xl p-5 mb-5 space-y-3">
                <h3 class="text-sm font-semibold text-text-primary">Nuevo hook</h3>

                <div>
                    <label class="block text-xs text-text-muted mb-1.5">Categoría</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="cat in CATEGORIES"
                            :key="cat.value"
                            type="button"
                            @click="form.category = cat.value"
                            :class="['px-3 py-1 text-xs rounded-full border transition-colors', form.category === cat.value ? 'bg-amber/20 border-amber text-amber font-semibold' : 'border-border text-text-muted hover:border-amber/50']"
                        >
                            {{ cat.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-text-muted mb-1">Texto del hook</label>
                    <textarea
                        v-model="form.content"
                        rows="3"
                        placeholder="¿Sabías que este mercado tiene más de 400 años y aún funciona igual que siempre...?"
                        class="w-full bg-surface-2 border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors resize-none"
                    />
                </div>

                <div>
                    <label class="block text-xs text-text-muted mb-1.5">Potencial</label>
                    <div class="flex gap-1">
                        <button v-for="n in 5" :key="n" type="button" @click="setRating(n)"
                            :class="['p-0.5 transition-colors', (form.rating ?? 0) >= n ? 'text-amber' : 'text-border hover:text-amber/50']">
                            <Star :class="['w-4 h-4', (form.rating ?? 0) >= n ? 'fill-amber' : '']" />
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button @click="showForm = false; form.reset()" class="px-3 py-1.5 text-sm text-text-muted hover:text-text-primary transition-colors">Cancelar</button>
                    <button @click="saveHook" :disabled="form.processing || !form.content" class="px-4 py-1.5 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors disabled:opacity-50">
                        Guardar
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="hooks.length === 0" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
                <Sparkles class="w-10 h-10 text-text-muted mx-auto mb-3" />
                <p class="text-sm text-text-muted">El banco de hooks está vacío</p>
                <button @click="showForm = true" class="mt-3 text-sm text-amber hover:underline">Añadir el primero</button>
            </div>

            <!-- Hooks by category -->
            <div v-else class="space-y-6">
                <div v-for="cat in CATEGORIES" :key="cat.value">
                    <div v-if="filteredHooks(cat.value).length > 0">
                        <div class="flex items-center gap-2 mb-3">
                            <span :class="['text-xs font-bold uppercase tracking-wider', cat.color]">{{ cat.label }}</span>
                            <span class="text-xs font-mono text-text-muted">{{ filteredHooks(cat.value).length }}</span>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="hook in filteredHooks(cat.value)"
                                :key="hook.id"
                                class="group bg-surface-1 border border-border rounded-xl px-4 py-3 flex items-start gap-3 hover:border-border/70 transition-colors"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-text-primary leading-relaxed">{{ hook.content }}</p>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <div v-if="hook.rating" class="flex gap-0.5">
                                            <Star v-for="n in hook.rating" :key="n" class="w-2.5 h-2.5 text-amber fill-amber" />
                                        </div>
                                        <span v-if="hook.usage_count > 0" class="text-[10px] font-mono text-text-muted">usado {{ hook.usage_count }}x</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="copyHook(hook.content)" class="p-1.5 text-text-muted hover:text-text-primary bg-surface-2 rounded transition-colors">
                                        <Copy class="w-3 h-3" />
                                    </button>
                                    <button @click="deleteHook(hook.id)" class="p-1.5 text-text-muted hover:text-danger bg-surface-2 rounded transition-colors">
                                        <Trash2 class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
