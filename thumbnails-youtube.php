<?php
/*
Plugin Name: Show Thumbnails on YouTube Videos
Plugin URI:
Description: on embed YouTube videos, not load video but show thumbnails. It may can reduce load time.
Version: 1.0
Author: densuke
Author URI: https://engineering.dn-voice.info/
License: GPL2

Copyright 2018 densuke (email : hoge@hoge.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if( !class_exists('dn_thum_youtube_class') ){
    class dn_thum_youtube_class{
        function __construct() {
            add_filter( 'the_content', array($this , 'thumbnails_on_youtube_filter') ) ;
        }

        function thumbnails_on_youtube_filter( $content ){
            // 埋め込みYouTubeの検索
            preg_match_all('/<iframe.*?src="https:\/\/www\.youtube.*?\.com\/embed\/(.*?)["\?].*?<\/iframe>/', $content , $frames);

            // 埋め込みYouTubeのiframeがあれば変換する
            if(($cnt = count($frames[0])) > 0){
                // スタイル、JSは必要な時だけ読みこむ
                wp_enqueue_style( 'dn_thum_youtube_style', plugins_url('styles/dn_thum_youtube.css', __FILE__));
                wp_enqueue_script( 'dn_thum_youtube_script', plugins_url('scripts/dn_thum_youtube.js', __FILE__), array('jquery') );

                // 全く同一のiframeタグは重複処理を避ける
                $uniqueframes = array_unique($frames[0]);

                for($i = 0 ; $i < $cnt ; $i++){
                    if( ! array_key_exists($i,$uniqueframes) ) continue;

                    // サムネイル画像表示。初期表示時には動画をロードしないようにするため、iframeのsrc属性を削除する。
                    $after = '<div class="dn_thum_youtube_video_hide">' . preg_replace('/src\=/', 'data-orgsrc=' , $uniqueframes[$i]) . '<img class="dn_thum_youtube_thum_img" src="https://img.youtube.com/vi/' . $frames[1][$i] . '/hqdefault.jpg" alt="" /></div>';
                    $content = preg_replace('/' . preg_quote($uniqueframes[$i],'/') . '/' , $after, $content);
                }
            }

            return $content;
        }
    }
    $dn_thum_youtube_class_instance = new dn_thum_youtube_class();
}

?>
