<?php
/**
 * Plugin Name: WP Cache Buster
 * Description: Scan assets, flush GoDaddy cache, last edited column.
 * Version: 1.0
 * Author: Maurice
 */

if(!defined('ABSPATH')) exit;

class WPCB_Plugin {
    public function __construct(){
        add_action('admin_menu', [$this,'add_admin_page']);
        add_action('admin_enqueue_scripts', [$this,'load_admin_assets']);
        add_action('wp_enqueue_scripts', [$this,'load_frontend_assets']);

        // AJAX handlers
        add_action('wp_ajax_wpcb_full_site_scan', [$this,'full_site_scan']);
        add_action('wp_ajax_wpcb_scan_url', [$this,'scan_single_url']);
        add_action('wp_ajax_wpcb_object_flush', [$this,'object_cache_flush']);

        // Last edited column
        add_filter('manage_pages_columns', [$this,'add_last_edited_column']);
        add_action('manage_pages_custom_column', [$this,'render_last_edited_column'], 10, 2);
    }

    // ---------------- Admin Page ----------------
    public function add_admin_page(){
        add_menu_page('Asset Overview','Asset Overview','manage_options','wpcb_plugin',[$this,'page_assets'],'dashicons-admin-site',100);
    }

    public function page_assets(){
        ?>
        <div class="wrap">
            <h1 class="text-2xl font-bold mb-4">Asset Overview</h1>
            <button id="wpcb-flush-gd" class="button button-primary">Flush GoDaddy Cache</button>
            <button id="wpcb-flush-object" class="button button-secondary">Flush Object Cache</button>
            <button id="wpcb-full-scan" class="button button-secondary">Scan Entire Site</button>
            <table id="wpcb-assets-table" class="display min-w-full mt-4">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>URL</th>
                        <th>Location</th>
                        <th>Group</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $assets = get_transient('wpcb_page_assets') ?: [];
                foreach(['styles','scripts'] as $type){
                    if(isset($assets[$type])){
                        foreach($assets[$type] as $a){
                            echo "<tr>
                                <td>$type</td>
                                <td>{$a['url']}</td>
                                <td>{$a['location']}</td>
                                <td>{$a['group']}</td>
                                <td>{$a['modified']}</td>
                            </tr>";
                        }
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ---------------- Admin Assets ----------------
    public function load_admin_assets($hook){
        if(strpos($hook,'wpcb_plugin')===false) return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('datatables-js','https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',['jquery'],null,true);
        wp_enqueue_script('datatables-buttons-js','https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js',['jquery','datatables-js'],null,true);
        wp_enqueue_script('datatables-html5-js','https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js',['jquery','datatables-buttons-js'],null,true);
        wp_enqueue_script('datatables-print-js','https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js',['jquery','datatables-buttons-js'],null,true);

        wp_enqueue_style('datatables-css','https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
        wp_enqueue_style('datatables-buttons-css','https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css');

wp_enqueue_script(
    'wpcb-admin-js',
    plugin_dir_url(__FILE__) . 'assets/js/admin.js', // juiste locatie
    ['jquery'],
    null,
    true
);
        wp_localize_script('wpcb-admin-js','WPCB',[
            'ajax'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('wpcb_nonce'),
            'fallback'=>[
                'wpcb_full_site_scan'=>admin_url('plugin-install.php?tab=gd-recommended&wpaas_action=flush_cache&wpaas_nonce=adbeee6fe2'),
                'wpcb_object_flush'=>admin_url('options-general.php?page=objectcache&action=flush-cache&_wpnonce=61657c6cb4'),
            ]
        ]);
    }

    // ---------------- Frontend Assets ----------------
    public function load_frontend_assets(){
        if(!current_user_can('manage_options')) return;
        wp_enqueue_script('jquery');
        wp_enqueue_script('datatables-js','https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',['jquery'],null,true);
        wp_enqueue_script('datatables-buttons-js','https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js',['jquery','datatables-js'],null,true);
        wp_enqueue_script('datatables-html5-js','https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js',['jquery','datatables-buttons-js'],null,true);
        wp_enqueue_script('datatables-print-js','https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js',['jquery','datatables-buttons-js'],null,true);

        wp_enqueue_style('datatables-css','https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
        wp_enqueue_style('datatables-buttons-css','https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css');

wp_enqueue_script(
    'wpcb-frontend-js',
    plugin_dir_url(__FILE__) . 'assets/js/frontend-scan.js',
    ['jquery'],
    null,
    true
);
        wp_localize_script('wpcb-frontend-js','WPCB_SCAN',[
            'ajax'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('wpcb_scan_nonce'),
            'fallback_url'=>site_url()
        ]);
    }

    // ---------------- AJAX Handlers ----------------
    public function full_site_scan(){
        $nonce = $_POST['nonce'] ?? '';
        if(!wp_verify_nonce($nonce,'wpcb_nonce')) wp_send_json_error('Invalid nonce');
        if(!current_user_can('manage_options')) wp_send_json_error('Permission denied');

        set_transient('wpcb_page_assets',$this->scan_assets(),12*HOUR_IN_SECONDS);
        wp_send_json_success('Full site scan completed!');
    }

    public function scan_single_url(){
        $nonce = $_POST['nonce'] ?? '';
        if(!wp_verify_nonce($nonce,'wpcb_scan_nonce')) wp_send_json_error('Invalid nonce');
        if(!current_user_can('manage_options')) wp_send_json_error('Permission denied');

        $url = esc_url_raw($_POST['url'] ?? '');
        if(!$url) wp_send_json_error('No URL provided');

        $data = $this->scan_assets($url);
        wp_send_json_success($data);
    }

    public function object_cache_flush(){
        $nonce = $_POST['nonce'] ?? '';
        if(!wp_verify_nonce($nonce,'wpcb_nonce')) wp_send_json_error('Invalid nonce');
        if(!current_user_can('manage_options')) wp_send_json_error('Permission denied');

        // flush object cache via WordPress
        if(function_exists('wp_cache_flush')){
            wp_cache_flush();
        }

        wp_send_json_success('Object cache flushed');
    }

    // ---------------- Pages Last Edited Column ----------------
    public function add_last_edited_column($columns){
        $columns['last_edited'] = 'Last Edited';
        return $columns;
    }

    public function render_last_edited_column($column,$post_id){
        if($column === 'last_edited'){
            echo get_post_modified_time('Y-m-d H:i',$post_id);
        }
    }

    // ---------------- Scan Assets Helper ----------------
    private function scan_assets($url=null){
        $data = ['styles'=>[],'scripts'=>[]];

        $styles = wp_styles()->queue;
        foreach($styles as $handle){
            $s = wp_styles()->registered[$handle];
            $data['styles'][] = [
                'url'=>$s->src,
                'location'=>'header',
                'group'=>$this->detect_asset_group($s->src),
                'modified'=>date('Y-m-d H:i',file_exists(ABSPATH.$this->url_to_path($s->src)) ? filemtime(ABSPATH.$this->url_to_path($s->src)) : time())
            ];
        }

        $scripts = wp_scripts()->queue;
        foreach($scripts as $handle){
            $s = wp_scripts()->registered[$handle];
            $data['scripts'][] = [
                'url'=>$s->src,
                'location'=>'footer',
                'group'=>$this->detect_asset_group($s->src),
                'modified'=>date('Y-m-d H:i',file_exists(ABSPATH.$this->url_to_path($s->src)) ? filemtime(ABSPATH.$this->url_to_path($s->src)) : time())
            ];
        }

        return $data;
    }

    private function detect_asset_group($url){
        if(strpos($url,'/wp-content/themes/')!==false) return 'theme';
        if(strpos($url,'/wp-content/plugins/')!==false) return 'plugin';
        return 'custom';
    }

    private function url_to_path($url){
        $site_url = site_url();
        if(strpos($url,$site_url)===0){
            return str_replace($site_url.'/', '', $url);
        }
        return $url;
    }
}

new WPCB_Plugin();
