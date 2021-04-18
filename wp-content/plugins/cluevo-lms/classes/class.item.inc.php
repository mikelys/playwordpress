<?php
require_once(plugin_dir_path(__DIR__) . "conf/config.inc.php");

class CluevoItem {
  public $item_id = 0;
  public $parent_id = 0;
  public $tree_id;
  public $metadata_id = 0;
  public $level = 0;
  public $name = "";
  public $type = "";
  public $path = "/";
  public $sort_order = 0;
  public $points_worth = 0;
  public $points_required = 0;
  public $practice_points = 0;
  public $level_required = 0;
  public $login_required = 0;
  public $repeat_interval = 0;
  public $repeat_interval_type = "d";
  public $date_added = "";
  public $date_modified = "";
  public $module_id = null;
  public $module_index = null;
  public $access_level;
  public $display_mode = "";
  public $scorm_version = "";
  public $published = 1;
  public $completed = 1;

  public $dependencies = [];
  public $access = true;
  public $access_status = [ "dependencies" => true, "points" => true, "level" => true, "access_level" => false ];
  public $modules = [];
  public $module = null;
  public $children = [];
  public $completed_children;
  public $acl;

  public $new = false;
  public $id;
  public $iframe_index = "";

  public $settings = [];
  public $post = null;

  public function __construct() {
    $this->dependencies = cluevo_get_conf_const('CLUEVO_EMPTY_DEPENDENCY_ARRAY');
  }

  public function __get($prop) {
    switch ($prop) {
      case "module":
      case "module_id":
        if (!empty($this->module) && $this->module > 0) return $this->module;
        if (count($this->modules) > 0)
          return $this->modules[0];
        else
          return null;
      break;
      case "tree_id":
        $parts = explode(trim($this->path, "/"));
        if (!empty($parts)) {
          return (!empty($parts[0])) ? $parts[0] : $this->item_id;
        }
        break;
      case "item_id":
        if (!empty($this->item_id))
          return $this->item_id;
        else
          return $this->tree_id;
        break;
      case "type":
        if (!empty($this->module) && $this->module > 0) return cluevo_get_item_level_name(3);
        return cluevo_get_item_level_name($this->level);
        break;
      default:
        if (property_exists($this, $prop))
          return $this->$prop;
    }
  }

  public static function from_std_class($obj) {
    $result = new CluevoItem();
    foreach (array_keys(get_object_vars($obj)) as $prop) {
      if (property_exists($result, $prop)) {
        $result->$prop = $obj->$prop;
      }
    }

    if (!empty($result->module) > 0 && (int)$result->module > 0) {
      $result->module_id = $result->module;
    }

    if (!empty($result->module) > 0 && (int)$result->module > 0) {
      $module = cluevo_get_module($result->module_id);
      $result->iframe_index = "test";
      if (!empty($module)) {
        $tmpIndex = parse_url($module->module_index, PHP_URL_PATH);
        $dir = cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $module->module_dir . "/";
        if (empty($module->module_index) || ($module->type_id == 1 && !file_exists(cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $module->module_dir . "/" . $tmpIndex))) {
          $result->iframe_index = cluevo_find_module_index(cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $module->module_dir);
        } else {
          $result->iframe_index = cluevo_get_conf_const('CLUEVO_MODULE_URL') . $module->module_dir . "/" . $module->module_index;
        }
        $result->scos = [];
        if (file_exists($dir)) {
          $result->scos = cluevo_find_scos($dir);
          if (!empty($result->scos) && is_array($result->scos)) {
            foreach ($result->scos as $key => $sco) {
              $result->scos[$key]['href'] = cluevo_get_conf_const('CLUEVO_MODULE_URL') . $module->module_dir . "/" . $sco["href"];
            }
          }
        }
      }
    }

    return $result;
  }

  public function get_setting($strKey, $strPrefix = CLUEVO_META_DATA_PREFIX) {
    if (is_array($this->settings) && array_key_exists($strPrefix . $strKey, $this->settings)) {
      return (count($this->settings[$strPrefix . $strKey]) == 1) ? $this->settings[$strPrefix . $strKey][0] : $this->settings[$strPrefix . $strKey];
    }
  }

  public function load_settings() {
    $meta = get_post_meta($this->metadata_id);
    if (!empty($meta) && is_array($meta)) {
      $this->settings = array_filter($meta, function($key) { return strpos($key, CLUEVO_META_DATA_PREFIX) === 0; }, ARRAY_FILTER_USE_KEY);
    }
  }

  public function load_post() {
    $this->post = get_post($this->metadata_id);
  }

  public static function from_array($arr) {
    $result = new CluevoItem();
    foreach ($arr as $prop => $value) {
      if (property_exists($result, $prop)) {
        $result->$prop = $value;
      }
    }

    if (count($result->modules) > 0 && array_key_exists(0, $result->modules)) {
      $result->module_id = $result->modules[0];
    }

    if ($result->type == "module") {
      $module = cluevo_get_module($result->module_id);
      if (!empty($module)) {
        if (empty($module->module_index) || !file_exists(cluevo_get_conf_const('CLUEVO_MODULE_URL') . $module->module_name . "/" . $module->module_index)) {
          $result->iframe_index = cluevo_find_module_index(cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $module->module_dir);
        } else {
          $result->iframe_index = cluevo_get_conf_const('CLUEVO_MODULE_URL') . $module->module_name . "/" . $module->module_index;
        }
      }
    }

    return $result;
  }
}
?>
