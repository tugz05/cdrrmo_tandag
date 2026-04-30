import { reactive } from "vue"

export function useFetchCallerInfo() {
    const caller = reactive({
        data: [],
        processing: false
    })

    const fetchCaller = async (callerId) => {
        console.log(callerId)
        caller.processing = true
        try {
            const data = await (await fetch(route('caller-info.index', callerId))).json()
            // console.log(data)
            caller.data = data
        } catch (e) {
            caller.processing = false
            throw e;
        } finally {
            caller.processing = false
        }
    }

    return {
        fetchCaller,
        caller
    }
}