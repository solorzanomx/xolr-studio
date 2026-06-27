<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Zap, ChevronDown, LogOut, User, Bell, CheckCircle, AlertTriangle, Send, Sparkles, DollarSign, X } from '@lucide/vue'

const props = defineProps({
    sidebarOpen: Boolean,
    user: Object,
})
defineEmits(['toggle-sidebar'])

const page = usePage()
const userMenuOpen  = ref(false)
const bellOpen      = ref(false)

const unreadCount   = computed(() => page.props.unreadCount ?? 0)
const notifications = computed(() => page.props.unreadNotifications ?? [])

function logout() {
    router.post('/logout')
}

function markRead(id) {
    router.post(`/notifications/${id}/read`, {}, { preserveScroll: true })
}

function markAllRead() {
    router.post('/notifications/read-all', {}, {
        onSuccess: () => { bellOpen.value = false },
        preserveScroll: true,
    })
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
    render_completed:       'text-emerald-400',
    render_failed:          'text-danger',
    post_published:         'text-violet',
    post_failed:            'text-danger',
    budget_alert:           'text-amber',
    ai_director_completed:  'text-violet',
}
</script>

<template>
    <header class="h-14 bg-surface-1 border-b border-border flex items-center px-4 gap-4 shrink-0">
        <!-- Active jobs indicator -->
        <div class="flex items-center gap-1.5 text-sm">
            <Zap class="w-3.5 h-3.5 text-amber" />
            <span class="font-mono text-text-secondary text-xs">
                <span class="text-text-primary font-semibold" id="active-jobs-count">0</span> jobs
            </span>
        </div>

        <!-- Cost today -->
        <div class="font-mono text-xs text-text-secondary">
            <span class="text-text-primary font-semibold" id="today-cost">$0.00</span>
            <span class="ml-1">hoy</span>
        </div>

        <div class="flex-1" />

        <!-- Bell icon + dropdown -->
        <div class="relative">
            <button @click="bellOpen = !bellOpen; userMenuOpen = false"
                class="relative p-1.5 text-text-muted hover:text-text-primary transition-colors rounded-lg hover:bg-surface-2">
                <Bell class="w-4 h-4" />
                <span v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-0.5 bg-amber text-surface-0 text-[9px] font-bold rounded-full flex items-center justify-center">
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </button>

            <div v-if="bellOpen"
                class="absolute right-0 top-full mt-2 w-80 bg-surface-2 border border-border rounded-xl shadow-2xl z-50 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-border">
                    <p class="text-xs font-semibold text-text-primary">Notificaciones</p>
                    <div class="flex items-center gap-2">
                        <button v-if="unreadCount > 0" @click="markAllRead"
                            class="text-[10px] text-amber hover:text-amber/80 transition-colors">
                            Marcar todo leído
                        </button>
                        <Link href="/notifications" @click="bellOpen = false"
                            class="text-[10px] text-text-muted hover:text-text-primary transition-colors">
                            Ver todas
                        </Link>
                    </div>
                </div>

                <div v-if="notifications.length" class="divide-y divide-border max-h-72 overflow-y-auto">
                    <div v-for="n in notifications" :key="n.id"
                        class="flex items-start gap-3 px-4 py-3 hover:bg-surface-3 transition-colors">
                        <component :is="NOTIF_ICON[n.icon] ?? Bell"
                            class="w-4 h-4 mt-0.5 shrink-0"
                            :class="NOTIF_COLOR[n.type] ?? 'text-text-muted'" />
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-text-primary">{{ n.title }}</p>
                            <p class="text-[11px] text-text-muted truncate">{{ n.body }}</p>
                            <p class="text-[10px] text-text-muted mt-0.5">{{ n.created_at }}</p>
                        </div>
                        <button @click.stop="markRead(n.id)"
                            class="p-0.5 text-text-muted hover:text-text-primary transition-colors shrink-0 mt-0.5">
                            <X class="w-3 h-3" />
                        </button>
                    </div>
                </div>
                <div v-else class="px-4 py-8 text-center">
                    <Bell class="w-6 h-6 text-text-muted mx-auto mb-2" />
                    <p class="text-xs text-text-muted">Sin notificaciones nuevas</p>
                </div>
            </div>
        </div>

        <!-- User menu -->
        <div class="relative" v-if="user">
            <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2 text-sm text-text-secondary hover:text-text-primary transition-colors"
            >
                <div class="w-7 h-7 rounded-full bg-amber/20 flex items-center justify-center">
                    <span class="text-amber font-semibold text-xs">
                        {{ user.name?.charAt(0) }}
                    </span>
                </div>
                <span class="text-sm hidden sm:block">{{ user.name?.split(' ')[0] }}</span>
                <ChevronDown class="w-3.5 h-3.5" />
            </button>

            <div
                v-if="userMenuOpen"
                class="absolute right-0 top-full mt-2 w-44 bg-surface-2 border border-border rounded-lg shadow-xl py-1 z-50"
            >
                <Link
                    href="/settings"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm text-text-secondary hover:text-text-primary hover:bg-surface-3 transition-colors"
                    @click="userMenuOpen = false"
                >
                    <User class="w-4 h-4" />
                    Configuración
                </Link>
                <div class="border-t border-border my-1" />
                <button
                    @click="logout"
                    class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-danger hover:bg-surface-3 transition-colors"
                >
                    <LogOut class="w-4 h-4" />
                    Cerrar sesión
                </button>
            </div>
        </div>
    </header>
</template>
