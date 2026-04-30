<script setup>
import JUtility from '@/utilities/JUtility';
import { useForm } from '@inertiajs/vue3';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';


const choice = JUtility.choice()
const choiceLabels = JUtility.choiceLabels()

const props = defineProps({
    category_id: null,
    question_id: null
})

const form = useForm({
    choice: {
        category_id: null,
        question_id: null,
        type: null,
        name: null,
        value: null,
        label: null,
        placeholder: null,
        items: null,
        is_required: false
    }
})

const createChoice = async (choiceType) => {
    form.choice.items = splitAndConvertToJSON(form.choice.items)
    form.choice.type = choiceType
    form.choice.category_id = props.category_id
    form.choice.question_id = props.question_id

    await form.post(route('choice.create'), {
        onSuccess: () => {
            form.reset()
        }
    })
}

const splitAndConvertToJSON = (items) => {
    if (items === null) return null
    const splittedItems = items.split('\n')
    return JSON.stringify(splittedItems)
}

</script>
<template>
    <div class="btn-add-choices mt-2 d-inline-block ms-4">
        <div class="px-2 d-flex flex-wrap gap-2 rounded shadow-sm">
            <template v-for="choiceLabel, index in choiceLabels" :key="index">
                <div class="btn-group dropup">
                    <button type="button" class="btn btn-sm btn-link link-primary text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ choiceLabel.name }}
                    </button>
                    <JDropdownMenu class="px-2">
                        <form @submit.prevent="createChoice(choiceLabel.type)">
                            <div class="d-flex align-items-center gap-3">
                                <span>
                                    <input 
                                        v-if="(choiceLabel.type == choice.checkbox) || (choiceLabel.type == choice.radio)" 
                                        type="text" v-model.lazy.trim="form.choice.label" 
                                        class="form-control form-control-sm" 
                                        placeholder="Enter Label" 
                                        style="width:10em" 
                                        required>
                                    <input 
                                        v-if="(choiceLabel.type == choice.shortText) || (choiceLabel.type == choice.longText) || (choiceLabel.type == choice.select)" 
                                        type="text" v-model.lazy.trim="form.choice.label" 
                                        class="form-control form-control-sm" 
                                        placeholder="Enter Label" 
                                        style="width:10em">
                                    <textarea 
                                        v-if="choiceLabel.type == choice.select" 
                                        v-model.trim.lazy="form.choice.items" 
                                        class="form-control form-control-sm mt-2" 
                                        cols="20" 
                                        rows="5" 
                                        placeholder="Enter Items"></textarea>
                                </span>
                                <span>
                                    <input 
                                        v-model.lazy="form.choice.is_required" 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        title="Required">
                                </span>
                                <button type="submit" class="btn btn-sm btn-link text-decoration-none btn-link-primary">OK</button>
                            </div>
                        </form>
                    </JDropdownMenu>
                </div>
            </template>
        </div>
    </div>
</template>
