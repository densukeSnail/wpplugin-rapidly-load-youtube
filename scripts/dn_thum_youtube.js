jQuery(function($){
    $(".dn_thum_youtube_video_hide").each(function(){
        var $iframe = $(this).children("iframe");
        var w = $iframe.attr("width") ? $iframe.attr("width") : 300;
        var h = $iframe.attr("height") ? $iframe.attr("height") : 150;
        $(this).children(".dn_thum_youtube_thum_img").attr("width", w).attr("height", h);
    });

    $(".dn_thum_youtube_video_hide").click(function(){
        // orgsrcを、元のsrc属性に戻す
        $(this).children("iframe").attr("src", $(this).children("iframe").attr("data-orgsrc")).removeAttr("data-orgsrc");

        //autoplayをつけて、読み込みと同時に再生する
        var srcurl = $(this).children("iframe").attr("src");
        $(this).children("iframe").attr("src" , srcurl + ( srcurl.indexOf("?") > 0 ? "&autoplay=1" : "?autoplay=1" ));

        // 動画のdisplay:noneを解除する
        $(this).removeClass("dn_thum_youtube_video_hide").addClass("dn_thum_youtube_video_show");
    });
});
