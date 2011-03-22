$(function() {
        var d=300;
        $('#toplevel_navigation a').each(function(){
            $(this).stop().animate({
                'marginTop':'-80px', 'opacity':'0.7', 'filter:progid':'DXImageTransform.Microsoft.Alpha(opacity=70)'
                },d+=150);
            });

        $('#toplevel_navigation > li').hover(
            function () {
            $('a',$(this)).stop().animate({
                'marginTop':'0px', 'opacity':'0.95', 'filter:progid':'DXImageTransform.Microsoft.Alpha(opacity=95)'
                },200);
            },
            function () {
            $('a',$(this)).stop().animate({
                'marginTop':'-80px', 'opacity':'0.7', 'filter:progid':'DXImageTransform.Microsoft.Alpha(opacity=70)'
                },200);
            });
        });
$(function() {
        $('#side_navigation > li').hover(
            function () {
            $(this).stop().animate({'marginLeft':'0px', 'opacity':'0.95', 'filter:progid':'DXImageTransform.Microsoft.Alpha(opacity=95)'},200);
            },
            function () {
            $(this).stop().animate({'marginLeft':'-180px', 'opacity':'0.7', 'filter:progid':'DXImageTransform.Microsoft.Alpha(opacity=70)'},200);
            }
            );
        });

