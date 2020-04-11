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
        wp_enqueue_script('jquery');
        wp_enqueue_style(
            'realtimesearch-frontend',
            plugin_dir_url(__FILE__) . 'assets/frontend.css',
            false,
            self::REALTIME_SEARCH_VERSION
        );
    }

    /**
     * Footer
     *
     * @return nothing
     */
    function footer()
    { ?>
        <div id="realTimeSearchResult"></div>
        <script type="text/javascript">
            var realTimeSearchTimer = null;
            var realTimeSearchWaiting = false;
            var realTimeSearchText = '';
            var realTimeSearchActive = false;

            function realTimeSearchSearchAjax() {
                let searchInput = document.getElementById('realTimeSearchSearchInput');
                let text = searchInput.value;
                if (realTimeSearchText == text) {
                    return;
                }
                if (text == '') {
                    document.getElementById('realTimeSearchResult').style.display = 'none';
                    realTimeSearchActive = false;
                    window.clearInterval(realTimeSearchTimer);
                } else {
                    if (!realTimeSearchWaiting) {
                        realTimeSearchText = text;
                        realTimeSearchWaiting = true;
                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            data: {
                                action: 'realTimeSearch_search',
                                text: text
                            },
                            success: function (data, textStatus, XMLHttpRequest) {
                                realTimeSearchTimer = null;
                                let searchInput = document.getElementById('realTimeSearchSearchInput');
                                let searchResult = document.getElementById('realTimeSearchResult');
                                let bounds = searchInput.getBoundingClientRect();
                                searchResult.innerHTML = data;
                                let left = (bounds.left - 250);
                                if (left < 0) {
                                    left = 0;
                                }
                                searchResult.style.left = left + 'px';
                                searchResult.style.top = (bounds.top + bounds.height + window.scrollY) + 'px';
                                searchResult.style.width = (bounds.width + 250) + 'px';
                                searchResult.style.display = 'block';
                                let listEntry = searchResult.firstElementChild.firstElementChild;
                                while (listEntry) {
                                    listEntry.firstElementChild.addEventListener('keydown', event => {
                                        let entry = event.target.parentElement;
                                        const realTimeSearchResult = document.getElementById('realTimeSearchResult');
                                        if (event.keyCode == 40 && realTimeSearchActive) {
                                            if (entry.nextElementSibling) {
                                                entry.nextElementSibling.firstElementChild.focus();
                                            } else {
                                                document.getElementById('realTimeSearchSearchInput').focus();
                                            }
                                            event.preventDefault();
                                        } else if (event.keyCode == 38 && realTimeSearchActive) {
                                            if (entry.previousElementSibling) {
                                                entry.previousElementSibling.firstElementChild.focus();
                                            } else {
                                                document.getElementById('realTimeSearchSearchInput').focus();
                                            }
                                            event.preventDefault();
                                        } else if (event.keyCode == 27) {
                                            realTimeSearchResult.style.display = 'none';
                                            event.preventDefault();
                                        }
                                    });
                                    listEntry = listEntry.nextElementSibling;
                                }
                                realTimeSearchWaiting = false;
                                realTimeSearchActive = true;
                            },
                            error: function (XMLHttpRequest, textStatus, errorThrown) {
                                realTimeSearchWaiting = false;
                                console.log('realTimeSearchSearch error: ' + errorThrown)
                            }
                        });
                    }
                }
            }

            function realTimeSearchSearch() {
                if (realTimeSearchTimer != null) {
                    window.clearInterval(realTimeSearchTimer);
                }

                realTimeSearchTimer = window.setInterval('realTimeSearchSearchAjax()', 250);
            }

            jQuery(document).ready(function () {
                document.getElementById('realTimeSearchSearchInput').setAttribute('autocomplete', 'off');

                document.addEventListener('click', (evt) => {
                    const realTimeSearchSearchInput = document.getElementById('realTimeSearchSearchInput');
                    const realTimeSearchResult = document.getElementById('realTimeSearchResult');
                    let targetElement = evt.target;

                    do {
                        if (targetElement == realTimeSearchResult || targetElement == realTimeSearchSearchInput) {
                            return;
                        }
                        targetElement = targetElement.parentNode;
                    } while (targetElement);

                    realTimeSearchResult.style.display = 'none';
                });

                document.getElementById('realTimeSearchSearchInput').addEventListener('focus', (event) => {
                    if (realTimeSearchActive) {
                        document.getElementById('realTimeSearchResult').style.display = 'block';
                    }
                });

                document.getElementById('realTimeSearchSearchInput').addEventListener('keydown', (event) => {
                    const realTimeSearchResult = document.getElementById('realTimeSearchResult');
                    if (event.keyCode == 40 && realTimeSearchActive && !realTimeSearchWaiting) {
                        realTimeSearchResult.style.display = 'block';
                        realTimeSearchResult.firstElementChild.firstElementChild.firstElementChild.focus();
                        event.preventDefault();
                    } else if (event.keyCode == 38 && realTimeSearchActive && !realTimeSearchWaiting) {
                        realTimeSearchResult.style.display = 'block';
                        realTimeSearchResult.firstElementChild.lrealTimeSearchElementChild.firstElementChild.focus();
                        event.preventDefault();
                    } else if (event.keyCode == 27 && realTimeSearchActive && !realTimeSearchWaiting) {
                        realTimeSearchResult.style.display = 'none';
                        event.preventDefault();
                    } else {
                        realTimeSearchSearch();
                    }
                });
            })
        </script>
        <?php
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
