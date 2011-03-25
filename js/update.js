$(document).ready(function(){
    $('#update_db').click(function(){
        $('#update_db_progress').progressbar({value: 0});
        $.ajax({
            cache: false,
            type: "POST",
            data: "task=update_db",
            url: "update_process.php",
            beforeSend: start_display("display_update('#update_db_status', '#update_db_progress')")
        });
    });
    $('#update_theme').click(function(){
        $('#update_theme_progress').progressbar({value: 0});
        $.ajax({
            cache: false,
            type: "POST",
            data: "task=update_theme",
            url: "update_process.php",
            beforeSend: start_display("display_update('#update_theme_status', '#update_theme_progress')")
        });
    });
    $('#update_thumb').click(function(){
        $('#update_thumb_progress').progressbar({value: 0});
        $.ajax({
            cache: false,
            type: "POST",
            data: "task=update_thumb",
            url: "update_process.php",
            beforeSend: start_display("display_update('#update_thumb_status', '#update_thumb_progress')")
        });
    });
    $('#clean_thumb').click(function(){
        $('#clean_thumb_progress').progressbar({value: 0});
        $.ajax({
            cache: false,
            type: "POST",
            data: "task=clean_thumb",
            url: "update_process.php",
            beforeSend: start_display("display_update('#clean_thumb_status', '#clean_thumb_progress')")
        });
    });
    $('#clear_thumb').click(function(){
        $('#clear_thumb_progress').progressbar({value: 0});
        $.ajax({
            cache: false,
            type: "POST",
            data: "task=clear_thumb",
            url: "update_process.php",
            beforeSend: start_display("display_update('#clear_thumb_status', '#clear_thumb_progress')")
        });
    });
    $('#clear_cache').click(function(){
        $('#clear_cache_progress').progressbar({value: 0});
        $.ajax({
            cache: false,
            type: "POST",
            data: "task=clear_cache",
            url: "update_process.php",
            beforeSend: start_display("display_update('#clear_cache_status', '#clear_cache_progress')")
        });
    });
});

var interval='';

function start_display(display_function) {
    if (interval=="") {
        interval=window.setInterval(display_function, 200);
    } else {
        stop_display();
    }
}

function stop_display() {
    if (interval!="") {
        window.clearInterval(interval);
        interval="";
    }
}

function display_update(status_id, progress_id) {
    $.ajax({
        cache: false,
        type: 'get',
        url: 'update_status.php',
        data: 'status',
        success: function(response) {
            $(status_id).empty();
            $(status_id).append(response);
        }
    });
    $.ajax({
        cache: false,
        type: 'get',
        url: 'update_status.php',
        success: function(response) {
            $(progress_id).progressbar("option", "value", parseInt(response));
            if (response==100) {
                stop_display();
            }
        }
    });
}

