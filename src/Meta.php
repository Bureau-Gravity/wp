<?php

namespace BureauGravity\wp;

use BureauGravity\wp\MetaBox;

class Meta
{
  public static function post($id) {
    return new MetaGetter($id);
  }
}

class MetaGetter
{
  public $id;
  public $box;
  public $field;

  public function __construct($id) {
    $this->id = $id;
  }

  public function box($box) {
    $this->box = $box;
    return $this;
  }

  public function field($field) {
    $this->field = $field;
    return $this;
  }

  public function get() {
    $post_type = get_post_type($this->id);
    return get_post_meta($this->id, MetaBox::buildFieldId($post_type, $this->box, $this->field), true);
  }
}
