<?php
if (!defined("CLUEVO_ACTIVE")) exit;

/**
 * Updates the given item in the database
 *
 * @param CluevoItem $item
 *
 * @return int|false
 */
function cluevo_update_learning_structure_item($item) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $path = cluevo_get_item_path($item->parent_id) . "/";
  $sql = "UPDATE $treeTable SET
    parent_id = %d,
    metadata_id = %d,
    level = %d,
    name = %s,
    path = %s,
    sort_order = %d,
    points_worth = %d,
    points_required = %d,
    practice_points = %d,
    level_required = %d,
    login_required = %d,
    published = %d
    WHERE item_id = %d";
  $result = $wpdb->query(
    $wpdb->prepare(
      $sql, 
      array (
        $item->parent_id,
        $item->metadata_id,
        $item->level,
        $item->name,
        $path,
        $item->sort_order,
        $item->points_worth,
        $item->points_required,
        $item->practice_points,
        $item->level_required,
        $item->login_required,
        $item->published,
        $item->item_id
      )
    )
  );

  $status = ($item->published) ? 'publish' : 'draft';
  wp_update_post([ 'ID' => $item->metadata_id, 'post_status' => $status ]);
  cluevo_update_metadata_page($item);

  cluevo_save_learning_structure_item_settings($item);

  cluevo_create_learning_structure_item_dependencies($item);
  if ($item->type == "module") {
    cluevo_create_module_dependencies($item);
  }

  if (!empty($item->module_id)) {
    cluevo_create_learning_structure_module_item($item->item_id, $item->module_id, $item->display_mode);
  }

  return $result;
}

function cluevo_save_learning_structure_item_settings($item) {
  if (!empty($item->settings) && is_array($item->settings)) {
    foreach ($item->settings as $key => $value) {
      if (empty($value)) {
        delete_post_meta($item->metadata_id, CLUEVO_META_DATA_PREFIX . $key);
      } else {
        update_post_meta($item->metadata_id, CLUEVO_META_DATA_PREFIX . $key, $value);
      }
    }
  }
}

function cluevo_rename_learning_structure_item($intItemId, $strName) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;

  $sql = "UPDATE $table SET name = %s WHERE item_id = %d";

  return $wpdb->query(
    $wpdb->prepare($sql, [ $strName, $intItemId ] )
  );
}

/**
 * Links a tree item id with a module id in the database
 *
 * @param int $intItemId
 * @param int $intModuleId
 *
 * @return int|false
 */
function cluevo_create_learning_structure_module_item($intItemId, $intModuleId, $strDisplayMode = "") {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;

  $sql = "INSERT INTO $table SET item_id = %d, module_id = %d, display_mode = %s ON DUPLICATE KEY UPDATE module_id = %d, display_mode = %s";
  return $wpdb->query($wpdb->prepare($sql, [ $intItemId, $intModuleId, $strDisplayMode, $intModuleId, $strDisplayMode ]));
}

function cluevo_remove_learning_structure_module_item($intItemId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;

  $sql = "DELETE FROM $table WHERE item_id = %d";
  return $wpdb->query($wpdb->prepare($sql, [ $intItemId ]));
}

/**
 * Writes item dependencies to the database
 *
 * @param CluevoItem $item
 *
 * @return void
 */
function cluevo_create_learning_structure_item_dependencies($item) {
  global $wpdb;
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_DEPENDENCIES;

  $wpdb->query(
    $wpdb->prepare("DELETE FROM $depTable WHERE item_id = %d", [ $item->item_id ])
  );

  if (!empty($item->dependencies["other"])) {
    foreach ($item->dependencies["other"] as $type => $deps) {
      foreach ($deps as $d => $access) {
        cluevo_create_learning_structure_item_dependency($item->item_id, $d, $type);
      }
    }
  }
}

/**
 * Writes an item dependency to the database
 *
 * @param int $intItemId
 * @param int $intDepId
 * @param string $strType Can be normal, inherited or blocked
 *
 * @return int|false
 */
function cluevo_create_learning_structure_item_dependency($intItemId, $intDepId, $strType) {
  global $wpdb;
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_DEPENDENCIES;

  $sql = "INSERT IGNORE INTO $depTable SET item_id = %d, dep_id = %d, dep_type = %s";
  return $wpdb->query($wpdb->prepare($sql, [$intItemId, $intDepId, $strType]));
}

/**
 * Creates module dependency entries in the database for given item
 *
 * @param CluevoItem $item
 *
 * @return void
 */
function cluevo_create_module_dependencies($item) {
  global $wpdb;
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULE_DEPENDENCIES;

  $wpdb->query(
    $wpdb->prepare("DELETE FROM $depTable WHERE module_id = %d", [ $item->module_id ])
  );

  foreach ($item->dependencies['modules'] as $type => $deps) {
    foreach ($deps as $d) {
      cluevo_create_module_dependency_db_entry($item->module_id, $d, $type);
    }
  }
}

/**
 * Writes module dependency to the database
 *
 * @param int $intItemId
 * @param int $intDepId
 * @param string $strType Can be either normal, inherited of blocked
 *
 * @return int|false
 */
function cluevo_create_module_dependency_db_entry($intItemId, $intDepId, $strType) {
  global $wpdb;
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULE_DEPENDENCIES;

  $sql = "INSERT IGNORE INTO $depTable SET module_id = %d, dep_id = %d, dep_type = %s";
  return $wpdb->query($wpdb->prepare($sql, [$intItemId, $intDepId, $strType]));
}

/**
 * Removes an item dependency
 *
 * @param int $intItemId
 * @param int $intDepId
 *
 * @return int|false
 */
function cluevo_remove_learning_structure_item_dependency($intItemId, $intDepId) {
  global $wpdb;
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_DEPENDENCIES;

  $sql = "DELETE FROM $depTable WHERE item_id = %d AND dep_id = %d";
  return $wpdb->query($wpdb->prepare($sql, [ $intItemId, $intDepId ]));
}

/**
 * Removes a module dependency
 *
 * @param int $intItemId
 * @param int $intDepId
 *
 * @return int|false
 */
function cluevo_remove_module_dependency($intItemId, $intDepId) {
  global $wpdb;
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULE_DEPENDENCIES;

  $sql = "DELETE FROM $depTable WHERE module_id = %d AND dep_id = %d";
  return $wpdb->query($wpdb->prepare($sql, [$intItemId, $intDepId]));
}

/**
 * Retrieves the dependencies of an item from the database
 *
 * If a user id is passed as well the dependency status is included
 *
 * @param int $intItemId
 * @param int $intUserId (optional)
 *
 * @return array
 */
function cluevo_get_learning_structure_item_dependencies($intItemId, $intUserId = null) {
  global $wpdb;
  $results = [ "normal" => [], "inherited" => [], "blocked" => [], "all" => [] ];
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_DEPENDENCIES;
  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;

  $sql = "SELECT d.*, COUNT(m.module_id) AS module_count, GROUP_CONCAT(m.module_id) AS modules, COUNT(s.module_id) AS completed_module_count, GROUP_CONCAT(s.module_id) AS completed_modules, IF(COUNT(m.module_id) = COUNT(s.module_id), true, false) AS status
    FROM $depTable d
    LEFT JOIN $treeTable t ON t.path LIKE CONCAT('%/', d.dep_id, '%') AND t.level = (SELECT MAX(level) FROM $typeTable)
    LEFT JOIN $moduleTable m ON t.item_id = m.item_id AND m.module_id != -1
    LEFT JOIN $stateTable s ON ISNULL(s.user_id) OR (s.module_id = m.module_id AND s.user_id = %d AND s.completion_status = 'completed' AND s.success_status = 'passed' AND s.attempt_id = (SELECT MAX(attempt_id) FROM $stateTable WHERE module_id = s.module_id AND user_id = %d AND success_status = 'passed' AND completion_status = 'completed' LIMIT 1))
    WHERE d.item_id = %d
    GROUP BY d.item_id, d.dep_id";

  $rows = $wpdb->get_results(
    $wpdb->prepare(
      $sql,
      [ $intUserId, $intUserId, $intItemId ]
    ), ARRAY_A);

  if (!empty($rows)) {
    foreach ($rows as $row) {
      if (!array_key_exists($row['dep_type'], $results))
        $results[$row['dep_type']] = [];

      $results[$row['dep_type']][$row["dep_id"]] = false;

      if (!empty($intUserId) && !empty($row["dep_id"]))
        $results[$row['dep_type']][$row["dep_id"]] = $row['status'];

      if (!empty($row["dep_id"]) && ($row["dep_type"] == "normal" || $row["dep_type"] == "inherited")) {
        $results['all'][$row["dep_id"]] = false;

        if (!empty($intUserId) && !empty($row["dep_id"]))
          $results['all'][$row["dep_id"]] = $row['status'];
      }
    }
  }

  return $results;
}

/**
 * Retrieves the module dependencies of a given item id
 *
 * @param int $itemId
 *
 * @return array
 */
function cluevo_get_module_dependencies($intItemId, $arrCompleted = []) {
  global $wpdb;
  $results = [ "normal" => [], "inherited" => [], "blocked" => [], "all" => [] ];
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULE_DEPENDENCIES;
  $sql = "SELECT * FROM $depTable WHERE module_id = %d";
  $rows = $wpdb->get_results($wpdb->prepare($sql, [$intItemId]), ARRAY_A);
  if (!empty($rows)) {
    foreach ($rows as $row) {
      if (!array_key_exists($row['dep_type'], $results))
        $results[$row['dep_type']] = [];

      $results[$row['dep_type']][$row['dep_id']] = false;
      if (!empty($row["dep_id"]) && in_array($row["dep_id"], $arrCompleted)) $results[$row['dep_type']][$row['dep_id']] = true;

      if (!empty($row["dep_id"]) && ($row["dep_type"] == "normal" || $row["dep_type"] == "inherited")) {
        $results['all'][$row['dep_id']] = false;
        if (in_array($row["dep_id"], $arrCompleted)) $results["all"][$row['dep_id']] = true;
      }
    }
  }

  return $results;
}

/**
 * Checks whether a module exists in the database
 *
 * @param mixed $mixedModule Can either be a module id of module name
 *
 * @return bool
 */
function cluevo_module_exists($mixedModule, $strLang = "") {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;
  $sql = "SELECT COUNT(*) FROM $table WHERE ";
  $sql .= (is_numeric($mixedModule)) ? "module_id = %d" : "module_name = %s";
  $parms = [ $mixedModule ];
  if (!empty($strLang)) {
    $sql .= " AND lang_code = %s";
    $parms[] = $strLang;
  }
  $result = $wpdb->get_var($wpdb->prepare($sql, $parms));
  return ($result > 0);
}

/**
 * Retrieves a module entry from the database
 *
 * @param mixed $mixedId Can either be a module id or module name
 *
 * @return object|null
 */
function cluevo_get_module($mixedId, $strLangCode = "") {
  global $wpdb;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;
  $moduleTypeTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_TYPES;
  $sql = "SELECT m.*, LOWER(t.type_name) AS type_name, t.type_description
    FROM $moduleTable m
    LEFT JOIN $moduleTypeTable t ON m.type_id = t.type_id
    WHERE ";
  $sql .= (is_numeric($mixedId)) ? "module_id = %d" : "module_name = %s";
  $sql .= " AND lang_code = %s";
  $row = $wpdb->get_row($wpdb->prepare($sql, [ $mixedId, $strLangCode ]), OBJECT);
  return $row;
}

/**
 * Retrieves learning structure items from the database
 *
 * Results can be paginated, if a user id is supplied dependency status is included
 *
 * @param int $intItemId (optional)
 * @param int $intUserId (optional)
 * @param int $intPage (optional)
 * @param int $intPerPage (optional)
 *
 * @return array
 */
function cluevo_get_learning_structure_items($intItemId = 0, $intUserId = null, $intPage = 0, $intPerPage = null) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $permTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_PERMS;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $path = "/$intItemId/%";
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
  $wpUserTable = $wpdb->users;
  $collate = $wpdb->collate;

  $from = $treeTable;
  $args = [];
  if (current_user_can("administrator")) {
    $from = "(
      SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
      FROM $treeTable tree
      LEFT JOIN $permTable p ON tree.item_id = p.item_id 
      )";
  } else {
    if (empty($intUserId)) {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN $permTable p ON tree.item_id = p.item_id 
        WHERE p.perm = CONCAT('g:', %d) OR ISNULL(p.perm)
        ORDER BY access_level DESC
    )";
      $args = [ CLUEVO_DEFAULT_GROUP_GUEST ];
    } else {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, MAX(p.access_level) AS access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN (
          SELECT p.perm_id, p.item_id, p.perm, p.access_level
          FROM $permTable p
          INNER JOIN (
            SELECT item_id, MAX(access_level) AS access_level
            FROM $permTable
            GROUP BY item_id
          ) p2 ON p.item_id = p2.item_id AND p2.access_level >= p.access_level
        ) p ON tree.item_id = p.item_id 
        WHERE ISNULL(p.perm) OR p.perm = CONCAT('u:', %d) OR p.perm IN (
          SELECT CONCAT('g:', g.group_id)
          FROM $groupTable g
          LEFT JOIN $wpUserTable wp ON LOCATE('@', g.group_name) AND wp.user_email COLLATE $collate RLIKE g.group_name
          LEFT JOIN $usersToGroupsTable utg ON g.group_id = utg.group_id 
          WHERE user_id = %d OR ID = %d
      )
      GROUP BY tree.item_id
      ORDER BY access_level DESC
    )";
      $args = [ $intUserId, $intUserId, $intUserId ];
    }
  }

  $sql = "SELECT t.*,
    COUNT(COALESCE(t2.item_id, t.item_id)) AS 'module_count',
    GROUP_CONCAT(m.module_id) AS 'modules',
    im.module_id AS 'module',
    TRUE AS 'access',
    it.type,
    COUNT(s.module_id) AS completed_module_count,
    GROUP_CONCAT(s.module_id) AS completed_modules,
    IF(COUNT(COALESCE(t2.item_id, t.item_id)) = COUNT(s.module_id), true, false) AS completed,
    m.display_mode
    FROM $from t
    INNER JOIN $typeTable it ON t.level = it.level
    LEFT JOIN $treeTable t2 ON t2.path LIKE CONCAT('%/', t.item_id, '/%')
    LEFT JOIN $moduleTable im ON t.item_id = im.item_id AND im.module_id > -1
    LEFT JOIN $moduleTable m ON t2.item_id = m.item_id OR t.item_id = m.item_id
    LEFT JOIN $stateTable s ON s.module_id = m.module_id AND s.user_id = %d AND s.completion_status = 'completed' AND s.success_status = 'passed' AND s.attempt_id = (SELECT MAX(attempt_id) FROM $stateTable WHERE module_id = s.module_id AND user_id = %d AND success_status = 'passed' AND completion_status = 'completed' LIMIT 1)
    WHERE t.path LIKE %s ";
  if (!current_user_can('administrator')) $sql .= "AND t.published = 1 ";
  $sql .= "GROUP BY t.item_id
    ORDER BY t.sort_order";

  if (!empty($intPerPage) && is_numeric($intPage) && is_numeric($intPerPage)) {
    $limit = $intPage * $intPerPage;
    $sql .= " LIMIT $limit, $intPerPage";
  }

  $args[] = $intUserId;
  $args[] = $intUserId;
  $args[] = $path;

  $result = $wpdb->get_results(
    $wpdb->prepare(
      $sql, $args
    ), OBJECT
  );

  $completedModules = cluevo_get_users_completed_modules($intUserId);
  foreach ($result as $key => $item) {
    $result[$key]->access_status = [ "dependencies" => true, "points" => true, "level" => true ];
    $result[$key]->modules = explode(",", $item->modules);
    $result[$key]->dependencies["other"] = cluevo_get_learning_structure_item_dependencies($item->item_id, $intUserId);
    if (!empty($item->module) && $item->module > 0) {
      $result[$key]->dependencies["modules"] = cluevo_get_module_dependencies($item->module, $completedModules);
    }
    $access = true;
    if (!empty($intUserId)) {
      $granted = true;
      foreach ($result[$key]->dependencies["other"]["all"] as $dep => $value) {
        if ($value == false) {
          $granted = false;
          $access = false;
          break;
        }
      }
      if ($granted && !empty($result[$key]->dependencies["modules"])) {
        foreach ($result[$key]->dependencies["modules"]["all"] as $dep => $value) {
          if (!in_array($value, $completedModules)) {
            $granted = false;
            $access = false;
            break;
          }
        }
      }
      $result[$key]->access_status["dependencies"] = $granted;
    }

    foreach ($result[$key]->access_status as $type => $value) {
      if ($value == false || ($type == "access_level" && $value < 2)) {
        $access = false;
      }
    }
    $result[$key]->access = ($access) || current_user_can("administrator");

    $children = cluevo_get_learning_structure_item_children($item->item_id, $intUserId);
    $result[$key]->children = [];
    $result[$key]->completed_children = [];
    foreach ($children as $child) {
      $childItem = CluevoItem::from_std_class($child);
      if ($childItem->access_level < 1) continue;
      $result[$key]->children[] = $childItem;
      if ($child->completed)
        $result[$key]->completed_children[] = $child->item_id;
    }

    $obj = CluevoItem::from_std_class($item);
    $result[$key] = $obj;

  }

  return $result;
}

/**
 * Returns an array with pagination information for an item id
 *
 * @param int $intItemId
 * @param int $intUserId (optional)
 * @param int $intPage (optional)
 * @param int $intPerPage (optional)
 */
function cluevo_get_learning_structure_items_pagination($intItemId, $intUserId = null, $intPage = 0, $intPerPage = null) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $sql = "SELECT COUNT(*)
    FROM $treeTable t
    INNER JOIN $typeTable it ON t.level = it.level
    LEFT JOIN $treeTable t2 ON t2.path LIKE CONCAT('%/', t.item_id, '/%')
    LEFT JOIN $moduleTable m ON t2.item_id = m.item_id OR t.item_id = m.item_id
    LEFT JOIN $stateTable s ON s.module_id = m.module_id AND s.user_id = %d AND s.completion_status = 'completed' AND s.success_status = 'passed' AND s.attempt_id = (SELECT MAX(attempt_id) FROM $stateTable WHERE module_id = s.module_id AND user_id = %d AND success_status = 'passed' AND completion_status = 'completed' LIMIT 1)
    WHERE t.path LIKE %s
    GROUP BY t.item_id
    ORDER BY t.sort_order";

  $rows = $wpdb->get_var(
    $wpdb->prepare(
      $sql,
      [ $intUserId, $intUserId, "/$intItemId/%" ]
    ), OBJECT
  );

  $pages = ceil($rows / $intPerPage);

  return [ "pages" => $pages, "items_per_page" => $intPerPage, "items" => $rows ];
}

/**
 * Finds the learning structure item id from a given metadata post id
 *
 * @param int $intMetadataId
 *
 * @return int|null
 */
function cluevo_get_item_id_from_metadata_id($intMetadataId) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "SELECT item_id FROM $treeTable WHERE metadata_id = %d";
  $result = $wpdb->get_var(
    $wpdb->prepare(
      $sql,
      [ $intMetadataId ]
    ));

  return (!empty($result)) ? (int)$result : null;
}

function cluevo_get_metadata_id_from_item_id($intItemId) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "SELECT metadata_id FROM $treeTable WHERE item_id = %d";
  $result = $wpdb->get_var(
    $wpdb->prepare(
      $sql,
      [ $intItemId ]
    ));

  return (!empty($result)) ? (int)$result : null;
}

/**
 * Retrieves the children of a given item id
 *
 * Result can be paginated if parameters are supplied. 
 *
 * @param int $intItemId (optional)
 * @param int $intUserId (optional)
 * @param int $intPage (optional)
 * @param int$intPerPage (optional)
 *
 * @return array
 */
function cluevo_get_learning_structure_item_children($intItemId = null, $intUserId = null, $intPage = 0, $intPerPage = null) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $permTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_PERMS;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $wpUserTable = $wpdb->users;
  $collate = $wpdb->collate;

  $path = (empty($intItemId)) ? "/" : "%/$intItemId/";

  $from = $treeTable;
  $args = [];
  if (current_user_can("administrator")) {
    $from = "(
      SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
      FROM $treeTable tree
      LEFT JOIN $permTable p ON tree.item_id = p.item_id 
    )";
  } else {
    if (empty($intUserId)) {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN $permTable p ON tree.item_id = p.item_id 
        WHERE p.perm = CONCAT('g:', %d) OR ISNULL(p.perm)
        ORDER BY access_level DESC
    )";
      $args = [ CLUEVO_DEFAULT_GROUP_GUEST ];
    } else {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, MAX(p.access_level) AS access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN (
          SELECT p.perm_id, p.item_id, p.perm, p.access_level
          FROM $permTable p
          INNER JOIN (
            SELECT item_id, MAX(access_level) AS access_level
            FROM $permTable
            GROUP BY item_id
          ) p2 ON p.item_id = p2.item_id AND p2.access_level >= p.access_level
        ) p ON tree.item_id = p.item_id 
        WHERE ISNULL(p.perm) OR p.perm = CONCAT('u:', %d) OR p.perm IN (
          SELECT CONCAT('g:', g.group_id)
          FROM $groupTable g
          LEFT JOIN $wpUserTable wp ON LOCATE('@', g.group_name) AND wp.user_email COLLATE $collate RLIKE g.group_name
          LEFT JOIN $usersToGroupsTable utg ON g.group_id = utg.group_id 
          WHERE user_id = %d OR ID = %d
      )
      GROUP BY tree.item_id
      ORDER BY access_level DESC
    )";
      $args = [ $intUserId, $intUserId, $intUserId ];
    }
  }

  $sql = "SELECT t.*,
      COUNT(DISTINCT m.module_id) AS 'module_count',
      GROUP_CONCAT(DISTINCT m.module_id) AS 'modules',
      im.module_id AS 'module',
      TRUE AS 'access',
      it.type,
      COUNT(DISTINCT s.module_id) AS completed_module_count,
      GROUP_CONCAT(DISTINCT s.module_id) AS completed_modules,
      IF(COUNT(COALESCE(t2.item_id, t.item_id)) = COUNT(s.module_id), TRUE, FALSE) AS completed,
      m.display_mode
    FROM $from t
    INNER JOIN $typeTable it ON
      t.level = it.level
    LEFT JOIN $treeTable t2 ON
      t2.path LIKE CONCAT('%/', t.item_id, '/%')
    LEFT JOIN $moduleTable im ON t.item_id = im.item_id AND im.module_id > -1
    LEFT JOIN $moduleTable m ON
      t2.item_id = m.item_id OR
      t.item_id = m.item_id
    LEFT JOIN $stateTable s ON
      s.module_id = m.module_id AND
      s.user_id = %d AND
      s.completion_status = 'completed' AND
      s.success_status = 'passed' AND
      s.attempt_id = (
        SELECT MAX(attempt_id)
        FROM $stateTable
        WHERE
          module_id = s.module_id AND
          user_id = %d AND
          success_status = 'passed' AND
          completion_status = 'completed'
          LIMIT 1
      )
    WHERE t.path LIKE %s ";
    if (!current_user_can('administrator')) $sql .= "AND t.published = 1 ";
    $sql .= "GROUP BY t.item_id
    ORDER BY t.sort_order";

  //echo "<pre>children for: $intItemId</pre>";
  //echo "<pre>";
  //echo "$sql\n";

  if (!empty($intPerPage) && is_numeric($intPage) && is_numeric($intPerPage)) {
    $limit = $intPage * $intPerPage;
    $sql .= " LIMIT $limit, $intPerPage";
  }

  $args[] = $intUserId;
  $args[] = $intUserId;
  $args[] = $path;

  //print_r($args);
  //echo "</pre>";

  $result = $wpdb->get_results(
    $wpdb->prepare(
      $sql, $args
    ), OBJECT
  );

  $completedModules = cluevo_get_users_completed_modules($intUserId);
  foreach ($result as $key => $item) {
    $result[$key]->access_status = [ "dependencies" => true, "points" => true, "level" => true ];
    $result[$key]->modules = explode(",", $item->modules);
    $result[$key]->dependencies["other"] = cluevo_get_learning_structure_item_dependencies($item->item_id, $intUserId);

    if (!empty($item->module) && $item->module > 0) {
      $result[$key]->dependencies["modules"] = cluevo_get_module_dependencies($item->module, $completedModules);
    }

    $granted = true;
    $access = true;
    foreach ($result[$key]->dependencies["other"]["all"] as $dep => $value) {
      if ($value == false) {
        $granted = false;
        $access = false;
        break;
      }
    }
    if ($granted && !empty($result[$key]->dependencies["modules"])) {
      foreach ($result[$key]->dependencies["modules"]["all"] as $dep => $value) {
        if (!in_array($value, $completedModules)) {
          $granted = false;
          $access = false;
          break;
        }
      }
    }
    $result[$key]->access_status["dependencies"] = $granted;
    $access_level = 0;
    if (current_user_can('administrator')) {
      $access_level = 999;
    } else {
      $tmpLevel = (!empty($result[$key]->access_level)) ? $result[$key]->access_level : 0;
      if ($tmpLevel) {
        $access_level = (int)$result[$key]->access_level;
      }
    }
    $result[$key]->access_level = $access_level;
    $result[$key]->access_status["access_level"] = $access_level;

    foreach ($result[$key]->access_status as $type => $value) {
      if ($value == false || ($type == "access_level" && $value < 2)) {
        $access = false;
      }
    }
    $result[$key]->access = ($access || current_user_can("administrator"));;

    $children = cluevo_get_learning_structure_item_children($item->item_id, $intUserId);
    $result[$key]->children = [];
    $result[$key]->completed_children = [];
    foreach ($children as $child) {
      if (!empty($child->module) && $child->module > 0) {
        $child->completed = cluevo_user_completed_module($intUserId, $child->module_id);
      }
      $childItem = CluevoItem::from_std_class($child);
      if ($childItem->access_level < 1) continue;
      $result[$key]->children[] = $childItem;
      if ($child->completed)
        $result[$key]->completed_children[] = $child->item_id;
    }
    $result[$key]->completed = (count($result[$key]->completed_children) == count($result[$key]->children));
  }


  $list = [];
  foreach ($result as $obj) {
    $list[] = CluevoItem::from_std_class($obj);
  }

  return $list;
}

/**
 * Returns pagination information for the supplied item id children
 *
 * @param int $intItemId (optional)
 * @param int $intPerPage (optional)
 *
 * @return array
 */
function cluevo_get_learning_structure_item_children_pagination($intItemId = null, $intPerPage = 100) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;

  $sql = "SELECT COUNT(t.item_id)
      FROM $treeTable t
      WHERE t.path LIKE %s";

  $path = (empty($intItemId)) ? "/" : "%/$intItemId/";

  $rows = $wpdb->get_var(
    $wpdb->prepare(
      $sql, $path
    )
  );

  $pages = ceil($rows / $intPerPage);

  return [ "pages" => $pages, "items_per_page" => $intPerPage, "items" => $rows ];
}

/**
 * Retrieves a specific learning structure item from the database
 *
 * Includes access status and dependency status if a user id is passed
 *
 * @param int $intItemId
 * @param int $intUserId (optional)
 *
 * @return CluevoItem|false
 */
function cluevo_get_learning_structure_item($intItemId, $intUserId = null) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $permTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_PERMS;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $wpUserTable = $wpdb->users;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $collate = $wpdb->collate;

  $from = $treeTable;
  $args = [];
  if (current_user_can("administrator")) {
    $from = "(
      SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
      FROM $treeTable tree
      LEFT JOIN $permTable p ON tree.item_id = p.item_id 
    )";
  } else {
    if (empty($intUserId)) {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN $permTable p ON tree.item_id = p.item_id 
        WHERE p.perm = CONCAT('g:', %d) OR ISNULL(p.perm)
        ORDER BY access_level DESC
    )";
      $args = [ CLUEVO_DEFAULT_GROUP_GUEST ];
    } else {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, MAX(p.access_level) AS access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN (
          SELECT p.perm_id, p.item_id, p.perm, p.access_level
          FROM $permTable p
          INNER JOIN (
            SELECT item_id, MAX(access_level) AS access_level
            FROM $permTable
            GROUP BY item_id
          ) p2 ON p.item_id = p2.item_id AND p2.access_level >= p.access_level
        ) p ON tree.item_id = p.item_id 
        WHERE ISNULL(p.perm) OR p.perm = CONCAT('u:', %d) OR p.perm IN (
          SELECT CONCAT('g:', g.group_id)
          FROM $groupTable g
          LEFT JOIN $wpUserTable wp ON LOCATE('@', g.group_name) AND wp.user_email COLLATE $collate RLIKE g.group_name
          LEFT JOIN $usersToGroupsTable utg ON g.group_id = utg.group_id 
          WHERE user_id = %d OR ID = %d
      )
      GROUP BY tree.item_id
      ORDER BY access_level DESC
    )";
      $args = [ $intUserId, $intUserId, $intUserId ];
    }
  }

  $sql = "SELECT t.*,
    COUNT(DISTINCT m.module_id) AS 'module_count',
    GROUP_CONCAT(DISTINCT m.module_id) AS 'modules',
    im.module_id AS 'module',
    TRUE AS 'access',
    it.type, COUNT(DISTINCT s.module_id) AS completed_module_count,
    GROUP_CONCAT(DISTINCT s.module_id) AS completed_modules,
    IF(COUNT(COALESCE(t2.item_id, t.item_id)) = COUNT(s.module_id), TRUE, FALSE) AS completed,
    m.display_mode
    FROM $from t
    INNER JOIN $typeTable it ON
      t.level = it.level
    LEFT JOIN $moduleTable im ON t.item_id = im.item_id AND im.module_id > -1
    LEFT JOIN $treeTable t2 ON
      t2.path LIKE CONCAT('%/', t.item_id, '/%')
    LEFT JOIN $moduleTable m ON
      t2.item_id = m.item_id OR t.item_id = m.item_id
    LEFT JOIN $stateTable s ON
      s.module_id = m.module_id AND
      s.user_id = %d AND
      s.completion_status = 'completed' AND
      s.success_status = 'passed' AND
      s.attempt_id = (
        SELECT MAX(attempt_id)
        FROM $stateTable
        WHERE
          module_id = s.module_id AND
          user_id = %d AND
          success_status = 'passed' AND
          completion_status = 'completed'
        LIMIT 1
      )
    WHERE t.item_id = %d
    GROUP BY t.item_id
    ORDER BY t.sort_order";

  $args[] = $intUserId;
  $args[] = $intUserId;
  $args[] = $intItemId;

  $result = $wpdb->get_row(
    $wpdb->prepare(
      $sql, $args
    ), OBJECT
  );

  if (!empty($result)) {
    $result->dependencies = [ 'other' => [], 'modules' => [] ];
    $result->dependencies['other'] = cluevo_get_learning_structure_item_dependencies($intItemId, $intUserId);
    $result->access_status = [ "dependencies" => true, "points" => true, "level" => true, "access_level" => false ];
    $result->modules = explode(",", $result->modules);
    if (!empty($result->module) && $result->module > 0) {
      $completedModules = cluevo_get_users_completed_modules($intUserId);
      $result->dependencies['modules'] = cluevo_get_module_dependencies($result->module, $completedModules);
    }

    $granted = true;
    $access = true;
    foreach ($result->dependencies['other']["all"] as $dep => $value) {
      if ($value == false) {
        $granted = false;
        $access = false;
        break;
      }
    }
    if ($granted && !empty($result->dependencies["modules"])) {
      foreach ($result->dependencies["modules"]["all"] as $dep => $value) {
        if (!in_array($value, $completedModules)) {
          $granted = false;
          $access = false;
          break;
        }
      }
    }
    $result->access_status["dependencies"] = $granted;

    $access_level = 0;
    if (current_user_can('administrator')) {
      $access_level = 999;
    } else {
      $access_level = (!empty($result->access_level)) ? (int)$result->access_level : 0;
    }
    $result->access_level = $access_level;
    $result->access_status["access_level"] = $access_level;

    $access = true;
    foreach ($result->access_status as $type => $value) {
      if ($value == false || ($type == "access_level" && $value < 2)) {
        $access = false;
      }
    }

    $result->access = ($access || current_user_can("administrator"));

    //echo "<pre>get children: $intItemId</pre>";
    $children = cluevo_get_learning_structure_item_children($intItemId, $intUserId);
    $result->children = [];
    $result->completed_children = [];
    foreach ($children as $child) {
      $childItem = CluevoItem::from_std_class($child);
      if ($childItem->access_level < 1) continue;
      $result->children[] = $childItem;
      if ($child->completed)
        $result->completed_children[] = $child->item_id;
    }
    //die(print_r($result));
    //die(print_r(CluevoItem::from_std_class($result)));
    return CluevoItem::from_std_class($result);
  }

  return false;
}

/**
 * Retrieves multiple items from the database
 *
 * Includes dependency/access status if a user is specified
 *
 * @param int $arrDeps
 * @param int $intUserId (optional)
 *
 * @return array
 */
function cluevo_get_multiple_learning_structure_items($arrDeps, $intUserId = null) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $sql = "SELECT t.*, COUNT(COALESCE(t2.item_id, t.item_id)) AS 'module_count', GROUP_CONCAT(m.module_id) AS 'modules', TRUE AS 'access', it.type, COUNT(s.module_id) AS completed_module_count, GROUP_CONCAT(s.module_id) AS completed_modules, IF(COUNT(COALESCE(t2.item_id, t.item_id)) = COUNT(s.module_id), true, false) AS completed
    FROM $treeTable t
    INNER JOIN $typeTable it ON t.level = it.level
    LEFT JOIN $treeTable t2 ON t2.path LIKE CONCAT('%/', t.item_id, '/%')
    LEFT JOIN $moduleTable m ON t2.item_id = m.item_id OR t.item_id = m.item_id
    LEFT JOIN $stateTable s ON s.module_id = m.module_id AND s.user_id = %d AND s.completion_status = 'completed' AND s.success_status = 'passed' AND s.attempt_id = (SELECT MAX(attempt_id) FROM $stateTable WHERE module_id = s.module_id AND user_id = %d AND success_status = 'passed' AND completion_status = 'completed' LIMIT 1)
    WHERE t.item_id IN (" . implode(", ", array_fill(0, count($arrDeps), "%s")) . ")
    GROUP BY t.item_id
    ORDER BY t.sort_order";
  //die($sql);

  $parms = [ $intUserId, $intUserId ];
  foreach($arrDeps as $d) {
    $parms[] = $d;
  }
  //die(print_r($parms));

  $result = $wpdb->get_results(
    $wpdb->prepare(
      $sql,
      $parms
    ), ARRAY_A
  );

  foreach ($result as $key => $item) {
    $result[$key]["access_status"] = [ "dependencies" => true, "points" => true, "level" => true ];
    $result[$key]["modules"] = explode(",", $item["modules"]);
    $result[$key]["dependencies"] = cluevo_get_learning_structure_item_dependencies($item["item_id"], $intUserId);
    if (!empty($intUserId)) {
      $granted = true;
      foreach ($result[$key]["dependencies"]["all"] as $dep => $value) {
        if ($value == false) {
          $granted = false;
          break;
        }
      }
      $result[$key]["access_status"]["dependencies"] = $granted;
    }

    $access = true;
    foreach ($result[$key]["access_status"] as $type => $value) {
      if ($value == false) {
        $access = false;
      }
    }
    $result[$key]["access"] = $access;

    $children = cluevo_get_learning_structure_item_children($item["item_id"], $intUserId);
    $result[$key]["children"] = [];
    $result[$key]["completed_children"] = [];
    foreach ($children as $child) {
      $childItem = CluevoItem::from_std_class($child);
      if ($childItem->access_level < 1) continue;
      $result[$key]["children"][] = $childItem;
      if ($child["completed"])
        $result[$key]["completed_children"][] = $child["item_id"];
    }

  }

  return $result;
}

/**
 * Retrieves a learning structure item by it's metadata post id
 *
 * Includes depedency/access status if a user is specified
 *
 * @param int $intMetadataId
 * @param int $intUserId (optional)
 *
 * @return object
 */
function cluevo_get_learning_structure_item_from_metadata_id($intMetadataId, $intUserId = null) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

    $sql = "SELECT t.*, COUNT(DISTINCT m.module_id) AS 'module_count', GROUP_CONCAT(DISTINCT m.module_id) AS 'modules', TRUE AS 'access', it.type, COUNT(DISTINCT s.module_id) AS completed_module_count, GROUP_CONCAT(DISTINCT s.module_id) AS completed_modules, IF(COUNT(COALESCE(t2.item_id, t.item_id)) = COUNT(s.module_id), TRUE, FALSE) AS completed, m.display_mode
    FROM $treeTable t
    INNER JOIN $typeTable it ON t.level = it.level
    LEFT JOIN $treeTable t2 ON t2.path LIKE CONCAT('%/', t.item_id, '/%')
    LEFT JOIN $moduleTable m ON t2.item_id = m.item_id OR t.item_id = m.item_id
    LEFT JOIN $stateTable s ON s.module_id = m.module_id AND s.user_id = %d AND s.completion_status = 'completed' AND s.success_status = 'passed' AND s.attempt_id = (SELECT MAX(attempt_id) FROM $stateTable WHERE module_id = s.module_id AND user_id = %d AND success_status = 'passed' AND completion_status = 'completed' LIMIT 1)
    WHERE t.metadata_id = %d
    GROUP BY t.item_id
    ORDER BY t.sort_order";

  // TODO: Create CluevoItem, return false|null if not successfull
  $result = $wpdb->get_row(
    $wpdb->prepare(
      $sql,
      [ $intUserId, $intUserId, $intMetadataId ]
    )
  );

  if (!empty($result)) {
    $result->dependencies = cluevo_get_learning_structure_item_dependencies($result->item_id, $intUserId);
    $result->access_status = [ "dependencies" => true, "points" => true, "level" => true ];
    $result->modules = explode(",", $result->modules);

    $access = true;
    if (!empty($intUserId)) {
      $granted = true;
      foreach ($result->dependencies["all"] as $dep => $value) {
        if ($value == false) {
          $granted = false;
          $access = false;
          break;
        }
      }
      $result->access_status["dependencies"] = $granted;
    }

    foreach ($result->access_status as $type => $value) {
      if ($value == false || ($type == "access_level" && $value < 2)) {
        $access = false;
      }
    }
    $result->access = ($access || current_user_can("administrator"));

    $children = cluevo_get_learning_structure_item_children($result->item_id, $intUserId);
    $result->children = [];
    $result->completed_children = [];
    foreach ($children as $child) {
      $childItem = CluevoItem::from_std_class($child);
      if ($childItem->access_level < 1) continue;
      $result->children[] = $childItem;
      if ($child->completed)
        $result->completed_children[] = $child->item_id;
    }
  }

  return $result;
}

/**
 * Deletes a learning structure item
 *
 * @param int $intItemId
 *
 * @return bool
 */
function cluevo_remove_learning_structure_item($intItemId) {
  global $wpdb;
  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $depTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_DEPENDENCIES;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;

  $sql = "DELETE t, m, d FROM $treeTable t
    LEFT JOIN $moduleTable m ON t.item_id = m.item_id
    LEFT JOIN $depTable d ON t.item_id = d.item_id
    LEFT JOIN $depTable d2 ON t.item_id = d.dep_id
    WHERE t.item_id = %d OR t.parent_id = %d OR t.path LIKE CONCAT('%/', %s, '/%')";

  $result = $wpdb->query(
    $wpdb->prepare(
      $sql,
      [ $intItemId, $intItemId, $intItemId ]
    )
  );

  return ($result !== false);
}

/**
 * Builds a hierachical array from a flat array
 *
 * @param array $array
 * @param int $intParentId (optional)
 *
 * @return array
 */
function cluevo_build_tree(&$array, $intParentId = 0) {
  $items = [];
  foreach ($array as $key => $item) {
    if ($item->parent_id == $intParentId) {
      $result = cluevo_build_tree($array, $item->item_id);
      $children = [];

      if ($result) {
        foreach ($result as $c) {
          $children[] = $c;
        }
      }
      $item->id = $item->item_id;
      $item->children = $children;
      $item->dependencies["other"] = cluevo_get_learning_structure_item_dependencies($item->item_id);
      $item->dependencies["modules"] = cluevo_get_module_dependencies($item->module_id);
      $items[] = $item;
      unset($array[$key]);
    }
  }

  return $items;
}

/**
 * Transforms an array of objects/arrays into a hierarchical array
 *
 * @param mixed $array
 * @param string $strIdProp
 * @param string $strParentProp
 * @param string $strChildProp
 * @param int $intParentId
 *
 * @return array
 */
function cluevo_array_to_tree(&$array, $intParentId = 0, $strIdProp = "item_id", $strParentProp = "parent_id", $strChildProp = "children") {
  $items = [];
  foreach ($array as $key => $item) {
    if (is_array($item)) {
      if ($item[$strParentProp] == $intParentId) {
        $result = cluevo_array_to_tree($array, $item[$strIdProp], $strIdProp, $strParentProp, $strChildProp);
        $children = [];

        if ($result) {
          foreach ($result as $c) {
            $children[] = $c;
          }
        }
        $item[$strChildProp]= $children;
        $items[] = $item;
        unset($array[$key]);
      }
    } else {
      if ($item->{$strParentProp} == $intParentId) {
        $result = cluevo_array_to_tree($array, $item->{$strIdProp}, $strIdProp, $strParentProp, $strChildProp);
        $children = [];

        if ($result) {
          foreach ($result as $c) {
            $children[] = $c;
          }
        }
        $item->{$strChildProp}= $children;
        $items[] = $item;
        unset($array[$key]);
      }
    }
  }

  return $items;
}

/**
 * Retrieves all root level items (course groups)
 *
 * @return array|null
 */
function cluevo_get_learning_structures($intUserId = null) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_MODULES;
  $typeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES;
  $stateTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $permTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE_PERMS;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $wpUserTable = $wpdb->users;
  $collate = $wpdb->collate;

  $from = $treeTable;
  $args = [];
  if (current_user_can("administrator")) {
    $from = "(
      SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
      FROM $treeTable tree
      LEFT JOIN $permTable p ON tree.item_id = p.item_id 
      )";
  } else {
    if (empty($intUserId)) {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, p.access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN $permTable p ON tree.item_id = p.item_id 
        WHERE p.perm = CONCAT('g:', %d) OR ISNULL(p.perm)
        ORDER BY access_level DESC
    )";
      $args = [ CLUEVO_DEFAULT_GROUP_GUEST ];
    } else {
      $from = "(
        SELECT DISTINCT p.perm_id, p.perm, MAX(p.access_level) AS access_level, tree.*
        FROM $treeTable tree
        LEFT JOIN (
          SELECT p.perm_id, p.item_id, p.perm, p.access_level
          FROM $permTable p
          INNER JOIN (
            SELECT item_id, MAX(access_level) AS access_level
            FROM $permTable
            GROUP BY item_id
          ) p2 ON p.item_id = p2.item_id AND p2.access_level >= p.access_level
        ) p ON tree.item_id = p.item_id 
        WHERE ISNULL(p.perm) OR p.perm = CONCAT('u:', %d) OR p.perm IN (
          SELECT CONCAT('g:', g.group_id)
          FROM $groupTable g
          LEFT JOIN $wpUserTable wp ON LOCATE('@', g.group_name) AND wp.user_email COLLATE $collate RLIKE g.group_name
          LEFT JOIN $usersToGroupsTable utg ON g.group_id = utg.group_id 
          WHERE user_id = %d OR ID = %d
      )
      GROUP BY tree.item_id
      ORDER BY access_level DESC
    )";
      $args = [ $intUserId, $intUserId, $intUserId ];
    }
  }

  $sql = "SELECT t.*,
    COUNT(DISTINCT m.module_id) AS 'module_count',
    GROUP_CONCAT(DISTINCT m.module_id) AS 'modules',
    TRUE AS 'access',
    it.type, COUNT(DISTINCT s.module_id) AS completed_module_count,
    GROUP_CONCAT(DISTINCT s.module_id) AS completed_modules,
    IF(COUNT(COALESCE(t2.item_id, t.item_id)) = COUNT(s.module_id), TRUE, FALSE) AS completed,
    m.display_mode
    FROM $from t
    INNER JOIN $typeTable it ON
      t.level = it.level
    LEFT JOIN $treeTable t2 ON
      t2.path LIKE CONCAT('%/', t.item_id, '/%')
    LEFT JOIN $moduleTable m ON
      t2.item_id = m.item_id OR t.item_id = m.item_id
    LEFT JOIN $stateTable s ON
      s.module_id = m.module_id AND
      s.user_id = %d AND
      s.completion_status = 'completed' AND
      s.success_status = 'passed' AND
      s.attempt_id = (
        SELECT MAX(attempt_id)
        FROM $stateTable
        WHERE
          module_id = s.module_id AND
          user_id = %d AND
          success_status = 'passed' AND
          completion_status = 'completed'
        LIMIT 1
      )
    WHERE t.parent_id = 0
    GROUP BY t.item_id
    ORDER BY t.sort_order";

  $args[] = $intUserId;
  $args[] = $intUserId;

  //echo "<pre>";
  //echo "$sql\n";
  //print_r($args);
  //echo "</pre>";

  $result = $wpdb->get_results(
    $wpdb->prepare(
      $sql, $args
    ), OBJECT
  );
  $structures = [];
  if (!empty($result)) {
    foreach ($result as $tree) {
      $access_level = 0;
      if (current_user_can('administrator')) {
        $access_level = 999;
      } else {
        $tmpLevel = (!empty($tree->access_level)) ? $tree->access_level : 0;
        if ($tmpLevel) {
          $access_level = (int)$tree->access_level;
        }
      }
      $tree->access_level = $access_level;
      $tree->access_status["access_level"] = $access_level;
      $children = cluevo_get_learning_structure_item_children($tree->item_id, $intUserId);
      $tree->completed_children = [];
      foreach ($children as $child) {
        $childItem = CluevoItem::from_std_class($child);
        if ($childItem->access_level < 1) continue;
        $tree->children[] = $childItem;
        if ($childItem->completed)
          $tree->completed_children[] = $childItem->item_id;
      }
      $structures[] = CluevoItem::from_std_class($tree);
    }
  }
  return $structures;
}

function cluevo_get_learning_structure_items_downwards($intItemId, $intUserId = null) {
  $item = cluevo_get_learning_structure_item($intItemId, $intUserId);
  if (!empty($item)) {
    if (!empty($item->children)) {
      foreach ($item->children as $key => $child) {
        $item->children[$key] = cluevo_get_learning_structure_items_downwards($child->item_id, $intUserId);
      }
    }
  }
  return $item;
}

/**
 * Returns pagination for course groups
 *
 * @param int $intPerPage (optional)
 */
function cluevo_get_learning_structures_pagination($intPerPage = 100) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "SELECT COUNT(*) FROM $table WHERE level = 0";

  $rows = $wpdb->get_var($sql);

  $pages = ceil($rows / $intPerPage);

  return [ "pages" => $pages, "items_per_page" => $intPerPage, "items" => $rows ];
}

/**
 * Creates a new course group
 *
 * @param string $strName
 * @param int $intMetaId (optional)
 *
 * @return int|false
 */
function cluevo_create_learning_structure($strName, $intMetaId = null) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "INSERT IGNORE INTO $table SET name = %s, level = 0, metadata_id = %d";
  $result = $wpdb->query(
    $wpdb->prepare($sql, [ $strName, $intMetaId ])
  );

  if ($result !== false)
    return $wpdb->insert_id;
  else
    return false;
}

/**
 * Updates the name and metadata post id of a course group
 *
 * @param int $intTreeId
 * @param string $strName
 * @param int $intMetaId (optional)
 */
function cluevo_update_learning_structure($intTreeId, $strName, $intMetaId = null) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "UPDATE $table SET name = %s, metadata_id = %d WHERE item_id = %d";
  $result = $wpdb->query(
    $wpdb->prepare($sql, [ $strName, $intMetaId, $intTreeId])
  );
}

/**
 * Returns the string path as array of the given id path
 *
 * Explodes an id path like /1/2/3 into it's components, looks up each
 * item name and returns an array with item names
 *
 * @param string $strPath
 *
 * @return array
 */
function cluevo_get_string_path($strPath) {
  $parts = explode('/', $strPath);
  $path = [];
  foreach ($parts as $id) {
    if (!empty($id)) {
      $name = cluevo_get_item_name($id);
      if (!empty($name)) {
        $path[] = $name;
      }
    }
  }

  return $path;
}

function cluevo_get_item_name($intItemId) {
  global $wpdb;
  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $result = $wpdb->get_row(
    $wpdb->prepare("SELECT name FROM $treeTable WHERE item_id = %d", [ $intItemId ])
  );
  if (empty($result)) return false;
  return $result->name;
}

/**
 * Updates the metadata post of a module
 *
 * @param int $intModuleId
 * @param int $intMetadataId
 *
 * @return int|false
 */
function cluevo_update_module_metadata_id($intModuleId, $intMetadataId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;
  $sql = "UPDATE $table SET metadata_id = %d WHERE module_id = %d";
  $result = $wpdb->query(
    $wpdb->prepare($sql, [ $intMetadataId, $intModuleId ])
  );

  return $result;
}

/**
 * Retrieves the first course group from the database
 *
 * @return int|null
 */
function cluevo_get_first_course_group() {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;

  $sql = "SELECT item_id FROM $table WHERE level = 0 ORDER BY item_id ASC LIMIT 1";

  $result = $wpdb->get_var($sql);

  return (!empty($result)) ? (int)$result : null;
}

/**
 * Retrieves the type name (tree, course, etc.) of an item level
 *
 * @param int $intLevel
 *
 * @return string|null
 */
function cluevo_get_item_level_name($intLevel) {
  global $wpdb;

  $sql = "SELECT type FROM " . $wpdb->prefix . CLUEVO_DB_TABLE_TREE_ITEM_TYPES . " WHERE level = %d";

  return $wpdb->get_var(
    $wpdb->prepare($sql, [ $intLevel ])
  );
}

/**
 * Returns the path of a given item
 *
 * @param int $intItemId
 *
 * @return string|null
 */
function cluevo_get_item_path($intItemId) {
  global $wpdb;

  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "SELECT CONCAT(COALESCE(path, ''), item_id) FROM $treeTable WHERE item_id = %d";
  return $wpdb->get_var($wpdb->prepare($sql, [ $intItemId ] ));
}

/**
 * Retrieves a module id by it's name from the database
 *
 * @param string $strName
 *
 * @return int|null
 */
function cluevo_get_module_id_by_name($strName) {
  global $wpdb;
  $sql = "SELECT module_id FROM " . $wpdb->prefix . CLUEVO_DB_TABLE_MODULES . " WHERE module_name = %s";
  $result = $wpdb->get_var($wpdb->prepare($sql, [$strName]));

  return (!empty($result)) ? (int)$result : null;
}

/**
 * Removes a module from the database
 *
 * @param int $intId
 *
 * @return int|false
 */
function cluevo_remove_module($intId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;
  $sql = "DELETE FROM $table WHERE module_id = %d";
  return $wpdb->query($wpdb->prepare($sql, [$intId]));
}

/**
 * Creates of updates a module's database entry
 *
 * @param int $strModule
 * @param int $intMetadataId
 * @param string $strDir
 * @param string $strZipFile
 * @param string $strIndex
 *
 * @return int|false
 */
function cluevo_create_module($strModule, $intType, $intMetadataId, $strDir, $strZipFile, $strIndex, $strLang = null, $intParentId = null, $strScormVersion = null) {
  global $wpdb;
  $sql = "REPLACE INTO " . $wpdb->prefix . CLUEVO_DB_TABLE_MODULES . " SET module_name = %s, metadata_id = %d, module_dir = %s, module_zip = %s, module_index = %s, lang_code = %s, type_id = %d, scorm_version = %s";
  $parms = [ $strModule, $intMetadataId, $strDir, $strZipFile, $strIndex, $strLang, $intType, $strScormVersion ];

  if (!empty($intParentId)) {
    $sql .= ", module_id = %d";
    $parms[] = $intParentId;
  }

  return $wpdb->query($wpdb->prepare($sql, $parms));
}

function cluevo_set_module_language($intModuleId, $strLangCodeOld, $strLangCodeNew) {
  global $wpdb;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

  //if (!cluevo_module_language_exists($intModuleId, $strLangCodeNew)) {
  $sql = "UPDATE IGNORE $moduleTable SET lang_code = %s WHERE module_id = %d AND lang_code = %s";

  $result = $wpdb->query(
    $wpdb->prepare($sql, [ $strLangCodeNew, $intModuleId, $strLangCodeOld ] )
  );

  return ($result !== false && is_numeric($result) && $result > 0);
  //}

  //return false;
}

function cluevo_module_language_exists($intModuleId, $strLangCode) {
  global $wpdb;
  $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

  $sql = "SELECT COUNT(*) FROM $moduleTable  WHERE module_id = %d AND lang_code = %s";

  $result = $wpdb->get_var(
    $wpdb->prepare($sql, [ $intModuleId, $strLangCode ] )
  );

  return ((int)$result == 0);
}

/**
 * Creates a learning structures database entry
 *
 * Returns the new item id on success
 *
 * @param CluevoItem $item
 *
 * @return int|false
 */
function cluevo_create_learning_structure_item($item) {
  global $wpdb;
  $treeTable = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $path = cluevo_get_item_path($item->parent_id) . "/";
  $data = array(
    "parent_id" => $item->parent_id,
    "metadata_id" => $item->metadata_id,
    "level" => $item->level,
    "name" => $item->name,
    "path" => $path,
    "sort_order" => $item->sort_order,
    "points_worth" => $item->points_worth,
    "points_required" => $item->points_required,
    "practice_points" => $item->practice_points,
    "level_required" => $item->level_required,
    "login_required" => $item->login_required,
    "published" => $item->published
  );

  $result = $wpdb->insert($treeTable, $data);

  if ($result !== false) {
    $insertId = $wpdb->insert_id;
    $item->item_id = $insertId;

    cluevo_save_learning_structure_item_settings($item);
    cluevo_create_learning_structure_item_dependencies($item);
    if ($item->type == "module")
      cluevo_create_module_dependencies($item);

    if (!empty($item->module_id)) {
      cluevo_create_learning_structure_module_item($item->item_id, $item->module_id, $item->display_mode);
    }

    return $insertId;
  }

  return false;
}

function cluevo_update_module($intModuleId, $args) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;
  $parms = [];
  $sql = "UPDATE $table SET ";
  foreach ($args as $col => $value) {
    $sql .= " $col = %s,";
    $parms[] = $value;
  }
  $sql = trim($sql, ',');
  $sql .= " WHERE module_id = %d";
  $parms[] = $intModuleId;

  $result = $wpdb->query(
    $wpdb->prepare($sql, $parms)
  );

  return ($result == 1);
}

function cluevo_get_users_completed_modules($intUserId) {
  if (empty($intUserId)) return [];

  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $results = $wpdb->get_results(
    $wpdb->prepare("SELECT DISTINCT module_id FROM $table WHERE user_id = %d AND ((completion_status = 'completed' AND success_status = 'passed') OR (lesson_status = 'passed'))", [ $intUserId ])
  );
  
  $ids = [];
  if (!empty($results)) {
    foreach ($results as $r) {
      $ids[] = $r->module_id;
    }
    
  }

  return $ids;
}

?>
