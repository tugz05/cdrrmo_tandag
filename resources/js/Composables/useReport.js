import { confirmDialog } from "@/Helpers/JConfirmDialog"
import { toggleModal } from "@/Helpers/JModal"
import { toggleOffCanvas } from "@/Helpers/JOffcanvas"
import { useReportImages } from "@/Services/useReportImages"
import { useForm } from "@inertiajs/vue3"

export function useReport() {

    const { reportImages, fetchReportImages } = useReportImages()

    const form = useForm({
        id: '',
        user_id: '',
        latitude: '',
        longitude: '',
        details: '',
        call_started_at: '',
        call_ended_at: '',
        user: {},
        status: '',
        type: '',
        created_at: '',
        address: '',
        reported_by: '',
        reporters_address: '',
        is_manually_added: true
    })

    const create = () => {
        form.reset()
        toggleModal('Add Report')
    }

    const store = () => {        
        form.post(route('reports.store'), {
            onSuccess: () => {
                form.reset()
                toggleModal()
            }
        })
    }

    const edit = (report) => {
        form.id = report.id;
        form.details = report.details;
        form.status = report.status;
        form.created_at = report.created_at;
        form.address = report.address; 
        form.reported_by = report.reported_by;
        form.reporters_address = report.reporters_address;
        toggleModal()
    }

    const details = async (report) => { 
        form.reset()

        toggleOffCanvas('offcanvas-reports')

        form.user_id = report.user_id;
        form.latitude = report.latitude;
        form.longitude = report.longitude;
        form.details = report.details;
        form.call_started_at = report.call_started_at;
        form.call_ended_at = report.call_ended_at;
        form.user = report.user;
        form.status = report.status;
        form.type = report.type;
        form.created_at = report.created_at
        form.address = report.address
        form.is_manually_added = report.is_manually_added
        form.reported_by = report.reported_by
        form.reporters_address = report.reporters_address
        // console.log('report id', report.id);
        await fetchReportImages(report.id)
    }

    const destroy = (id) => {
        confirmDialog({
            title: 'Delete Payroll?',
            message: `You're going to delete report. Are you sure?`
        }).then(async (confirmed) => {
            if (confirmed) {
                await form.delete(route('reports.destroy', id), {
                    preserveScroll: true,
                    onError: () => showToast(true, 'Something went wrong. Please try again.', 'warning'),
                    onSuccess: () => {
                        form.reset()
                        console.log('deleted')
                    }
                })
            }
        }).catch(err => console.log(err))
    }

    const updateStatus = (id, status) => {
        form.id = id
        form.status = status
        form.put(route('reports.update-status'))
    }

    return {
        form,
        reportImages,
        create,
        store,
        edit,
        destroy,
        details,
        updateStatus,
    }
}