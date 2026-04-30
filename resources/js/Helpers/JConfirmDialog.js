export const confirmDialog = (data, modalId = 'confirm-modal') => {
    return new Promise((resolve) => {
        const modalTitle = document.querySelector(`#${modalId} .modal-title`)
        const modalMessage = document.querySelector(`#${modalId} .modal-body`)
        
        const btnOk = document.querySelector(`#${modalId} .btn-ok`)
        const btnCancel = document.querySelector(`#${modalId} .btn-cancel`)

        const btnOk_DefaultText = 'Yes, Delete!'
        const btnCancel_DefaultText = 'No, Keep it.'

        modalTitle.innerHTML = data.title ?? ''
        modalMessage.innerHTML = data.message ?? ''

        btnOk.innerHTML = data.btnOkText ?? btnOk_DefaultText
        btnCancel.innerHTML = data.btnCancelText ?? btnCancel_DefaultText


        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId))

        btnOk.addEventListener('click', () => {
            modal.hide()
            resolve(true);
        })

        btnCancel.addEventListener('click', () => {
            modal.hide()
            resolve(false);
        })

        modal.show()
    })
}