const defaultConfirmModalId = () => 'confirm-modal';
const toggleModal = (title = '', modalId = 'default-modal') => {
    const modalEl = document.getElementById(modalId)
    if (!modalEl) {
        return
    }
    const modalTitle = document.querySelector(`#${modalId} .modal-title`)
    if (modalTitle) {
        modalTitle.innerHTML = title
    }
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl)
    modal.toggle()
}

const hideModal = (title = '', modalId = 'default-modal') => {
    const modalEl = document.getElementById(modalId)
    if (!modalEl) {
        return
    }
    const modalTitle = document.querySelector(`#${modalId} .modal-title`)
    if (modalTitle) {
        modalTitle.innerHTML = title
    }
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl)
    modal.hide()
}

const focusOnToggleModal = (inputId, modalId = 'default-modal') => {
    const myModal = document.getElementById(modalId)
    const myInput = document.getElementById(inputId);

    myModal.addEventListener('shown.bs.modal', () => {
        if (myInput) {
            myInput.focus()
        }
    })
}

export { toggleModal, hideModal, focusOnToggleModal, defaultConfirmModalId }

