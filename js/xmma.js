var currentMediumId = null;
var currentMediumIndex = -1;
var $medias = null;
var lightboxShowing = false;

$(document).ready(function() 
{
	// window events
	$(window).resize(onResizeWindow);
	
	// attach navigation keys
    $(document).keydown(onKeyPressed);
  
	// attach to click events on all image links
	$(".xmmaThumb").click(onThumbClicked);
	
	// lightbox
	$("#xmmaLightboxNavClose").click(onLightboxClose);
	$("#xmmaLightboxNavPrev").click(onLightboxPrevClick);
	$("#xmmaLightboxNavNext").click(onLightboxNextClick);
	$("#xmmaLightboxLoading").hide();
	$("#xmmaLightboxInfo").hide();
	
	// start isotope
	$('#xmmaAlbum').isotope({
		itemSelector: '.xmmaImage, .xmmaVideo',
		layoutMode : 'masonry' // masonry, fitRows, fitColumns, masonryHorizontal
		/*masonry : { columnWidth : 160 },
		/*masonryHorizontal : { rowHeight: 160 },
		cellsByRow : { columnWidth : 160, rowHeight : 160 },
		cellsByColumn : { columnWidth : 160, rowHeight : 160 }*/
	});
});

function onThumbClicked(event)
{
    // don't allow the link being clicked
    event.preventDefault();

    // get the link
    var $medium = $(this);
    
    $("#xmmaLightbox").fadeIn(300, function(){
    	
    	lightboxShowing = true;
    	
    	// position lightbox loading
    	var loading = $("#xmmaLightboxLoading");
    	loading.css("margin-left", (loading.width() / -2) + "px");
    	loading.css("margin-top", (loading.height() / -2) + "px");

    	// load image
    	loadMedium($medium);
    });
};

function loadMedium($medium)
{
    // get medias here for correct order
    $medias = $(".xmmaThumb");
    currentMediumId = $medium.attr("data-id");
    currentMediumIndex = $medias.index($medium);
    
    var $element = undefined;
    var src = undefined;
    
    // what type is it?
    var type = $medium.attr("data-type");
    if (type == "image")
    {
    	$element = $("<img>");
    	$element.attr("src", $medium.attr("href"));
    }
    else
    {
    	$element = $(unescape($medium.attr("data-iframe")));
    }
    
    // common properties
    $element.attr("data-title", $medium.attr("data-title"));
    $element.attr("data-desc", $medium.attr("data-desc"));

    // set data id
    $element.attr("data-id", $medium.attr("data-id"));
    
    // is image and not loaded yet?
    if (type == "image" && !$element.get(0).complete)
	{
		$("#xmmaLightboxLoading").show();
		$element.one("error load onreadystatechange", function(event){        	
        	onMediumLoaded($element);
        });
	}
    else if (type == "video")
	{
    	// load directly
    	onMediumLoaded($element);
	}
    else 
    {
    	// load directly
    	onMediumLoaded($element);
    }
}

function onMediumLoaded($medium)
{
	// assign loaded image
    //var $medium = $(medium);
    
    // is no longer the requested medium?
    if ($medium.attr("data-id") != currentMediumId)
    	return;
    
    // set the natural width & height (Bugfix for IE7)
    $medium.prop("naturalWidth", $medium.prop("width"));
    $medium.prop("naturalHeight", $medium.prop("height"));
    
    // set image css
    setImageBounds($medium);
    
    // remove the current medias (if any)
    $("#xmmaLightboxContent").children().remove();
    
    // add the new image and show it
    $medium.hide();
    $("#xmmaLightboxContent").append($medium);
    $medium.show();
	
	// set title and description
	$("#xmmaLightboxTitle").html($medium.attr("data-title"));
	$("#xmmaLightboxDesc").html($medium.attr("data-desc"));	
	$("#xmmaLightboxCount").html((currentMediumIndex + 1) + " / " + $medias.length);
	$("#xmmaLightboxInfo").show();
	
	// hide loading image
	$("#xmmaLightboxLoading").fadeOut(400);	
}

function setImageBounds($image)
{
	var $parent = $("#xmmaLightboxContent");
	
	// get sizes
    var width = $image.prop("naturalWidth");
    var height = $image.prop("naturalHeight");    
    var parentWidth = $parent.prop("clientWidth");
    var parentHeight = $parent.prop("clientHeight");
    
    // image larger than parent? adjust it
    if (width > parentWidth || height > parentHeight)
	{
    	var ratio = width / height;
    	var parentRatio = parentWidth / parentHeight;

    	if (ratio < parentRatio)
		{
    		height = parentHeight;
    		width = height * ratio;
		}
    	else
		{
    		width = parentWidth;
    		height = width / ratio;
		}
	}
    
    // set offset
    $image.css("position", "absolute");
    $image.css("left", ((parentWidth - width) / 2) + "px");
    $image.css("top", ((parentHeight - height) / 2) + "px");
    $image.css("width", width + "px");
    $image.css("height", height + "px");
}

function onLightboxClose(e)
{
	$medias = null;
	currentMediumId = null;
	currentMediumIndex = -1;
	
	lightboxShowing = false;
	
	$("#xmmaLightbox").fadeOut(300, function()
	{
	    // remove the current medias (if any)
	    $("#xmmaLightboxContent").children().remove();
	    
	    $("#xmmaLightboxInfo").hide();
	});
}

function onLightboxPrevClick(e)
{
	// get previous image
	var prevIndex = currentMediumIndex - 1;
	if (prevIndex < 0)
		prevIndex = $medias.length - 1;
	
	loadMedium($medias.eq(prevIndex));
}

function onLightboxNextClick(e)
{
	// get next image
	var nextIndex = currentMediumIndex + 1;
	if (nextIndex >= $medias.length)
		nextIndex = 0;
	
	loadMedium($medias.eq(nextIndex));
}

function onResizeWindow()
{
    // no lightbox, no resizing needed
	if (!lightboxShowing)
        return;
	
	delay(refreshImageBounds, 200, "resize");
}

function refreshImageBounds()
{
	var $img = $("#xmmaLightboxContent").children().eq(0);
	if ($img)
		setImageBounds($img);
}

function onKeyPressed(event)
{
    // keys can be pressed when lightbox is showing
    if (!lightboxShowing)
        return;
    
    switch (event.which)
    {
        case 27: // ESC
            $("#xmmaLightboxNavClose").click();
            break;
        
        case 37: // Left Arrow
            $("#xmmaLightboxNavPrev").click();
            break;
    
        case 39: // Right Arrow
            $("#xmmaLightboxNavNext").click();
            break;
    }
}

//var delay = (function() 
//{
	var timers = {};
	function delay(callback, ms, uniqueId) 
	{
		if (!uniqueId) 
			uniqueId = "Don't call this twice without a uniqueId";
		
		if (timers[uniqueId]) 
		{
			clearTimeout(timers[uniqueId]);
		}
		
		timers[uniqueId] = setTimeout(callback, ms);
	};
//})();
