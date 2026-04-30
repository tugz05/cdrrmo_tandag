<template>
    <li class="list-group-item">
        <div class="d-flex gap-3 align-items-center justify-content-between flex-wrap">
            <div class="d-flex gap-3 align-items-center"><slot name="start"></slot></div>
            <div class="d-flex gap-3 align-items-center"><slot name="end"></slot></div>
        </div>
        <slot></slot>
    </li>
</template>