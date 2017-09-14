<?php

namespace BureauGravity\wp;

use BureauGravity\wp\Builder;
use BureauGravity\wp\Util;

/**
 * Class containing static methods to help with managing taxonomies
 */
class Taxonomy
{
  /**
   * Registers a new taxonomy
   * @param  string $taxonomy_name Name of taxonomy
   * @param  array $post_types    An array of strings of the names of the post types
   *                              for which this taxonomy should be used
   * @param  function $callback      callback that is called with a TaxonomyBuilder
   * @return void
   */
  public static function register($taxonomy_name, $post_types, $callback) {
    $builder = new TaxonomyBuilder($taxonomy_name, $post_types);
    call_user_func_array($callback, [&$builder]);
    $builder->finish();
  }
}

/**
 * Used to construct a taxonomy with the Taxonomy::register method
 */
class TaxonomyBuilder implements Builder
{
  public $name;
  public $post_types = [];
  public $labels = [];
  public $rewrites = [];
  public $hierarchical = false;

  public function __construct($name, $post_types) {
    $this->name = $name;
    $this->post_types = $post_types;
  }

  /**
   * Execute the adding of the taxonomy registration to the 'init' action
   * @return void
   */
  public function finish() {
    add_action('init', function() {
      register_taxonomy($this->name, $this->post_types, [
        'rewrites' => $this->rewrites,
        'hierarchical' => $this->hierarchical,
        'labels' => $this->labels
      ]);
    });
  }


  public function slug($name) {
    $this->rewrites['slug'] = $name;
  }

  public function hierarchical() {
    $this->hierarchical = true;
  }

  public function labels($labels) {
    $this->labels = $labels;
  }

  /**
   * Lets methods be called on this TaxonomyBuilder, but only applies them
   * to the post type provided
   * @param  string $post_type Post type name
   * @param  function $callback  callback called with this TaxonomyBuilder
   *                             instance
   * @return void
   */
  public function posttype($post_type, $callback) {
    $post_types_holder = $this->post_types;
    $this->post_types = [$post_type];
    call_user_func_array($callback, [&$this]);
    $this->post_types = $post_types_holder;
  }

  /**
   * Adds a column for the taxonomy to the taxonomy's post types
   * @return ColumnBuilder  Allows for specifying some additional
   *                        settings for the column
   */
  public function column() {
    foreach ($this->post_types as $post_type) {
      add_filter( "manage_{$post_type}_posts_columns", function($columns) {
        $columns[$this->name] = $this->labels['singular_name'];
        return $columns;
      });
      add_action( "manage_{$post_type}_posts_custom_column", function( $column, $post_id ) {
        if ($column == $this->name) {
          $terms = wp_get_post_terms($post_id, $this->name);
          $str = "";
          for ($i=0;$i<count($terms);$i++) {
            if ($i != 0) $str .= ", ";
            $str .= $terms[$i]->name;
          }
          echo $str;
        }
      }, 10, 2);
      add_filter( "manage_edit-{$post_type}_sortable_columns", function($columns) {
        $columns[$this->name] = $this->name;
        return $columns;
      });
    }
    return new ColumnBuilder($this);
  }

  /**
   * Adds a filter dropdown to the admin list page for this taxonomy's
   * post types
   * @return void
   */
  public function filter() {
    if (is_admin()) {
      foreach ($this->post_types as $post_type) {
        add_action( 'restrict_manage_posts', function() use ($post_type) {
          $screen = get_current_screen();
          global $wp_query;
          if ( $screen->post_type == $post_type ) {
              wp_dropdown_categories( array(
                  'show_option_all' => 'Show All '.$this->labels['name'],
                  'taxonomy' => $this->name,
                  'name' => $this->name,
                  'orderby' => 'name',
                  'selected' => ( isset( $wp_query->query[$this->name] ) ? $wp_query->query[$this->name] : '' ),
                  'hierarchical' => false,
                  'depth' => 3,
                  'show_count' => false,
                  'hide_empty' => true,
              ) );
          }
        });
        add_filter( 'parse_query', function($query){
          $qv = &$query->query_vars;
          if ( isset($qv[$this->name]) && ($qv[$this->name] != false) && is_numeric( $qv[$this->name] ) ) {
            $term = get_term_by( 'id', $qv[$this->name], $this->name );
            $qv[$this->name] = $term->slug;
          }
        });
      }
    }
  }
}

class ColumnBuilder
{
  public $taxonomy;
  public function __construct($taxonomy) {
    $this->taxonomy = $taxonomy;
  }
  public function after($column_name) {
    foreach ($this->taxonomy->post_types as $post_type) {
      add_filter( "manage_{$post_type}_posts_columns", function($columns) use ($column_name) {
        $ref_column_index = array_search($column_name, array_keys($columns));
        $column = [$this->taxonomy->name => $this->taxonomy->labels['singular_name']];
        $columns = array_slice($columns, 0, $ref_column_index + 1, true) + $column + array_slice($columns, $ref_column_index, count($columns) - 1, true);
        return $columns;
      });
    }
    return $this;
  }
}
