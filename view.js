function swapDownloadLink(event)
{
    $(event.target).closest('td').find('.swfDownloadLink').toggle();
    $(event.target).closest('td').find('.metaDownloadLink').toggle();
}