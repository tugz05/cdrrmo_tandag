export const dataTable = (className, options = {}) => {
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

    return new DataTable(`table.${className}`, options)
}