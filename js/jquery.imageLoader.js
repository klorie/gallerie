/*
 * jQuery imageLoader plugin
 * Version 1.0 (Feb 03 2010)
 * Copyright (c) 2009-2010 Brandon Clark
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */


imageLoaderDefaultConfig = {
    LoadingImage: './images/loading.gif',
    FailImage: './images/nothumb.jpg'
}

jQuery.fn.loadImage = function(pConfig, pCallback) {
    if (pConfig)
	var config = jQuery.extend(imageLoaderDefaultConfig, pConfig);
    else
	var config = imageLoaderDefaultConfig;

    var loader = jQuery(this);    

    loader.html('<img src="' + config.LoadingImage + '" width="32" height="32" />');

    var img_src = loader.attr('src');
    var img_title = loader.attr('title');
    loader.removeAttr('src');
    loader.removeAttr('title');

    img = jQuery(new Image());
    img.css('display', 'inline');
    img.css('border', '1px solid #FFFFFF');
    img.hide();

    img.load(function(){
	if (loader.get(0).getAttribute('onload')){
	    cb_js = loader.get(0).getAttribute('onload')
	    onload_cb = function() { eval(cb_js); }
	    loader.removeAttr('onload');
	} else
	    onload_cb = null;

	loader.html(this);
	jQuery(this).show();

	if (onload_cb)
	    onload_cb(jQuery(this));

	if (pCallback)
	    pCallback(jQuery(this));
    })
    .attr('src', img_src)
    .attr('title', img_title)
    .attr('alt', img_title)
    .error(function(){ jQuery(this).attr('src', config.FailImage); })
    .fadeIn();
}

jQuery.fn.loadImages = function(pConfig, pCallback){
    this.each(function(){
	jQuery(this).loadImage(pConfig, pCallback)
    })
}
