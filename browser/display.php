<?php

function displayTagSelector(mediaDB &$db)
{
    $tag_list = $db->getAvailableTags();

    if (count($tag_list) > 0) {
        echo "<h2>S&eacute;lectionnez un ou plusieurs mots-clefs:</h2>\n";
        echo "<form action=\"index.php\" method=\"get\">\n";
        echo '<select name="param[]" class="chosen" multiple="multiple" style="width: 50%">'."\n";
        foreach($tag_list as $current_tag) {
            echo "\t<option value=\"$current_tag\">$current_tag</option>\n";
        }
        echo "</select>\n";
        echo "<input type=\"hidden\" name=\"browse\" value=\"tags\" />\n";
        echo "<button onclick=\"submit();\" style=\"padding: 0; border: 0; background: 0;\"><img style=\"height: 2em; align: middle;\" src=\"images/apply.jpg\"/></button>\n";
        echo "</form>\n";
    }
}

function displayTagElements($tag_array, mediaDB &$db)
{
    if (count($tag_array) == 0)
        return false;

    $element_list = $db->getElementsByTags($tag_array);

    if (count($element_list) > 0) {
        echo "<div class=\"gallery\">\n";
        $tabthumb = 0;
        $tabid    = 1;
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $db->loadMediaObject($element, $current_id);
            echo "<div class=\"element\">";
            if ($element->type == 'movie') {
                // Video
                echo "<a href=\"#\" rel=\"#video".$videoid."\">";
                $videoid++;
            } else {
                // Images
                echo "<a href=\"".getResizedPath($current_id)."\" rel=\"#gallery\" title=\"".htmlentities($element->getSubTitle())."\">";
            }
            echo "<img class=\"lazy\" src=\"images/nothumb.jpg\" data-src=\"".getThumbnailPath($current_id)."\" border=\"0\" alt=\"".htmlentities($element->title)."\"/>\n";
            echo "<div class=\"tooltip\">".htmlentities($element->title)."<br />".strftime('%e %B %Y %Hh%M', strtotime($element->originaldate));
            if (count($element->tags) > 0) {
                echo "<br /><i>";
                $tagline = "";
                foreach($element->tags as $tag) {
                    if ($tagline != "")
                        $tagline .= ", ";
                    $tagline .= htmlentities($tag);
                }
                echo "$tagline</i>";
            } else if (($element->type == 'movie') && ($element->duration != ''))
                echo "<br /><i>".$element->duration."</i>";
            echo "</div></a></div>\n";
        }
        echo "</div>\n";
        // Videos overlay
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $db->loadMediaObject($element, $current_id);
            if($element->type == 'movie') {
                echo "<div class=\"videoverlay\" id=\"video".$videoid."\">";
                echo "<a class=\"player\" href=\"".getResizedPath($current_id)."\"></a>";
                echo "</div>\n";
                $videoid++;
            }
        }
    }
}

function displayElementList($id, mediaDB &$db)
{
    global $thumbs_per_page;

    setlocale(LC_ALL, "fr_FR.utf8");

    $element_list = $db->getFolderElements($id);

    if (count($element_list) > 0) {
        echo "<div id=\"gallery\">\n";
        $tabthumb = 0;
        $tabid    = 1;
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $db->loadMediaObject($element, $current_id);
            echo "<div class=\"element\">";
            if ($element->type == 'movie') {
                // Video
                echo "<a href=\"#\" rel=\"#video".$videoid."\">";
                $videoid++;
            } else {
                // Images
                echo "<a href=\"".getResizedPath($current_id)."\" rel=\"#gallery\" title=\"".htmlentities($element->getSubTitle())."\">";
            }
            echo "<img class=\"lazy\" src=\"images/nothumb.jpg\" data-src=\"".getThumbnailPath($current_id)."\" border=\"0\" alt=\"".htmlentities($element->title)."\"/>\n";
            echo "<div class=\"tooltip\">".htmlentities($element->title)."<br />".strftime('%e %B %Y %Hh%M', strtotime($element->originaldate));
            if (count($element->tags) > 0) {
                echo "<br /><i>";
                $tagline = "";
                foreach($element->tags as $tag) {
                    if ($tagline != "")
                        $tagline .= ", ";
                    $tagline .= htmlentities($tag);
                }
                echo "$tagline</i>";
            } else if (($element->type == 'movie') && ($element->duration != ''))
                echo "<br /><i>".$element->duration."</i>";
            echo "</div></a></div>\n";
        }
        echo "</div>\n";
        // Videos overlay
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $db->loadMediaObject($element, $current_id);
            if($element->type == 'movie') {
                echo "<div class=\"videoverlay\" id=\"video".$videoid."\">";
                echo "<a class=\"player\" href=\"$BASE_URL/".getResizedPath($current_id)."\"></a>";
                echo "</div>\n";
                $videoid++;
            }
        }
    }
}

function displaySubFolderList($id, mediaDB &$db)
{ 
    $subfolder_list = $db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<div id=\"galleryfolder\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($db->getFolderTitle($subfolder));
            echo "<div class=\"folder\">";
            echo "<a href=\"index.php?path=".urlencode($db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >";
            echo "<img class=\"lazy\" src=\"images/nothumb.jpg\" data-src=\"".getFolderThumbnailPath($subfolder)."\" border=\"0\" alt=\"".htmlentities($subfolder_title)."\"/>\n";
            echo "<div class=\"tooltip\">$subfolder_title<br />".$db->getFolderDate($subfolder)."<br />".$db->getFolderElementsCount($subfolder, true)." images</div>";
            echo "<p>$subfolder_title</p></a></div>\n";        
        }
        echo "</div>\n";
        // Separate Directory list and Pictures
        if ($db->getFolderElementsCount($id) > 0) {
            echo "<div class=\"clearfix\"></div>\n";
            echo "<h2></h2>\n";
        }
    }
}

function displayFolderHierarchy($id, mediaDB &$db, $show_slide_map_link = true)
{
    global $BASE_URL;
    global $disable_dynamic;

    echo "<h2>\n";
    if ($id != -1) {
        echo "<a href=\"index.php\">Accueil</a>";
        $folderhierarchy = $db->getFolderHierarchy($id);
        foreach($folderhierarchy as $fhier) {
            if ($fhier == -1) continue; // Discard top-level
            echo "/<a href=\"$BASE_URL/index.php?path=".urlencode($db->getFolderPath($fhier))."\">".htmlentities($db->getFolderTitle($fhier))."</a>";
        }
    }
    $path = $db->getFolderPath($id);
    if ($db->getFolderElementsCount($id) > 0) {
        if ($disable_dynamic == false)
            echo " <a href=\"$BASE_URL/browser/download.php?id=$id\"><img src=\"$BASE_URL/images/save.png\" title=\"T&eacute;l&eacute;charger les images\" alt=\"T&eacute;l&eacute;charger les images\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
        echo " <a href=\"$BASE_URL/browser/slideshow.php?path=$path\"><img src=\"$BASE_URL/images/slideshow.png\" title=\"Diaporama\" alt=\"Diaporama\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    }
    if (($db->getFolderElementsCount($id, true) > 0) && ($id != -1) && ($disable_dynamic == false))
        echo " <a href=\"$BASE_URL/browser/gettimeline.php?id=$id\"><img src=\"$BASE_URL/images/date.png\" title=\"Au fil du temps\" alt=\"Au fil du temps\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    if (($show_slide_map_link == true) and (getFolderGeolocalizedCount($id, $db) > 0))
        echo " <a href=\"$BASE_URL/browser/getmap.php?path=$path\"><img src=\"$BASE_URL/images/googlemaps.png\" title=\"Carte\" alt=\"Carte\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    echo "</h2>\n";

}

function displayTopFoldersMenu(mediaDB &$db)
{
    echo "<ul id=\"toplevel_navigation\">\n"; 
    echo "<li class=\"home\"><a href=\"index.php\"><span><b>Accueil</b></span></a></li>\n"; 
    // Build menu with only top-level directories
    $topfolderlist = $db->getSubFolders(-1);
    foreach($topfolderlist as $topfolder) {
        echo "<li class=\"toplevel_".$db->getFolderName($topfolder)."\"><a href=\"index.php?path=".urlencode($db->getFolderPath($topfolder))."\"><span>".htmlentities($db->getFolderTitle($topfolder))."</span></a></li>\n";
    }
    echo "</ul>\n"; 
}

function generateTopFolderStylesheet(mediaDB &$db)
{
    global $BASE_DIR;

    $topfolderlist = $db->getSubFolders(-1);

    $css  = "/* Top-level menu CSS File generated by generateTopFolderStylesheet() - Do not edit */\n";
    $css .= "ul#toplevel_navigation {\n";
    $css .= "\tposition: fixed;\n";
    $css .= "\tmargin: 0px;\n";
    $css .= "\tpadding: 0px;\n";
    $css .= "\ttop: 0px;\n";
    $css .= "\tright: 10px;\n";
    $css .= "\tlist-style: none;\n";
    $css .= "\tz-index: 10;\n";
    $css .= "\twidth:".((count($topfolderlist) + 1) * 103)."px;\n";
    $css .= "}\n\n";
    $css .= "ul#toplevel_navigation li {\n";
    $css .= "\twidth: 103px;\n";
    $css .= "\tdisplay:inline;\n";
    $css .= "\tfloat:left;\n";
    $css .= "}\n\n";
    $css .= "ul#toplevel_navigation li a {\n";
    $css .= "\tdisplay: block;\n";
    $css .= "\tfloat:left;\n";
    $css .= "\tmargin-top: 0px;\n";
    $css .= "\twidth: 100px;\n";
    $css .= "\theight: 20px;\n";
    $css .= "\tbackground-color: #000000;\n";
    $css .= "\tbackground-repeat:no-repeat;\n";
    $css .= "\tbackground-position:50% 10px;\n";
    $css .= "\tborder:1px solid #313739;\n";
    $css .= "\t-moz-border-radius:0px 0px 10px 10px;\n";
    $css .= "\t-webkit-border-bottom-right-radius: 10px;\n";
    $css .= "\t-webkit-border-bottom-left-radius: 10px;\n";
    $css .= "\t-khtml-border-bottom-right-radius: 10px;\n";
    $css .= "\t-khtml-border-bottom-left-radius: 10px;\n";
    $css .= "\ttext-decoration:none;\n";
    $css .= "\ttext-align:center;\n";
    $css .= "\tpadding-top:80px;\n";
    $css .= "\topacity: 0.7;\n";
    $css .= "\tfilter:progid:DXImageTransform.Microsoft.Alpha(opacity=70);\n";
    $css .= "}\n\n";
    $css .= "ul#toplevel_navigation li a span {\n";
    $css .= "\tfont-size:1.1em;\n";
    $css .= "\tcolor:#FFFFFF;\n";
    $css .= "\ttext-shadow: 0 -1px 1px #313739;\n";
    $css .= "}\n\n";

    $css .= "ul#toplevel_navigation .home a {\n";
    $css .= "\tbackground-image: url(../images/toplevel/home.png);\n";
    $css .= "}\n\n";
    foreach($topfolderlist as $topfolder) {
        $css .= "ul#toplevel_navigation .toplevel_".$db->getFolderName($topfolder)." a {\n";
        $css .= "\tbackground-image: url(../images/toplevel/".$db->getFolderName($topfolder).".jpg);\n";
        $css .= "}\n\n";
    }     

    file_put_contents("$BASE_DIR/css/toplevelmenu.css", $css);
}

/** Display a side menu containing folder-related elements
    - Latest modified folders
    - Neighbour folders (if any)
    - Filters
*/
function displaySideMenu($id, mediaDB &$db)
{
    global $disable_dynamic;
    global $latest_album_count;

    // Display menu icon
    echo "<ul id=\"side_navigation\">\n"; 
    echo "  <li class=\"other_folders\">\n";
    // Side folder or Latest Folders
    if ($id != -1) {
        // Side folders
        $neighborlist = $db->getNeighborFolders($id);
        if (count($neighborlist) > 0) {
            echo "  <div><h3>Albums Voisins</h3>\n";
            foreach($neighborlist as $neighbor) {
                $neighbor_title = htmlentities($db->getFolderTitle($neighbor));
                echo "    <a href=\"index.php?path=".urlencode($db->getFolderPath($neighbor))."\" title=\"$neighbor_title\" >$neighbor_title</a>\n";
            }
            echo "  </div>\n";
        }
    }

    // Sub folders (not for toplevel)
    if ($id != -1) {
        $subfolder_list = $db->getSubFolders($id);
        if (count($subfolder_list) > 0) {
            echo "  <div><h3>Sous-Albums</h3>\n";        
            foreach($subfolder_list as $subfolder) {
                $subfolder_title = htmlentities($db->getFolderTitle($subfolder));
                echo "    <a href=\"index.php?path=".urlencode($db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >$subfolder_title</a>\n";
            }
            echo "  </div>\n";
        }
    }
    // Latest folders
    echo "  <div><h3>Nouveaut&eacute;s</h3>\n";
    $latestfolderlist = $db->getLatestUpdatedFolder($latest_album_count);
    foreach($latestfolderlist as $latestfolder) {
        $latestfolder_title = htmlentities($db->getFolderTitle($latestfolder));
        echo "    <a href=\"index.php?path=".urlencode($db->getFolderPath($latestfolder))."\" title=\"$latestfolder_title\" >$latestfolder_title</a>\n";
    }
    echo "  </div>\n";
    // Browse by...
    if ($disable_dynamic == false) {
        echo "  <div><h3>Filtres</h3>\n";
        echo "     <a href=\"index.php?browse=tags\" title=\"Mots-Clefs\">Mots-Clefs</a>\n";
        echo "     <a href=\"browser/gettimeline.php\" title=\"Au fil du temps\">Date</a>\n";
        echo "  </div>\n";
        echo "  </li>\n";
    }
    // Top level googlemap
    if ($id == -1) {
        echo "  <li class=\"googlemaps\">\n";
        echo "  <h3>Cartographie</h3>\n";
        echo "  <a href=\"browser/getmap.php\">Voir les photos sur une carte</a>\n";
        echo "  </li>\n";
    }
    echo "</ul>\n"; 
}

function displayFooter()
{
    echo "<ul class=\"submenu\">\n";
    echo "<li>Gallerie v2.6.0 - H. Raffard &amp; C. Laury</li>\n";
    echo "</ul>\n";
    echo "<br clear=\"all\" />\n";
}
?>
