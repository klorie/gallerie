$(function() {
        var d=300;
        $('#toplevel_navigation a').each(function(){
            $(this).stop().animate({
                'marginTop':'-80px'
                },d+=150);
            });

        $('#toplevel_navigation > li').hover(
            function () {
            $('a',$(this)).stop().animate({
                'marginTop':'0px'
                },200);
            },
            function () {
            $('a',$(this)).stop().animate({
                'marginTop':'-80px'
                },200);
            });
        });
$(function() {
        $('#side_navigation > li').hover(
            function () {
            $(this).stop().animate({'marginLeft':'-2px', 'opacity':'0.9', 'filter:progid':'DXImageTransform.Microsoft.Alpha(opacity=90)'},200);
            },
            function () {
            $(this).stop().animate({'marginLeft':'-178px', 'opacity':'0.7', 'filter:progid':'DXImageTransform.Microsoft.Alpha(opacity=70)'},200);
            }
            );
        });

