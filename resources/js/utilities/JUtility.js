import Sortable from "sortablejs"

export default class JUtility {
    // static defaultModalId() { return 'default-modal' }
    static defaultConfirmModalId() { return 'confirm-modal' }
    static toggleModal(title = '', modalId = 'default-modal') {
        const modalTitle = document.querySelector(`#${modalId} .modal-title`)
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId))
        modalTitle.innerHTML = title
        modal.toggle()
    }

    static focusOnToggleModal(inputId, modalId = 'default-modal') {
        const myModal = document.getElementById(modalId)
        const myInput = document.getElementById(inputId);

        myModal.addEventListener('shown.bs.modal', () => {
            if (myInput) {
                myInput.focus()
            }
        })
    }

    // static confirmDialog(title = '', modalId = 'confirm-modal', is_confirm = false) {
    //     const modalTitle = document.querySelector(`#${modalId} .modal-title`)
    //     const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId))
    //     modalTitle.innerHTML = title
    //     modal.toggle()

    //     return is_confirm
    // }

    static confirm(data, modalId='confirm-modal') {
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

    static showToast(show = false, message = 'Success Message', type = 'success', title = null) {
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

    static switchBootstrapTheme(buttonId = 'btn-switch-theme', iconId = 'theme-icon') {
        const btnSwitchTheme = document.getElementById(buttonId);
        const getCurrentThemeFromLocalStorage = localStorage.getItem('theme') || 'light';

        const switchTheme = (theme) => {
            localStorage.setItem('theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
            theme === 'dark'
                ? document.getElementById(iconId)?.setAttribute('class', 'me-1 bi bi-moon')
                : document.getElementById(iconId)?.setAttribute('class', 'me-1 bi bi-sun')
        }

        switchTheme(getCurrentThemeFromLocalStorage)

        btnSwitchTheme?.addEventListener('click', () => {
            const getCurrentThemeFromAttribute = document.documentElement.getAttribute('data-bs-theme');
            console.log(getCurrentThemeFromAttribute)
            const newTheme = getCurrentThemeFromAttribute === 'dark' ? 'light' : 'dark';
            switchTheme(newTheme)
        });
    }

    static getUppercaseLetters(text) {
        if (text) {
            let uppercaseLetters = text.match(/[A-Z]/g) ?? [];
            return uppercaseLetters.join('');
        }
    }

    static fullname(data) {
        var fullname = data?.fname;

        if (data?.mname)
            fullname += ' ' + data?.mname;

        fullname += ' ' + data?.lname;

        if (data?.suffix)
            fullname += ' ' + data?.suffix;

        return fullname.trim();
    }

    static timeAgo(timestamp) {
        const seconds = Math.floor((Date.now() - new Date(timestamp)) / 1000);

        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60,
        };

        for (let interval in intervals) {
            const count = Math.floor(seconds / intervals[interval]);
            if (count >= 1) {
                return count === 1 ? count + interval.slice(0, 1) : count + interval.slice(0, 1) + "s";
            }
        }

        return "just now";
    }

    static dataTable(className, options = {}) {
        let language = {
            search: "",
            searchPlaceholder: "Search..."
        }

        let dom = "<'row'" +
            "<'col-sm-12 col-md-6'l>" +
            "<'col-sm-12 col-md-6'f>" +
            ">" +
            "<'row my-2'" +
            "<'col-sm-12'tr>" +
            ">" +
            "<'row'" +
            "<'col-sm-12 col-md-5'i>" +
            "<'col-sm-12 col-md-7'p>" +
            ">"

        options.language = language
        options.dom = dom

        // $(`#${tableId}`).DataTable(options);

        new DataTable(`table.${className}`, options)

        // $('#example_wrapper #example_info').addClass('fw-light')
        // $('#example_wrapper .dataTables_length label').addClass('fw-light')

        // $('.dataTables_length label').addClass('fw-light')
        // $('.dataTables_info').addClass('fw-light')

    }

    static humanReadableDate(dateString) {
        // const dateString = '2023-07-26T03:07:48.000000Z';
        const date = new Date(dateString);

        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');

        const humanReadableDate = `${year}-${month}-${day}`;
        return humanReadableDate;
    }

    static chartTheme() {
        return localStorage.getItem('theme') || 'light';
    }

    static renderChart(chartId, options) {
        var chart = new ApexCharts(document.querySelector(`#${chartId}`), options);
        return chart.render();
    }
   
    static color()
    {
        return {
            blue_900: '#cde1fe',
            blue_800: '#9ac2fe',
            blue_700: '#68a4fd',
            blue_600: '#3585fd',
            blue_500: '#0d6efd',
            blue_400: '#0252ca',
            blue_300: '#023e97',
            blue_200: '#012965',
            blue_100: '#011532',
        }
    }   

    static sortable(el, options = {}) {
        options.animation = 150

        Sortable.create(document.querySelector(el), options)
    }

    static formatDate(timestamp = '')
    {
         const months = [
            "Jan", "Feb", "Mar", "Apr", "May", "Jun",
            "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
        ];

        const date = new Date(timestamp)
        const year = date.getFullYear();
        const month = months[date.getMonth()];
        const day = date.getDate().toString().padStart(2, "0");

        return `${year}-${month}-${day}`;
    }

    static showOffCanvas(offcanvasId = 'offcanvasDefault')
    {
        const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById(offcanvasId))
        bsOffcanvas.show()
    }

    static hideOffCanvas(offcanvasId = 'offcanvasDefault')
    {
        const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById(offcanvasId))
        bsOffcanvas.hide()
    }

    static toggleOffCanvas(offcanvasId = 'offcanvasDefault')
    {
        const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById(offcanvasId))
        bsOffcanvas.toggle()
    }
}


