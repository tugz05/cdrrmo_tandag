const defaultConfirmModalId = () => 'confirm-modal';
const toggleModal = (title = '', modalId = 'default-modal') => {
    const modalTitle = document.querySelector(`#${modalId} .modal-title`)
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId))
    modalTitle.innerHTML = title
    modal.toggle()
}

const hideModal = (title = '', modalId = 'default-modal') => {
    const modalTitle = document.querySelector(`#${modalId} .modal-title`)
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId))
    modalTitle.innerHTML = title
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

