<?php

function displayTagSelector(mediaDB &$db = NULL)
{
    global $BASE_URL;

    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $tag_list = $m_db->getAvailableTags();

    if (count($tag_list) > 0) {
        echo "<h2>S&eacute;lectionnez un ou plusieurs mots-clefs:</h2>\n";
        echo "<form action=\"$BASE_URL/index.php\" method=\"get\">\n";
        echo '<select name="param[]" class="chosen" multiple="multiple" style="width: 50%">'."\n";
        foreach($tag_list as $current_tag) {
            echo "\t<option value=\"$current_tag\">$current_tag</option>\n";
        }
        echo "</select>\n";
        echo "<input type=\"hidden\" name=\"browse\" value=\"tags\" />\n";
        echo "<button onclick=\"submit();\" style=\"padding: 0; border: 0; background: 0;\"><img style=\"height: 2em; align: middle;\" src=\"$BASE_URL/images/apply.jpg\"/></button>\n";
        echo "</form>\n";
    }
}

function displayTagElements($tag_array, mediaDB &$db = NULL)
{
    if (count($tag_array) == 0)
        return false;

    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $element_list = $m_db->getElementsByTags($tag_array, $m_db);

    if (count($element_list) > 0) {
        echo "<ul class=\"gallery\">\n";
        $tabthumb = 0;
        $tabid    = 1;
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if ($element->type == 'movie') {
                // Video
                echo "<li><a href=\"#\" rel=\"#video".$videoid."\">";
                $videoid++;
            } else {
                // Images
                echo "<li><a href=\"$BASE_URL/browser/getresized.php?id=$current_id\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($element->getSubTitle())."\">";
            }
            echo "<div class=\"dynamic-thumbnail\" src=\"$BASE_URL/browser/getthumb.php?id=$current_id\" title=\"".htmlentities($element->title)."\"></div>";
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
            echo "</div></a></li>\n";
        }
        echo "</ul>\n";
        // Videos overlay
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if($element->type == 'movie') {
                echo "<div class=\"videoverlay\" id=\"video".$videoid."\">";
                echo "<a class=\"player\" href=\"$BASE_URL/$resized_folder/".getResizedPath($current_id)."\"></a>";
                echo "</div>\n";
                $videoid++;
            }
        }
        echo "<div class=\"clearfix\"></div>\n";
    }
    if ($db == NULL)
        $m_db->close();
}

function displayElementList($id, mediaDB &$db = NULL)
{
    global $BASE_URL;
    global $thumbs_per_page;
    global $image_folder;
    global $resized_folder;

    setlocale(LC_ALL, "fr_FR.utf8");

    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $element_list = $m_db->getFolderElements($id);

    if (count($element_list) > 0) {
        echo "<ul class=\"gallery\">\n";
        $tabthumb = 0;
        $tabid    = 1;
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if ($element->type == 'movie') {
                // Video
                echo "<li><a href=\"#\" rel=\"#video".$videoid."\">";
                $videoid++;
            } else {
                // Images
                echo "<li><a href=\"$BASE_URL/browser/getresized.php?id=$current_id\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($element->getSubTitle())."\">";
            }
            echo "<div class=\"dynamic-thumbnail\" src=\"$BASE_URL/browser/getthumb.php?id=$current_id\" title=\"".htmlentities($element->title)."\"></div>";
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
            echo "</div></a></li>\n";
        }
        echo "</ul>\n";
        // Videos overlay
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if($element->type == 'movie') {
                echo "<div class=\"videoverlay\" id=\"video".$videoid."\">";
                echo "<a class=\"player\" href=\"$BASE_URL/$resized_folder/".getResizedPath($current_id)."\"></a>";
                echo "</div>\n";
                $videoid++;
            }
        }
        echo "<div class=\"clearfix\"></div>\n";
    }
    if ($db == NULL)
        $m_db->close();
}

function displaySubFolderList($id, mediaDB &$db = NULL)
{ 
    global $BASE_URL;
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $subfolder_list = $m_db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<ul class=\"galleryfolder\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($m_db->getFolderTitle($subfolder));
            echo "<li><a href=\"$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >";
            echo "<div class=\"dynamic-thumbnail\" src=\"$BASE_URL/browser/getthumb.php?folder=$subfolder\" title=\"".$subfolder_title."\"></div>";
            echo "<div class=\"tooltip\">$subfolder_title<br />".$m_db->getFolderDate($subfolder)."<br />".$m_db->getFolderElementsCount($subfolder, true)." images</div>";
            echo "$subfolder_title</a></li>\n";        
        }
        echo "</ul>\n";
        // Separate Directory list and Pictures
        if ($m_db->getFolderElementsCount($id) > 0) {
            echo "<div class=\"clearfix\"></div>\n";
            echo "<h2></h2>\n";
        }
    }

    if ($db == NULL)
        $m_db->close();
}

function displayFolderHierarchy($id, mediaDB &$db = NULL, $show_slide_map_link = true)
{
    global $BASE_URL;
    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;
    echo "<h2 id=\"gallery\">\n";
    if ($id != 1) {
        echo "<a href=\"$BASE_URL/index.php\">Accueil</a>";
        $folderhierarchy = $m_db->getFolderHierarchy($id);
        foreach($folderhierarchy as $fhier) {
            if ($fhier == 1) continue; // Discard top-level
            echo "/<a href=\"$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($fhier))."\">".htmlentities($m_db->getFolderTitle($fhier))."</a>";
        }
    }
    $path = $m_db->getFolderPath($id);
    if ($m_db->getFolderElementsCount($id) > 0) {
        echo " <a href=\"$BASE_URL/browser/download.php?id=$id\"><img src=\"$BASE_URL/images/save.png\" title=\"T&eacute;l&eacute;charger les images\" alt=\"T&eacute;l&eacute;charger les images\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
        echo " <a href=\"$BASE_URL/browser/slideshow.php?path=$path\"><img src=\"$BASE_URL/images/slideshow.png\" title=\"Diaporama\" alt=\"Diaporama\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    }
    if (($m_db->getFolderElementsCount($id, true) > 0) && ($id != 1))
        echo " <a href=\"$BASE_URL/browser/gettimeline.php?id=$id\"><img src=\"$BASE_URL/images/date.png\" title=\"Au fil du temps\" alt=\"Au fil du temps\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    if (($show_slide_map_link == true) and (getFolderGeolocalizedCount($id, $m_db) > 0))
        echo " <a href=\"$BASE_URL/browser/getmap.php?path=$path\"><img src=\"$BASE_URL/images/googlemaps.png\" title=\"Carte\" alt=\"Carte\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    echo "</h2>\n";

    if ($db == NULL)
        $m_db->close();

}

function displayTopFoldersMenu(mediaDB &$db = NULL)
{
    global $BASE_URL;
    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;
    echo "<ul id=\"toplevel_navigation\">\n"; 
    echo "<li class=\"home\"><a href=\"$BASE_URL/index.php\"><span><b>Accueil</b></span></a></li>\n"; 
    // Build menu with only top-level directories
    $topfolderlist = $m_db->getSubFolders(1);
    foreach($topfolderlist as $topfolder) {
        echo "<li class=\"".$m_db->getFolderName($topfolder)."\"><a href=\"$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($topfolder))."\" ><span>".htmlentities($m_db->getFolderTitle($topfolder))."</span></a> </li>\n";
    }
    echo "</ul>\n"; 

    if ($db == NULL)
        $m_db->close();
}

function generateTopFolderStylesheet(mediaDB &$db = NULL)
{
    global $BASE_DIR;
    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $topfolderlist = $m_db->getSubFolders(1);

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
        $css .= "ul#toplevel_navigation .".$m_db->getFolderName($topfolder)." a {\n";
        $css .= "\tbackground-image: url(../images/toplevel/".$m_db->getFolderName($topfolder).".jpg);\n";
        $css .= "}\n\n";
    }     
    if ($db == NULL)
        $m_db->close();

    file_put_contents("$BASE_DIR/css/toplevelmenu.css", $css);
}

function displaySideMenu($id, mediaDB &$db = NULL)
{
    global $BASE_URL;
    global $latest_album_count;

    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;
    // Display menu icon
    echo "<ul id=\"side_navigation\">\n"; 
    echo "  <li class=\"other_folders\">\n";
    // Side folder or Latest Folders
    if ($id > 1) {
        // Side folders
        $neighborlist = $m_db->getNeighborFolders($id);
        if (count($neighborlist) > 0) {
            echo "  <div><h3>Albums Voisins</h3>\n";
            foreach($neighborlist as $neighbor) {
                $neighbor_title = htmlentities($m_db->getFolderTitle($neighbor));
                echo "    <a href=\"$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($neighbor))."\" title=\"$neighbor_title\" >$neighbor_title</a>\n";
            }
            echo "  </div>\n";
        }
    }

    // Sub folders (not for toplevel)
    if ($id > 1) {
        $subfolder_list = $m_db->getSubFolders($id);
        if (count($subfolder_list) > 0) {
            echo "  <div><h3>Sous-Albums</h3>\n";        
            foreach($subfolder_list as $subfolder) {
                $subfolder_title = htmlentities($m_db->getFolderTitle($subfolder));
                echo "    <a href=\"$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >$subfolder_title</a>\n";
            }
            echo "  </div>\n";
        }
    }
    // Latest folders
    echo "  <div><h3>Nouveaut&eacute;s</h3>\n";
    $latestfolderlist = $m_db->getLatestUpdatedFolder($latest_album_count);
    foreach($latestfolderlist as $latestfolder) {
        $latestfolder_title = htmlentities($m_db->getFolderTitle($latestfolder));
        echo "    <a href=\"$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($latestfolder))."\" title=\"$latestfolder_title\" >$latestfolder_title</a>\n";
    }
    echo "  </div>\n";
    // Browse by...
    echo "  <div><h3>Filtres</h3>\n";
    echo "     <a href=\"$BASE_URL/index.php?browse=tags\" title=\"Mots-Clefs\">Mots-Clefs</a>\n";
    echo "     <a href=\"$BASE_URL/browser/gettimeline.php\" title=\"Au fil du temps\">Date</a>\n";
    echo "  </div>\n";
    echo "  </li>\n";
    // Top level googlemap
    if ($id == 1) {
        echo "  <li class=\"googlemaps\">\n";
        echo "  <h3>Cartographie</h3>\n";
        echo "  <a href=\"$BASE_URL/browser/getmap.php\">Voir les photos sur une carte</a>\n";
        echo "  </li>\n";
    }
    echo "</ul>\n"; 

    if ($db == NULL)
        $m_db->close();
}

function displayFooter()
{
    echo "<ul class=\"submenu\">\n";
    echo "<li>Gallerie v2.5.0 - H. Raffard &amp; C. Laury</li>\n";
    echo "</ul>\n";
    echo "<br clear=\"all\" />\n";
}
?>
