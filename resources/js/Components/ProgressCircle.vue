<script setup>
const props = defineProps({
    value: {
        type: Number,
        required: true
    }
})

const colorIndicator = () => {
    if (props.value <= 25) {
        return '#e2483d';
    } else if (props.value > 25 && props.value <= 50) {
        return '#e56910'
    } else if (props.value > 50 && props.value <= 75) {
        return '#b38600'
    } else if (props.value > 75 && props.value <= 100) {
        return '#6a9a23'
    }
}
</script>
<template>
    <div class="progress-bar" :style="`
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: 
        radial-gradient(closest-side, var(--ds-surface-overlay) 70%, transparent 80% 100%),
        conic-gradient(${colorIndicator()} ${value}%, #d3d3d36e 0);    
    `">
        <span :style="`
            font-weight: semibold !important; 
            font-size: xx-small; 
            color: ${colorIndicator()};
        `">{{ value }}</span>
    </div>
</template>