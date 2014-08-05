<?php

function displayHeader($mode, $submode)
{
    global $gal_title;
    global $thumb_size;
    global $BASE_URL;

    echo "  <title>$gal_title</title>\n";
    echo "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
    echo "  <link rel=\"stylesheet\" href=\"$BASE_URL/css/layout.css\" type=\"text/css\" media=\"screen\"  />\n";
    echo "  <link rel=\"stylesheet\" href=\"$BASE_URL/css/sidemenu.css\" type=\"text/css\" media=\"screen\" />\n";
    echo "  <script type=\"text/javascript\" src=\"http://code.jquery.com/jquery-2.1.1.min.js\"></script>\n";
    echo "  <link rel=\"stylesheet\" href=\"http://cdnjs.cloudflare.com/ajax/libs/nanogallery/5.0.0/css/nanogallery.min.css\" type=\"text/css\" media=\"screen\" >\n";
    echo "  <script type=\"text/javascript\" src=\"http://cdnjs.cloudflare.com/ajax/libs/nanogallery/5.0.0/jquery.nanogallery.min.js\"></script>\n";
    echo "  <script type=\"text/javascript\">\n";
    echo "    $(function() {\n";
    echo "        $('#side_navigation').click(function () {\n";
    echo "            $('#side_navigation').toggleClass('expand');\n";
    echo "        });\n";
    echo "    })\n";
    echo "  </script>\n";

    if ($mode == 'home') {
        echo "  <script type=\"text/javascript\" src=\"js/masonry.pkgd.min.js\"></script>\n";
        echo "  <script type=\"text/javascript\" src=\"js/imagesloaded.pkgd.min.js\"></script>\n";
        echo "  <script type=\"text/javascript\">\n";
        echo "    $(function() {\n";
        echo "      var \$container = $('#container');\n";
        echo "      \$container.imagesLoaded( function() {\n";
        echo "        \$container.masonry({\n";
        echo "          itemSelector: '.box',\n";
        echo "          isFitWidth: true,\n";
        echo "          gutterWidth: 2,\n";
        echo "          columnWidth: ".($thumb_size*2)."\n";
        echo "        });\n";
        echo "      });\n";
        echo "    });\n";
        echo "    $(window).load(function(){\n";
        echo "      $('div.home_caption').each(function(){\n";
        echo "        $(this).css('opacity', 0);\n";
        echo "        $(this).css('width', $(this).siblings('img').width());\n";
        echo "        $(this).parent().css('width', $(this).siblings('img').width());\n";
        echo "        $(this).css('display', 'block');\n";
        echo "      });\n";
        echo "      $('div.home_caption_wrapper').hover(function() {\n";
        echo "        $(this).children('.home_caption').stop().fadeTo(500, 0.7);\n";
        echo "      }, function() {\n";
        echo "        $(this).children('.home_caption').stop().fadeTo(500, 0.0);\n";
        echo "      });\n";
        echo "    });\n";
        echo "  </script>\n";
    } else if (($mode == 'folder') || ($mode == 'tags')) {
        if ($mode == 'tags') {
            echo "  <link rel=\"stylesheet\" href=\"css/chosen.min.css\" type=\"text/css\" media=\"screen\" />\n";
            echo "  <script type=\"text/javascript\" src=\"js/chosen.jquery.min.js\"></script>\n";
        }
        echo "  <script type=\"text/javascript\">\n";
        echo "    $(function(){\n";
        if ($mode == 'tags') {
            echo "      $(\".chosen\").chosen();\n";
        }
        echo "      $(\"#nanoGallery\").nanoGallery({\n";
        echo "        itemBaseURL: '$BASE_URL',\n";
        echo "        thumbnailWidth: $thumb_size,\n";
        echo "        thumbnailHeight: $thumb_size,\n";
        echo "        lazyBuild: 'display',\n";
        echo "        imageTransition: 'fade',\n";
        echo "        thumbnailHoverEffect:'borderLighter,descriptionSlideUp,imageInvisible',\n";
        echo "        thumbnailLazyLoad: true\n";
        echo "      });\n";
        echo "    });\n";
        echo "  </script>\n";
    } else if ($mode == 'date') {
        echo "  <script type=\"text/javascript\">\n";
        echo "    $(function(){\n";
        echo "      $(\"#nanoGallery\").nanoGallery({\n";
        echo "        itemBaseURL: '$BASE_URL',\n";
        echo "        thumbnailWidth: $thumb_size,\n";
        echo "        thumbnailHeight: $thumb_size,\n";
        echo "        lazyBuild: 'display',\n";
        echo "        imageTransition: 'fade',\n";
        if ($submode == 0) {
            echo "        thumbnailHoverEffect:'borderLighter',\n";
            echo "        thumbnailLabel : {display:true,align:'center',position:'overImageOnMiddle',hideIcons:true},\n";
        } else {
            echo "        thumbnailHoverEffect:'borderLighter,descriptionSlideUp,imageInvisible',\n";
        }
        echo "        thumbnailLazyLoad: true\n";
        echo "      });\n";
        echo "    });\n";
        echo "  </script>\n";
        if ($submode == 0) {
            echo "  <style>\n";
            echo "  div.labelImage { font-size: 200%; }\n";
            echo "  </style>\n";
        }
    } else if ($mode == 'map') {
        echo '  <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />'."\n";
        echo '  <script src="http://maps.google.com/maps/api/js?language=fr" type="text/javascript"></script>'."\n";
    }

}
?>
