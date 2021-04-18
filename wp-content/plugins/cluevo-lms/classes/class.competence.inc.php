<?php
require_once(plugin_dir_path(__DIR__) . "conf/config.inc.php");

class CluevoCompetence {
  public $competence_id = 0;
  public $competence_name = "";
  public $competence_type = "system";
  public $metadata_id = 0;
  public $metadata_post;
  public $user_added_id;
  public $user_added;
  public $date_added;
  public $user_modified_id;
  public $user_modified;
  public $date_modified;

  public $areas = [];
  public $modules = [];

  public function load_areas() {
    $list = [];
    if (!empty($this->areas)) {
      foreach ($this->areas as $a) {
        $area = cluevo_get_competence_area($a);
        if (!empty($area))
          $list[] = $area;
      }
    }

    $this->areas = $list;
  }

  public function load_modules() {
    $list = [];
    if (!empty($this->modules)) {
      foreach ($this->modules as $m) {
        $id = $m[0];
        $coverage = $m[1];
        $module = cluevo_get_module($id);
        if (!empty($module)) {
          $module->competence_coverage = (float)$coverage;
          $list[] = $module;
        }
      }
    }

    $this->modules = $list;
  }

  public function load_metadata() {
    if ($this->metadata_id !== 0) {
      $this->metadata_post = get_post($this->metadata_id);
    }
  }

  public static function from_std_class($obj) {
    $result = new CluevoCompetence();
    foreach (array_keys(get_object_vars($obj)) as $prop) {
      if (property_exists($result, $prop)) {
        if ($prop === "areas" || $prop === "modules") {
          $values =  explode(";", $obj->$prop);
          $result->$prop = [];
          if (!empty($values)) {
            foreach ($values as $v) {
              if (!empty(trim($v)))
                if ($prop === "modules") {
                  $tmp = explode(":", $v);
                  array_push($result->$prop, [ (int)trim($tmp[0]), (float)trim($tmp[1]) ]);
                  //$result->$prop[] = [ (int)trim($tmp[0]), (float)trim($tmp[1]) ];
                } else {
                  array_push($result->$prop, trim($v));
                  //$result->$prop[] = trim($v);
                }
            }
          }
        } else {
          $result->$prop = $obj->$prop;
        }
      }
    }

    return $result;
  }

  public static function from_array($arr) {
    $result = new CluevoCompetence();
    foreach ($arr as $prop => $value) {
      if (property_exists($result, $prop)) {
        if ($prop === "areas" || $prop === "modules") {
          $values =  explode(";", $value);
          $result->$prop = [];
          if (!empty($values)) {
            foreach ($values as $v) {
              if (!empty(trim($v)))
                array_push($result->$prop, trim($v));
                //$result->$prop[] = trim($v);
            }
          }
        } else {
          $result->$prop = $value;
        }
      }
    }

    return $result;
  }
}

?>
