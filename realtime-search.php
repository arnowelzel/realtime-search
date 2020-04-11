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
	function footer()
	{
		wp_enqueue_script('jquery');
?>
<div id="astResult"></div>
<script type="text/javascript" >
var astTimer = null;
var astWaiting = false;
var astText = '';
var astActive = false;

function astSearchAjax()
{
    let searchInput = document.getElementById('astSearchInput');
    let text = searchInput.value;
    if(astText==text) {
        return;
    }
    if(text == '') {
        document.getElementById('astResult').style.display = 'none';
        astActive = false;
        window.clearInterval(astTimer);
    } else {
        if(!astWaiting) {
            astText = text;
            astWaiting = true;
            jQuery.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        action: 'ast_search',
                        text: text
                    },
                success: function (data, textStatus, XMLHttpRequest) {
                    astTimer = null;
                    let searchInput = document.getElementById('astSearchInput');
                    let searchResult = document.getElementById('astResult');
                    let bounds = searchInput.getBoundingClientRect();
                    searchResult.innerHTML=data;
                    let left = (bounds.left-250);
                    if (left<0) {
                        left = 0;
                    }
                    searchResult.style.left = left+'px';
                    searchResult.style.top = (bounds.top+bounds.height+window.scrollY)+'px';
                    searchResult.style.width = (bounds.width+250)+'px';
                    searchResult.style.display = 'block';
                    let listEntry = searchResult.firstElementChild.firstElementChild;
                    while(listEntry) {
                        listEntry.firstElementChild.addEventListener('keydown', event => {
                            let entry = event.target.parentElement;
                            const astResult = document.getElementById('astResult');
                            if(event.keyCode == 40 && astActive) {
                                if (entry.nextElementSibling) {
                                    entry.nextElementSibling.firstElementChild.focus();
                                } else {
                                    document.getElementById('astSearchInput').focus();
                                }
                                event.preventDefault();
                            } else if(event.keyCode == 38 && astActive) {
                                if (entry.previousElementSibling) {
                                    entry.previousElementSibling.firstElementChild.focus();
                                } else {
                                    document.getElementById('astSearchInput').focus();
                                }
                                event.preventDefault();
                            } else if(event.keyCode == 27) {
                                astResult.style.display='none';
                                event.preventDefault();
                            }
                        });
                        listEntry = listEntry.nextElementSibling;
                    }
                    astWaiting = false;
                    astActive = true;
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    astWaiting = false;
                    console.log('astSearch error: '+errorThrown)
                }
            });
        }
    }
}

function astSearch()
{
    if(astTimer != null) {
        window.clearInterval(astTimer);
    }

    astTimer = window.setInterval('astSearchAjax()', 250);
}

jQuery(document).ready(function() {
    document.getElementById('astSearchInput').setAttribute('autocomplete', 'off');

    document.addEventListener('click', (evt) => {
        const astSearchInput = document.getElementById('astSearchInput');
        const astResult = document.getElementById('astResult');
        let targetElement = evt.target;

        do {
            if (targetElement == astResult || targetElement == astSearchInput) {
                return;
            }
            targetElement = targetElement.parentNode;
        } while (targetElement);

        astResult.style.display='none';
    });

    document.getElementById('astSearchInput').addEventListener('focus', (event) => {
       if (astActive) {
           document.getElementById('astResult').style.display='block';
       }
    });

    document.getElementById('astSearchInput').addEventListener('keydown', (event) => {
        const astResult = document.getElementById('astResult');
        if(event.keyCode == 40 && astActive && !astWaiting) {
            astResult.style.display='block';
            astResult.firstElementChild.firstElementChild.firstElementChild.focus();
            event.preventDefault();
        } else if(event.keyCode == 38 && astActive && !astWaiting) {
            astResult.style.display='block';
            astResult.firstElementChild.lastElementChild.firstElementChild.focus();
            event.preventDefault();
        } else if(event.keyCode == 27 && astActive && !astWaiting) {
            astResult.style.display='none';
            event.preventDefault();
        } else {
            astSearch();
        }
    });
})
</script>
<?php
}
add_action('wp_footer', 'arno_searchfooter');

function arno_search()
{
    $posts = new WP_Query(['s'=>$_POST['text'], 'post_type' => ['post', 'page'], 'post_status' => ['publish'], 'posts_per_page' => 11]);
    $num = 0;
    $result = '<ul class="astSearchResult">';
    if (count($posts->posts) > 0) {
        foreach($posts->posts as $post) {
            switch($post->post_type) {
                case 'page':
                    $result .= sprintf('<li><a tabindex="%d" href="%s">%s <span class="astSearchResultPage">(%s)</span></a></li>',
                        (20000+$num),
                        get_page_link($post->ID),
                        $post->post_title,
                        __('Page', 'arnos-site-tools'));
                    $num++;
                    break;
                case 'post':
                    $result .= sprintf('<li><a tabindex="%d" href="%s">%s</a></li>',
                        (20000+$num),
                        get_permalink($post->ID),
                        $post->post_title);
                    $num++;
                    break;
            }
            if($num>9) {
                break;
            }
        }
        if($num<count($posts->posts)) {
            $result .= sprintf('<li><a tabindex="%d" href="%s">%s</a></li>',
                (20000+$num),
                home_url().'/?s='.urlencode($_POST['text']),
                __('Show more', 'arnos-site-tools').' …');
        }
    } else {
        $result .= sprintf('<li><span class="astSearchResultNone">(%s)</span></li>',
            __('Nothing found.', 'arnos-site-tools'));
    }
    $result .= '</ul>';
    echo $result;
	wp_die();
}
add_action('wp_ajax_ast_search', 'arno_search');
add_action('wp_ajax_nopriv_ast_search', 'arno_search');

function arno_search_form( $form ) {
    $form='<form method="get" class="search-form" action="' . home_url( '/' ) . '">
        <label>
            <span class="screen-reader-text">' . __('Search for', 'arnos-site-tools') .':</span>
            <input type="search" id="astSearchInput" class="search-field" placeholder="' . __('Search', 'arnos-site-tools') .'&nbsp;…" value="" name="s" title="' . __('Search for', 'arnos-site-tools') .':" tabindex="20000">
        </label>
        <input type="submit" class="search-submit" value="Suchen">
    </form>';

    return $form;
}
add_filter( 'get_search_form', 'arno_search_form' );
