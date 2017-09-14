<?php

namespace BureauGravity\wp;

use BureauGravity\wp\Builder;

class PostType
{
  /**
   * Create a new post type
   * @param  string $name     Name of post type
   * @param  function $callback callback that is called with a PostTypeBuilder
   * @return void
   */
  public static function create($name, $callback) {
    $builder = new PostTypeBuilder($name);
    call_user_func_array($callback, [&$builder]);
    $builder->finish();
  }

  /**
   * Removes an existing post type from the admin menu
   * (does not entirely remove the post type itself)
   * @param  string $name Post type name
   * @return void
   */
  public static function remove($name) {
    add_action('admin_menu',function () use ($name) {
      if ($name == 'post') {
        remove_menu_page('edit.php');
      }
    });
  }
}

class PostTypeBuilder implements Builder
{
  public $name;
  public $labels = [];
  public $rewrites = [];
  public $menu_icon = "";
  public $public = true;
  public $has_archive = true;
  public $supports = [];
  public $menu_position = 25;

  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Sets the 'labels' option to the array provided
   * @param  array $labels 'labels' array
   * @return void
   */
  public function labels($labels) {
    $this->labels = $labels;
  }

  /**
   * Sets 'public' to true if no arguments provided
   * @param  boolean $public The value of the 'public' setting
   * @return void
   */
  public function public($public = true) {
    $this->public = $public;
  }

  /**
   * Sets 'has_archive' to true if no arguments provided
   * @param  boolean $has_archive The value of the 'has_archive' setting
   * @return void
   */
  public function has_archive($has_archive = true) {
    $this->has_archive = $has_archive;
  }

  /**
   * Sets the post type icon.
   * @param  string $icon Dashicon: https://developer.wordpress.org/resource/dashicons/
   * @return void
   */
  public function icon($icon) {
    $this->menu_icon = $icon;
  }

  /**
   * Sets the 'rewrite['slug']' setting to the string provided
   * @param  string $slug
   * @return void
   */
  public function slug($slug) {
    $this->rewrites['slug'] = $slug;
  }

  /**
   * Sets the 'supports' setting to the array provided
   * @param  array $supports
   * @return void
   */
  public function supports($supports) {
    $this->supports = $supports;
  }

  /**
   * Sets the 'menu_position' setting to the integer provided
   * @param  integer $position https://codex.wordpress.org/Function_Reference/register_post_type#menu_position
   * @return void
   */
  public function menu_position($menu_position) {
    $this->menu_position = $menu_position;
  }

  /**
   * Executes the adding of the creation of the post type to the 'init' action
   * @return void
   */
  public function finish() {
    add_action( 'init', function() {
      register_post_type($this->name, [
        'labels' => $this->labels,
        'menu_icon' => $this->menu_icon,
        'has_archive' => $this->has_archive,
        'public' => $this->public,
        'rewrites' => $this->rewrites,
        'supports' => $this->supports,
        'menu_position' => $this->menu_position
      ]);
    });
  }
}
