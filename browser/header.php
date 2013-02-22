<?php

function displayHeader($mode)
{
    global $gal_theme;
    global $BASE_URL;

    echo "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
    echo "  <link rel=\"stylesheet\" href=\"css/layout.css\" type=\"text/css\" media=\"screen\"  />\n";
    echo "  <link rel=\"stylesheet\" href=\"css/sidemenu.css\" type=\"text/css\" media=\"screen\" />\n";
    echo "  <script src=\"http://code.jquery.com/jquery-1.7.2.min.js\"></script>\n";
    echo "  <script src=\"js/navigation.js\"></script>\n";
    echo "  <script src=\"js/jquery.masonry.min.js\" type=\"text/javascript\"></script>\n";

    if ($mode == 'home') {
        echo "  <script type=\"text/javascript\">\n";
        echo "    $(function() {\n";
        echo "      var \$container = $('#container');\n";
        echo "      \$container.imagesLoaded( function() {\n";
        echo "        \$container.masonry({\n";
        echo "          itemSelector: '.box',\n";
        echo "          isFitWidth: true,\n";
        echo "          isAnimated: true,\n";
        echo "          gutterWidth: 2,\n";
        echo "          columnWidth: 300\n";
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
        echo "  <link rel=\"stylesheet\" href=\"css/prettyPhoto.css\" type=\"text/css\" media=\"screen\" />\n";
        echo "  <link rel=\"stylesheet\" href=\"css/tooltip.css\" type=\"text/css\" media=\"screen\" />\n";
        echo "  <link rel=\"stylesheet\" href=\"css/toplevelmenu.css\" type=\"text/css\" media=\"screen\" />\n";
        echo "  <script src=\"js/jquery-ui-1.8.11.custom.min.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/jquery.tools-1.2.5.min.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/flowplayer-3.2.2.min.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/jquery.prettyPhoto.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/jquery.lazy.min.js\" type=\"text/javascript\"></script>\n";
        if ($mode == 'tags') {
            echo "  <link rel=\"stylesheet\" href=\"css/chosen.css\" type=\"text/css\" media=\"screen\" />\n";
            echo "  <script src=\"js/chosen.jquery.js\" type=\"text/javascript\"></script>\n";
        }
        echo "  <script type=\"text/javascript\">\n";
        echo "    $(function(){\n";
        echo "      jQuery('img.lazy').lazy({ effect: 'fadeIn', effectTime: 1500 });\n";
        echo "      var \$container = $('#gallery');\n";
        echo "      \$container.imagesLoaded( function() {\n";
        echo "        \$container.masonry({\n";
        echo "          itemSelector: '.element',\n";
        echo "          isFitWidth: true,\n";
        echo "          isAnimated: true,\n";
        echo "          gutterWidth: 2,\n";
        echo "          columnWidth: 150\n";
        echo "        });\n";
        echo "      });\n";
        echo "      var \$foldercontainer = $('#galleryfolder');\n";
        echo "      \$foldercontainer.imagesLoaded( function() {\n";
        echo "        \$foldercontainer.masonry({\n";
        echo "          itemSelector: '.folder',\n";
        echo "          isFitWidth: true,\n";
        echo "          isAnimated: true,\n";
        echo "          gutterWidth: 2,\n";
        echo "          columnWidth: 160\n";
        echo "        });\n";
        echo "      });\n";
        if ($mode == 'tags') {
            echo "\t\t\tjQuery(\".chosen\").chosen();\n";
        }
        echo "      $.tools.tooltip.conf.relative = true;\n";
        echo "      $.tools.tooltip.conf.cancelDefault = false;\n";
        echo "      $.tools.tooltip.conf.predelay = 1000;\n";
        echo "      $.tools.tooltip.conf.offset = [110, 0];\n";
        echo "      $(\"div.element > a\").each(function(e) {\n";
        echo "          var title = $(this).attr('title');\n";
        echo "          $(this).mouseover(\n";
        echo "              function() {\n";
        echo "                  $(this).attr('title','');\n";
        echo "              }).mouseout(\n";
        echo "                  function() {\n";
        echo "                  $(this).attr('title', title);\n";
        echo "          });\n";
        echo "          $(this).click(\n";
        echo "          function() {\n";
        echo "              $(this).attr('title', title);\n";
        echo "              }\n";
        echo "          );\n";
        echo "      });\n";        
        echo "      $(\".lazy\").tooltip();\n";
        echo "      $(\"a[rel^='#gallery']\").prettyPhoto({\n";
        echo "        animationSpeed: 'fast',\n";
        echo "        opacity: 0.9,\n";
        echo "        showTitle: true,\n";
        echo "        allowresize: true,\n";
        echo "        counter_separator_label: '/',\n";
        echo "        social_tools: false,\n";
        echo "        overlay_gallery: false,\n";
        echo "        slideshow: false,\n";
        echo "        theme: '$gal_theme'\n";
        echo "      });\n";
        echo "      $(\"a[rel^='#video']\").overlay({\n";
        echo "         expose: '#111',\n";
        echo "         effect: 'apple',\n";
        echo "         onLoad: function(content) {\n";
        echo "            this.getOverlay.find(\"a.player\").flowplayer(0).load();\n";
        echo "         }\n";
        echo "      });\n";
        echo "      $(\"a.player\").flowplayer(\"./swf/flowplayer-3.2.2.swf\", { clip: { scaling: 'fit' } });\n";
        echo "    });\n";
        echo "  </script>\n";
    }
}
?>
