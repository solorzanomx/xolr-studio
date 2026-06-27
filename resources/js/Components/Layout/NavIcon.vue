<script setup>
import {
    LayoutGrid, Users, MapPin, Palette, Folder, Camera, Image,
    Mic, Video, Sparkles, BarChart, Calendar, TrendingUp, Settings, Grid3x3,
    Package
} from '@lucide/vue'

const props = defineProps({ name: String, class: String })

const icons = {
    grid: LayoutGrid,
    users: Users,
    'map-pin': MapPin,
    palette: Palette,
    folder: Folder,
    camera: Camera,
    image: Image,
    mic: Mic,
    video: Video,
    sparkles: Sparkles,
    'bar-chart': BarChart,
    calendar: Calendar,
    'trending-up': TrendingUp,
    settings: Settings,
    package: Package,
}
</script>

<template>
    <component :is="icons[name] ?? Grid3x3" :class="props.class" />
</template>
