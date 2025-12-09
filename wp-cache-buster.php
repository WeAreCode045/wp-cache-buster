<?php
/*
Plugin Name: WP Cache Buster
Description: Asset overview, cache flush, site scan, last edited column, frontend page scan.
Version: 1.1
Author: You
*/

if(!defined('ABSPATH')) exit;

class WPCB_Plugin {

    public function __construct() {
        // Admin menu
        add_action('admin_menu', [$this,'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this,'load_admin_assets']);

        // Pages list last edit column
        add_filter('manage_pages_columns', [$this,'add_last_edit_column']);
        add_action('manage_pages_custom_column', [$this,'fill_last_edit_column'], 10, 2);

        // AJAX
        add_action('wp_ajax_wpcb_full_site_scan', [$this,'full_site_scan']);
        add_action('wp_ajax_wpcb_scan_url', [$this,'scan_single_url']);

        // Frontend admin bar
        add_action('admin_bar_menu', [$this,'frontend_scan_button'], 100);
        add_action('wp_enqueue_scripts', [$this,'load_frontend_assets']);
    }

    // ---------------- Admin Menu ----------------
    public function register_admin_menu(){
        add_menu_page(
            'WP Asset Overview',
            'WP Assets',
            'manage_options',
            'wpcb-assets',
            [$this,'page_assets'],
            'dashicons-admin-generic',
            25
        );
    }

    // ---------------- Admin Assets ----------------
    public function load_admin_assets($hook){
        if($hook !== 'toplevel_page_wpcb-assets') return;

        echo '<script src="https://cdn.tailwindcss.com"></script>';
        wp_enqueue_style('wpcb-admin-extra', plugin_dir_url(__FILE__).'assets/css/admin.css', [], false);
        wp_enqueue_script('wpcb-admin', plugin_dir_url(__FILE__).'assets/js/admin.js', ['jquery'], false, true);

        wp_localize_script('wpcb-admin', 'WPCB', [
            'ajax'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('wpcb_nonce')
        ]);
    }

    // ---------------- Pages last edit column ----------------
    public function add_last_edit_column($columns){
        $columns['last_edit'] = 'Last Edited';
        return $columns;
    }

    public function fill_last_edit_column($column, $post_id){
        if($column === 'last_edit'){
            echo get_post_modified_time('d-m-Y H:i', false, $post_id);
        }
    }

    // ---------------- Admin Page ----------------
    public function page_assets(){
        include plugin_dir_path(__FILE__).'template-assets-page.php';
    }

    // ---------------- Full Site Scan ----------------
    public function full_site_scan(){
        check_ajax_referer('wpcb_nonce');

        set_transient('wpcb_page_assets', $this->scan_assets(), 12*HOUR_IN_SECONDS);
        wp_send_json_success('Full site scan completed!');
    }

    private function scan_assets(){
        $assets = [];
        $all_pages = get_posts(['post_type'=>'page','numberposts'=>-1]);
        foreach($all_pages as $p){
            $assets[$p->post_title] = [
                'styles'=>[], // scan CSS files
                'scripts'=>[] // scan JS files
            ];
        }
        return $assets;
    }

    // ---------------- Frontend scan ----------------
    public function frontend_scan_button($wp_admin_bar){
        if(!current_user_can('manage_options')) return;

        $args = [
            'id'=>'wpcb-scan-page',
            'title'=>'Scan Page Assets',
            'meta'=>['class'=>'wpcb-scan-button'],
            'href'=>'#'
        ];
        $wp_admin_bar->add_node($args);
    }

    public function load_frontend_assets(){
        if(!current_user_can('manage_options')) return;
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], null, true);
wp_enqueue_script('datatables-buttons-js', 'https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js', ['jquery','datatables-js'], null, true);
wp_enqueue_script('datatables-html5-js', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js', ['jquery','datatables-buttons-js'], null, true);
wp_enqueue_script('datatables-print-js', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js', ['jquery','datatables-buttons-js'], null, true);

wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
wp_enqueue_style('datatables-buttons-css', 'https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css');
        wp_enqueue_script('wpcb-frontend', plugin_dir_url(__FILE__).'assets/js/frontend-scan.js', ['jquery'], false, true);
        wp_localize_script('wpcb-frontend', 'WPCB_SCAN', [
            'ajax'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('wpcb_scan_nonce')
        ]);
    }

   public function scan_single_url(){
    check_ajax_referer('wpcb_scan_nonce');

    $url = esc_url_raw($_POST['url'] ?? '');
    if(empty($url)){
        wp_send_json_error('No URL provided');
    }

    $data = ['styles'=>[], 'scripts'=>[]];

    // Fetch page HTML
    $response = wp_remote_get($url);
    if(is_wp_error($response)){
        wp_send_json_error('Failed to fetch page HTML');
    }

    $html = wp_remote_retrieve_body($response);
    if(empty($html)){
        wp_send_json_error('Empty page HTML');
    }

    // Load HTML into DOMDocument
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    libxml_clear_errors();

    // Detect CSS
    foreach($doc->getElementsByTagName('link') as $link){
        if($link->getAttribute('rel') === 'stylesheet'){
            $href = $link->getAttribute('href');
            $location = $this->get_asset_location($link);
            $group = $this->detect_asset_group($href);
            $data['styles'][] = [
                'url'=>$href,
                'location'=>$location,
                'group'=>$group,
                'modified'=>date('Y-m-d H:i', file_exists(ABSPATH.$this->url_to_path($href)) ? filemtime(ABSPATH.$this->url_to_path($href)) : time())
            ];
        }
    }

    // Detect JS
    foreach($doc->getElementsByTagName('script') as $script){
        $src = $script->getAttribute('src');
        if($src){
            $location = $this->get_asset_location($script);
            $group = $this->detect_asset_group($src);
            $data['scripts'][] = [
                'url'=>$src,
                'location'=>$location,
                'group'=>$group,
                'modified'=>date('Y-m-d H:i', file_exists(ABSPATH.$this->url_to_path($src)) ? filemtime(ABSPATH.$this->url_to_path($src)) : time())
            ];
        }
    }

    wp_send_json_success($data);
}

// Helper: Determine asset location
private function get_asset_location($element){
    $parent = $element->parentNode;
    while($parent){
        if($parent->nodeName === 'head') return 'header';
        $parent = $parent->parentNode;
    }
    return 'footer';
}

// Helper: Detect asset group
private function detect_asset_group($url){
    if(strpos($url,'/wp-content/themes/') !== false) return 'theme';
    if(strpos($url,'/wp-content/plugins/') !== false) return 'plugin';
    return 'custom';
}

// Helper: Convert URL to server path
private function url_to_path($url){
    $site_url = site_url();
    if(strpos($url,$site_url) === 0){
        return str_replace($site_url.'/', '', $url);
    }
    return $url;
}


}

new WPCB_Plugin();
