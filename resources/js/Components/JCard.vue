<template>
    <div class="card shadow-md">
        <div v-if="$slots.header" class="card-header border-bottom py-3">
            <slot name="header"></slot>
        </div>
        <div class="card-body">
            <slot></slot>
        </div>
        <div v-if="$slots.footer" class="card-footer py-3">
            <slot name="footer"></slot>
        </div>
    </div>
</template>