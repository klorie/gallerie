<?php

function displaySubFolderList($id, mediaDB &$db = NULL)
{ 
    global $BASE_URL;
    global $thumb_folder;
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $subfolder_list = $m_db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<ul data-role=\"listview\" data-theme=\"a\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($m_db->getFolderTitle($subfolder));
            echo "<li>";
            echo "<img src=\"$BASE_URL/$thumb_folder/".getFolderThumbnailPath($subfolder)."\" title=\"".$subfolder_title."\" />";
            echo "<a href=\"$BASE_URL/mobile.php?path=".urlencode($m_db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" ><h4>$subfolder_title</h4></a>";
            echo "<p>".$m_db->getFolderDate($subfolder)."</p>";
            echo "<div class=\"ui-li-count\">".$m_db->getFolderElementsCount($subfolder, true)."</div>";
            echo "</li>\n";        
        }
        // Separate Directory list and Pictures
        if ($m_db->getFolderElementsCount($id) > 0) {
            echo "<li data-role=\"list-divider\">Photos<span class=\"ui-li-count\">".$m_db->getFolderElementsCount($id)."</span></li>\n";
        } else {
          echo "</ul>\n";
        }
    }

    if ($db == NULL)
        $m_db->close();
}

function displayElementList($id, mediaDB &$db = NULL)
{
    global $BASE_URL;
    global $image_folder;
    global $resized_folder;
    global $thumb_folder;

    setlocale(LC_ALL, "fr_FR.utf8");

    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $element_list   = $m_db->getFolderElements($id);
    $subfolder_list = $m_db->getSubFolders($id);

    if (count($element_list) > 0) {
        if (count($subfolder_list) == 0) {
            echo "<ul data-role=\"listview\" data-theme=\"a\">\n";
        }
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $m_db->loadMediaObject($element, $current_id);
            if ($element->type == 'picture') {
                echo "<li>";
                echo "<img src=\"$BASE_URL/$thumb_folder/".getThumbnailPath($current_id)."\" title=\"".htmlentities($element->title)."\" />";
                echo "<a href=\"$BASE_URL/browser/getresized.php?id=$current_id\" rel=\"external\" title=\"".htmlentities($element->title)."\">";
                echo "<h3 class=\"element-title\">".htmlentities($element->title)."</h3><p class=\"element-subtitle\">".htmlentities($element->getSubTitle(true))."</p>";
                echo "</a></li>\n";
            }
        }
        echo "</div>\n";
    }
    if ($db == NULL)
        $m_db->close();
}

