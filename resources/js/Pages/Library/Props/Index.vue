<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Package, Plus, Search, Trash2 } from '@lucide/vue'

const props = defineProps({ items: Object, filters: Object })

const search = ref(props.filters.search ?? '')
const active = ref(!!props.filters.active)

let timer = null
watch([search, active], () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/library/props', {
            search: search.value || undefined,
            active: active.value || undefined,
        }, { preserveState: true, replace: true })
    }, 300)
})

function deleteProp(id, name) {
    if (!confirm(`¿Eliminar "${name}"?`)) return
    router.delete(`/library/props/${id}`)
}
</script>

<template>
    <Head title="Props — Biblioteca" />
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-text-primary">Props</h1>
                <p class="text-sm text-text-muted mt-0.5">Objetos y accesorios reutilizables en prompts</p>
            </div>
            <Link href="/library/props/create" class="inline-flex items-center gap-2 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                <Plus class="w-4 h-4" />
                Nuevo prop
            </Link>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                <input v-model="search" type="text" placeholder="Buscar prop..." class="w-full bg-surface-1 border border-border rounded-lg pl-9 pr-3 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-amber transition-colors" />
            </div>
            <label class="flex items-center gap-2 text-sm text-text-secondary cursor-pointer select-none">
                <input v-model="active" type="checkbox" class="accent-amber" />
                Solo activos
            </label>
        </div>

        <div v-if="items.data.length === 0" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
            <Package class="w-10 h-10 text-text-muted mx-auto mb-3" />
            <p class="text-text-muted text-sm">No hay props aún</p>
            <Link href="/library/props/create" class="mt-3 inline-flex text-sm text-amber hover:underline">Crear el primero</Link>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="prop in items.data" :key="prop.id" class="bg-surface-1 border border-border rounded-xl p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span :class="['w-2 h-2 rounded-full shrink-0', prop.is_active ? 'bg-success' : 'bg-surface-3']" />
                        <p class="text-sm font-medium text-text-primary">{{ prop.name }}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <Link :href="`/library/props/${prop.id}/edit`" class="p-1 text-text-muted hover:text-text-primary transition-colors">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </Link>
                        <button @click="deleteProp(prop.id, prop.name)" class="p-1 text-text-muted hover:text-danger transition-colors">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
                <p v-if="prop.description" class="text-xs text-text-muted mb-2">{{ prop.description }}</p>
                <pre v-if="prop.prompt_fragment" class="text-xs font-mono text-text-secondary bg-surface-2 rounded px-2 py-1.5 whitespace-pre-wrap line-clamp-3">{{ prop.prompt_fragment }}</pre>
            </div>
        </div>

        <div v-if="items.last_page > 1" class="flex justify-center gap-2 mt-8">
            <Link v-for="link in items.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                :class="['px-3 py-1.5 text-sm rounded-lg border transition-colors', link.active ? 'bg-amber text-surface-0 border-amber font-semibold' : link.url ? 'bg-surface-1 border-border text-text-secondary hover:text-text-primary' : 'bg-surface-1 border-border text-text-muted cursor-default opacity-40']" />
        </div>
    </AppLayout>
</template>
