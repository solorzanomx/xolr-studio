<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DollarSign, RefreshCw, Download, TrendingUp, AlertTriangle,
    CheckCircle, ChevronLeft, ChevronRight, Image, Mic, Video
} from '@lucide/vue'

const props = defineProps({
    project:     Object,
    projects:    Array,
    budget:      Object,
    actualCosts: Object,
    history:     Array,
    topShots:    Array,
    burnRate:    Number,
    forecast:    Number,
    period:      Object,
    filters:     Object,
})

// --- Navigation ---
const MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']

function changeProject(id) {
    router.get('/budget', { project_id: id, year: props.period.year, month: props.period.month }, { preserveState: false })
}

function navigatePeriod(delta) {
    let m = props.period.month + delta
    let y = props.period.year
    if (m > 12) { m = 1; y++ }
    if (m < 1)  { m = 12; y-- }
    router.get('/budget', { project_id: props.project?.id, year: y, month: m }, { preserveState: false })
}

// --- Budget edit ---
const editingBudget = ref(false)
const budgetForm = useForm({ monthly_budget_usd: props.project?.monthly_budget_usd ?? 0 })

function saveBudget() {
    budgetForm.put(`/projects/${props.project.id}/budget`, {
        onSuccess: () => { editingBudget.value = false },
        preserveScroll: true,
    })
}

// --- Sync ---
const syncing = ref(false)
function syncMonth() {
    syncing.value = true
    router.post(`/projects/${props.project.id}/budget/sync`,
        { year: props.period.year, month: props.period.month },
        { onFinish: () => { syncing.value = false }, preserveScroll: true }
    )
}

// --- Budget utilization ---
const budgetUsd   = computed(() => props.budget?.budget_usd ?? props.project?.monthly_budget_usd ?? 0)
const spentActual = computed(() => props.actualCosts?.total ?? 0)
const utilPct     = computed(() => budgetUsd.value > 0 ? Math.min(100, (spentActual.value / budgetUsd.value) * 100) : 0)

const utilColor = computed(() => {
    if (utilPct.value >= 90) return 'bg-danger'
    if (utilPct.value >= 70) return 'bg-amber'
    return 'bg-emerald-400'
})

const utilTextColor = computed(() => {
    if (utilPct.value >= 90) return 'text-danger'
    if (utilPct.value >= 70) return 'text-amber'
    return 'text-emerald-400'
})

// Cost breakdown for bar
const totalCostForBar = computed(() =>
    Math.max(spentActual.value, 0.0001)
)
function costPct(v) {
    return Math.round((v / totalCostForBar.value) * 100)
}

// Max for history chart
const historyMax = computed(() =>
    Math.max(...(props.history?.map(h => Math.max(h.spent_usd, h.budget_usd)) ?? [1]), 1)
)
function histPct(v) {
    return Math.max(2, Math.round((v / historyMax.value) * 100))
}

function fmt(v) {
    if (v === null || v === undefined) return '—'
    return '$' + parseFloat(v).toFixed(4)
}
function fmtShort(v) {
    if (!v && v !== 0) return '—'
    return '$' + parseFloat(v).toFixed(2)
}

const TIER_COLOR = {
    draft:    'text-text-muted bg-surface-3',
    standard: 'text-amber bg-amber/10',
    final:    'text-emerald-400 bg-emerald-400/10',
}
</script>

<template>
    <AppLayout>
        <Head title="Budget" />

        <div class="max-w-5xl mx-auto px-6 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">Budget</h1>
                    <p class="text-sm text-text-muted">Control de costos de producción por proyecto y período</p>
                </div>
                <div class="flex items-center gap-3">
                    <select
                        :value="project?.id"
                        @change="changeProject($event.target.value)"
                        class="bg-surface-2 border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary focus:outline-none focus:border-amber transition-colors"
                    >
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <button @click="syncMonth" :disabled="syncing || !project"
                        class="flex items-center gap-2 px-3 py-1.5 bg-surface-2 border border-border text-text-secondary text-sm rounded-lg hover:border-amber disabled:opacity-40 transition-colors">
                        <RefreshCw class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" />
                        Sincronizar
                    </button>
                    <a v-if="project" :href="`/projects/${project.id}/budget/export`"
                        class="flex items-center gap-2 px-3 py-1.5 bg-surface-2 border border-border text-text-secondary text-sm rounded-lg hover:border-amber transition-colors">
                        <Download class="w-4 h-4" /> CSV
                    </a>
                </div>
            </div>

            <div v-if="!project" class="bg-surface-1 border border-border rounded-xl p-16 text-center">
                <DollarSign class="w-10 h-10 text-text-muted mx-auto mb-3" />
                <p class="text-sm text-text-secondary">Selecciona un proyecto para ver su presupuesto.</p>
            </div>

            <template v-else>
                <!-- Period nav -->
                <div class="flex items-center justify-between bg-surface-1 border border-border rounded-xl px-4 py-3 mb-5">
                    <button @click="navigatePeriod(-1)" class="p-1 text-text-muted hover:text-text-primary transition-colors">
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    <h2 class="text-base font-semibold text-text-primary">
                        {{ MONTHS[period.month - 1] }} {{ period.year }}
                    </h2>
                    <button @click="navigatePeriod(1)" class="p-1 text-text-muted hover:text-text-primary transition-colors">
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>

                <!-- Main budget card -->
                <div class="bg-surface-1 border border-border rounded-xl p-6 mb-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-text-muted mb-1">Presupuesto mensual</p>
                            <div class="flex items-center gap-3">
                                <template v-if="!editingBudget">
                                    <p class="text-3xl font-mono font-bold text-text-primary">{{ fmtShort(budgetUsd) }}</p>
                                    <button @click="editingBudget = true; budgetForm.monthly_budget_usd = project.monthly_budget_usd"
                                        class="text-xs text-text-muted hover:text-amber transition-colors">Editar</button>
                                </template>
                                <template v-else>
                                    <div class="flex items-center gap-2">
                                        <span class="text-text-muted font-mono">$</span>
                                        <input v-model="budgetForm.monthly_budget_usd" type="number" step="0.01" min="0"
                                            class="w-32 bg-surface-2 border border-amber rounded-lg px-3 py-1.5 text-sm font-mono text-text-primary focus:outline-none"
                                            @keyup.enter="saveBudget" autofocus />
                                        <button @click="saveBudget" :disabled="budgetForm.processing"
                                            class="px-3 py-1.5 bg-amber text-surface-0 text-xs font-semibold rounded-lg hover:bg-amber/90 disabled:opacity-50 transition-colors">
                                            Guardar
                                        </button>
                                        <button @click="editingBudget = false" class="text-xs text-text-muted hover:text-text-primary transition-colors">
                                            Cancelar
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-text-muted mb-1">Gastado este mes</p>
                            <p class="text-3xl font-mono font-bold" :class="utilTextColor">{{ fmtShort(spentActual) }}</p>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-2">
                        <div class="flex items-center justify-between text-xs text-text-muted mb-1.5">
                            <span>{{ utilPct.toFixed(1) }}% utilizado</span>
                            <span class="font-mono">{{ fmtShort(budgetUsd - spentActual) }} restante</span>
                        </div>
                        <div class="h-3 bg-surface-3 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500" :class="utilColor"
                                :style="{ width: utilPct + '%' }" />
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div v-if="utilPct >= 90"
                        class="mt-3 flex items-center gap-2 px-3 py-2 bg-danger/10 text-danger text-xs rounded-lg">
                        <AlertTriangle class="w-4 h-4 shrink-0" />
                        Presupuesto casi agotado — {{ (100 - utilPct).toFixed(1) }}% restante
                    </div>
                    <div v-else-if="utilPct >= 70"
                        class="mt-3 flex items-center gap-2 px-3 py-2 bg-amber/10 text-amber text-xs rounded-lg">
                        <AlertTriangle class="w-4 h-4 shrink-0" />
                        70% del presupuesto consumido
                    </div>
                </div>

                <!-- Cost breakdown + Burn rate -->
                <div class="grid grid-cols-3 gap-4 mb-5">

                    <!-- Cost breakdown -->
                    <div class="col-span-2 bg-surface-1 border border-border rounded-xl p-5">
                        <p class="text-xs font-semibold text-text-primary mb-4">Desglose de costos — {{ MONTHS[period.month - 1] }}</p>

                        <div class="space-y-3">
                            <!-- Renders -->
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="flex items-center gap-1.5 text-text-secondary"><Image class="w-3 h-3" /> Renders GPU</span>
                                    <span class="font-mono text-amber">{{ fmt(actualCosts.render_cost) }}</span>
                                </div>
                                <div class="h-2 bg-surface-3 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber/60 rounded-full transition-all duration-500"
                                        :style="{ width: costPct(actualCosts.render_cost) + '%' }" />
                                </div>
                            </div>

                            <!-- Audio -->
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="flex items-center gap-1.5 text-text-secondary"><Mic class="w-3 h-3" /> Audio (ElevenLabs)</span>
                                    <span class="font-mono text-violet">{{ fmt(actualCosts.audio_cost) }}</span>
                                </div>
                                <div class="h-2 bg-surface-3 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet/60 rounded-full transition-all duration-500"
                                        :style="{ width: costPct(actualCosts.audio_cost) + '%' }" />
                                </div>
                            </div>

                            <!-- Lip Sync -->
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="flex items-center gap-1.5 text-text-secondary"><Video class="w-3 h-3" /> Lip Sync (D-ID)</span>
                                    <span class="font-mono text-blue-400">{{ fmt(actualCosts.lipsync_cost) }}</span>
                                </div>
                                <div class="h-2 bg-surface-3 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-400/60 rounded-full transition-all duration-500"
                                        :style="{ width: costPct(actualCosts.lipsync_cost) + '%' }" />
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="pt-2 border-t border-border flex items-center justify-between">
                                <span class="text-xs font-semibold text-text-primary">Total</span>
                                <span class="font-mono font-bold text-sm" :class="utilTextColor">{{ fmt(actualCosts.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Burn rate + Forecast -->
                    <div class="bg-surface-1 border border-border rounded-xl p-5 flex flex-col gap-4">
                        <div>
                            <p class="text-xs font-semibold text-text-primary mb-3">Burn rate</p>
                            <div class="flex items-center gap-2 mb-1">
                                <TrendingUp class="w-4 h-4 text-amber" />
                                <p class="text-xs text-text-muted">Promedio diario</p>
                            </div>
                            <p class="text-2xl font-mono font-bold text-text-primary">{{ fmt(burnRate) }}</p>
                            <p class="text-[10px] text-text-muted mt-0.5">/ día</p>
                        </div>

                        <div class="pt-3 border-t border-border">
                            <p class="text-xs text-text-muted mb-1">Proyección fin de mes</p>
                            <p class="text-xl font-mono font-bold"
                                :class="forecast > budgetUsd ? 'text-danger' : 'text-text-primary'">
                                {{ fmtShort(forecast) }}
                            </p>
                            <div v-if="forecast > budgetUsd"
                                class="mt-1.5 flex items-center gap-1 text-[10px] text-danger">
                                <AlertTriangle class="w-3 h-3" />
                                {{ fmtShort(forecast - budgetUsd) }} sobre presupuesto
                            </div>
                            <div v-else class="mt-1.5 flex items-center gap-1 text-[10px] text-emerald-400">
                                <CheckCircle class="w-3 h-3" />
                                Dentro del presupuesto
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History chart -->
                <div class="bg-surface-1 border border-border rounded-xl p-5 mb-5">
                    <p class="text-xs font-semibold text-text-primary mb-4">Historial mensual — últimos 12 meses</p>
                    <div v-if="history?.length" class="flex items-end gap-1.5 h-28">
                        <div v-for="h in [...history].reverse()" :key="h.label"
                            class="flex-1 flex flex-col items-center gap-0.5 group">
                            <!-- Budget target line -->
                            <div class="relative w-full flex flex-col justify-end gap-0.5" style="height: 100%">
                                <!-- Spent bar -->
                                <div class="w-full rounded-sm transition-all duration-500 relative"
                                    :class="h.spent_usd > h.budget_usd ? 'bg-danger/60' : 'bg-amber/50'"
                                    :style="{ height: histPct(h.spent_usd) + '%' }"
                                    :title="`${h.label}: ${fmtShort(h.spent_usd)} gastado`">
                                </div>
                            </div>
                            <p class="text-[9px] font-mono text-text-muted text-center leading-tight">
                                {{ h.label.split(' ')[0] }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-xs text-text-muted text-center py-8">Sin historial todavía. Haz clic en Sincronizar cada mes.</p>
                </div>

                <!-- Top expensive shots -->
                <div class="bg-surface-1 border border-border rounded-xl">
                    <div class="p-4 border-b border-border">
                        <p class="text-xs font-semibold text-text-primary">Top 10 shots más costosos</p>
                    </div>
                    <div class="divide-y divide-border">
                        <div v-for="(shot, i) in topShots" :key="shot.shot_id"
                            class="flex items-center justify-between px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-mono text-text-muted w-5">{{ i + 1 }}</span>
                                <div>
                                    <p class="text-sm text-text-primary font-medium">{{ shot.label }}</p>
                                    <p class="text-[10px] text-text-muted">{{ shot.date }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] px-1.5 py-0.5 rounded" :class="TIER_COLOR[shot.quality_tier]">
                                    {{ shot.quality_tier }}
                                </span>
                                <span class="text-sm font-mono font-bold text-amber">{{ fmt(shot.cost) }}</span>
                            </div>
                        </div>
                        <div v-if="!topShots?.length" class="px-4 py-8 text-center text-sm text-text-muted">
                            Aún no hay renders con costo registrado.
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
