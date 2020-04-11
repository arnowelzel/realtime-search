<?php
/*
Plugin Name: Realtime Search
Plugin URI: https://wordpress.org/plugins/realtime-search/
Description: Lightbox with PhotoSwipe
Version: 1.0
Author: Arno Welzel
Author URI: http://arnowelzel.de
Text Domain: realtime-search
*/
defined('ABSPATH') or die();

/**
 * Realtime Search
 * 
 * @package RealtimeSearch
 */
class RealtimeSearch
{
    const REALTIME_SEARCH_VERSION = '1.0';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_realTimeSearch_search', [$this, 'ajaxSearch']);
        add_action('wp_ajax_nopriv_realTimeSearch_search', [$this, 'ajaxSearch']);
        add_filter('get_search_form', [$this, 'getSearchForm']);

        if (!is_admin()) {
            add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
            add_action('wp_footer', [$this, 'footer']);
        }

        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Scripts/CSS
     *
     * @return nothing
     */
    function enqueueScripts()
    {
        wp_enqueue_script(
            'realtimesearch-frontend',
            plugin_dir_url(__FILE__) . 'assets/frontend.min.js',
            array('jquery'),
            self::REALTIME_SEARCH_VERSION
        );
        wp_enqueue_style(
            'realtimesearch-frontend',
            plugin_dir_url(__FILE__) . 'assets/frontend.css',
            false,
            self::REALTIME_SEARCH_VERSION
        );
        $translation_array['ajaxurl'] = admin_url('admin-ajax.php');
        wp_localize_script('realtimesearch-frontend', 'realtimesearch_options', $translation_array);
    }

    /**
     * Footer
     *
     * @return nothing
     */
    function footer()
    {
        echo '<div id="realTimeSearchResult"></div>';
    }

    function ajaxSearch()
    {
        $posts  = new WP_Query([
            's'              => $_POST['text'],
            'post_type'      => ['post', 'page'],
            'post_status'    => ['publish'],
            'posts_per_page' => 11
        ]);
        $num    = 0;
        $result = '<ul class="realTimeSearchSearchResult">';
        if (count($posts->posts) > 0) {
            foreach ($posts->posts as $post) {
                switch ($post->post_type) {
                    case 'page':
                        $result .= sprintf('<li><a tabindex="%d" href="%s">%s <span class="realTimeSearchSearchResultPage">(%s)</span></a></li>',
                            (20000 + $num),
                            get_page_link($post->ID),
                            $post->post_title,
                            __('Page', 'realtime-search'));
                        $num ++;
                        break;
                    case 'post':
                        $result .= sprintf('<li><a tabindex="%d" href="%s">%s</a></li>',
                            (20000 + $num),
                            get_permalink($post->ID),
                            $post->post_title);
                        $num ++;
                        break;
                }
                if ($num > 9) {
                    break;
                }
            }
            if ($num < count($posts->posts)) {
                $result .= sprintf('<li><a tabindex="%d" href="%s">%s</a></li>',
                    (20000 + $num),
                    home_url() . '/?s=' . urlencode($_POST['text']),
                    __('Show more', 'realtime-search') . ' …');
            }
        } else {
            $result .= sprintf('<li><span class="realTimeSearchSearchResultNone">(%s)</span></li>',
                __('Nothing found', 'realtime-search'));
        }
        $result .= '</ul>';
        echo $result;
        wp_die();
    }

    function getSearchForm($form)
    {
        $form = '<form method="get" class="search-form" action="' . home_url('/') . '">
        <label>
            <span class="screen-reader-text">' . __('Search for', 'realtime-search') . ':</span>
            <input type="search" id="realTimeSearchSearchInput" class="search-field" placeholder="' . __('Search',
                'realtime-search') . '&nbsp;…" value="" name="s" title="' . __('Search for', 'realtime-search') . ':" tabindex="20000">
        </label>
        <input type="submit" class="search-submit" value="Suchen">
    </form>';

        return $form;
    }

    /**
     * Plugin initialization
     *
     * @return void
     */
    function init()
    {
        load_plugin_textdomain('realtime-search', false, 'realtime-search/languages/');
    }
}

$realtime_search = new RealtimeSearch();
