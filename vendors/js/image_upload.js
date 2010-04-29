function loadPiece(href, divName, data) 
{     
    $(divName).load(href, data, function()
    { 
        var divPaginationLinks = divName+" #pagination a"; 
        $(divPaginationLinks).click(function() 
        {      
            var thisHref = $(this).attr("href"); 
            loadPiece(thisHref, divName, data); 
            return false; 
        }); 
    }); 
}

// This function adds additional hidden fields that stores currently selected
// images.
//
// Usage: $('#SymbolAddForm').submit(function() { img_select_submit_handler(); } );
//   in the document ready function.
function img_select_submit_handler()
{
    var objects = $("#rightimgs > div[selected]");
    $('<input type="hidden" name="num_selected" value="' + objects.length + '" />').appendTo('#SymbolAddForm');
    
    for (i=0; i < objects.length; i++)
    {
        div = objects[i];
        id = div.getAttribute('id');
        str = "<input type='hidden' name='Selected" + i + "' value='" + id + "' />";
        $(str).appendTo($('#SymbolAddForm'));
    }
}