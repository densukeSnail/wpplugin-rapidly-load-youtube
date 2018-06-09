<?php
/*
Plugin Name: Rapidly load YouTube（YouTube高速ローダー）
Plugin URI: https://engineering.dn-voice.info/wpplugin-dev/rapidly-load-youtube/
Description: Load YouTube embedded videos rapidly by setting the initial display as thumbnail image.
Version: 1.0.0
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
        private $lang;

        private static $option_grp = 'dn_thum_youtube_option_grp';
        private static $option_param = [
            'movie_num' => 'dn_thum_youtube_option_movie_num',
            'show_author' => 'dn_thum_youtube_option_show_author',
            'qtag' => 'dn_thum_youtube_option_qtag',
        ];

        function __construct() {
            add_action( 'plugins_loaded', array( $this , 'plugin_init' ) );
            add_action( 'admin_menu', array( $this , 'add_menu' ) );
            add_action( 'admin_init', array( $this , 'setting_init' ) );
            add_filter( 'the_content', array($this , 'thumbnails_on_youtube_filter') ) ;

            if( ! get_option( self::$option_param['qtag'] ) ){
                add_action('admin_print_footer_scripts', array( $this , 'add_qtag_dontshow_thumnail' ) );
            }

            if(function_exists('register_uninstall_hook')) {
                register_uninstall_hook (__FILE__, array(get_class($this),'do_uninstall') );
            }
        }

        function plugin_init(){
            $this->load_lang_strings();
        }

        function add_menu(){
            $page = add_management_page( $this->lang['plugin_name'], $this->lang['plugin_name'], 'manage_options', 'dn_thum_youtube_menu', array($this,'show_menu') );

            /* 設定画面表示時のCSSファイル読み込みをフック */
            add_action( 'admin_print_styles-' . $page, array( $this , 'enque_setting_style' ) );
        }

        function enque_setting_style(){
            wp_enqueue_style( 'dn_thum_youtube_setting_style', plugins_url('styles/dn_thum_youtube_setting.css', __FILE__));
        }

        function setting_init(){
            foreach( self::$option_param as $key => $val ){
                register_setting( self::$option_grp, $val );
            }
        }

        private function load_lang_strings(){
            load_plugin_textdomain( 'dn_thum_youtube_lang', false, basename( dirname( __FILE__ ) ) . '/languages' );

            $this->lang['plugin_name'] = __('Rapidly load YouTube embedding', 'dn_thum_youtube_lang');
            $this->lang['setting_page'] = __('Setting Page', 'dn_thum_youtube_lang');
            $this->lang['disable'] = __("Disable YouTube Thumnailing", 'dn_thum_youtube_lang');
            $this->lang['movie_in_post'] = __("In your post, if number of YouTube Movies within ", 'dn_thum_youtube_lang');
            $this->lang['not_show_thum'] = __(" , this plugin does not work at the post.", 'dn_thum_youtube_lang');
            $this->lang['dont_show_name'] = __("Don't show author name on thumnail", 'dn_thum_youtube_lang');
            $this->lang['dont_show_qtag'] = __("Don't show QuickTag 'Disable YouTube Thumnailing' at Edit Post view", 'dn_thum_youtube_lang');
        }

        /*****
        記事内のYouTube動画を検索して、サムネイル画像を表示させる関数
        *****/
        function thumbnails_on_youtube_filter( $content ){
            // 埋め込みYouTubeの検索
            preg_match_all('/<iframe.*?src="https:\/\/www\.youtube.*?\.com\/embed\/.*?<\/iframe>/', $content , $frames);

            // 記事内の動画数が少ない場合は、サムネイル化せずに処理終了
            if( ($cnt = count($frames[0])) > get_option(self::$option_param['movie_num'] , 0) ){
                // スタイル、JSは必要な時だけ読みこむ
                wp_enqueue_style( 'dn_thum_youtube_style', plugins_url('styles/dn_thum_youtube.css', __FILE__));
                wp_enqueue_script( 'dn_thum_youtube_script', plugins_url('scripts/dn_thum_youtube.js', __FILE__), array('jquery') );

                // 全く同一のiframeタグは重複処理を避ける
                $uniqueframes = array_unique($frames[0]);

                for($i = 0 ; $i < $cnt ; $i++){
                    if( ! array_key_exists($i,$uniqueframes) ) continue;

                    //YouTubeのoEmbedから、動画情報をjsonで取得
                    $oembedurl = 'https://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=';
                    $pregstr = '/embed\/(.*?)[\?&"]/';
                    $videostr = 'movie';
                    if( false !== strpos($uniqueframes[$i] , 'videoseries') ){
                        $oembedurl = 'https://www.youtube.com/oembed?url=http://www.youtube.com/playlist?list=';
                        $pregstr = '/list=(.*?)[\?&"]/';
                        $videostr = 'playlist';
                    }
                    preg_match($pregstr, $uniqueframes[$i], $res);

                    $json = json_decode(file_get_contents($oembedurl . $res[1]),true);

                    // サムネイル画像表示。
                    // iframeのsrc属性をdata-orgsrc属性に変えて、初期表示時に動画をロードしないようにする。
                    // 作成者名を表示するかどうかは設定値に依存。
                    $titlestr = $json['title'];
                    if(get_option(self::$option_param['show_author']) != 1){
                        $titlestr = $videostr . ' via <a href="'. $json['author_url'] . '" target="_blank">' . $json['author_name'] . '</a><br />' . $titlestr;
                    }
                    $after = '<div class="dn_thum_youtube_video_hide" style="background-image:url(' . $json['thumbnail_url'] . ');">' .
                                 '<span class="dn_thum_youtube_video_title">' . $titlestr . '</span>' .
                                 '<span class="dn_thum_youtube_video_clickplay"></span>' .
                                 preg_replace('/src\=/', 'data-orgsrc=' , $uniqueframes[$i]) .
                             '</div>';
                    $content = preg_replace('/' . preg_quote($uniqueframes[$i],'/') . '/' , $after, $content);
                }
            }
            return $content;
        }

        /*****
        設定画面のHTMLを作成、表示する関数
        *****/
        function show_menu(){
            ?>
                <h2><?php echo $this->lang['plugin_name'] . " " . $this->lang['setting_page']; ?></h2>
                <form method="post" action="options.php">
                    <?php settings_fields( self::$option_grp ); ?>
                    <?php do_settings_sections( self::$option_grp ); ?>
                    <hr>
                    <ul>
                        <li>
                            <label><?php echo $this->lang['movie_in_post']; ?><input type="number" step="1" min="0" name="<?php echo self::$option_param['movie_num']; ?>" required
                                value="<?php echo get_option( self::$option_param['movie_num'] , "0" ); ?>"><?php echo $this->lang['not_show_thum']; ?></label>
                        </li>
                        <li>
                            <label for="<?php echo self::$option_param['show_author']; ?>_id"><?php echo $this->lang['dont_show_name']; ?> : </label>
                            <input id="<?php echo self::$option_param['show_author']; ?>_id" type="checkbox" name="<?php echo self::$option_param['show_author']; ?>"
                            value="1" <?php if(get_option(self::$option_param['show_author'])) echo 'checked'; ?>>
                        </li>
                        <li>
                            <label for="<?php echo self::$option_param['qtag']; ?>_id"><?php echo $this->lang['dont_show_qtag']; ?> : </label>
                            <input id="<?php echo self::$option_param['qtag']; ?>_id" type="checkbox" name="<?php echo self::$option_param['qtag']; ?>"
                            value="1" <?php if(get_option(self::$option_param['qtag'])) echo 'checked'; ?>>
                        </li>
                    </ul>
                    <?php submit_button(); ?>
                </form>
            <?php
        }

        /*****
        クイックタグ追加関数
        *****/
        function add_qtag_dontshow_thumnail(){
            if (wp_script_is('quicktags')){
                ?>
                <script type="text/javascript">
                /** 書式 : QTags.addButton('ID', 'ボタンのラベル', '開始タグ', '終了タグ', 'アクセスキー', 'タイトル', プライオリティ); **/
                QTags.addButton('disable_thumnail_button' , "<?php echo $this->lang['disable']; ?>" , "<div class=\"disablethum\">\n" , "\n</div>");
                </script>
                <?php
            }
        }

        /*****
        アンインストール時にoption値をDBから削除する関数
        *****/
        static function do_uninstall(){
            foreach( self::$option_param as $key => $val ){
                unregister_setting( self::$option_grp, $val );
                delete_option($val);
            }
        }
    }
    $dn_thum_youtube_class_instance = new dn_thum_youtube_class();
}

?>
