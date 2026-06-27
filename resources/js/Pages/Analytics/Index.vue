<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    BarChart2, RefreshCw, Eye, Heart, Share2, MessageSquare,
    TrendingUp, Users, Video
} from '@lucide/vue'

const props = defineProps({
    project:        Object,
    projects:       Array,
    byPlatform:     Object,
    topPosts:       Array,
    daily:          Array,
    days:           Number,
    publishedCount: Number,
})

const syncing = ref(false)

function syncNow() {
    syncing.value = true
    router.post('/analytics/sync', { project_id: props.project?.id }, {
        onFinish: () => { syncing.value = false },
    })
}

function changeProject(id) {
    router.get('/analytics', { project_id: id, days: props.days }, { preserveState: false })
}

function changeDays(d) {
    router.get('/analytics', { project_id: props.project?.id, days: d }, { preserveState: false })
}

// Bar chart helpers
const maxViews = computed(() => Math.max(...(props.daily?.map(d => d.views) ?? [1]), 1))

function barHeight(value) {
    return Math.max(2, Math.round((value / maxViews.value) * 100))
}

const PLATFORM_META = {
    instagram: { label: 'Instagram', color: 'text-pink-400',  bg: 'bg-pink-500/10' },
    youtube:   { label: 'YouTube',   color: 'text-red-400',   bg: 'bg-red-500/10'  },
    tiktok:    { label: 'TikTok',    color: 'text-text-primary', bg: 'bg-surface-3' },
    facebook:  { label: 'Facebook',  color: 'text-blue-400',  bg: 'bg-blue-500/10' },
}

function fmt(n) {
    if (!n) return '0'
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
    return String(n)
}

const DAY_OPTIONS = [7, 30, 90]
</script>

<template>
    <AppLayout>
        <Head title="Analytics" />

        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Analytics</h1>
                    <p class="text-sm text-text-muted">Rendimiento por plataforma — {{ project?.name ?? 'Todos los proyectos' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Days filter -->
                    <div class="flex items-center bg-surface-2 border border-border rounded-lg overflow-hidden">
                        <button
                            v-for="d in DAY_OPTIONS" :key="d"
                            @click="changeDays(d)"
                            :class="[
                                'px-3 py-1.5 text-xs transition-colors',
                                days === d ? 'bg-amber text-surface-0 font-semibold' : 'text-text-muted hover:text-text-primary'
                            ]"
                        >{{ d }}d</button>
                    </div>

                    <!-- Project selector -->
                    <select
                        :value="project?.id"
                        @change="changeProject($event.target.value)"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                    >
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>

                    <!-- Sync -->
                    <button
                        @click="syncNow"
                        :disabled="syncing"
                        class="flex items-center gap-2 px-3 py-1.5 bg-surface-2 border border-border text-text-secondary text-sm rounded-lg hover:text-text-primary hover:border-amber disabled:opacity-50 transition-colors"
                    >
                        <RefreshCw class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" />
                        Sincronizar
                    </button>
                </div>
            </div>

            <!-- Summary KPIs -->
            <div class="grid grid-cols-4 gap-3 mb-6">
                <div class="bg-surface-1 border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Video class="w-4 h-4 text-text-muted" />
                        <span class="text-xs text-text-muted">Posts publicados</span>
                    </div>
                    <p class="text-2xl font-mono font-bold text-text-primary">{{ fmt(publishedCount) }}</p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Eye class="w-4 h-4 text-text-muted" />
                        <span class="text-xs text-text-muted">Vistas totales</span>
                    </div>
                    <p class="text-2xl font-mono font-bold text-text-primary">
                        {{ fmt(Object.values(byPlatform ?? {}).reduce((a, p) => a + (p.total_views ?? 0), 0)) }}
                    </p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Heart class="w-4 h-4 text-text-muted" />
                        <span class="text-xs text-text-muted">Likes totales</span>
                    </div>
                    <p class="text-2xl font-mono font-bold text-text-primary">
                        {{ fmt(Object.values(byPlatform ?? {}).reduce((a, p) => a + (p.total_likes ?? 0), 0)) }}
                    </p>
                </div>
                <div class="bg-surface-1 border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Users class="w-4 h-4 text-text-muted" />
                        <span class="text-xs text-text-muted">Reach total</span>
                    </div>
                    <p class="text-2xl font-mono font-bold text-text-primary">
                        {{ fmt(Object.values(byPlatform ?? {}).reduce((a, p) => a + (p.total_reach ?? 0), 0)) }}
                    </p>
                </div>
            </div>

            <!-- Per-platform cards -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div
                    v-for="(stats, platform) in byPlatform"
                    :key="platform"
                    class="bg-surface-1 border border-border rounded-xl p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded font-semibold" :class="PLATFORM_META[platform]?.bg + ' ' + PLATFORM_META[platform]?.color">
                                {{ PLATFORM_META[platform]?.label ?? platform }}
                            </span>
                            <span class="text-xs text-text-muted">{{ stats.posts_count }} posts</span>
                        </div>
                        <span class="text-xs font-mono text-amber">{{ stats.avg_engagement }}% eng.</span>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <p class="text-[10px] text-text-muted mb-0.5 flex items-center gap-1"><Eye class="w-3 h-3" /> Vistas</p>
                            <p class="text-lg font-mono font-bold text-text-primary">{{ fmt(stats.total_views) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-text-muted mb-0.5 flex items-center gap-1"><Heart class="w-3 h-3" /> Likes</p>
                            <p class="text-lg font-mono font-bold text-text-primary">{{ fmt(stats.total_likes) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-text-muted mb-0.5 flex items-center gap-1"><Users class="w-3 h-3" /> Reach</p>
                            <p class="text-lg font-mono font-bold text-text-primary">{{ fmt(stats.total_reach) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-text-muted mb-0.5 flex items-center gap-1"><MessageSquare class="w-3 h-3" /> Comentarios</p>
                            <p class="text-lg font-mono font-bold text-text-primary">{{ fmt(stats.total_comments) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-text-muted mb-0.5 flex items-center gap-1"><Share2 class="w-3 h-3" /> Shares</p>
                            <p class="text-lg font-mono font-bold text-text-primary">{{ fmt(stats.total_shares) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-text-muted mb-0.5 flex items-center gap-1"><TrendingUp class="w-3 h-3" /> CTR</p>
                            <p class="text-lg font-mono font-bold text-text-primary">{{ stats.avg_ctr }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!Object.keys(byPlatform ?? {}).length"
                    class="col-span-2 bg-surface-1 border border-border rounded-xl p-12 text-center">
                    <BarChart2 class="w-10 h-10 text-text-muted mx-auto mb-3" />
                    <p class="text-sm text-text-secondary">Sin datos de analytics todavía.</p>
                    <p class="text-xs text-text-muted mt-1">Publica contenido y sincroniza para ver métricas aquí.</p>
                    <button @click="syncNow" class="mt-4 px-4 py-2 bg-amber text-surface-0 text-sm font-semibold rounded-lg hover:bg-amber/90 transition-colors">
                        Sincronizar ahora
                    </button>
                </div>
            </div>

            <!-- Daily trend chart + Top posts (two-column) -->
            <div class="grid grid-cols-5 gap-4">

                <!-- Daily trend bar chart -->
                <div class="col-span-3 bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-4">Vistas diarias — últimos {{ days }} días</p>

                    <div v-if="daily?.length" class="flex items-end gap-0.5 h-28">
                        <div
                            v-for="point in daily"
                            :key="point.date"
                            class="flex-1 flex flex-col items-center justify-end gap-0.5 group"
                        >
                            <div
                                class="w-full bg-amber/30 hover:bg-amber/60 rounded-sm transition-colors cursor-default"
                                :style="{ height: barHeight(point.views) + '%' }"
                                :title="`${point.date}: ${fmt(point.views)} vistas`"
                            ></div>
                        </div>
                    </div>
                    <div v-else class="h-28 flex items-center justify-center">
                        <p class="text-xs text-text-muted">Sin datos</p>
                    </div>

                    <!-- X axis labels (first, mid, last) -->
                    <div v-if="daily?.length > 2" class="flex justify-between mt-2">
                        <span class="text-[10px] font-mono text-text-muted">{{ daily[0]?.date?.slice(5) }}</span>
                        <span class="text-[10px] font-mono text-text-muted">{{ daily[Math.floor(daily.length/2)]?.date?.slice(5) }}</span>
                        <span class="text-[10px] font-mono text-text-muted">{{ daily[daily.length-1]?.date?.slice(5) }}</span>
                    </div>
                </div>

                <!-- Top posts -->
                <div class="col-span-2 bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-4">Top posts</p>
                    <div class="space-y-2">
                        <div
                            v-for="(post, i) in topPosts"
                            :key="post.platform_post_id"
                            class="flex items-center gap-3 py-2 border-b border-border last:border-0"
                        >
                            <span class="text-xs font-mono text-text-muted w-4">{{ i + 1 }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded shrink-0"
                                :class="PLATFORM_META[post.platform]?.bg + ' ' + PLATFORM_META[post.platform]?.color">
                                {{ PLATFORM_META[post.platform]?.label ?? post.platform }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-text-muted font-mono truncate">{{ post.platform_post_id }}</p>
                                <p class="text-[10px] text-text-muted">{{ post.date }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-mono font-bold text-text-primary">{{ fmt(post.views) }}</p>
                                <p class="text-[10px] text-text-muted">{{ post.engagement_rate }}% eng</p>
                            </div>
                        </div>
                        <p v-if="!topPosts?.length" class="text-xs text-text-muted text-center py-4">Sin posts publicados aún</p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
