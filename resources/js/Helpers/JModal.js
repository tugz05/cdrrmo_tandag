const defaultConfirmModalId = () => 'confirm-modal';

/**
 * Remove Bootstrap modal backdrops / body locks left behind when Inertia navigates away
 * while a modal was open (e.g. Save on Posts index → Edit page: #default-modal no longer exists).
 */
function cleanupStrayBootstrapModalArtifacts() {
    document.body.classList.remove('modal-open')
    document.body.style.removeProperty('overflow')
    document.body.style.removeProperty('padding-right')
    document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove())
}

const toggleModal = (title = '', modalId = 'default-modal') => {
    const modalEl = document.getElementById(modalId)
    if (!modalEl) {
        cleanupStrayBootstrapModalArtifacts()
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
        cleanupStrayBootstrapModalArtifacts()
        return
    }
    const modalTitle = document.querySelector(`#${modalId} .modal-title`)
    if (modalTitle) {
        modalTitle.innerHTML = title
    }
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl)
    modal.hide()
    // Inertia can swap the DOM before Bootstrap finishes hiding; clear any leftover backdrop.
    setTimeout(() => {
        const el = document.getElementById(modalId)
        if (!el || !el.classList.contains('show')) {
            cleanupStrayBootstrapModalArtifacts()
        }
    }, 350)
}

const focusOnToggleModal = (inputId, modalId = 'default-modal') => {
    const myModal = document.getElementById(modalId)
    if (!myModal) {
        return
    }
    const myInput = document.getElementById(inputId);

    myModal.addEventListener('shown.bs.modal', () => {
        if (myInput) {
            myInput.focus()
        }
    })
}

export { toggleModal, hideModal, focusOnToggleModal, defaultConfirmModalId, cleanupStrayBootstrapModalArtifacts }
