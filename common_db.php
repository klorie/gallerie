<?php
require_once "common_media.php";

class mediaDB extends SQLite3
{
    function init_database()
    {
        // Method which initialize the database with proper structure
        if (!$this->exec('CREATE TABLE media_objects (id INTEGER PRIMARY KEY AUTOINCREMENT, folder_id INTEGER, type TEXT, title TEXT, filesize INTEGER, duration TEXT, lastmod DATETIME, filename TEXT, camera TEXT, focal INTEGER, lens TEXT, fstop TEXT, shutter TEXT, iso INTEGER, originaldate DATETIME, width INTEGER, height INTEGER, lens_is_zoom INTEGER, longitude REAL, latitude REAL, altitude REAL);')) {
            throw new Exception($this->lastErrorMsg());
        }
        if (!$this->exec('CREATE TABLE media_folders (id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id INTEGER, title TEXT, lastmod DATETIME, foldername TEXT);')) {
            throw new Exception($this->lastErrorMsg());
        }
        if (!$this->exec('CREATE TABLE media_tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, media_id INTEGER);')) {
            throw new Exception($this->lastErrorMsg());
        }
        if (!$this->exec('CREATE INDEX media_tag_name ON media_tags(name);')) {
            throw new Exception($this->lastErrorMsg());
        }
    }

    function __construct()
    {
        try {
            $this->open('gallery.db', SQLITE3_OPEN_READWRITE);
        } catch (Exception $e) {
            // Database does not exists, create it
            $this->open('gallery.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $this->init_database();
        }
    }

    function findMediaObjectID(mediaObject &$media)
    {
        // Return ID in database if the same object is already present (same filename, same folder)
        // return -1 on failure
        $query = "SELECT id FROM media_objects WHERE folder_id = ";
        if ($media->folder != NULL)
            $query .= $media->folder->db_id;
        else
            $query .= "-1";
        $query .= " AND filename = '$media->filename'";

        $result = $this->querySingle($query);

        if ($result == NULL)
            return -1;
        else
            return $result;
    }

    function storeMediaObject(mediaObject &$media)
    {
        $store_id = $this->findMediaObjectID($media);
        if ($store_id != -1)
            $query = 'REPLACE INTO media_objects (id, folder_id, type, title, filesize, lastmod, filename, ';
        else
            $query = 'REPLACE INTO media_objects (folder_id, type, title, filesize, lastmod, filename, ';
        $query .= 'focal, lens, fstop, shutter, iso, originaldate, width, height, lens_is_zoom, ';
        $query .= 'camera, duration, longitude, latitude, altitude) VALUES (';
        if ($store_id != -1)
            $query .= $store_id. ', ';
        if ($media->folder != NULL) {
            $query .= $media->folder->db_id. ', ';
        } else
            $query .= ' -1, ';
        $query .= "'".$media->type        . "', ";
        $query .= "'".$media->title       . "', ";
        $query .= $media->filesize        . ', ';
        $query .= "'".$media->lastmod     . "', ";
        $query .= "'".$media->filename    . "', ";
        $query .= $media->focal           . ', ';
        $query .= "'".$media->lens        . "', ";
        $query .= "'".$media->fstop       . "', ";
        $query .= "'".$media->shutter     . "', ";
        $query .= $media->iso             . ', ';
        $query .= "'".$media->originaldate. "', ";
        $query .= $media->width           . ', ';
        $query .= $media->height          . ', ';
        $query .= $media->lens_is_zoom    . ', ';
        $query .= "'".$media->camera      . "', ";
        $query .= "'".$media->duration    . "', ";
        $query .= $media->longitude       . ', ';
        $query .= $media->latitude        . ', ';
        $query .= $media->altitude        . ');';
        if ($this->exec($query) == FALSE) {
            throw new Exception($this->lastErrorMsg());
        } else {
            $media->db_id = $this->lastInsertRowID();
        }
        // Process tags
        if ($store_id != -1) {
            // Clean old tags
            if ($this->exec("DELETE FROM media_tags WHERE media_id=$store_id;") == FALSE)
                throw new Exception($this->lastErrorMsg());
        }
        foreach ($media->tags as $tag) {
            if ($this->exec("REPLACE INTO media_tags (media_id, name) VALUES ($media->db_id, '$tag');") == FALSE)
                throw new Exception($this->lastErrorMsg());
        }
    }

    function loadMediaObject(mediaObject &$media, $id)
    {
        $result = $this->querySingle("SELECT * FROM media_objects WHERE id=$id;", true);

        if ($result === FALSE) {
            throw new Exception($this->lastErrorMsg());
        } else {
            $media->db_id        = $id;
            $media->title        = $result['title'];
            $media->type         = $result['type'];
            $media->camera       = $result['camera'];
            $media->filesize     = $result['filesize'];
            $media->lastmod      = $result['lastmod'];
            $media->filename     = $result['filename'];
            $media->focal        = $result['focal'];
            $media->lens         = $result['lens'];
            $media->fstop        = $result['fstop'];
            $media->shutter      = $result['shutter'];
            $media->iso          = $result['iso'];
            $media->originaldate = $result['originaldate'];
            $media->width        = $result['width'];
            $media->height       = $result['height'];
            $media->lens_is_zoom = $result['lens_is_zoom'];
            $media->duration     = $result['duration'];
            $media->longitude    = $result['longitude'];
            $media->latitude     = $result['latitude'];
            $media->altitude     = $result['altitude'];
        }
        // Load corresponding tags
        $results = $this->query("SELECT name FROM media_tags WHERE media_id=$id;");
        if ($results === FALSE)
            throw new Exception($this->lastErrorMsg());
        while($row = $results->fetchArray()) {
            $media->tags[] = $row['name'];
        }
    }

    function findMediaFolderID(mediaFolder &$media)
    {
        // Return ID in database if the same object is already present (same name, same parent)
        // return -1 on failure
        $query = "SELECT id FROM media_folders WHERE parent_id = ";
        if ($media->parent != NULL)
            $query .= $media->parent->db_id;
        else
            $query .= "-1";
        $query .= " AND foldername = '$media->name'";

        $result = $this->querySingle($query);

        if ($result == NULL)
            return -1;
        else
            return $result;        
    }

    function storeMediaFolder(mediaFolder &$media)
    {
        $store_id = $this->findMediaFolderID($media);
        if ($store_id != -1)
            $query = "REPLACE INTO media_folders (id, parent_id, title, lastmod, foldername) VALUES (";
        else
            $query = "REPLACE INTO media_folders (parent_id, title, lastmod, foldername) VALUES (";
        if ($store_id != -1)
            $query .= $store_id.', ';
        if ($media->parent == NULL)
            $query .= "-1, ";
        else
            $query .= $media->parent->db_id.", ";
        $query .= "'".$media->title."', ";
        $query .= "'".$media->lastmod."', ";
        $query .= "'".$media->name."');";
        if ($this->exec($query) == FALSE) {
            throw new Exception($this->lastErrorMsg());
        } else {
            $media->db_id = $this->lastInsertRowID();
        }
        if (count($media->subfolder) > 0) {
            for ($i = 0; $i < count($media->subfolder); $i++)
                $this->storeMediaFolder($media->subfolder[$i]);
        }
        if (count($media->element) > 0) {
            for ($i = 0; $i < count($media->element); $i++)
                $this->storeMediaObject($media->element[$i]);
        }
    }

    function loadMediaFolder(mediaFolder &$media, $id)
    {
        $result = $this->querySingle("SELECT * FROM media_folders WHERE id=$id;", true);

        if ($result === FALSE) {
            throw new Exception($this->lastErrorMsg());
        } else {
            $media->db_id   = $id;
            $media->title   = $result['title'];
            $media->lastmod = $result['lastmod'];
            $media->name    = $result['foldername'];
        }
        // Fetch subfolders
        $results = $this->query("SELECT id FROM media_folders WHERE parent_id=$id;");
        if ($results === FALSE)
            throw new Exception($this->lastErrorMsg());
        else
            while($row = $results->fetchArray()) {
                if ($row['id'] != -1) {
                    $subfolder = new mediaFolder($media);
                    $this->loadMediaFolder($subfolder, $row['id']);
                    $media->subfolder[] = $subfolder;
                }
            }
        // Fetch elements
        $results = $this->query("SELECT id FROM media_objects WHERE folder_id=$id;");
        if ($results === FALSE)
            throw new Exception($this->lastErrorMsg());
        while($row = $results->fetchArray()) {
            if ($row['id'] != -1) {
                $element = new mediaObject($media);
                $this->loadMediaObject($element, $row['id']);
                $media->element[] = $element;
            }
        }
    }

    function getMediaFolderID($path)
    {
        // Find folder ID in DB according to the path given (relative to the gallery)
        $path_array = explode('/', $path);
        $parent_id  = 1;
        $folder_id  = 1;

        foreach($path_array as $current_path_level) {
            $parent_id = $folder_id;
            $result = $this->querySingle("SELECT id FROM media_folders WHERE parent_id=$parent_id AND foldername='$current_path_level';");
            if ($result === FALSE)
                throw new Exception($this->lastErrorMsg());
            else if ($result != NULL)
                $folder_id = $result;
            else
                return -1;
        }
        return $folder_id;
    }
}

?>
