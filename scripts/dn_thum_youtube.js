jQuery(function($){
    $(".disablethum").find(".dn_thum_youtube_video_hide").each(function(){
        // orgsrcを、元のsrc属性に戻す
        $(this).children("span").remove();
        const $frame = $(this).children("iframe");
        $frame.attr("src", $frame.attr("data-orgsrc")).removeAttr("data-orgsrc");
        $frame.unwrap();
    });

    $(".dn_thum_youtube_video_hide").each(function(){
        const $iframe = $(this).children("iframe");
        const w = $iframe.attr("width") ? $iframe.attr("width") : 300;
        const h = $iframe.attr("height") ? $iframe.attr("height") : 150;
        $(this).css({"width" : w + "px" , "height" : h + "px"});
    });

    $(".dn_thum_youtube_video_title a").click(function(e){
        e.stopPropagation();
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
