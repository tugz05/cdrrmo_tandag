
<script setup>
import JUtility from '@/utilities/JUtility';
import { useForm } from '@inertiajs/vue3';


const choice = JUtility.choice()

const props = defineProps({
    choices: {
        type: Array,
        default: () => ([])
    },
    question: {
        type: Object,
        default: () => ({})
    }
})

const form = useForm({})

const deleteChoice = async (id) => {
    if (id === null) return
    if (confirm('Remove?'))
        await form.delete(route('choice.destroy', id))
}

</script>

<template>
    <div>
        <div v-for="choiceItem, index in choices" :key="index">
            <div class="choice-item gap-1 d-flex align-items-center">
                <button 
                    @click.prevent="deleteChoice(choiceItem.id)" 
                    class="btn btn-sm btn-link link-primary" 
                    title="Remove">
                    <i class="bi bi-x-circle"></i>
                </button>
                <label :for="`${question.id} ${index}`" class="form-label m-0 choice-item">
                    <span v-if="(choiceItem.type === choice.radio) || (choiceItem.type === choice.checkbox)">
                        <input 
                            :type="choiceItem.type === choice.radio ? 'radio' : 'checkbox'" 
                            :name="question.question.replace(' ', '').toLowerCase()" 
                            :id="`${question.id} ${index}`" 
                            class="form-check-input"
                        >
                        <span class="ps-2">{{ choiceItem.label }}</span>
                    </span>
                    <input 
                        v-if="choiceItem.type === choice.shortText" 
                        type="text" 
                        class="my-1 form-control"
                        style="max-width:30em">
                    <textarea 
                        v-if="choiceItem.type === choice.longText" 
                        class="my-1 form-control"
                        style="max-width:30em"></textarea>
                    <select 
                        v-if="choiceItem.type === choice.select" 
                        class="my-1 form-select"
                        style="max-width:30em">
                        <option v-for="item, index in JSON.parse(choiceItem.items)" :key="index" :value="item">{{ item }}</option>
                    </select>
                </label>
            </div>
        </div>
    </div>

</template>


<style>
    .btn-add-choices, .choice-item button {
        visibility: hidden;
        opacity: 0;
        transition: 0.3s ease-in;
    }
    .category-questions:hover .btn-add-choices, .choice-item:hover button {
        opacity: 1;
        visibility: visible;
    }
</style>