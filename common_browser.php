<?php

require_once "common_db.php";
require_once "resized.php";

function displayElementList($id, mediaDB &$db = NULL)
{
    global $thumbs_per_page;
    global $image_folder;
    global $resized_folder;

    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $element_list = $m_db->getFolderElements($id);

    if (count($element_list) > 0) {
        $tabcount = count($element_list) / $thumbs_per_page;
        if (count($element_list) > $thumbs_per_page) {
            echo "<ul class=\"tabs\">\n";
            for ($t = 0; $t < $tabcount; $t++) {
                echo "\t<li><a href=\"#\">".($t+1)."</a></li>\n";
            }
            echo "</ul>\n";
            echo "<div class=\"clearfix\"></div>\n";
        }
        echo "<ul class=\"gallery\">\n";
        $tabthumb = 0;
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
                echo "<li><a href=\"./getresized.php?id=$current_id\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($element->getSubTitle())."\">";
            }
            echo "<div class=\"dynamic-thumbnail\" src=\"./getthumb.php?id=$current_id\" title=\"".htmlentities($element->title)."\"></div><div class=\"tooltip\">".htmlentities($element->title)."<br />".strftime('%d/%m/%Y %Hh%M', strtotime($element->originaldate));
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
            $tabthumb++;
            if ($tabthumb >= $thumbs_per_page) {
                $tabthumb = 0;
                echo "</ul>\n<ul class=\"gallery\">\n";
            }
        }
        if (($tabthumb < $thumbs_per_page) && ($tabcount > 1)) {
            while($tabthumb < $thumbs_per_page) {
                echo "<li></li>\n";
                $tabthumb++;
            }
        }
        echo "</ul>\n";
        // Videos overlay
        $videoid  = 0;
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if($element->type == 'movie') {
                echo "<div class=\"videoverlay\" id=\"video".$videoid."\">";
                echo "<a class=\"player\" href=\"$resized_folder/".getResizedPath($current_id)."\"></a>";
                echo "</div>\n";
                $videoid++;
            }
        }
        echo "<div class=\"clearfix\"></div>\n";
        echo "<h3></h3>\n";
    }
    if ($db == NULL)
        $m_db->close();
}

function displaySubFolderList($id, mediaDB &$db = NULL)
{ 
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $subfolder_list = $m_db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<ul class=\"galleryfolder\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($m_db->getFolderTitle($subfolder));
            echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >";
            echo "<img src=\"./getthumb.php?folder=$subfolder\" alt=\"".$m_db->getFolderName($subfolder)."\"/>";
            echo "<br />$subfolder_title</a></li>\n";        
        }
        echo "</ul>\n";
        // Separate Directory list and Pictures
        echo "<div class=\"clearfix\"></div>\n";
        echo "<h3></h3>\n";
    }

    if ($db == NULL)
        $m_db->close();
}

function displaySubFolderMenu($id, mediaDB &$db = NULL)
{ 
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $subfolder_list = $m_db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<h3>Sous-Albums</h3>\n";  
        echo "<ul class=\"menu\">\n";
        foreach($subfolder_list as $subfolder) {
            echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($subfolder))."\" >".htmlentities($m_db->getFolderTitle($subfolder))."</a></li>\n";
        }
        echo "</ul>\n";
    }

    if ($db == NULL)
        $m_db->close();
}

function displayNeighborFoldersMenu($id, mediaDB &$db = NULL)
{
    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    echo "<h3>Albums Voisins</h3>\n";
    $neighborlist = $m_db->getNeighborFolders($id);
    if (count($neighborlist) > 0) {
        echo "<ul class=\"menu\">\n";
        foreach($neighborlist as $neighbor) {
            echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($neighbor))."\" >".htmlentities($m_db->getFolderTitle($neighbor))."</a></li>\n";
        }
        echo "</ul>\n";
    }

    if ($db == NULL)
        $m_db->close();
}

function displayLatestFoldersMenu(mediaDB &$db = NULL)
{
    global $latest_album_count;

    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;
    $latestfolderlist = $m_db->getLatestUpdatedFolder($latest_album_count);
    echo "<h3>Nouveaut&eacute;s</h3>\n";
    echo "<ul class=\"menu\">\n";
    foreach($latestfolderlist as $latestfolder) {
        echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($m_db->getFolderPath($latestfolder))."\" >".htmlentities($m_db->getFolderTitle($latestfolder))."</a></li>\n";
    }
    echo "</ul>\n";

    if ($db == NULL)
        $m_db->close();
}

function displayTopFoldersMenu(mediaDB &$db = NULL)
{
    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;
    echo "<ul class=\"submenu\">\n"; 
    echo "<li><a href=\"http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/index.php\"><b>Accueil</b></a></li>\n"; 
    // Build menu with only top-level directories
    $topfolderlist = $m_db->getSubFolders(1);
    foreach($topfolderlist as $topfolder) {
        echo "<li><a href=\"http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/index.php?path=".urlencode($m_db->getFolderPath($topfolder))."\" >".htmlentities($m_db->getFolderTitle($topfolder))."</a> </li>\n";
    }
    echo "</ul>\n"; 

    if ($db == NULL)
        $m_db->close();
}

function displayFolderHierarchy($id, mediaDB &$db = NULL, $show_slide_map_link = true)
{
    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;
    echo "<h3 id=\"gallery\">\n";
    if ($id != 1) {
        echo "<a href=\"http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/index.php\">Accueil</a>";
        $folderhierarchy = $m_db->getFolderHierarchy($id);
        foreach($folderhierarchy as $fhier) {
            if ($fhier == 1) continue; // Discard top-level
            echo "/<a href=\"http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/index.php?path=".urlencode($m_db->getFolderPath($fhier))."\">".htmlentities($m_db->getFolderTitle($fhier))."</a>";
        }
    }
    if ($show_slide_map_link == true) {
        if ($m_db->getFolderElementsCount($id) > 1) {
            echo " [ <a href=\"javascript:PicLensLite.start({feedUrl:'http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/photos.rss.php?id=$id', delay:6});\">Diaporama</a> ]\n";
        }
        if (getFolderGeolocalizedCount($id, $m_db) > 0) {
            $path = $m_db->getFolderPath($id);
            echo " [ <a href=\"http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/getmap.php?path=$path\">Carte</a> ]\n";
        }
    }
    echo "</h3>\n";

    if ($db == NULL)
        $m_db->close();

}

function displayTopFoldersMenuNew(mediaDB &$db = NULL)
{
    $m_db = NULL;
    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;
    echo "<ul id=\"toplevel_navigation\">\n"; 
    echo "<li class=\"home\"><a href=\"http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/index.php\"><span><b>Accueil</b></span></a></li>\n"; 
    // Build menu with only top-level directories
    $topfolderlist = $m_db->getSubFolders(1);
    foreach($topfolderlist as $topfolder) {
        echo "<li class=\"".$m_db->getFolderName($topfolder)."\"><a href=\"http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/index.php?path=".urlencode($m_db->getFolderPath($topfolder))."\" ><span>".htmlentities($m_db->getFolderTitle($topfolder))."</span></a> </li>\n";
    }
    echo "</ul>\n"; 

    if ($db == NULL)
        $m_db->close();
}

function generateTopFolderStylesheet(mediaDB &$db = NULL)
{
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
    $css .= "\tz-index:999999;\n";
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
    $css .= "\tmargin-top: -2px;\n";
    $css .= "\twidth: 100px;\n";
    $css .= "\theight: 30px;\n";
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
    $css .= "\tletter-spacing:2px;\n";
    $css .= "\tfont-size:11px;\n";
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

    file_put_contents('css/toplevelmenu.css', $css);
}


?>
