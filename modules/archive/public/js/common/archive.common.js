

$(document).ready(function() {
    $('.archiveExtractAction').click(function() {
        midas.loadDialog('extractArchive', '/archive/extract/dialog?itemId='+json.item.item_id);
        midas.showDialog('Extract Archive', false);
    });
});
