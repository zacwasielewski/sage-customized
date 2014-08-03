<?php
/**
 * Scripts and stylesheets
 *
 * Enqueue stylesheets in the following order:
 * 1. /theme/assets/css/main.css
 *
 * Enqueue scripts in the following order:
 * 1. jquery-1.11.1.js via Google CDN
 * 2. /theme/assets/js/vendor/modernizr.js
 * 3. /theme/assets/js/scripts.js (in footer)
 *
 * Google Analytics is loaded after enqueued scripts if:
 * - An ID has been defined in config.php
 * - You're not logged in as an administrator
 */

$jquery_version = '1.11.1';

function roots_load_manifest() {
  $manifest = file_get_contents(get_template_directory() . '/assets/manifest.json');
  return json_decode($manifest, true);
}

function roots_manifest($path, $attr) {
  global $manifest;

  if (WP_ENV !== 'development') {
    $manifest = isset($manifest) ? $manifest : roots_load_manifest();
    return $manifest["assets/$path"][$attr];
  }
}

function roots_asset_path($path) {
  if (WP_ENV === 'development') {
    return get_template_directory() . '/assets/' . $path;
  } else {
    return get_template_directory() . roots_manifest($path, 'path');
  }
}

function roots_enqueue_script($handle, $src, $deps = array(), $in_footer = false) {
  $src = roots_asset_path($src);
  $ver = roots_manifest($src, 'hash');

  wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
}

function roots_enqueue_style($handle, $src, $deps = array(), $media = 'all') {
  $src = roots_asset_path($src);
  $ver = roots_manifest($src, 'hash');

  wp_enqueue_style($handle, $src, $deps, $ver, $media);
}

function roots_scripts() {
  roots_enqueue_style('roots_css', 'css/main.css');

  /**
   * jQuery is loaded using the same method from HTML5 Boilerplate:
   * Grab Google CDN's latest jQuery with a protocol relative URL; fallback to local if offline
   * It's kept in the header instead of footer to avoid conflicts with plugins.
   */
  if (!is_admin() && current_theme_supports('jquery-cdn')) {
    wp_deregister_script('jquery');

    if (WP_ENV == 'development') {
      wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/$jquery_version/jquery.js", array(), null);
    } else {
      wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/$jquery_version/jquery.min.js", array(), null);
    }

    add_filter('script_loader_src', 'roots_jquery_local_fallback', 10, 2);
  }

  if (is_single() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }

  roots_enqueue_script('modernizr', 'vendor/modernizr/modernizr.js');
  wp_enqueue_script('jquery');
  roots_enqueue_script('roots_js', 'js/scripts.js', array(), true);
}
add_action('wp_enqueue_scripts', 'roots_scripts', 100);

// http://wordpress.stackexchange.com/a/12450
function roots_jquery_local_fallback($src, $handle = null) {
  static $add_jquery_fallback = false;

  if ($add_jquery_fallback) {
    if (WP_ENV == 'development') {
      $jquery = roots_asset_path("vendor/jquery/dist/jquery.js?$jquery_version");
    } else {
      $jquery = roots_asset_path("vendor/jquery/dist/jquery.min.js?$jquery_version");
    }

    echo '<script>window.jQuery || document.write(\'<script src="' . $jquery . '"><\/script>\')</script>' . "\n";
    $add_jquery_fallback = false;
  }

  if ($handle === 'jquery') {
    $add_jquery_fallback = true;
  }

  return $src;
}
add_action('wp_head', 'roots_jquery_local_fallback');

/**
 * Google Analytics snippet from HTML5 Boilerplate
 */
function roots_google_analytics() { ?>
<script>
  (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
  function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
  e=o.createElement(i);r=o.getElementsByTagName(i)[0];
  e.src='//www.google-analytics.com/analytics.js';
  r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
  ga('create','<?php echo GOOGLE_ANALYTICS_ID; ?>');ga('send','pageview');
</script>

<?php }
if (GOOGLE_ANALYTICS_ID && !current_user_can('manage_options')) {
  add_action('wp_footer', 'roots_google_analytics', 20);
}
