<?php

function displayHeader($mode)
{
    global $gal_theme;

    echo "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
    echo "  <link rel=\"stylesheet\" href=\"css/layout.css\" type=\"text/css\" media=\"screen\"  />\n";
    echo "  <link rel=\"stylesheet\" href=\"css/sidemenu.css\" type=\"text/css\" media=\"screen\" />\n";
    echo "  <script src=\"js/jquery-1.7.2.min.js\" type=\"text/javascript\"></script>\n";
    echo "  <script src=\"js/navigation.js\" type=\"text/javascript\"></script>\n";

    if ($mode == 'home') {
        echo "  <script src=\"js/jquery.mousewheel.js\" type=\"text/javascript\"></script>\n";
        echo "  <script src=\"js/cloud-carousel.1.0.5.min.js\" type=\"text/javascript\"></script>\n";
        echo "  <script type=\"text/javascript\">\n";
        echo "    $(document).ready(function(){\n";
        echo "      $(\"#carousel\").CloudCarousel({\n";
        echo "        xPos: 240, yPos: 30, mouseWheel: true,\n";
        echo "        buttonLeft: $(\"#left-button\"), buttonRight: $(\"#right-button\"),\n";
        echo "        titleBox: $(\"#title-text\"),\n";
        echo "        reflHeight: 50, minScale: 0.3\n";
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
        echo "  <script src=\"js/jquery.imageLoader.js\" type=\"text/javascript\"></script>\n";
        if ($mode == 'tags') {
            echo "  <link rel=\"stylesheet\" href=\"css/chosen.css\" type=\"text/css\" media=\"screen\" />\n";
            echo "  <script src=\"js/chosen.jquery.js\" type=\"text/javascript\"></script>\n";
        }
        echo "  <script type=\"text/javascript\">\n";
        echo "    $(document).ready(function(){\n";
        echo "      jQuery('.dynamic-thumbnail').loadImages();\n";
        echo "      $.tools.tooltip.conf.relative = true;\n";
        echo "      $.tools.tooltip.conf.cancelDefault = false;\n";
        echo "      $.tools.tooltip.conf.predelay = 1000;\n";
        echo "      $.tools.tooltip.conf.offset = [110, 0];\n";
        echo "      $(\".dynamic-thumbnail\").tooltip();\n";
        echo "      $(\"a[rel^='prettyPhoto']\").prettyPhoto({\n";
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
        echo "    });\n";
        echo "    $(function() {\n";
        echo "      $(\"a[rel^='#video']\").overlay({\n";
        echo "         expose: '#111',\n";
        echo "         effect: 'apple',\n";
        echo "         onLoad: function(content) {\n";
        echo "            this.getOverlay.find(\"a.player\").flowplayer(0).load();\n";
        echo "         }\n";
        echo "      });\n";
        echo "      $(\"a.player\").flowplayer(\"./swf/flowplayer-3.2.2.swf\", { clip: { scaling: 'fit' } });\n";
        if ($mode == 'tags') {
            echo "\t\t\tjQuery(\".chosen\").chosen();\n";
        }
        echo "    });\n";
        echo "  </script>\n";
    }
}
?>
