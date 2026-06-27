<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Bell, CheckCircle, AlertTriangle, Send, Sparkles,
    DollarSign, X, Trash2, Check
} from '@lucide/vue'

const props = defineProps({
    notifications: Object,  // paginated
    unreadCount:   Number,
    tab:           String,
})

function changeTab(t) {
    router.get('/notifications', { tab: t }, { preserveState: true, replace: true })
}

function markRead(id) {
    router.post(`/notifications/${id}/read`, {}, { preserveScroll: true })
}

function markAllRead() {
    router.post('/notifications/read-all', {}, { preserveScroll: true })
}

function destroy(id) {
    router.delete(`/notifications/${id}`, { preserveScroll: true })
}

function destroyAll() {
    if (!confirm('¿Eliminar todas las notificaciones?')) return
    router.delete('/notifications', { preserveScroll: true })
}

const NOTIF_ICON = {
    'check-circle':   CheckCircle,
    'alert-triangle': AlertTriangle,
    'send':           Send,
    'sparkles':       Sparkles,
    'dollar-sign':    DollarSign,
    'bell':           Bell,
}

const NOTIF_COLOR = {
    render_completed:      'text-emerald-400 bg-emerald-400/10',
    render_failed:         'text-danger bg-danger/10',
    post_published:        'text-violet bg-violet/10',
    post_failed:           'text-danger bg-danger/10',
    budget_alert:          'text-amber bg-amber/10',
    ai_director_completed: 'text-violet bg-violet/10',
}

const TYPE_LABEL = {
    render_completed:      'Render',
    render_failed:         'Render',
    post_published:        'Social',
    post_failed:           'Social',
    budget_alert:          'Budget',
    ai_director_completed: 'AI',
}
</script>

<template>
    <AppLayout>
        <Head title="Notificaciones" />

        <div class="max-w-2xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Notificaciones</h1>
                    <p class="text-sm text-text-muted">
                        {{ unreadCount > 0 ? `${unreadCount} sin leer` : 'Todo al día' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="unreadCount > 0" @click="markAllRead"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-surface-2 border border-border text-text-secondary rounded-lg hover:border-amber hover:text-text-primary transition-colors">
                        <Check class="w-3.5 h-3.5" /> Marcar todo leído
                    </button>
                    <button @click="destroyAll"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-surface-2 border border-border text-text-secondary rounded-lg hover:border-danger hover:text-danger transition-colors">
                        <Trash2 class="w-3.5 h-3.5" /> Limpiar todo
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 bg-surface-1 border border-border rounded-xl p-1 mb-5">
                <button
                    v-for="t in [['all', 'Todas'], ['unread', 'Sin leer']]"
                    :key="t[0]"
                    @click="changeTab(t[0])"
                    :class="[
                        'flex-1 py-1.5 text-xs font-semibold rounded-lg transition-colors',
                        tab === t[0] ? 'bg-amber text-surface-0' : 'text-text-muted hover:text-text-primary'
                    ]"
                >
                    {{ t[1] }}
                    <span v-if="t[0] === 'unread' && unreadCount > 0"
                        class="ml-1 px-1.5 py-0.5 rounded-full text-[9px]"
                        :class="tab === 'unread' ? 'bg-surface-0/20' : 'bg-amber/20 text-amber'">
                        {{ unreadCount }}
                    </span>
                </button>
            </div>

            <!-- Notification list -->
            <div class="space-y-2">
                <div
                    v-for="n in notifications.data"
                    :key="n.id"
                    class="flex items-start gap-4 bg-surface-1 border rounded-xl px-4 py-3.5 transition-colors"
                    :class="n.read_at ? 'border-border opacity-60' : 'border-border hover:border-border-hover'"
                >
                    <!-- Icon -->
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                        :class="(NOTIF_COLOR[n.type] ?? 'text-text-muted bg-surface-2').split(' ').slice(1).join(' ')">
                        <component :is="NOTIF_ICON[n.icon] ?? Bell"
                            class="w-4 h-4"
                            :class="(NOTIF_COLOR[n.type] ?? 'text-text-muted').split(' ')[0]" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-mono px-1.5 py-0.5 bg-surface-2 text-text-muted rounded">
                                {{ TYPE_LABEL[n.type] ?? n.type }}
                            </span>
                            <span v-if="!n.read_at" class="w-1.5 h-1.5 rounded-full bg-amber"></span>
                        </div>
                        <p class="text-sm font-semibold text-text-primary">{{ n.title }}</p>
                        <p class="text-xs text-text-muted mt-0.5 truncate">{{ n.body }}</p>
                        <p class="text-[10px] text-text-muted mt-1">{{ n.created_at }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 shrink-0">
                        <Link v-if="n.url" :href="n.url"
                            class="text-[10px] px-2 py-1 bg-surface-2 text-text-muted rounded hover:text-text-primary transition-colors">
                            Ver →
                        </Link>
                        <button v-if="!n.read_at" @click="markRead(n.id)"
                            class="p-1 text-text-muted hover:text-emerald-400 transition-colors" title="Marcar leído">
                            <Check class="w-3.5 h-3.5" />
                        </button>
                        <button @click="destroy(n.id)"
                            class="p-1 text-text-muted hover:text-danger transition-colors" title="Eliminar">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!notifications.data?.length" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
                    <Bell class="w-10 h-10 text-text-muted mx-auto mb-3" />
                    <p class="text-sm text-text-secondary">
                        {{ tab === 'unread' ? 'No hay notificaciones sin leer.' : 'Sin notificaciones todavía.' }}
                    </p>
                    <p class="text-xs text-text-muted mt-1">
                        Aparecerán aquí cuando completen renders, posts o análisis de AI Director.
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="flex items-center justify-center gap-3 mt-6">
                <Link v-if="notifications.prev_page_url" :href="notifications.prev_page_url"
                    class="px-3 py-1.5 text-xs bg-surface-1 border border-border rounded-lg text-text-muted hover:text-text-primary transition-colors">
                    ← Anterior
                </Link>
                <span class="text-xs text-text-muted font-mono">{{ notifications.current_page }} / {{ notifications.last_page }}</span>
                <Link v-if="notifications.next_page_url" :href="notifications.next_page_url"
                    class="px-3 py-1.5 text-xs bg-surface-1 border border-border rounded-lg text-text-muted hover:text-text-primary transition-colors">
                    Siguiente →
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
