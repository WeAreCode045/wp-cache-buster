<?php
/*
Plugin Name: WP Cache Buster & Asset Scanner
Description: Scan assets, flush caches, view last-edited pages, full site asset overview with Tailwind + DataTables
Version: 1.3
Author: Your Name
*/

if(!defined('ABSPATH')) exit;

class WPCB_Plugin {

    public function __construct(){
        add_action('admin_menu', [$this,'admin_menu']);
        add_action('admin_enqueue_scripts', [$this,'load_admin_assets']);
        add_action('wp_ajax_wpcb_scan_all_pages', [$this,'ajax_scan_all_pages']);
        add_action('wp_ajax_wpcb_flush_cache', [$this,'ajax_flush_cache']);
        add_action('wp_ajax_wpcb_flush_object_cache', [$this,'ajax_flush_object_cache']);
        add_action('wp_ajax_wpcb_get_assets', [$this,'ajax_get_assets']);
        add_action('manage_pages_columns', [$this,'add_last_edited_column']);
        add_action('manage_pages_custom_column', [$this,'render_last_edited_column'],10,2);
    }

    // Admin menu
    public function admin_menu(){
        add_menu_page(
            'WP Cache Buster',
            'WP Cache Buster',
            'manage_options',
            'wpcb_plugin',
            [$this,'page_assets'],
            'dashicons-admin-tools',
            60
        );
    }

    // Admin assets enqueue
    public function load_admin_assets($hook){
        if(strpos($hook,'wpcb_plugin')===false) return;

        wp_enqueue_script('jquery');

        // DataTables scripts & styles
        wp_enqueue_script('datatables-js','https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',['jquery'],null,true);
        wp_enqueue_script('datatables-buttons-js','https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js',['jquery','datatables-js'],null,true);
        wp_enqueue_script('datatables-html5-js','https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js',['jquery','datatables-buttons-js'],null,true);
        wp_enqueue_script('datatables-print-js','https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js',['jquery','datatables-buttons-js'],null,true);

        wp_enqueue_style('datatables-css','https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
        wp_enqueue_style('datatables-buttons-css','https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css');

        // Tailwind via Play CDN
        echo '<script src="https://cdn.tailwindcss.com"></script>';

        // Admin CSS & JS met cache-busting
        wp_enqueue_style(
            'wpcb-admin-style',
            plugin_dir_url(__FILE__).'assets/css/admin-tailwind.build.css',
            [],
            $this->cache_bust(plugin_dir_path(__FILE__).'assets/css/admin-tailwind.build.css')
        );

        wp_enqueue_script(
            'wpcb-admin-js',
            plugin_dir_url(__FILE__).'assets/js/admin.js',
            ['jquery'],
            $this->cache_bust(plugin_dir_path(__FILE__).'assets/js/admin.js'),
            true
        );

        // Localize script
        wp_localize_script('wpcb-admin-js','WPCB',[
            'ajax'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('wpcb_nonce'),
            'urls'=>[
                'flush_cache'=>admin_url('plugin-install.php?tab=gd-recommended&wpaas_action=flush_cache&wpaas_nonce='.wp_create_nonce('wpcb_gdflush')),
                'flush_object'=>admin_url('options-general.php?page=objectcache&action=flush-cache&_wpnonce='.wp_create_nonce('wpcb_objflush'))
            ]
        ]);
    }

    // Cache-buster helper
    private function cache_bust($file){
        if(file_exists($file)){
            return filemtime($file);
        }
        return time();
    }

    // Admin page
    public function page_assets(){
        include plugin_dir_path(__FILE__).'template-assets-page.php';
    }

    // AJAX flush GoDaddy cache
    public function ajax_flush_cache(){
        check_ajax_referer('wpcb_nonce','nonce');
        wp_remote_get(admin_url('plugin-install.php?tab=gd-recommended&wpaas_action=flush_cache&wpaas_nonce='.wp_create_nonce('wpcb_gdflush')));
        wp_send_json_success(['message'=>'GoDaddy cache flushed']);
    }

    // AJAX flush Object Cache
    public function ajax_flush_object_cache(){
        check_ajax_referer('wpcb_nonce','nonce');
        
        $result = false;
        $message = 'Object cache flush failed';
        
        // Probeer WordPress native flush
        if(function_exists('wp_cache_flush')){
            $result = wp_cache_flush();
            $message = $result ? 'Object cache flushed successfully' : 'Object cache flush failed';
        }
        
        // Als er Redis Object Cache plugin is
        if(function_exists('wp_redis_flush_cache')){
            wp_redis_flush_cache();
            $result = true;
            $message = 'Redis object cache flushed successfully';
        }
        
        // Als er LiteSpeed Cache is
        if(class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')){
            \LiteSpeed_Cache_API::purge_all();
            $result = true;
            $message = 'LiteSpeed cache purged successfully';
        }
        
        // Als er W3 Total Cache is
        if(function_exists('w3tc_flush_all')){
            w3tc_flush_all();
            $result = true;
            $message = 'W3 Total Cache flushed successfully';
        }
        
        // Als er WP Super Cache is
        if(function_exists('wp_cache_clean_cache')){
            global $file_prefix;
            wp_cache_clean_cache($file_prefix, true);
            $result = true;
            $message = 'WP Super Cache cleaned successfully';
        }
        
        if($result){
            wp_send_json_success(['message' => $message]);
        } else {
            wp_send_json_error(['message' => $message]);
        }
    }

    // Scan all pages
    public function ajax_scan_all_pages(){
        check_ajax_referer('wpcb_nonce','nonce');
        $urls = $this->get_all_site_pages();
        $all_assets = [];

        foreach($urls as $url){
            $all_assets[$url] = $this->scan_page_assets($url);
        }

        update_option('wpcb_page_assets',$all_assets);

        wp_send_json_success(['message'=>'Scan completed','count'=>count($urls)]);
    }

    private function get_all_site_pages(){
        $pages = get_posts([
            'post_type'=>['page','post'],
            'posts_per_page'=>-1,
            'post_status'=>'publish',
            'fields'=>'ids'
        ]);
        $urls=[];
        foreach($pages as $id) $urls[]=get_permalink($id);
        $urls[]=home_url('/');
        return array_unique($urls);
    }

    private function scan_page_assets($url){
        $response = wp_remote_get($url);
        if(is_wp_error($response)) return ['styles'=>[],'scripts'=>[]];

        $html = wp_remote_retrieve_body($response);
        $assets=['styles'=>[],'scripts'=>[]];

        if(preg_match_all('/<script[^>]+src=["\']([^"\']+)["\']/i',$html,$matches)){
            foreach($matches[1] as $src){
                $assets['scripts'][]=[
                    'url'=>$src,
                    'location'=>'footer/header unknown',
                    'group'=>'page scan',
                    'modified'=>date('Y-m-d H:i:s')
                ];
            }
        }

        if(preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\']/i',$html,$matches)){
            foreach($matches[1] as $href){
                $assets['styles'][]=[
                    'url'=>$href,
                    'location'=>'head',
                    'group'=>'page scan',
                    'modified'=>date('Y-m-d H:i:s')
                ];
            }
        }
        return $assets;
    }

    // AJAX return assets for DataTable live refresh
    public function ajax_get_assets(){
        check_ajax_referer('wpcb_nonce','nonce');
        $assets_option = get_option('wpcb_page_assets',[]);
        
        // Groepeer assets per URL en type
        $grouped_assets = [];
        foreach($assets_option as $page_url => $assets){
            foreach(['scripts','styles'] as $type){
                if(isset($assets[$type])){
                    foreach($assets[$type] as $a){
                        $key = $a['url'] . '|' . $type;
                        if(!isset($grouped_assets[$key])){
                            $grouped_assets[$key] = [
                                'url' => $a['url'],
                                'type' => $type,
                                'location' => $a['location'],
                                'group' => $a['group'],
                                'modified' => $a['modified'],
                                'pages' => []
                            ];
                        }
                        $grouped_assets[$key]['pages'][] = $page_url;
                    }
                }
            }
        }
        
        wp_send_json_success($grouped_assets);
    }

    // Last edited column
    public function add_last_edited_column($columns){
        $columns['wpcb_last_edited']='Last Edited';
        return $columns;
    }

    public function render_last_edited_column($column,$post_id){
        if($column=='wpcb_last_edited'){
            echo get_post_modified_time('Y-m-d H:i:s',$post_id);
        }
    }
}

new WPCB_Plugin();
