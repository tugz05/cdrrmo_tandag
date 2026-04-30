export const showToast =(show = false, message = 'Success Message', type = 'success', title = null) => {
    const bootstrapToast = document.getElementById('bootstrapToast1')
    const toastBootstrap = bootstrap.Toast.getOrCreateInstance(bootstrapToast)

    if (!show) return toastBootstrap.hide()

    const toastMessage = bootstrapToast.querySelector('.toast-body-text')
    // const toastTitle = bootstrapToast.querySelector('.toast-title')
    const toastIcon = bootstrapToast.querySelector('.toast-icon')

    // let removeUndo = document.querySelector('.toast-body-undo')
    // if (removeUndo)
    //     removeUndo.remove();

    // JTODO: refactor
    toastIcon.classList.remove('bi-check-circle-fill')
    toastIcon.classList.remove('text-primary')
    toastIcon.classList.remove('bi-x-circle-fill')
    toastIcon.classList.remove('text-danger')
    toastIcon.classList.remove('bi-exclamation-triangle-fill')
    toastIcon.classList.remove('text-warning')

    switch (type.toLowerCase()) {
        case 'success':
            title = 'Success';
            toastIcon.classList.add('bi-check-circle-fill')
            toastIcon.classList.add('text-primary')
            break
        case 'danger':
            title = 'Error';
            toastIcon.classList.add('bi-x-circle-fill')
            toastIcon.classList.add('text-danger')
            break
        case 'warning':
            title = 'Warning';
            toastIcon.classList.add('bi-exclamation-triangle-fill')
            toastIcon.classList.add('text-warning')
            break
        default:
    }

    // toastTitle.innerHTML = title
    toastMessage.innerHTML = message
    return toastBootstrap.show()
}