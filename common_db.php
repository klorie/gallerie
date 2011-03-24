<?php
require_once "common.php";
require_once "common_media.php";

class mediaDB extends SQLite3
{
    function init_database()
    {
        // Method which initialize the database with proper structure
        $query  = "CREATE TABLE media_objects (id INTEGER PRIMARY KEY AUTOINCREMENT, folder_id INTEGER, type TEXT, ";
        $query .= "title TEXT, filesize INTEGER, duration TEXT, lastmod DATETIME, filename TEXT, download_path TEXT, thumbnail TEXT, resized TEXT, ";
        $query .= "camera TEXT, focal INTEGER, lens TEXT, fstop TEXT, shutter TEXT, iso INTEGER, originaldate DATETIME, ";
        $query .= "width INTEGER, height INTEGER, lens_is_zoom INTEGER, longitude REAL, latitude REAL, altitude REAL);";
        if (!$this->exec($query))
            throw new Exception($this->lastErrorMsg());
        $query  = "CREATE TABLE media_folders (id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id INTEGER, title TEXT, ";
        $query .= "lastmod DATETIME, originaldate DATETIME, thumbnail TEXT, foldername TEXT);";
        if (!$this->exec($query))
            throw new Exception($this->lastErrorMsg());
        if (!$this->exec('CREATE TABLE media_tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, media_id INTEGER);')) 
            throw new Exception($this->lastErrorMsg());
        if (!$this->exec('CREATE INDEX media_tag_name ON media_tags(name);'))
            throw new Exception($this->lastErrorMsg());
        if (!$this->exec('CREATE INDEX media_tag_media_id ON media_tags(media_id);'))
            throw new Exception($this->lastErrorMsg());
        if (!$this->exec('CREATE INDEX media_folders_parent_id ON media_folders(parent_id);'))
            throw new Exception($this->lastErrorMsg());
        if (!$this->exec('CREATE INDEX media_folders_foldername ON media_folders(foldername);'))
            throw new Exception($this->lastErrorMsg());
        if (!$this->exec('CREATE INDEX media_objects_folder_id ON media_objects(folder_id);'))
            throw new Exception($this->lastErrorMsg());
        if (!$this->exec('CREATE INDEX media_objects_filename ON media_objects(filename);'))
            throw new Exception($this->lastErrorMsg());
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
        $query .= " AND filename = '".$this->escapeString($media->filename)."'";

        $result = $this->querySingle($query);

        if ($result == NULL)
            return -1;
        else
            return $result;
    }

    function storeMediaObject(mediaObject &$media, $update=false)
    {
        $store_id = -1;
        if ($update == true)
            $store_id = $this->findMediaObjectID($media);
        if ($store_id != -1)
            $query = 'REPLACE INTO media_objects (id, folder_id, type, title, filesize, lastmod, filename, thumbnail, resized, ';
        else
            $query = 'INSERT INTO media_objects (folder_id, type, title, filesize, lastmod, filename, thumbnail, resized, ';
        $query .= 'download_path, focal, lens, fstop, shutter, iso, originaldate, width, height, lens_is_zoom, ';
        $query .= 'camera, duration, longitude, latitude, altitude) VALUES (';
        if ($store_id != -1)
            $query .= $store_id. ', ';
        if ($media->folder != NULL) {
            $query .= $media->folder->db_id. ', ';
        } else
            $query .= ' -1, ';
        $query .= "'".$this->escapeString($media->type)         . "', ";
        $query .= "'".$this->escapeString($media->title)        . "', ";
        $query .= $media->filesize         . ', ';
        $query .= "'".$this->escapeString($media->lastmod)      . "', ";
        $query .= "'".$this->escapeString($media->filename)     . "', ";
        $query .= "'".$this->escapeString($media->thumbnail)    . "', ";
        $query .= "'".$this->escapeString($media->resized)      . "', ";
        $query .= "'".$this->escapeString($media->download_path). "', ";
        $query .= $media->focal            . ', ';
        $query .= "'".$this->escapeString($media->lens)         . "', ";
        $query .= "'".$this->escapeString($media->fstop)        . "', ";
        $query .= "'".$this->escapeString($media->shutter)      . "', ";
        $query .= $media->iso              . ', ';
        $query .= "'".$this->escapeString($media->originaldate) . "', ";
        $query .= $media->width            . ', ';
        $query .= $media->height           . ', ';
        $query .= $media->lens_is_zoom     . ', ';
        $query .= "'".$this->escapeString($media->camera)       . "', ";
        $query .= "'".$this->escapeString($media->duration)     . "', ";
        $query .= $media->longitude        . ', ';
        $query .= $media->latitude         . ', ';
        $query .= $media->altitude         . ');';
        if ($this->exec($query) == FALSE) {
            print("-E- Failed query: $query\n");
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
            $query = "REPLACE INTO media_tags (media_id, name) VALUES ($media->db_id, '".$this->escapeString($tag)."');";
            if ($this->exec($query) == FALSE) {
                print("-E-  Failed query was: $query\n");
                throw new Exception($this->lastErrorMsg());
            }
        }
    }

    function loadMediaObject(mediaObject &$media, $id)
    {
        $result = $this->querySingle("SELECT * FROM media_objects WHERE id=$id;", true);

        if ($result === FALSE) {
            throw new Exception($this->lastErrorMsg());
        } else {
            $media->db_id         = $id;
            $media->folder_id     = $result['folder_id'];
            $media->title         = $result['title'];
            $media->type          = $result['type'];
            $media->camera        = $result['camera'];
            $media->filesize      = $result['filesize'];
            $media->lastmod       = $result['lastmod'];
            $media->filename      = $result['filename'];
            $media->thumbnail     = $result['thumbnail'];
            $media->resized       = $result['resized'];
            $media->download_path = $result['download_path'];
            $media->focal         = $result['focal'];
            $media->lens          = $result['lens'];
            $media->fstop         = $result['fstop'];
            $media->shutter       = $result['shutter'];
            $media->iso           = $result['iso'];
            $media->originaldate  = $result['originaldate'];
            $media->width         = $result['width'];
            $media->height        = $result['height'];
            $media->lens_is_zoom  = $result['lens_is_zoom'];
            $media->duration      = $result['duration'];
            $media->longitude     = $result['longitude'];
            $media->latitude      = $result['latitude'];
            $media->altitude      = $result['altitude'];
        }
        // Load corresponding tags
        $results = $this->query("SELECT name FROM media_tags WHERE media_id=$id;");
        if ($results === FALSE)
            throw new Exception($this->lastErrorMsg());
        while($row = $results->fetchArray()) {
            $media->tags[] = $row['name'];
        }
        $results->finalize();
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
        $query .= " AND foldername = '".$this->escapeString($media->name)."'";

        $result = $this->querySingle($query);

        if ($result == NULL)
            return -1;
        else
            return $result;        
    }

    function storeMediaFolder(mediaFolder &$media, $update=false)
    {
        $store_id = -1;
        if ($update == true)
            $store_id = $this->findMediaFolderID($media);

        if ($store_id != -1)
            $query = 'REPLACE INTO media_folders (id, parent_id, title, lastmod, originaldate, foldername, thumbnail) VALUES (';
        else
            $query = 'INSERT INTO media_folders (parent_id, title, lastmod, originaldate, foldername, thumbnail) VALUES (';
        if ($store_id != -1)
            $query .= $store_id.', ';
        if ($media->parent == NULL)
            $query .= "-1, ";
        else
            $query .= $media->parent->db_id.", ";
        $query .= "'".$this->escapeString($media->title)."', ";
        $query .= "'".$this->escapeString($media->lastmod)."', ";
        $query .= "'".$this->escapeString($media->originaldate)."', ";
        $query .= "'".$this->escapeString($media->name)."', ";
        $query .= "'".$this->escapeString($media->thumbnail)."');";
        if ($this->exec($query) == FALSE) {
            print("-E-  Failed query: $query\n");
            throw new Exception($this->lastErrorMsg());
        } else {
            $media->db_id = $this->lastInsertRowID();
        }
        if (count($media->subfolder) > 0) {
            foreach($media->subfolder as $subfolder)
                $this->storeMediaFolder($subfolder, $update);
        }
        if (count($media->element) > 0) {
            foreach($media->element as $element)
                $this->storeMediaObject($element, $update);
        }
    }

    function loadMediaFolder(mediaFolder &$media, $id, $depth = 1)
    {
        $result = $this->querySingle("SELECT * FROM media_folders WHERE id=$id;", true);

        if ($result === FALSE) {
            throw new Exception($this->lastErrorMsg());
        } else {
            $media->db_id        = $id;
            $media->title        = $result['title'];
            $media->lastmod      = $result['lastmod'];
            $media->originaldate = $result['originaldate'];
            $media->name         = $result['foldername'];
            $media->thumbnail    = $result['thumbnail'];
        }
        // Fetch subfolders if required
        if ($depth > 0) {
            $depth--;
            $results = $this->query("SELECT id FROM media_folders WHERE parent_id=$id;");
            if ($results === FALSE)
                throw new Exception($this->lastErrorMsg());
            while($row = $results->fetchArray()) {
                if ($row['id'] != -1) {
                    $subfolder = new mediaFolder($media);
                    $this->loadMediaFolder($subfolder, $row['id'], $depth);
                    $media->subfolder[] = $subfolder;
                }
            }
            $results->finalize();
        }
        // Fetch elements
        $results = $this->query("SELECT id FROM media_objects WHERE folder_id=$id;");
        if ($results === FALSE) throw new Exception($this->lastErrorMsg());
        while($row = $results->fetchArray()) {
            if ($row['id'] != -1) {
                $element = new mediaObject($media);
                $this->loadMediaObject($element, $row['id']);
                $media->element[] = $element;
            }
        }
        $results->finalize();
    }

    function getFolderID($path)
    {
        // Top folder get db_id = 1 by construction
        if ($path == "") return 1;
        // Find folder ID in DB according to the path given (relative to the gallery)
        $path_array = explode('/', $path);
        $parent_id  = 1;
        $folder_id  = 1;

        foreach($path_array as $current_path_level) {
            $parent_id = $folder_id;
            $result = $this->querySingle("SELECT id FROM media_folders WHERE parent_id=$parent_id AND foldername='".$this->escapeString($current_path_level)."';");
            if ($result === FALSE) throw new Exception($this->lastErrorMsg());
            if ($result != NULL)
                $folder_id = $result;
            else
                return -1;
        }
        return $folder_id;
    }

    function getFolderPath($id)
    {
        // Return folder (which id is given in arg) full path
        $parent_id   = -1;
        $folder_path = "";
        $result = $this->querySingle("SELECT parent_id, foldername FROM media_folders WHERE id=$id;", true);
        if ($result === FALSE) throw new Exception($this->lastErrorMsg());
        $parent_id = $result['parent_id'];
        if ($result['foldername'] != "")
            $folder_path = $result['foldername'];
        while($parent_id != -1) {
            $result = $this->querySingle("SELECT parent_id, foldername FROM media_folders WHERE id=$parent_id;", true);
            if ($result === false) throw new Exception($this->lastErrorMsg());
            $parent_id = $result['parent_id'];
            if ($result['foldername'] != "")
                $folder_path = $result['foldername'].'/'.$folder_path; 
        }
        return $folder_path;
    }

    function getNeighborFolders($id)
    {
        $neighbor_array = array();
        $parent_id      = $this->querySingle("SELECT parent_id FROM media_folders WHERE id=$id;");
        if ($parent_id === FALSE) throw new Exception($this->lastErrorMsg());
        // Returns array of id of neighbor folders
        $results = $this->query("SELECT id FROM media_folders WHERE parent_id=$parent_id;");
        if ($results === FALSE) throw new Exception($this->lastErrorMsg());
        while($row = $results->fetchArray()) {
            if ($row['id'] != $id)
                $neighbor_array[] = $row['id'];
        }
        $results->finalize();
        return $neighbor_array;
    }

    function getFolderTitle($id)
    {
        // Return folder (which id is given in arg) title
        $result = $this->querySingle("SELECT title FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->lastErrorMsg());
        return $result;
    }

    function getFolderName($id)
    {
        // Return folder (which id is given in arg) name
        $result = $this->querySingle("SELECT foldername FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->lastErrorMsg());
        return $result;
    }

    function getFolderDate($id)
    {
        setlocale(LC_ALL, "fr_FR.utf8");
        // Return folder (which id is given in arg) date
        $result = $this->querySingle("SELECT originaldate FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->lastErrorMsg());
        return strftime("%e %B %Y", strtotime($result));
    }

    function getLatestUpdatedFolder($nb_latest)
    {
        $latest_array = array();
        // Return array of id of latest updated folders
        $results = $this->query("SELECT id FROM media_folders ORDER BY lastmod DESC;");
        if ($results === FALSE) throw new Exception($this->lastErrorMsg());
        while(($row = $results->fetchArray()) && (count($latest_array) < $nb_latest)) {
            if ($row['id'] != 1)
                $latest_array[] = $row['id'];
        }
        $results->finalize();
        return $latest_array;
    }

    function getFolderHierarchy($id)
    {
        $folder_array[] = $id;
        // Return array of folders from top level up to id
        $parent_id = $this->querySingle("SELECT parent_id FROM media_folders WHERE id=$id;");
        if ($parent_id === false) throw new Exception($this->lastErrorMsg());
        while($parent_id != -1) {
            array_unshift($folder_array, $parent_id);
            $parent_id = $this->querySingle("SELECT parent_id FROM media_folders WHERE id=$parent_id;");
        }
        return $folder_array;
    }

    function getSubFolders($id)
    {
        global $reverse_subalbum_sort;

        $sub_array = array();
        // Returns array of id of neighbor folders
        $query = "SELECT id FROM media_folders WHERE parent_id=$id";
        if ($id == 1)                         $query .= " ORDER BY foldername ASC;";
        else if ($reverse_subalbum_sort != 0) $query .= " ORDER BY foldername DESC;";
        else                                  $query .= " ORDER BY foldername ASC;";
        $results = $this->query($query);
        if ($results === false) throw new Exception($this->lastErrorMsg());
        while($row = $results->fetchArray()) {
            $sub_array[] = $row['id'];
        }
        $results->finalize();
        return $sub_array;
    }

    function getFolderElements($id)
    {
        $element_list = array();
        // Returns array of id of folder elements
        $results = $this->query("SELECT id FROM media_objects WHERE folder_id=$id ORDER BY filename ASC;");
        if ($results === false) throw new Exception($this->lastErrorMsg());
        while($row = $results->fetchArray()) {
            $element_list[] = $row['id'];
        }
        $results->finalize();
        return $element_list;
    }

    function getFolderElementsCount($id, $recurse=false)
    {
        // Returns number of elements inside this folder (recursively if wanted)
        $results = $this->querySingle("SELECT COUNT(*) FROM media_objects WHERE folder_id=$id;");
        if ($results === false) throw new Exception($this->lastErrorMsg());
        if ($recurse == true) {
            $subfolders = $this->getSubFolders($id);
            foreach($subfolders as $subfolder) {
                $results += $this->getFolderElementsCount($subfolder, true);
            }
        }
        return $results;
    }

}

?>
