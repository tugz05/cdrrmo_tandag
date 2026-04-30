export function jSelectAll(selectedItems, items) {
    return selectedItems?.length === items?.length ? [] : items.map(item => item.id)
}

export function jDeselectAll(selectedItems) {
    if (selectedItems?.length > 0) {
        return selectedItems.value = []
    }
}