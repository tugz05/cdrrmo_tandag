
export const showOffCanvas = (offcanvasId = 'offcanvasDefault') =>
    bootstrap.Offcanvas.getOrCreateInstance(
        document.getElementById(offcanvasId)
    ).show();


export const hideOffCanvas = (offcanvasId = 'offcanvasDefault') =>
    bootstrap.Offcanvas.getOrCreateInstance(
        document.getElementById(offcanvasId)
    ).hide();


export const toggleOffCanvas = (offcanvasId = 'offcanvasDefault') =>
    bootstrap.Offcanvas.getOrCreateInstance(
        document.getElementById(offcanvasId)
    ).toggle();
