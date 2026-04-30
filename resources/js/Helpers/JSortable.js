import Sortable from "sortablejs"

export const sortable = (el, options = {}) => {
    options.animation = 150

    Sortable.create(document.querySelector(el), options)
}