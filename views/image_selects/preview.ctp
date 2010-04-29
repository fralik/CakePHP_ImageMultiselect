<?php
$limit = $paginator->params['paging'][$modelClass]['options']['limit'];
?>
<script type="text/javascript">
var pagination_limit = <?php echo $limit; ?>;
var right_spacer = '<div class="imgselect_spacer" id="rightspacer">&nbsp;</div>';
var left_spacer = '<div class="imgselect_spacer" id="leftspacer">&nbsp;</div>';

function debugEl(obj)
{
	for (var key in obj) 
	{
		alert(key + '= \n' + obj[key]);
	}
}

/* Selects/deselects one image */
function toggle_img(div)
{
    var isselected = div.getAttribute('selected') == 'selected';
    if (isselected)
    {
        div.style.backgroundColor = '';
        div.childNodes[4].childNodes[0].checked = false;
        div.setAttribute('selected', '');
        //div.setAttribute('class', 'imgselect_float');
    }
    else
    {
        div.style.backgroundColor = '#3961af';
        div.childNodes[4].childNodes[0].checked = true;
        div.setAttribute('selected', 'selected');
        //div.setAttribute('class', 'imgselect_float_selected');
    }
}

/* Deselect all images */
function deselect(objects)
{
    for (i=0; i < objects.length; i++)
    {
        div = objects[i];
        toggle_img(div);
    }
}


function update_left(objects)
{
    var selected = new Array();
    for (i=0; i < objects.length; i++)
    {
        div = objects[i];
        selected[i] = div.getAttribute('id');
    }
    var data = new Object();
    data.selected = selected;
    
    loadPiece(reload_url, "#imageList", data);
}

function add_item()
{
    // delete the last spacer
    $("#rightspacer").remove();
    // delete images from left and add to the right
    $("#leftimgs > div[selected='selected']").remove().appendTo('#rightimgs');
    //add the spacer
    $("#rightimgs").append(right_spacer);
    
    deselect($("#rightimgs > div[selected='selected']"));
    
    // update pagination
    if ($("#leftimgs > div[selected='selected']").length == 0)
    {
        // get selected ids
        update_left($("#rightimgs > div[selected]"));
    }
}

function remove_item()
{
    $("#leftspacer").remove();
    $("#rightimgs > div[selected='selected']").remove().appendTo('#leftimgs');
    $("#leftimgs").append(left_spacer);

    deselect($("#leftimgs > div[selected='selected']"));
    if ($("#leftimgs > div[selected]").length > pagination_limit)
    {
        update_left($("#rightimgs > div[selected]"));
    }
}

$(document).ready(function() 
{
    $('#leftimgs > div[selected]').dblclick(function()
    {
        toggle_img(this);
        add_item();
    });
    $('#addItem').click(function() { add_item(); });
    $('#addAll').click(function() {
        $('#leftimgs > div[selected]').attr('selected', 'selected');
        add_item();
    });
    
    // add handlers for te selection
    $("#leftimgs > div[selected]").click( function(event) { toggle_img(this); });
    $("#rightimgs > div[selected]").click( function(event) { toggle_img(this); });
    
    // handle events to remove images from a selection list
    $('#rightimgs > div[selected]').dblclick(function() { toggle_img(this); remove_item(); } );
    $('#removeItem').click(function() { remove_item(); } );
    $('#removeAll').click(function() {
        $('#rightimgs > div[selected]').attr('selected', 'selected');
        remove_item();
    });
}); 
</script>
<?php 
if (count($allphotos)>0 || count($allselected) > 0) { 
     /* Display paging info */ 
?> 
<div id="pagination"> 
<?php 
      echo $paginator->prev() . " ";
      echo $paginator->numbers(array('separator'=>' - ')) . " ";
      echo $paginator->next();
      $absolute_url  = Router::url("/", true);
?> 
</div> 

<div class="imgselect_container" id="leftimgs">
    <div class="imgselect_spacer">&nbsp;</div>
<?php
    $right = array();
    foreach ($allphotos as $photo) 
    { 
        $id = $photo[$modelClass]['id'];
        $name = $photo[$modelClass]['name'];
        $img_path = $photo[$modelClass]['preview_link'];
        $img_path = str_replace("\\", "/", $img_path);
        $img_path = $absolute_url . $img_path;
?> 
        <div class="imgselect_float" selected="" id="<?php echo $id;?>">
        <?php echo $html->image($img_path, array('title' => $name, 'alt' => $name)); unset($img_path); ?><br />
        <p><input type="checkbox" value="<?php echo $id;?>" /></p></div>
<?php
    }
?>
    <div class="imgselect_spacer" id="leftspacer">&nbsp;</div>
</div>
<div class="imgselect_buttons">
    <input type="button" id="addItem" value="Select" class="imgselect_button"/><br />
    <input type="button" id="addAll" value="Select All" class="imgselect_button"/><br />
    <input type="button" id="removeItem" value="Remove" class="imgselect_button"/><br />
    <input type="button" id="removeAll" value="Remove All" class="imgselect_button"/>
</div>

<div class="imgselect_container" id="rightimgs">
<div class="imgselect_spacer">&nbsp;</div>
<?php
    foreach ($allselected as $photo)
    {
        $id = $photo[$modelClass]['id'];
        $name = $photo[$modelClass]['name'];
        $img_path = $photo[$modelClass]['preview_link'];
        $img_path = str_replace("\\", "/", $img_path);
        $img_path = $absolute_url . $img_path;
?> 
        <div class="imgselect_float" selected="" id="<?php echo $id;?>">
        <?php echo $html->image($img_path, array('title' => $name, 'alt' => $name)); unset($img_path); ?><br />
        <p><input type="checkbox" id="selected[]" name="selected[]" value="<?php echo $id;?>" /></p></div>
<?php
    }
?>
<div class="imgselect_spacer" id="rightspacer">&nbsp;</div></div>
<?php 
} 
?>