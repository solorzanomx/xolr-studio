<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    BarChart2, ChevronLeft, AlertTriangle, CheckCircle, Zap,
    Users, Image, Sparkles, TrendingUp, Shield, Clock, DollarSign,
    X, Camera
} from '@lucide/vue'

const props = defineProps({
    project: Object,
    stats:   Object,
    canvas:  Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash ?? {})

const activeTab = ref('overview')

// ── Helpers ────────────────────────────────────────────────────

const GRADE_COLORS = {
    S: 'text-amber',
    A: 'text-emerald-400',
    B: 'text-violet',
    C: 'text-warning',
    D: 'text-danger',
    'N/A': 'text-text-muted',
}

const ALERT_LEVEL_COLORS = {
    critical: 'border-danger/40 bg-danger/5 text-danger',
    warning:  'border-warning/40 bg-warning/5 text-warning',
    info:     'border-border bg-surface-2 text-text-muted',
}

const ALERT_TYPE_ICON = {
    budget:   DollarSign,
    lora:     Sparkles,
    workflow: Clock,
    coverage: Image,
    quality:  Shield,
}

const LORA_HEALTH_COLOR = {
    good:     'text-emerald-400',
    warning:  'text-amber',
    critical: 'text-danger',
}

const LORA_HEALTH_LABEL = {
    good:     'Saludable',
    warning:  'Atención',
    critical: 'Crítico',
}

function barWidth(value, max = 100) {
    return `${Math.min(100, Math.round((value / Math.max(max, 1)) * 100))}%`
}

// Canvas helpers
function shotHasCharacter(shot, characterId) {
    return shot.character_ids?.includes(characterId)
}

function renderIsUrl(path) {
    return path?.startsWith('http')
}
</script>

<template>
    <AppLayout>
        <Head :title="`Intelligence — ${project.name}`" />

        <div class="max-w-5xl mx-auto px-6 py-8">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link :href="`/projects/${project.id}`" class="text-text-muted hover:text-text-primary transition-colors">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest">Intelligence Engine</p>
                    <h1 class="text-lg font-semibold text-text-primary truncate">{{ project.name }}</h1>
                </div>
                <!-- Production Score -->
                <div class="text-center bg-surface-1 border border-border rounded-xl px-5 py-3 shrink-0">
                    <p class="text-[10px] font-mono text-text-muted uppercase tracking-widest mb-1">Production Score</p>
                    <div class="flex items-baseline gap-2 justify-center">
                        <span class="text-3xl font-bold font-mono text-text-primary">{{ stats.production_score.score }}</span>
                        <span class="text-xl font-bold" :class="GRADE_COLORS[stats.production_score.grade]">
                            {{ stats.production_score.grade }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <div v-if="stats.alerts?.length" class="space-y-2 mb-6">
                <div
                    v-for="alert in stats.alerts"
                    :key="alert.message"
                    class="flex items-start gap-2.5 text-sm px-3 py-2.5 border rounded-lg"
                    :class="ALERT_LEVEL_COLORS[alert.level]"
                >
                    <component :is="ALERT_TYPE_ICON[alert.type] ?? AlertTriangle" class="w-4 h-4 shrink-0 mt-0.5" />
                    <span>{{ alert.message }}</span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 border-b border-border mb-6">
                <button
                    v-for="tab in ['overview', 'personajes', 'canvas']"
                    :key="tab"
                    @click="activeTab = tab"
                    :class="[
                        'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors capitalize',
                        activeTab === tab ? 'border-amber text-text-primary' : 'border-transparent text-text-muted hover:text-text-secondary'
                    ]"
                >
                    {{ tab === 'canvas' ? 'Continuity Canvas' : tab.charAt(0).toUpperCase() + tab.slice(1) }}
                </button>
            </div>

            <!-- OVERVIEW TAB -->
            <div v-if="activeTab === 'overview'" class="space-y-6">

                <!-- KPI row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-surface-1 border border-border rounded-xl p-4">
                        <p class="text-[10px] text-text-muted uppercase tracking-wider mb-2">Shots totales</p>
                        <p class="text-2xl font-bold font-mono text-text-primary">{{ stats.shots.total }}</p>
                        <p class="text-xs text-text-muted mt-1">{{ stats.shots.completion_pct }}% con render</p>
                    </div>
                    <div class="bg-surface-1 border border-border rounded-xl p-4">
                        <p class="text-[10px] text-text-muted uppercase tracking-wider mb-2">Renders totales</p>
                        <p class="text-2xl font-bold font-mono text-text-primary">{{ stats.renders.total }}</p>
                        <p class="text-xs text-emerald-400 mt-1">{{ stats.renders.approved }} aprobados</p>
                    </div>
                    <div class="bg-surface-1 border border-border rounded-xl p-4">
                        <p class="text-[10px] text-text-muted uppercase tracking-wider mb-2">Tasa aprobación</p>
                        <p class="text-2xl font-bold font-mono text-text-primary">{{ stats.renders.approval_rate }}%</p>
                        <p class="text-xs text-text-muted mt-1">{{ stats.renders.failed }} fallidos</p>
                    </div>
                    <div class="bg-surface-1 border border-border rounded-xl p-4">
                        <p class="text-[10px] text-text-muted uppercase tracking-wider mb-2">Costo total GPU</p>
                        <p class="text-2xl font-bold font-mono text-text-primary">${{ stats.renders.total_cost_usd }}</p>
                        <p class="text-xs text-text-muted mt-1">${{ stats.renders.avg_cost_per_render }} avg/render</p>
                    </div>
                </div>

                <!-- Production Score breakdown -->
                <div class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-4">Desglose de Production Score</p>
                    <div class="space-y-3">
                        <div v-for="[key, label, weight] in [
                            ['render_completion', 'Renders completados', '40%'],
                            ['prompt_coverage',   'Cobertura de prompts', '20%'],
                            ['quality_rate',      'Tasa de calidad',      '30%'],
                            ['budget_health',     'Salud de presupuesto', '10%'],
                        ]" :key="key">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-text-muted">{{ label }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-mono text-text-muted">peso {{ weight }}</span>
                                    <span class="text-xs font-mono text-text-primary">{{ stats.production_score.breakdown[key] }}%</span>
                                </div>
                            </div>
                            <div class="h-1.5 bg-surface-2 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-amber rounded-full transition-all"
                                    :style="{ width: barWidth(stats.production_score.breakdown[key]) }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shot status breakdown -->
                <div class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-4">Estado de shots</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div
                            v-for="[status, label, color] in [
                                ['draft',              'Borrador',          'text-text-muted'],
                                ['prompt_ready',       'Prompt listo',      'text-violet'],
                                ['rendering',          'Renderizando',      'text-amber'],
                                ['completed',          'Completados',       'text-emerald-400'],
                                ['approved',           'Aprobados',         'text-emerald-400'],
                                ['audio_pending',      'Audio pendiente',   'text-warning'],
                                ['lip_sync_pending',   'Lip sync pendiente','text-warning'],
                            ]"
                            :key="status"
                            class="text-center"
                        >
                            <p class="text-xl font-bold font-mono" :class="color">
                                {{ stats.shots.status_counts[status] ?? 0 }}
                            </p>
                            <p class="text-[10px] text-text-muted mt-0.5">{{ label }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quality tiers -->
                <div v-if="Object.keys(stats.renders.by_quality ?? {}).length" class="bg-surface-1 border border-border rounded-xl p-5">
                    <p class="text-xs font-semibold text-text-primary mb-4">Renders por tier de calidad</p>
                    <div class="space-y-3">
                        <div v-for="(data, tier) in stats.renders.by_quality" :key="tier">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="capitalize text-xs text-text-primary">{{ tier }}</span>
                                    <span class="text-[10px] font-mono text-text-muted">{{ data.approved }}/{{ data.count }} aprobados</span>
                                </div>
                                <span class="text-xs font-mono text-text-muted">${{ data.cost }}</span>
                            </div>
                            <div class="h-1.5 bg-surface-2 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-violet rounded-full"
                                    :style="{ width: barWidth(data.approved, data.count) }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PERSONAJES TAB -->
            <div v-if="activeTab === 'personajes'">
                <div v-if="!stats.character_insights?.length" class="text-center py-12 text-text-muted text-sm">
                    No hay personajes asignados a shots en este proyecto aún.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="char in stats.character_insights"
                        :key="char.id"
                        class="bg-surface-1 border border-border rounded-xl p-4"
                    >
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div>
                                <p class="text-sm font-medium text-text-primary">{{ char.name }}</p>
                                <div class="flex gap-3 mt-1">
                                    <span class="text-xs text-text-muted">{{ char.total_shots }} shots</span>
                                    <span class="text-xs text-text-muted">{{ char.approved_renders }} renders aprobados</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-lg font-bold font-mono" :class="LORA_HEALTH_COLOR[char.lora_health]">
                                    {{ char.approval_rate }}%
                                </p>
                                <p class="text-[10px] font-mono" :class="LORA_HEALTH_COLOR[char.lora_health]">
                                    {{ LORA_HEALTH_LABEL[char.lora_health] }}
                                </p>
                            </div>
                        </div>

                        <!-- Approval rate bar -->
                        <div>
                            <div class="flex justify-between text-[10px] text-text-muted mb-1">
                                <span>Tasa de aprobación LoRA</span>
                                <span>{{ char.approval_rate }}%</span>
                            </div>
                            <div class="h-2 bg-surface-2 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="{
                                        'bg-emerald-400': char.lora_health === 'good',
                                        'bg-amber': char.lora_health === 'warning',
                                        'bg-danger': char.lora_health === 'critical',
                                    }"
                                    :style="{ width: barWidth(char.approval_rate) }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTINUITY CANVAS TAB -->
            <div v-if="activeTab === 'canvas'">
                <p class="text-xs text-text-muted mb-4">
                    Vista de renders aprobados por personaje × timeline de shots.
                </p>

                <div v-if="!canvas.shots?.length" class="text-center py-12 text-text-muted text-sm">
                    No hay shots con renders aprobados aún.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="text-xs border-collapse min-w-max">
                        <!-- Header: shots -->
                        <thead>
                            <tr>
                                <th class="sticky left-0 bg-surface-0 z-10 text-left p-2 pr-4 border-b border-r border-border text-text-muted font-normal">
                                    Personaje
                                </th>
                                <th
                                    v-for="shot in canvas.shots"
                                    :key="shot.id"
                                    class="p-2 border-b border-border text-center font-mono text-text-muted min-w-[52px]"
                                    :title="shot.description"
                                >
                                    {{ shot.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="char in canvas.characters"
                                :key="char.id"
                                class="hover:bg-surface-1 transition-colors"
                            >
                                <td class="sticky left-0 bg-surface-0 z-10 p-2 pr-4 border-r border-b border-border text-text-secondary whitespace-nowrap">
                                    {{ char.name }}
                                </td>
                                <td
                                    v-for="shot in canvas.shots"
                                    :key="shot.id"
                                    class="p-1 border-b border-border text-center align-middle"
                                >
                                    <template v-if="shotHasCharacter(shot, char.id)">
                                        <!-- Has approved render -->
                                        <div v-if="shot.render_url" class="relative group w-10 h-10 mx-auto">
                                            <img
                                                v-if="renderIsUrl(shot.render_url)"
                                                :src="shot.render_url"
                                                :alt="`${char.name} Shot ${shot.label}`"
                                                class="w-10 h-10 rounded object-cover"
                                            />
                                            <div v-else class="w-10 h-10 rounded bg-surface-2 flex items-center justify-center">
                                                <Image class="w-4 h-4 text-text-muted" />
                                            </div>
                                            <!-- Tier badge -->
                                            <span
                                                v-if="shot.render_tier"
                                                class="absolute -top-1 -right-1 text-[8px] font-mono px-1 rounded"
                                                :class="{
                                                    'bg-surface-2 text-text-muted': shot.render_tier === 'draft',
                                                    'bg-violet/20 text-violet': shot.render_tier === 'standard',
                                                    'bg-amber/20 text-amber': shot.render_tier === 'final',
                                                }"
                                            >{{ shot.render_tier[0].toUpperCase() }}</span>
                                        </div>
                                        <!-- In scene but no approved render yet -->
                                        <div v-else class="w-10 h-10 mx-auto rounded border border-dashed border-border flex items-center justify-center">
                                            <Camera class="w-3 h-3 text-text-muted opacity-40" />
                                        </div>
                                    </template>
                                    <!-- Character not in this shot -->
                                    <div v-else class="w-10 h-10 mx-auto" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Continuity alerts from flash -->
                <div v-if="flash.continuity_check" class="mt-6 bg-surface-1 border border-border rounded-xl">
                    <div class="flex items-center gap-2 p-4 border-b border-border">
                        <Shield class="w-4 h-4 text-violet" />
                        <p class="text-sm font-medium text-text-primary">Resultado de verificación de continuidad</p>
                    </div>
                    <div class="p-4 space-y-2">
                        <p v-if="flash.continuity_check.summary" class="text-sm text-text-secondary">
                            {{ flash.continuity_check.summary }}
                        </p>
                        <div
                            v-for="issue in (flash.continuity_check.issues ?? [])"
                            :key="issue.description"
                            class="flex gap-2 text-xs p-3 bg-danger/5 border border-danger/20 rounded-lg"
                        >
                            <AlertTriangle class="w-3.5 h-3.5 text-danger shrink-0 mt-0.5" />
                            <div>
                                <p class="text-text-primary font-medium capitalize">Shot {{ issue.shot_from }} → {{ issue.shot_to }}: {{ issue.type }}</p>
                                <p class="text-text-muted mt-0.5">{{ issue.description }}</p>
                                <p class="text-violet mt-1">→ {{ issue.suggestion }}</p>
                            </div>
                        </div>
                        <p v-if="!flash.continuity_check.has_issues" class="flex items-center gap-2 text-sm text-emerald-400">
                            <CheckCircle class="w-4 h-4" /> Sin problemas de continuidad detectados.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
