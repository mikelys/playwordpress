<?php
class CluevoPermission {
  public $perm_id;
  public $item_id;
  public $perm;
  public $access_level;
  public $date_added;

  public static function from_std_class($obj) {
    $result = new static();
    foreach (array_keys(get_object_vars($obj)) as $prop) {
      if (property_exists($result, $prop)) {
        $result->$prop = $obj->$prop;
      }
    }

    return $result;
  }

  public function save() {
    global $wpdb;
    $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_PERMS;

    if ($this->access_level === null) {
      $sql = "DELETE FROM $table WHERE item_id = %d AND perm = %s";
      $args = [ $this->item_id, $this->perm ];
    } else {
      $sql = "INSERT INTO $table SET item_id = %d, perm = %s, access_level = %d
        ON DUPLICATE KEY UPDATE access_level = %d";
      $args = [ $this->item_id, $this->perm, $this->access_level, $this->access_level ];
    }

    $result = $wpdb->query(
      $wpdb->prepare( $sql, $args )
    );

    return ($result !== false);
  }
}

?>
