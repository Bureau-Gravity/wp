<?php

namespace BureauGravity\wp;

use BureauGravity\wp\Builder;
use CaseHelper\CaseHelperFactory;

class MetaBox
{
  public static function create($name, $post_type, $callback) {
    $builder = new MetaBuilder($name, $post_type);
    call_user_func_array($callback, [&$builder]);
    $builder->finish();
  }

  public static function buildFieldId($post_type, $box, $field) {
    return $post_type."-".$box."-".$field;
  }
}

class MetaBuilder implements Builder
{
  public $name;
  public $post_type;
  public $fields = [];
  public $display_callbacks = [];

  public function __construct($name, $post_type) {
    $this->name = $name;
    $this->post_type = $post_type;
  }

  public function display($callback) {
    array_push($this->display_callbacks, $callback);
  }

  public function field($name, $type) {
    $field = ['name' => $name, 'type' => $type];
    array_push($this->fields, $field);

    $field_id = $this->field_id($field);
    $field_nonce = $this->field_nonce($field);

    array_push($this->display_callbacks, function($post, $metabox) use ($name, $type, $field_id, $field_nonce) {
      $field_value = get_post_meta($post->ID, $field_id, true);
      $nonce_value = wp_create_nonce($field_id);
      wp_nonce_field($field_nonce, $field_nonce);
      echo "<div>";
      echo "<label style=\"display:block\" for=\"$field_id\">$name</label>";
      echo "<input style=\"width:50%\" type=\"{$type}\" id=\"$field_id\" name=\"$field_id\" value=\"$field_value\">";
      echo "</div>";
    });
  }


  private function display_callback() {
    return function($post, $metabox) {
      foreach ($this->display_callbacks as $callback) {
        call_user_func_array($callback, [$post, $metabox]);
      }
    };
  }

  private function field_id($field) {
    return MetaBox::buildFieldId($this->post_type, $this->name, $field['name']);
  }
  private function field_nonce($field) {
    return $this->field_id($field)."-nonce";
  }

  public function finish() {
    $ch = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_SPACE_CASE);
    add_action( 'add_meta_boxes', function() use ($ch) {
      add_meta_box($ch->toKebabCase($this->name), $this->name, $this->display_callback(), $this->post_type);
    });
    add_action( 'save_post', function($post_id) {
      foreach ($this->fields as $field) {
        $field_id = $this->field_id($field);
        $field_nonce = $this->field_nonce($field);
        if (!isset($_POST[$field_nonce])
        || !wp_verify_nonce($_POST[$field_nonce], $field_nonce)
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || !current_user_can('edit_post', $post_id)) {
          return;
        }

        if (isset($_POST[$field_id])) {
          update_post_meta($post_id, $field_id, $_POST[$field_id]);
        }
      }
    });
  }
}
