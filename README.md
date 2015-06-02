# gallerie
Yet another simple file-based photo gallery

Requires at least php, mysql, imagemagick

Setup:
1. Copy config.php.dist into config.php
2. Edit config.php and add required values
3. Create required database with associated user
4. Copy/link images files and folder in the folder associated to $image_folder in config.php - Note that nothing will be written in this folder. Only read access is required.
4bis. Gallery hierarchy will use images named folder.jpg as album thumbnail (with their caption as title) 
5. run updater.php as www-data/wwwrun user (apache/lighttpd/ngxin/etc. user)
