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
