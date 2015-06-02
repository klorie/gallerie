# gallerie
Yet another simple file-based photo gallery

Requires at least php, mysql, imagemagick

Setup:<br />
1. Copy config.php.dist into config.php<br />
2. Edit config.php and add required values<br />
3. Create required database with associated user<br />
4. Copy/link images files and folder in the folder associated to $image_folder in config.php - Note that nothing will be written in this folder. Only read access is required.<br />
5. Gallery hierarchy will use images named folder.jpg as album thumbnail (with their caption as title)<br />
6. run updater.php as www-data/wwwrun user (apache/lighttpd/ngxin/etc. user)<br />
