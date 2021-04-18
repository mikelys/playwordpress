<?php
if (!defined("CLUEVO_ACTIVE")) exit;

/**
 * Returns a list of the current lms users
 *
 * @return array|object|null
 */
function cluevo_get_lms_users($mixedUsers = null) {
  global $wpdb;

  $lmsUserTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
  $wpUserTable = $wpdb->users;

  $sql = "SELECT lms.*, GROUP_CONCAT(DISTINCT g.group_id) AS \"groups\", GROUP_CONCAT(DISTINCT ugt.group_id) AS trainer_groups, wp.*
    FROM $lmsUserTable lms
    INNER JOIN $wpUserTable wp
      ON lms.user_id = wp.ID
    LEFT JOIN $usersToGroupsTable ug
      ON lms.user_id = ug.user_id
    LEFT JOIN $usersToGroupsTable ugt
      ON lms.user_id = ugt.user_id AND ugt.is_trainer = 1
    LEFT JOIN $groupTable g
      ON ug.group_id = g.group_id";

  $list = array();
  $where = "";
  if (!empty($mixedUsers)) {
    $where = " WHERE ";
    if (!is_array($mixedUsers)) {
      $list[] = $mixedUsers;
    } else {
      $list = $mixedUsers;
    }

    $args = [];
    foreach ($list as $g) {
      $args[] = $g;
      if (is_numeric($g)) {
        $where .= "lms.user_id = %d OR ";
      } else {
        $where .= "(wp.user_login LIKE CONCAT('%', %s, '%') OR wp.user_nicename LIKE CONCAT('%', %s, '%') OR wp.display_name LIKE CONCAT('%', %s, '%')) OR ";
        $args[] = $g;
        $args[] = $g;
      }
    }
    $where = trim($where, " OR ");
  }

  $sql .= " $where GROUP BY lms.user_id";

  if (!empty($mixedUsers)) {
    $results = $wpdb->get_results(
      $wpdb->prepare($sql, $args)
    );
  } else {
    $results = $wpdb->get_results($sql);
  }

  $users = [];
  if (!empty($results)) {
    foreach ($results as $user) {
      $u = CluevoUser::from_std_class($user);
      $u->load_groups();
      $u->load_competence_areas();
      $users[] = $u;
    }
  }

  if (count($users) == 1)
    return $users[0];
  else
    return $users;
}

/**
 * Returns a list of wordpress users matching the search string
 *
 * @param string $strSearch
 *
 * @return array
 */
function cluevo_get_wp_users($strSearch = "") {
  global $wpdb;
  $exclude = $wpdb->get_var("SELECT GROUP_CONCAT(user_id) FROM " . $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA);
  $users = get_users ( array ( "search" => "*$strSearch*", "exclude" => $exclude ));
  return $users;
}

/**
 * Makes a wordpress user a lms user
 *
 * @param int $intUserId
 *
 * @return bool
 */
function cluevo_make_lms_user($intUserId) {
  global $wpdb;

  $user = get_userdata( $intUserId );
  if ( $user === false ) {
    return false;
  } else {
    $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;
    $sql = "INSERT IGNORE INTO $table SET user_id = %d";
    $result = $wpdb->query(
      $wpdb->prepare( $sql, [ $intUserId ] )
    );
    if ($result === 1) {
      cluevo_set_users_group_memberships($intUserId, [ CLUEVO_DEFAULT_GROUP_USER, CLUEVO_DEFAULT_GROUP_GUEST ] );
    }
    return ($result === 1);
  }
}

/**
 * Removes a lms user
 *
 * @param int $intUserId
 *
 * @return bool
 */
function cluevo_delete_lms_user($intUserId) {
  global $wpdb;

  $user = get_userdata( $intUserId );
  if ( $user === false ) {
    return false;
  } else {
    $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;
    $sql = "DELETE FROM $table WHERE user_id = %d";
    $result = $wpdb->query(
      $wpdb->prepare( $sql, [ $intUserId ] )
    );

    $parmTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;
    $parmResult = $wpdb->query(
      $wpdb->prepare(
        "UPDATE $parmTable SET user_deleted = 1, date_user_deleted = NOW() WHERE user_id = %d",
        [ $intUserId ]
      )
    );

    $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
    $groupResult = $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM $groupTable WHERE user_id = %d",
        [ $intUserId ]
      )
    );

    //$progressTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
    //$progressResult = $wpdb->query(
      //$wpdb->prepare(
        //"UPDATE $progressTable SET user_deleted = 1, date_user_deleted = NOW() WHERE user_id = %d",
        //[ $intUserId ]
      //)
    //);

    $expTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_EXP_LOG;
    $expResult = $wpdb->query(
      $wpdb->prepare("DELETE FROM $expTable WHERE user_id = %d", [ $intUserId ] )
    );

    return ($result === 1);
  }
}

/**
 * Creates a trainer from a lms user
 *
 * @param int $intUserId
 *
 * @return bool
 */
function cluevo_make_lms_trainer($intUserId) {
}

/**
 * Checks whether a user is a lms user
 *
 * @param int $intUserId
 *
 * @return bool
 */
function cluevo_is_lms_user($intUserId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;

  $result = $wpdb->get_var(
    $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", [ $intUserId ] )
  );

  return (int)$result === 1;
}

/**
 * Checks whether a user is a trainer
 *
 * @param int $intUserId
 *
 * @return bool
 */
function cluevo_is_lms_trainer($intUserId) {
}

/**
 * cluevo_get_user_groups
 *
 * @return array
 */
function cluevo_get_user_groups($mixedGroups = null) {
  global $wpdb;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
  $wpUsers = $wpdb->users;

  $collate = $wpdb->collate;
  $sql = "SELECT g.*, GROUP_CONCAT(COALESCE(wp.ID, ug.user_id)) AS users, GROUP_CONCAT(CASE WHEN ug.is_trainer = 1 THEN COALESCE(wp.ID, ug.user_id) ELSE NULL END) AS trainers FROM $groupTable g
    LEFT JOIN $usersToGroupsTable ug ON g.group_id = ug.group_id
    LEFT JOIN $wpUsers wp ON LOCATE('@', g.group_name) AND wp.user_email COLLATE $collate RLIKE g.group_name";

  $list = array();
  $where = "";
  if (!empty($mixedGroups)) {
    $where = " WHERE ";
    if (!is_array($mixedGroups)) {
      $list[] = "$mixedGroups";
    } else {
      foreach ($mixedGroups as $g) {
        $list[] = (string)$g;
      }
    }
    foreach ($list as $g) {
      $where .= "g.group_id = CAST(%d AS CHAR) OR ";
    }
    $where = trim($where, " OR ");
  }

  $sql .= " $where GROUP BY g.group_id";

  if (!empty($mixedGroups)) {
    $results = $wpdb->get_results(
      $wpdb->prepare($sql, $list)
    );
  } else {
    $results = $wpdb->get_results($sql);
  }
  $return = array();

  foreach ($results as $row) {
    $g = CluevoGroup::from_std_class($row);
    $return[] = $g;
  }

  return $return;
}

/**
 * cluevo_get_user_group
 *
 * @return CluevoGroup
 */
function cluevo_get_user_group($mixedGroup) {
  global $wpdb;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $sql = "SELECT g.*, GROUP_CONCAT(ug.user_id) AS users, GROUP_CONCAT(CASE WHEN ug.is_trainer = 1 THEN ug.user_id ELSE NULL END) AS trainers FROM $groupTable g
    LEFT JOIN $usersToGroupsTable ug ON g.group_id = ug.group_id
    WHERE g.group_id = %d OR g.group_name = %s
    GROUP BY g.group_id";

  $result = $wpdb->get_row(
    $wpdb->prepare($sql, [ $mixedGroup, $mixedGroup ])
  );

  $group = false;

  if (!empty($result))
    $group = CluevoGroup::from_std_class($result);

  return $group;
}

/**
 * Inserts a new user group
 *
 * @param string $strName
 * @param string $strDesc
 *
 * @return int|bool
 */
function cluevo_create_user_group($strName, $strDesc = "") {
  global $wpdb;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;

  $sql = "INSERT IGNORE INTO $groupTable SET
    group_name = %s,
    group_description = %s";

  $result = $wpdb->query(
    $wpdb->prepare( $sql, [ $strName, $strDesc ] )
  );

  $insertId = $wpdb->insert_id;
  if ($result !== false && is_numeric($insertId) && $insertId != 0)
    return $insertId;
  else
    return false;
}

function cluevo_get_user_group_id($strName) {
  global $wpdb;
  $groupTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;

  $sql = "SELECT group_id FROM $groupTable WHERE group_name = %s";

  return $wpdb->get_var(
    $wpdb->prepare($sql, [ $strName ] )
  );
}

function cluevo_update_user_group($intGroupId, $strName, $strDesc = "") {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;

  if (empty($strName))
    return false;

  $sql = "UPDATE IGNORE $table SET
    group_name = %s,
    group_description = %s
    WHERE group_id = %d";

  $result = $wpdb->query(
    $wpdb->prepare($sql, [ $strName, $strDesc, $intGroupId ])
  );

  return ($result == 1);
}

function cluevo_add_users_to_group($mixedUsers, $intGroupId, $boolOverrideWpUserCheck = false) {
  global $wpdb;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $list = array();
  if (!is_array($mixedUsers)) {
    $list[] = $mixedUsers;
  } else {
    $list = $mixedUsers;
  }

  $sql = "INSERT IGNORE INTO $usersToGroupsTable SET user_id = %d, group_id = %d";
  $results = array();
  if (!empty($list)) {
    foreach ($list as $user) {
      $results[$user] = false;
      if (get_userdata($user) !== false || $boolOverrideWpUserCheck) {
        $result = $wpdb->query(
          $wpdb->prepare($sql, [ $user, $intGroupId ])
        );
        if ($result !== false && is_numeric($result) && $result > 0)
          $results[$user] = true;
      }
    }
  }

  return $results;
}

function cluevo_remove_users_from_group($mixedUsers, $intGroupId) {
  global $wpdb;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $list = array();
  if (!is_array($mixedUsers)) {
    $list[] = $mixedUsers;
  } else {
    $list = $mixedUsers;
  }

  $sql = "DELETE FROM $usersToGroupsTable WHERE user_id = %d AND group_id = %d";
  $results = array();
  if (!empty($list)) {
    foreach ($list as $user) {
      $results[$user] = false;
      if (get_userdata($user) !== false) {
        $result = $wpdb->query(
          $wpdb->prepare($sql, [ $user, $intGroupId ])
        );
        if ($result !== false && is_numeric($result) && $result > 0)
          $results[$user] = true;
      }
    }
  }

  return $results;
}

function cluevo_delete_user_group($intGroupId) {
  global $wpdb;
  $groupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;
  $usersToGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $resultGroups = $wpdb->query(
    $wpdb->prepare("DELETE FROM $groupsTable WHERE group_id = %d AND protected = 0", [ $intGroupId ] )
  );

  if ($resultGroups !== false) {
    $resultUsers = $wpdb->query(
      $wpdb->prepare("DELETE FROM $usersToGroupsTable WHERE group_id = %d", [ $intGroupId ] )
    );
  }

  return (int)$resultGroups + (int)$resultUsers;
}

function cluevo_promote_users_to_group_trainers($mixedUsers, $intGroupId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $list = array();
  if (!is_array($mixedUsers)) {
    $list[] = $mixedUsers;
  } else {
    $list = $mixedUsers;
  }

  $sql = "INSERT INTO $table SET user_id = %d, group_id = %d, is_trainer = 1
    ON DUPLICATE KEY UPDATE is_trainer = 1";

  $results = array();
  foreach ($list as $user) {
    $results[$user] = false;
    if (get_userdata($user) !== false) {
      $result = $wpdb->query(
        $wpdb->prepare($sql, [ $user, $intGroupId ] )
      );

      if ($result !== false && is_numeric($result) && $result > 0)
        $results[$user] = true;
    }
  }

  return $results;
}

function cluevo_remove_group_trainers($mixedUsers, $intGroupId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $list = array();
  if (!is_array($mixedUsers)) {
    $list[] = $mixedUsers;
  } else {
    $list = $mixedUsers;
  }

  $sql = "UPDATE $table
    SET is_trainer = 0
    WHERE user_id = %d AND group_id = %d AND is_trainer = 1";

  $results = array();
  foreach ($list as $user) {
    $results[$user] = false;
    if (get_userdata($user) !== false) {
      $result = $wpdb->query(
        $wpdb->prepare($sql, [ $user, $intGroupId ] )
      );

      if ($result !== false && is_numeric($result) && $result > 0)
        $results[$user] = true;
    }
  }

  return $results;
}

function cluevo_get_users_by_group_memberships($mixedGroups) {
  global $wpdb;

  $lmsUserTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;
  $wpUserTable = $wpdb->users;
  $userGroupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;
  $groupsTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_GROUPS;

  $list = array();
  if (!is_array($mixedGroups)) {
    $list[] = $mixedGroups;
  } else {
    $list = $mixedGroups;
  }

  $exp = "(^|,)%d{1}(,|$)";
  $placeholders = '';
  foreach ($list as $g) {
    $placeholders .= "$exp|";
  }
  $placeholders = trim($placeholders, "|");

  $sql = "SELECT lms.*, wp.*, GROUP_CONCAT(g.group_id) AS \"groups\"
    FROM $lmsUserTable lms
    INNER JOIN $wpUserTable wp
    ON lms.user_id = wp.ID
    LEFT JOIN $userGroupsTable ug
    ON lms.user_id = ug.user_id
    LEFT JOIN $groupsTable g
    ON g.group_id = ug.group_id
    GROUP BY lms.user_id
    HAVING GROUP_CONCAT(g.group_id) REGEXP \"$placeholders\"";

  $results = $wpdb->get_results(
    $wpdb->prepare($sql, $list)
  );

  $groups = array();
  foreach ($results as $g) {
    $groups[] = CluevoUser::from_std_class($g);
  }

  return $groups;
}

/**
 * Sets a users groups memberships, adds or removes the user from groups
 *
 * @param int $intUserId
 * @param array $arrGroups
 */
function cluevo_set_users_group_memberships($intUserId, $arrGroups) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $sql = "SELECT group_id FROM $table WHERE user_id = %d";
  $curGroups = $wpdb->get_col(
    $wpdb->prepare($sql, [ $intUserId ] )
  );

  $results = [ "added" => [], "removed" => [], "unchanged" => [] ];
  $diffAdd = array_diff($arrGroups, $curGroups);

  foreach ($diffAdd as $newGroup) {
    $result  = cluevo_add_users_to_group($intUserId, $newGroup);
    $results["added"][$newGroup] = $result[$intUserId];
  }

  $diffRemove = array_diff($curGroups, $arrGroups);
  foreach ($diffRemove as $remove) {
    $result =  cluevo_remove_users_from_group($intUserId, $remove);
    $results["removed"][$remove] = $result[$intUserId];
  }

  $results["unchanged"] = array_intersect($arrGroups, $curGroups);

  return $results;
}

function cluevo_set_user_groups_members($intGroupId, $arrUsers) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USERS_TO_GROUPS;

  $sql = "SELECT user_id FROM $table WHERE group_id = %d";
  $curUsers = $wpdb->get_col(
    $wpdb->prepare($sql, [ $intGroupId ] )
  );

  $results = [ "added" => [], "removed" => [], "unchanged" => [] ];
  $diffAdd = array_diff($arrUsers, $curUsers);

  foreach ($diffAdd as $newUser) {
    $result  = cluevo_add_users_to_group($newUser, $intGroupId);
    $results["added"][$newUser] = $result[$newUser];
  }

  $diffRemove = array_diff($curUsers, $arrUsers);
  foreach ($diffRemove as $remove) {
    $result =  cluevo_remove_users_from_group($remove, $intGroupId);
    $results["removed"][$remove] = $result[$remove];
  }

  $results["unchanged"] = array_intersect($arrUsers, $curUsers);

  return $results;
}

/**
 * Sets the last seen timestamp of a user
 *
 * @return void
 */
function cluevo_set_user_last_seen() {
  $id = get_current_user_id();
  if (!empty($id)) {
    update_user_meta($id, CLUEVO_USER_META_LAST_SEEN, time());
    global $wpdb;
    $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;
    $wpdb->query(
      $wpdb->prepare(
        "INSERT INTO $table SET user_id = %d, date_last_seen = NOW() ON DUPLICATE KEY UPDATE date_last_seen = NOW()",
        [ $id ]
      )
    );
  }
}

/**
 * Returns the cluevo user for the given id
 *
 * @param int $intUserId
 *
 * @return CluevoUser|null
 */
function cluevo_get_user($intUserId) {
  global $wpdb;
  $userTable = $wpdb->users;
  $progressTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $userDataTable = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;

  $sql = "SELECT
      u.ID,
      u.user_login,
      u.user_nicename,
      u.user_email,
      u.display_name,
      ud.*
    FROM
      $userTable u
    LEFT JOIN $userDataTable ud ON
      u.ID = ud.user_id
    WHERE
      u.ID = %d";

  $result = $wpdb->get_row(
    $wpdb->prepare($sql, [ $intUserId ])
  );

  $user = null;
  if (!empty($result)) {
    $user = CluevoUser::from_std_class($result);
  }

  return $user;
}

?>
