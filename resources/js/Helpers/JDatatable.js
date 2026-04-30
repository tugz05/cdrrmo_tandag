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

    // $(`#${tableId}`).DataTable(options);

    new DataTable(`table.${className}`, options)

    // $('#example_wrapper #example_info').addClass('fw-light')
    // $('#example_wrapper .dataTables_length label').addClass('fw-light')

    // $('.dataTables_length label').addClass('fw-light')
    // $('.dataTables_info').addClass('fw-light')

}