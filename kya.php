<?php

require_once( ABSPATH . WPINC . '/capabilities.php' );
/**
 * @package KYA
 */
/*
Plugin Name: KYA
Version: 2.0.10
Plugin URI: https://getkya.com/
Description: The KYA plugin lets you better understand your audience, track audience engagement on all of your content, perform comparison analysis, and keep your audience more engaged with personalized recommendations.
Author: KYA Inc.
Author URI: https://getkya.com/
License: GPLv2 or later
 */


$private_key = get_option( 'kya_private_key_setting' );
$public_key = get_option( 'kya_public_key_setting' );

$frontend_host = "https://analytics.getkya.com";
$api_host = "https://api.getkya.com";


function kya_settings_init() {

  add_settings_section(
    'kya_setting_section',
    'KYA Settings',
    'kya_setting_section_callback_function',
    'kya_settings'
  );

  add_settings_field(
    'kya_private_key_setting',
    'Private Key',
    'kya_setting_private_key_callback_function',
    'kya_settings',
    'kya_setting_section'
  );

  add_settings_field(
    'kya_public_key_setting',
    'Public Key',
    'kya_setting_public_key_callback_function',
    'kya_settings',
    'kya_setting_section'
  );


  register_setting('kya_settings', 'kya_private_key_setting');
  register_setting('kya_settings', 'kya_public_key_setting');

}

// function kya_deactivate() {
//   unregister_setting('kya_settings', 'kya__key_setting');
//   unregister_setting('kya_settings', 'kya__key_setting');
// }

function kya_settings_menu() {
  $page = add_options_page( "KYA Settings", "KYA", "manage_options", __FILE__, "kya_settings_page" );
}

function kya_settings_page() { 
?> 
  <div class="wrap">
  <h2><img src="https://getkya.com/wp-content/uploads/2015/11/kya-logo.png" alt="KYA"></h2>
  <form method="POST" action="options.php">
  <?php 

    settings_fields( 'kya_settings' );
    do_settings_sections( 'kya_settings' );  
    submit_button();
  
  ?>
  </form>
  </div>
<?php 
}

 
function kya_setting_section_callback_function() {
  echo '<p>Enter your site’s private and public keys below then click “Save Changes” to have KYA start collecting data.</p>
        <p>The keys can be found on the "Setup" tab here: <a href="https://analytics.getkya.com/dashboard/setup" target="_blank">Setup</a></p>
  ';
}
  
function kya_setting_private_key_callback_function() {
  echo '<input name="kya_private_key_setting" id="kya_private_key_setting" class="regular-text code" value="' . get_option( 'kya_private_key_setting' ) . '" type="text" />';
}

function kya_setting_public_key_callback_function() {
  echo '<input name="kya_public_key_setting" id="kya_public_key_setting" class="regular-text code" value="' . get_option( 'kya_public_key_setting' ) . '" type="text" />';
}


add_action( 'admin_init', 'kya_settings_init' );
add_action( 'admin_menu', 'kya_settings_menu' );
//register_deactivation_hook( __FILE__, 'kya_deactivate' );


function kya_api_key__warning() {
  $class = 'notice notice-warning';
  $message = __( 'The KYA plugin needs to have API keys entered on Settings -> KYA', 'kya-text-domain' );

  printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
}

if(!$private_key || !$public_key) {
 add_action( 'admin_notices', 'kya_api_key__warning' );
}


function read_kya_file($filename) {
  $base = dirname(__FILE__);
  $fp = fopen("$base/$filename", "r");
  $file_contents = stream_get_contents($fp);
  fclose($fp);
  return $file_contents;
}

function render_loader($public_key) {
  $file_contents = read_kya_file("kya_loader.js");
  $file_contents = str_replace("{{public_key}}", $public_key, $file_contents);
  return $file_contents;
}

function render_header_css() {
  return read_kya_file("kya_admin.css");
}

function read_admin_js() {
  return read_kya_file("kya_admin.js");
}

function echo_script_tag($script_tag_contents) {
  echo "<script>" . $script_tag_contents . "</script>";
}

function kya_scripts() {
  global $public_key;
  echo_script_tag(render_loader($public_key));
}

if($private_key && $public_key) {
  add_action('wp_head', 'kya_scripts');
}


function kya_admin_head() {
  echo "<style type=\"text/css\">" . render_header_css() . "</style>";
}
add_action('admin_head', 'kya_admin_head');

add_action("admin_menu", "setup_kya_menus");
add_action("admin_notices", "kya_render");

function cur_page_url() {
    return $_SERVER["SERVER_NAME"];
}

function frontend_host() {
    global $frontend_host;
    return $frontend_host;
}

function api_host() {
  global $api_host;
  return $api_host;
}

function make_iframe_with_url($url) {
    $current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    return "<iframe id='kya_frame' src='" . frontend_host() . $url . "?no_frameset=1&frameset_url=$current_url' style='width:100%;min-height:150vh;'></iframe>";
}

function kya() {
    echo "<br>";
    echo make_iframe_with_url("/dashboard");
}

function kya_submenu_shouts_and_comments() {
    echo "<br>";
    echo make_iframe_with_url("/dashboard/article_interest");
}

function menuItems() {
    return array(
        "Overview"        => "kya",
        "Realtime"        => "kya_submenu_realtime",
        "Content"         => "kya_submenu_article_views",
        "Author Leaderboard"     => "kya_submenu_authors",
        "Compare"        => "kya_submenu_compare",
        "Demographics"        => "kya_submenu_demographics"
    );
}

function show_users_table() {
    echo "<h1>Invite WordPress users to view the KYA Dashboard</h1>";
    echo "<ul>";
    foreach (get_users() as $user) {
        echo "<li>";
        if (!preg_match("/kya[0-f0-9]{16}/", $user->data->user_nicename)) {
            echo "<input type='checkbox' class='kya_invite_checkbox' id='kya_invite_" . $user->data->user_email . "'></input>";
            echo $user->data->user_nicename;
            echo " - ";
            echo $user->data->user_email;
        }
        echo "</li>";
    }
    echo "</ul>";
    echo "<button id='invite'>Send users KYA invites</button>";
?>
<script type="text/javascript">
    jQuery("#invite").click(function() {
        var emails = [];
       jQuery.each(jQuery(".kya_invite_checkbox"), function(i, e) {
            if (jQuery(e).prop("checked")) {
                var email = jQuery(e).attr("id").split("_");
                email = email[email.length-1];
                console.log(email);
                emails.push(email);
            }
        });

        window.open("<?php echo frontend_host() ?>/dashboard/email_invites?emails=" + encodeURIComponent(emails.join(",")));
    });
</script>
<?php
}

function menuUrls() {
    return array(
        "kya"                             => "/dashboard",
        "kya_submenu_realtime"            =>  "/dashboard/realtime",
        "kya_submenu_shouts_and_comments" => "/dashboard/article_interest",
        "kya_submenu_emails"              => "/dashboard/emails",
        "kya_submenu_authors"             => "/dashboard/authors",
        "kya_submenu_demographics"        => "/dashboard/demographics",
        "kya_submenu_article_views"       => "/dashboard/popular_content",
        "kya_submenu_compare"            => "/dashboard/compare",
    );
}

$rendered = false;

function anonymous_render() {
            //METABOX RENDERER BEGIN
?>
<!-- css to apply to the metabox -->
<style>
.clearfix {
clear:both;
}

.suggested-topic-1 {
float:left;
padding:5px;
margin-right:1em;
}
</style>
<!-- This javascript file loads the topics -->
<script>
            jQuery(document).ready(function() {

                var port = window.location.port;
                if (port == "") {
                    port = "80";
                }
                var params = {
                    "domain": window.location.hostname + ":" + port
                }

                //get topics
                jQuery.get("<?php echo api_host()?>/topics/author", params, function(response) {
                    //create a div to put the topics in to
                    $node = jQuery("<div></div>");
                    //for each topic in the response
                    for (var i = 0; i < response.topics.length; i++) {
                        var topic = response.topics[i];
                        //create a div with class 'suggested-topic-1' which contains all the words in the topic separated by brs
                        $node.append("<div class='suggested-topic-1'>" + topic.join("<br>") + "</div>");
                    }
                    //put a clearfix div at the end
                    $node.append("<div class='clearfix'></div>");
                    //put the html in the div with id topics
                    jQuery("#topics").html($node);
                });
            })
</script>
<!-- the actual html content of the metabox -->
<h4>Why not write about:</h4>
<div id='topics'></div>
<?php
                //METABOX RENDERER END
}

function kya_render() {
    global $rendered;
    echo "<br>";
    if (!$rendered) {
        if (in_array($_GET["page"], array_values(menuItems()))) {
            $urls = menuUrls();
            echo make_iframe_with_url($urls[$_GET["page"]]);
        }

        if ($_GET["page"] == "kya_submenu_add_wordpress_users") {
            show_users_table();
        }
        $path_name =  $_SERVER['SCRIPT_FILENAME'];

        if (strpos($path_name, "wp-admin/post.php") || strpos($path_name, "wp-admin/post-new.php")) {
            //the second string below which currently reads "KYA Suggested Topics"
            //can be changed, it is the title of the metabox
            add_meta_box("KYA_METABOX", "KYA Suggested Topics", "anonymous_render", "post", "side", "high");
        }

    }
    $rendered = true;
}

function setup_kya_menus() {
    add_menu_page("kya" , "KYA" , "manage_options" , "kya" , "jk_display_main_options");

    foreach (menuItems() as $menu_name => $menu_function) {
        add_submenu_page("kya" , $menu_name, $menu_name , "manage_options" , $menu_function, 'kya_render');
    }
    add_submenu_page("kya" , "Add WordPress users to KYA",  "Add WordPress users to KYA", "manage_options" , "kya_submenu_add_wordpress_users", 'kya_render');
}

$path_name =  $_SERVER['REQUEST_URI'];

 function isKyaContent()
{
  return is_page() || is_single();
}

  /**
   * @param $post_id
   * @return array
   */
   function getPostImagesArray( $post_id ) {
    $images_array = array();

    $attachment_images = getPostImages($post_id);

    if ($attachment_images && is_array($attachment_images)) {
      foreach($attachment_images as $image) {
        if ( isset( $image->guid ) ) {
          $images_array[] = $image->guid;
        }

        if ( count( $images_array ) > 5 ) break;
      }
    }

    return $images_array;
  }

  /**
   * @param $post_id
   * @return mixed|null
   */
   function getPostFeaturedImage( $post_id )
  {
    if (has_post_thumbnail( $post_id ) ) {
      list($url) = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'single-post-thumbnail' );

      if ( $url ) {
        return $url;
      }
    } else {
      $post_images = getPostImages( $post_id );

      if ( count( $post_images ) > 0 ) {
        $sorted_images = array();
        $check_images_count = 6;

        foreach ( $post_images as $image ) {
          // Check if image url, this is NOT URL to another server. If this is external URL, this can take a lot of time to detect image size
          if ( strpos( $image->guid, site_url() ) !== false ) {
            list($url, $width, $height) = wp_get_attachment_image_src($image->ID, 'full');
            $image_rank = $width + $height;

            if (!isset($sorted_images[$image_rank])) {
              $sorted_images[$image_rank] = array($url);
            } else {
              $sorted_images[$image_rank][] = $url;
            }

            if (count($sorted_images) >= $check_images_count) break;
          }
        }

        if ( count( $sorted_images ) == 0 ) {
          $first_image = reset( $post_images );
          if ( $first_image && isset( $first_image->guid ) ) {
            $sorted_images[0][] = $first_image->guid;
          }
        }

        krsort( $sorted_images );

        return current(reset($sorted_images));
      }
    }

    return null;
  }


  /**
   * @param $post_id
   * @param int $tags_num_limit
   * @return array
   */
   function getPostTagsArray( $post_id, $tags_num_limit = 5 ) {
    $tags_array = array();
    $post_tags = get_the_tags( $post_id );
    if (is_array($post_tags) && count($post_tags) > 0) {
      foreach (array_slice($post_tags, 0, $tags_num_limit) as $post_tag) {
        $tags_array[] = escape( $post_tag->name );
      }
    }

    return $tags_array;
  }

  /**
   * @param $post_id
   * @param int $categories_num_limit
   * @return array
   */
   function getPostCategoriesArray( $post_id, $categories_num_limit = 5 ) {
    $categories_array = array();
    $post_categories = wp_get_post_categories( $post_id );
    if (is_array($post_categories) && count($post_categories) > 0) {
      foreach ( array_slice($post_categories, 0, $categories_num_limit) as $category_id ) {
        $category = get_category( $category_id );
        if ( $category && strtolower( $category->name ) != "uncategorized" ) {
          $categories_array[] = escape( $category->name );
        }
      }
    }

    return $categories_array;
  }

  /**
   * @param $post_id
   * @return array|bool
   */
   function getPostImages($post_id)
  {
    $attachment_images = get_children(
      array(
        'post_parent'    => $post_id,
        'post_type'      => 'attachment',
        'numberposts'    => 0,
        'post_mime_type' => 'image'
      )
    );
    return $attachment_images;
  }

     function getAuthorFullName( $post ) {
      if ( get_the_author_meta( "first_name", $post->post_author ) || get_the_author_meta( "last_name", $post->post_author ) )
      {
          $name = get_the_author_meta( "first_name", $post->post_author ) . ' ' . get_the_author_meta( "last_name", $post->post_author );
        return escape( trim( $name ) );
      }
        return null;
    }

   function getAuthorDisplayName( $post ) {
    $display_name = get_the_author_meta( "display_name", $post->post_author );
    $nickname = get_the_author_meta( "nickname", $post->post_author );
    $name = $display_name ? $display_name : $nickname;

    return escape( $name );
  }   


 function buildMetadata( $post )
  {
    return array(
      'title'                    => escape( $post->post_title ),
      'url'                      => get_permalink( $post->ID ),
      'pub_date'                 => $post->post_date,
      'mod_date'                 => $post->post_modified,
      'type'                     => $post->post_type,
      'post_id'                  => $post->ID,
      'author_id'                => escape( $post->post_author ),
      'author_name'              => getAuthorFullName( $post ),
      'author_display_name'      => getAuthorDisplayName( $post ),
      'tags'                     => getPostTagsArray( $post->ID ),
      'categories'               => getPostCategoriesArray( $post->ID ),
      'image'                    => getPostFeaturedImage( $post->ID ),
      //'thumbnail'                 => escape( get_the_post_thumbnail( $post->ID ) )
    );
  }

  /**
   *
   */
 function insertMetatags()
  {
    if ( isKyaContent() ) {
      global $post;
      $json_data = null;

      if ( isset( $post ) ) {
        $json_data = buildMetadata( $post );
      }

      if ( $json_data !== null ) {?>

<meta name='kya-data' id='kya-data' content='<?php echo json_encode( $json_data ); ?>' />

<?php
      }
    }
}

add_action( 'wp_head', 'insertMetatags');

 function escape($text)
{
  return htmlspecialchars($text, ENT_QUOTES & ~ENT_COMPAT, 'utf-8');
}



function kya_api_present() {
  return array("present" => true);
}

function get_name($x) {
  return $x->name;
}

function kya_api_list_posts() {
  $page = $_GET["page"];
  $posts_per_page = 150;
  $args = array(
    'posts_per_page'   => $posts_per_page,
    'offset'           => $page * $posts_per_page,
    'category'         => '',
    'category_name'    => '',
    'orderby'          => 'date',
    'order'            => 'DESC',
    'include'          => '',
    'exclude'          => '',
    'meta_key'         => '',
    'meta_value'       => '',
    'post_type'        => 'post',
    'post_mime_type'   => '',
    'post_parent'      => '',
    'post_status'      => 'publish',
    'suppress_filters' => true
  );
  $posts_array = get_posts( $args );
  $build = array();
  foreach ($posts_array as $post) {
    $post_thumbnail = get_the_post_thumbnail($post->ID);
    
    $post_categories = get_the_category($post->ID);

    $post_tags = get_the_tags($post->ID);


    $build[] = array(
        "title"     => $post->post_title,
        "id"        => $post->ID,
        "content"   => $post->post_content,
        "link"      => get_permalink($post->ID),
        "thumb"     => $post_thumbnail,
        "tags"      => array_map('get_name',$post_tags),
        "categories"=> array_map('get_name',$post_categories),
        "posted_at" => $post->post_date_gmt,
    );
  }

  $wordpress_post_count_object = wp_count_posts();
  $count_of_published_posts = $wordpress_post_count_object->publish;
  $more = $count_of_published_posts > $page*$posts_per_page;
  return array("posts" => array("posts" => $build, "more" => $more));
}

function kya_api($path_name) {
  nocache_headers();
  ob_start();

  $api_call_without_query = explode("?", $path_name);
  $api_call_without_query = $api_call_without_query[0];
  var_dump($api_call_without_query);
  $api_call_path_parts = explode("/", $api_call_without_query);
  end($api_call_path_parts);

  $api_call_path = null;

  if ($api_call_without_query[strlen($api_call_without_query)-1] == '/') {
    $api_call_path = prev($api_call_path_parts);
  } else {
    $api_call_path = end($api_call_path_parts);
  }

  $api_calls = array(
    "present" => "kya_api_present",
    "posts"   => "kya_api_list_posts",
  );

  $api_call = $api_calls[$api_call_path];

  $result = $api_call();
  ob_end_clean();
  echo json_encode($result);
}

if (strstr($path_name, "kya-api")) {
  kya_api($path_name);
  exit;
}
?>
