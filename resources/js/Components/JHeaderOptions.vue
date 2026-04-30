<template>
    <div class="slide-up-1 d-flex flex-wrap gap-3 align-items-center justify-content-between mb-2 rounded py-2 pt-0">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <slot name="start"></slot>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-3">
            <slot name="center"></slot>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-3">
            <slot name="end"></slot>
        </div>
    </div>
</template>