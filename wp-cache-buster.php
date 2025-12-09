<?php
/**
 * Plugin Name: WP Cache Buster & Asset Overview
 * Description: Full-site asset scan, Tailwind UI, auto page scan, unused asset detection, cache flush.
 * Version: 2.0.2
 * Author: Maurice
 */

if (!defined('ABSPATH')) exit;

class WPCB_Plugin {

    function __construct() {
        // Admin menu
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'load_admin_assets']);

        // Frontend scripts
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('wp_footer', [$this, 'inject_frontend_modal']);
        add_action('admin_bar_menu', [$this, 'admin_bar_button'], 100);

        // Last edited column
        add_filter('manage_pages_columns', [$this, 'add_last_edit_column']);
        add_action('manage_pages_custom_column', [$this, 'render_last_edit_column'], 10, 2);

        // AJAX
        add_action('wp_ajax_wpcb_flush_gd_cache', [$this, 'flush_godaddy_cache']);
        add_action('wp_ajax_wpcb_flush_object_cache', [$this, 'flush_object_cache']);
        add_action('wp_ajax_wpcb_scan_url', [$this, 'ajax_scan_url']);
        add_action('wp_ajax_wpcb_save_page_scan', [$this, 'save_page_scan']);
        add_action('wp_ajax_wpcb_full_site_scan', [$this, 'ajax_full_site_scan']);

        // Full-page capture
        add_action('init', [$this, 'maybe_capture_output']);

        // Auto site scan
        add_action('admin_init', [$this, 'auto_scan_site']);
    }

    /*------------------------------
        Admin page
    ------------------------------*/
    function register_admin_page() {
        add_menu_page('Asset Overview', 'Asset Overview', 'manage_options', 'wpcb-assets', [$this, 'page_assets'], 'dashicons-media-code', 59);
    }

    function page_assets() {
        // Include template file
        include plugin_dir_path(__FILE__) . 'template-assets-page.php';
    }

    function load_admin_assets() {
        wp_enqueue_style('wpcb-admin', plugin_dir_url(__FILE__).'assets/css/admin.css', [], false);
        wp_enqueue_script('wpcb-admin', plugin_dir_url(__FILE__).'assets/js/admin.js', ['jquery'], false, true);
        wp_localize_script('wpcb-admin', 'WPCB', ['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('wpcb_nonce')]);
    }

    /*------------------------------
        Frontend
    ------------------------------*/
    function frontend_assets() {
        if(is_admin_bar_showing()){
            wp_enqueue_script('wpcb-frontend-scan', plugin_dir_url(__FILE__).'assets/js/frontend-scan.js',['jquery'],false,true);
            wp_localize_script('wpcb-frontend-scan', 'WPCB_SCAN', ['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('wpcb_nonce')]);
        }
    }

    function admin_bar_button($admin_bar){
        if(!is_admin_bar_showing()) return;
        $admin_bar->add_node([
            'id'=>'wpcb-scan',
            'title'=>'Scan Assets',
            'href'=>'#',
            'meta'=>['class'=>'wpcb-scan-button']
        ]);
    }

    function inject_frontend_modal(){
        if(!is_admin_bar_showing()) return;
        echo '<div id="wpcb-scan-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-6 rounded shadow-lg w-4/5 max-w-3xl">
                <h2 class="text-xl font-bold mb-4">Asset Scan Results</h2>
                <pre id="wpcb-scan-output" class="bg-gray-100 p-4 rounded overflow-auto h-96"></pre>
                <button id="wpcb-close-modal" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded">Close</button>
            </div>
        </div>';
    }

    /*------------------------------
        Pages last edited column
    ------------------------------*/
    function add_last_edit_column($cols){
        $cols['last_edit'] = 'Last Edited';
        return $cols;
    }

    function render_last_edit_column($col,$post_id){
        if($col==='last_edit'){
            echo get_post_modified_time('Y-m-d H:i',true,$post_id);
        }
    }

    /*------------------------------
        File system asset scan
    ------------------------------*/
    function scan_filesystem_assets(){
        $dirs=[get_template_directory(),WP_PLUGIN_DIR];
        $items=[];
        foreach($dirs as $base){
            $rii=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
            foreach($rii as $file){
                if($file->isDir()) continue;
                $path=$file->getPathname();
                if(preg_match('/\.(css|js)$/',$path)){
                    $items[]=[
                        'path'=>$path,
                        'url'=>$this->path_to_url($path),
                        'modified'=>date('Y-m-d H:i',filemtime($path)),
                        'type'=>substr($path, -3) === '.css' ? 'CSS' : 'JS', // PHP 7.4 compatible
                        'group'=>strpos($path,WP_PLUGIN_DIR)===0?'Plugin':'Theme',
                    ];
                }
            }
        }
        return $items;
    }

    function path_to_url($path){
        $path=wp_normalize_path($path);
        $content=wp_normalize_path(WP_CONTENT_DIR);
        if(strpos($path,$content)!==false){
            return content_url(str_replace($content,'',$path));
        }
        return '';
    }

    /*------------------------------
        Full page capture
    ------------------------------*/
    function maybe_capture_output(){
        if(isset($_GET['wpcb_capture'])) ob_start([$this,'parse_output']);
    }

    function parse_output($html){
        $results=['styles'=>[],'scripts'=>[]];
        if(preg_match_all('/<link[^>]+rel=[\'"]stylesheet[\'"][^>]*>/i',$html,$m)){
            foreach($m[0] as $tag){
                $src=$this->extract_attr($tag,'href');
                $loc=$this->find_location($html,$tag);
                $results['styles'][]=['url'=>$src,'location'=>$loc];
            }
        }
        if(preg_match_all('/<script[^>]*src=[\'"]([^\'"]+)[\'"][^>]*><\/script>/i',$html,$m)){
            foreach($m[0] as $tag){
                $src=$this->extract_attr($tag,'src');
                $loc=$this->find_location($html,$tag);
                $results['scripts'][]=['url'=>$src,'location'=>$loc];
            }
        }
        wp_send_json($results);
        return '';
    }

    function extract_attr($tag,$attr){
        if(preg_match('/'.$attr.'=[\'"]([^\'"]+)[\'"]/',$tag,$m)) return $m[1];
        return '';
    }

    function find_location($html,$tag){
        $pos=strpos($html,$tag);
        $head_end=strpos($html,'</head>');
        return $pos<$head_end?'head':'body/footer';
    }

    /*------------------------------
        AJAX cache flush
    ------------------------------*/
    function flush_godaddy_cache(){
        check_ajax_referer('wpcb_nonce');
        wp_remote_get(admin_url('/plugin-install.php?tab=gd-recommended&wpaas_action=flush_cache&wpaas_nonce=adbeee6fe2'));
        wp_send_json_success('GoDaddy Cache flushed!');
    }

    function flush_object_cache(){
        check_ajax_referer('wpcb_nonce');
        wp_remote_get(admin_url('/options-general.php?page=objectcache&action=flush-cache&_wpnonce=61657c6cb4'));
        wp_send_json_success('Object Cache flushed!');
    }

    /*------------------------------
        AJAX page scan
    ------------------------------*/
    function ajax_scan_url(){
        check_ajax_referer('wpcb_nonce');
        $url=esc_url_raw($_POST['url'].'?wpcb_capture=1');
        $res=wp_remote_get($url);
        wp_send_json(json_decode(wp_remote_retrieve_body($res),true));
    }

    function save_page_scan(){
        check_ajax_referer('wpcb_nonce');
        $url=esc_url_raw($_POST['url']);
        $data=$_POST['data'];
        $existing=get_transient('wpcb_page_assets')?:[];
        $existing[$url]=$data;
        set_transient('wpcb_page_assets',$existing,DAY_IN_SECONDS*30);
        wp_send_json_success('Saved');
    }

    /*------------------------------
        Automatic full site scan
    ------------------------------*/
    function auto_scan_site(){
        if(!current_user_can('manage_options')) return;
        $last_scan=get_option('wpcb_last_site_scan');
        if($last_scan && (time()-$last_scan)<3600) return;
        $pages=get_posts(['post_type'=>'page','numberposts'=>-1,'post_status'=>'publish']);
        foreach($pages as $p){
            $url=get_permalink($p);
            $res=wp_remote_get(add_query_arg('wpcb_capture','1',$url),['timeout'=>15]);
            if(!is_wp_error($res)){
                $body=wp_remote_retrieve_body($res);
                $json=json_decode($body,true);
                if($json){
                    $existing=get_transient('wpcb_page_assets')?:[];
                    $existing[$url]=$json;
                    set_transient('wpcb_page_assets',$existing,DAY_IN_SECONDS*30);
                }
            }
        }
        update_option('wpcb_last_site_scan',time());
    }
}

new WPCB_Plugin();
