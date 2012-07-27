<?php

function safeDirectory($path) {
    GLOBAL $displayError;
    $result = $path;
    if (strpos($path,"..")!==false)
        $result = "";
    if (substr($path,0,1)=="/") {
        $result = "";
    }
    if ($result!=$path) {
        $displayError[] = "Illegal path specified, ignoring.";
    }
    return $result;
}

function parentFolder($path) {
    $path_hier = explode('/', $path);
    if (count($path_hier) > 1)
        return implode('/', array_pop($path_hier));
    else
        return "";
}

function getProcessingTime($starttime)
{
    $mtime = explode(' ', microtime());
    $totaltime = ($mtime[0] + $mtime[1] - $starttime);
    if ($totaltime < 60) {
        return round($totaltime, 1)."s";
    } else if ($totaltime < 3600) {
        $totaltime = $totaltime / 60;
        return round($totaltime, 1)."min";
    } else {
        $totaltime = $totaltime / 3600;
        return round($totaltime, 2)."h";
    }
}

function genTimelineData(mediaDB &$db = NULL, $top_id = 1)
{
    global $BASE_URL;
    global $BASE_DIR;
    global $thumb_folder;
    global $resized_folder;

    $json_file = "$BASE_DIR/cache/timeline-$top_id.json";
    $json_data = "{\n";
    $folder    = new mediaFolder();
    $element   = new mediaObject();
    $m_db      = NULL;
    if ($db == NULL)
        $m_db = new mediaDB();
    else
        $m_db = $db;

    $json_data .= "\t".'"timeline":'."\n";
    $json_data .= "\t{\n";
    $json_data .= "\t\t".'"headline":"Photos de Catherine &amp; Cyril",'."\n";
    $json_data .= "\t\t".'"type":"default", "text":"Au fil du temps",'."\n";
    $json_data .= "\t\t".'"date": ['."\n";
    file_put_contents($json_file, $json_data);
    if (($top_id != 0) && ($m_db->getFolderElementsCount($top_id, true) < 1000)) {
        $items_are_folder = false;
        $item_list = $m_db->getFolderElements($top_id, true);
    } else {
        $items_are_folder = true;
        $item_list = $m_db->getTimedFolderList($top_id);
    }
    foreach($item_list as $idx => $itemid) {
        if ($items_are_folder == true) {
            $m_db->loadMediaFolder($folder, $itemid);
            $hierarchy = $m_db->getFolderHierarchy($itemid);
            $json_data = "\t\t\t{\n";
            $json_data .= "\t\t\t\t".'"startDate":"'.strftime("%Y,%m,%d", strtotime($folder->originaldate)).'",'."\n";
            $json_data .= "\t\t\t\t".'"headline":"'.utf8_encode($folder->title).'",'."\n";
            $json_data .= "\t\t\t\t".'"text":"<a href='."'$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($itemid))."'>Aller dans le r&eacute;pertoire</a>".'"'.",\n";
            $json_data .= "\t\t\t\t".'"tag":"'.utf8_encode($m_db->getFolderTitle($hierarchy[1])).'",'."\n";
            $json_data .= "\t\t\t\t".'"asset":'."\n";
            $json_data .= "\t\t\t\t\t{\n";
            $json_data .= "\t\t\t\t\t\t".'"media":"'."$BASE_URL/$resized_folder/".getFolderThumbnailPath($itemid).'",'."\n";
            $json_data .= "\t\t\t\t\t\t".'"credit":"",'."\n";
            $json_data .= "\t\t\t\t\t\t".'"thumbnail":"'."$BASE_URL/$thumb_folder/".getFolderThumbnailPath($itemid).'",'."\n";
            $json_data .= "\t\t\t\t\t\t".'"caption":""'."\n";
            $json_data .= "\t\t\t\t\t}\n";
            $json_data .= "\t\t\t}";
            if ($idx != (count($item_list) - 1))
                $json_data .= ",\n";
            else
                $json_data .= "\n";
        } else {
            // Items are images
            $m_db->loadMediaObject($element, $itemid);
            if ($element->type == 'movie') continue;
            $json_data = "";
            if ($idx > 0) {
                $json_data .= ",\n";
            }
            $json_data .= "\t\t\t{\n";
            $json_data .= "\t\t\t\t".'"startDate":"'.strftime("%Y,%m,%d,%H,%M,%S", strtotime($element->originaldate)).'",'."\n";
            $json_data .= "\t\t\t\t".'"headline":"'.utf8_encode($element->title).'",'."\n";
            $json_data .= "\t\t\t\t".'"text":"<a href='."'$BASE_URL/index.php?path=".urlencode($m_db->getFolderPath($element->folder_id))."'>Aller dans le r&eacute;pertoire</a>".'"'.",\n";
            $json_data .= "\t\t\t\t".'"tag":"'.utf8_encode($m_db->getFolderTitle($element->folder_id)).'",'."\n";
            $json_data .= "\t\t\t\t".'"asset":'."\n";
            $json_data .= "\t\t\t\t\t{\n";
            $json_data .= "\t\t\t\t\t\t".'"media":"'."$BASE_URL/$resized_folder/".getResizedPath($itemid).'",'."\n";
            $json_data .= "\t\t\t\t\t\t".'"credit":"",'."\n";
            $json_data .= "\t\t\t\t\t\t".'"thumbnail":"'."$BASE_URL/$thumb_folder/".getThumbnailPath($itemid).'",'."\n";
            $json_data .= "\t\t\t\t\t\t".'"caption":""'."\n";
            $json_data .= "\t\t\t\t\t}\n";
            $json_data .= "\t\t\t}";
        }
        file_put_contents($json_file, $json_data, FILE_APPEND);
    }
    $json_data = "\t\t]\n";
    $json_data .= "\t}\n";
    $json_data .= "}\n";
    file_put_contents($json_file, $json_data, FILE_APPEND);
}
?>
