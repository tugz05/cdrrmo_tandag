import { reactive } from "vue"

export function useReportImages() {
    const reportImages = reactive({
        data: [],
        processing: false
    })

    const fetchReportImages = async (reportId) => {
        console.log(reportId)
        reportImages.processing = true
        try {
            const data = await (await fetch(route('report.images', reportId))).json()
            console.log(data)
            reportImages.data = data
        } catch (e) {
            reportImages.processing = false
            throw e;
        } finally {
            reportImages.processing = false
        }
    }

    return {
        fetchReportImages,
        reportImages
    }
}