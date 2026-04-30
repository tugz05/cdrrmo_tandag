export const truncateText = (originalText, maxLength) => {
    if (originalText === null) 
        return

    return originalText.length > maxLength
        ? originalText?.substring(0, maxLength) + "..."
        : originalText;
    
    // if (originalText.length > maxLength) {
    //     return originalText.substring(0, maxLength) + "...";
    // } else {
    //     return originalText;
    // }
}