<?php
if (!defined("CLUEVO_ACTIVE")) exit;
/**
 * Saves the scorm parameters of the given item/user combination to the database
 *
 * Returns an array with progress state information if parameters have been written
 *
 * @param int $intItemId
 * @param int $intUserId
 * @param array $data
 *
 * @return array|null
 */
function cluevo_write_module_parameters($intItemId, $intUserId, $data) {
  global $wpdb;

  $item = cluevo_get_learning_structure_item($intItemId, $intUserId);
  $intModuleId = $item->module_id;

  $tableName = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;

  $moduleData = cluevo_get_module($intModuleId);
  $version = $moduleData->scorm_version;
  $prefix = ($version == "2004") ? "cmi.score" : "cmi.core.score";

  $state = cluevo_get_module_progress($intUserId, $intModuleId);  // get current progress for user
  if(empty($state))
    $state = cluevo_init_module_progress($intUserId, $intModuleId, $data, $version);  // init progress if no attempt is in progress

  if (!array_key_exists("$prefix.raw", $data))
    $data["$prefix.raw"] = 0.0;

  if (!array_key_exists("$prefix.scaled", $data)) {
    $raw = (array_key_exists("$prefix.raw", $data)) ? $data["$prefix.raw"] : 0;
    $max = (array_key_exists("$prefix.max", $data)) ? $data["$prefix.max"] : 0;

    if ($max > 0) {
      $data["$prefix.scaled"] = round($raw / $max, 2);
    }
  }

  if (!empty($data) && !empty($moduleData) && !empty($state)) { // insert all parameters into the database
    foreach ($data as $param => $value) {

      $results = $wpdb->query(
        $wpdb->prepare(
          'INSERT INTO ' . $tableName . ' SET module_id = %d, user_id = %d, attempt_id = %d, parameter = %s, value = %s ON DUPLICATE KEY UPDATE value = %s', [$intModuleId, $intUserId, $state["attempt_id"], $param, $value, $value]
        )
      );
    }

    $newScore = (!empty($data["$prefix.raw"]) && is_numeric($data["$prefix.raw"])) ? (float)$data["$prefix.raw"] : 0.0;
    $curScore = (!empty($data["score_raw"]) && is_numeric($data["score_raw"])) ? (float)$data["score_raw"] : 0.0;

    if (!array_key_exists("cmi.completion_status", $data))
      $data["cmi.completion_status"] = "unknown";

    if (!array_key_exists("cmi.success_status", $data))
      $data["cmi.success_status"] = "unknown";

    return cluevo_update_module_progress($intUserId, $intItemId, $state["attempt_id"], $data);
  }

  return null;
}

/**
 * Updates the module progress for the given parameter combination
 *
 * Calculates the progress for the given user/item/attempt combination
 * and awards points if applicable, returns the new state information as array
 *
 * @param int $intUserId
 * @param int $intItemId
 * @param int $intAttemptId
 * @param int $data
 *
 * @return array
 */
function cluevo_update_module_progress($intUserId, $intItemId, $intAttemptId, $data) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $item = cluevo_get_learning_structure_item($intItemId, $intUserId);
  $moduleId = $item->module_id;
  $module = cluevo_get_module($moduleId);
  $version = $module->scorm_version;
  $prefix = ($version == "2004") ? "cmi.score" : "cmi.core.score";

  $state = cluevo_get_module_progress($intUserId, $moduleId);
  $oldProgress = $state["score_scaled"];

  $fields = [
    "score_raw" => [ "$prefix.raw" => 0 ],
    "score_min" => [ "$prefix.min" => 0 ],
    "score_max" => [ "$prefix.max" => 0 ],
    "score_scaled" => [ "$prefix.scaled" => 0 ],
    "completion_status" => [ "cmi.completion_status" => "not attempted" ],
    "lesson_status" => [ "cmi.core.lesson_status" => "not attempted" ],
    "success_status" => [ "cmi.success_status" => "unknown" ],
    "credit" => [ "cmi.credit" => "credit" ]
  ];

  foreach ($fields as $field => $value) {
    $keys = array_keys($value);
    if (array_key_exists($keys[0], $data)) {
      $state[$field] = $data[$keys[0]];
    } else {
      $state[$field] = $value[$keys[0]];
    }
  }

  $newProgress = round($state["score_scaled"], 2);

  $practiceMode = cluevo_user_module_progress_complete($intUserId, $moduleId);

  if (!$practiceMode && $state["completion_status"] === "completed" && $state["success_status"] === "passed" && $state["credit"] === "credit" || (!$practiceMode && $state["lesson_status"] === "passed"))
    do_action('cluevo_user_cleared_module_first_time', [
      "user_id" => $intUserId,
      "module_id" => $moduleId,
      "attempt_id" => $intAttemptId
    ]);

  $pointsToAdd = 0;
  $sourceType = "";
  if (!$practiceMode) {
    if ($newProgress > $oldProgress) {
      $progressPoints = cluevo_get_user_module_progression_points($intUserId, $moduleId);
      $pointsWorth = $item->points_worth;
      $calcPoints = floor($pointsWorth * $state["score_scaled"]);
      $pointsToAdd = $calcPoints - $progressPoints;
      $sourceType = "scorm-module";
      if ($pointsToAdd > 0) {
        cluevo_add_points_to_user($intUserId, $pointsToAdd, "scorm-module", $moduleId, $intAttemptId);
        do_action('cluevo_award_user_progress_points_from_module', [
          "user_id" => $intUserId,
          "points_added" => $pointsToAdd,
          "module_id" => $moduleId,
          "attempt_id" => $intAttemptId
        ]);
      }
    }
  } else {
    if ($state["completion_status"] == "completed" || $state["lesson_status"] == "completed" || $state["lesson_status"] == "passed") {
      $pointsToAdd = $item->practice_points;
      $sourceType = "scorm-module-practice";
      cluevo_add_points_to_user($intUserId, $item->practice_points, "scorm-module-practice", $moduleId, $intAttemptId);
      do_action('cluevo_award_user_practice_points_from_module', [
        "user_id" => $intUserId,
        "points_added" => $item->practice_points,
        "module_id" => $moduleId,
        "attempt_id" => $intAttemptId
      ]);
    }
  }
  if ($pointsToAdd > 0) {
    do_action('cluevo_user_points_awarded_from_module', [
      "user_id" => $intUserId,
      "module_id" => $moduleId,
      "attempt_id" => $intAttemptId,
      "points_added" => $pointsToAdd,
      "is_practice" => $practiceMode,
      "source-type" => $sourceType
    ]);
  }

  $sql = "UPDATE $table SET
    score_min = %s,
    score_max = %s,
    score_raw = %s,
    score_scaled = %s,
    completion_status = %s,
    success_status = %s,
    lesson_status = %s,
    credit = %s
    WHERE user_id = %d AND module_id = %d AND attempt_id = %d";

  $result = $wpdb->query(
    $wpdb->prepare(
      $sql,
      [
        $state["score_min"],
        $state["score_max"],
        $state["score_raw"],
        $state["score_scaled"],
        $state["completion_status"],
        $state["success_status"],
        $state["lesson_status"],
        $state["credit"],
        $intUserId,
        $moduleId,
        $intAttemptId
      ]
    )
  );

  do_action('cluevo_user_module_progress_updated', [
    "user_id" => $intUserId,
    "module_id" => $moduleId,
    "attempt_id" => $intAttemptId,
    "state" => $state
  ]);

  return $state;
}

function cluevo_update_media_module_progress($intUserId, $intItemId, $max, $score) {
  if ($max <= 0)
    return false;

  $attempt = 0;
  $attempt = cluevo_get_current_attempt_id($intUserId, $intItemId);
  $attempt++;
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $complete = ($score >= $max) ? 'completed' : 'incomplete';
  $success = ($score >= $max) ? 'passed' : 'unknown';

  $sql = "INSERT INTO $table SET
    user_id = %d,
    module_id = %d,
    attempt_id = %d,
    score_min = 0,
    score_max = %s,
    score_raw = %s,
    score_scaled = %s,
    is_practice = %s,
    completion_status = %s,
    success_status = %s
    ON DUPLICATE KEY UPDATE
    score_raw = %s,
    score_scaled = %s,
    completion_status = %s,
    success_status = %s";

  $scaled = ($score / $max);
  $practice = ($attempt == 0) ? 0 : 1;

  $wpdb->query(
    $wpdb->prepare($sql, [
      $intUserId,
      $intItemId,
      $attempt,
      $max,
      $score,
      $scaled,
      $practice,
      $complete,
      $success,
      $score,
      $scaled,
      $complete,
      $success
    ])
  );
}

/**
 * Awards points to the user
 *
 * Writes an award entry to the database including the source if applicable
 *
 * @param int $intUserId
 * @param int $intPoints
 * @param int $strSource (optional)
 * @param int $intModuleId (optional)
 * @param int $intAttemptId (optional)
 * @param int $intAddedByUserId (optional)
 */
function cluevo_add_points_to_user($intUserId, $intPoints, $strSource = null, $intModuleId = null, $intAttemptId = null, $intAddedByUserId = 0) {

  if ($intPoints == 0)
    return;

  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_EXP_LOG;

  $sql = "INSERT INTO $table SET
    user_id = %d,
    added_by_user_id = %d,
    source_type = %s,
    source_module_id = %d,
    source_module_attempt_id = %d,
    exp_before = %d,
    exp_added = %d,
    exp_after = %d";

  $lastEntry = cluevo_get_latest_exp_log_entry($intUserId);

  if (!empty($lastEntry))
    $before = $lastEntry->exp_after;
  else
    $before = 0;

  $after = $before + $intPoints;

  $result = $wpdb->query(
    $wpdb->prepare(
      $sql,
      [ $intUserId, $intAddedByUserId, $strSource, $intModuleId, $intAttemptId, $before, $intPoints, $after ]
    )
  );

  if ($result !== false) {
    do_action('cluevo_user_points_awarded', [
      "user_id" => $intUserId,
      "points" => $intPoints,
      "source" => $strSource,
      "module_id" => $intModuleId,
      "attempt_id" => $intAttemptId,
      "added_by_user_id" => $intAddedByUserId
    ]);
    cluevo_update_user_data($intUserId, $after, $after);
  }
}

function cluevo_update_user_data($intUserId, $points, $exp) {
  global $wpdb;

  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_DATA;

  $sql = "INSERT INTO $table
    SET
      user_id = %d,
      total_points = %d,
      total_exp = %d,
      date_last_seen = NOW()
    ON DUPLICATE KEY UPDATE
      total_points = %d,
      total_exp = %d,
      date_last_seen = NOW()";

    $result = $wpdb->query(
      $wpdb->prepare( $sql, [
        $intUserId,
        $points,
        $exp,
        $points,
        $exp
      ])
    );

  do_action('cluevo_update_user_data', [
    "user_id" => $intUserId,
    "points" => $points,
    "exp" => $exp
  ]);
}

/**
 * Retrieves the latest exp log entry for the given user from the database
 *
 * @param int $intUserId
 *
 * @return object|null
 */
function cluevo_get_latest_exp_log_entry($intUserId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_USER_EXP_LOG;

  $sql = "SELECT * FROM $table WHERE user_id = %d ORDER BY log_id DESC LIMIT 1";

  $result = $wpdb->get_row(
    $wpdb->prepare( $sql, [ $intUserId ] )
  );

  return $result;
}

/**
 * Checks whether a user has completed the given module
 *
 * Completion is determined by checking whether there is any progress
 * entry where the scaled score (percentage completion) is >= 1
 *
 * @param int $intUserId
 * @param int $intModuleId
 *
 * @return bool
 */
function cluevo_user_module_progress_complete($intUserId, $intModuleId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $sql = "SELECT COUNT(*) FROM $table WHERE user_id = %d AND module_id = %d AND score_scaled >= 1";

  $count = $wpdb->get_var(
    $wpdb->prepare($sql, [ $intUserId, $intModuleId ])
  );

  return ($count >= 1);
}

/**
 * Retrieves the best attempt of a user for a given module id as scaled score
 *
 * @param int $intUserId
 * @param int $intModuleId
 *
 * @return int
 */
function cluevo_get_users_best_module_attempt($intUserId, $intModuleId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $sql = "SELECT COALESCE(MAX(score_scaled), 0) FROM $table WHERE user_id = %d AND module_id = %d AND credit = 'credit'";

  $result = $wpdb->get_var(
    $wpdb->prepare($sql, [ $intUserId, $intModuleId ])
  );

  return (!empty($result)) ? $result : 0;
}

function cluevo_user_completed_module($intUserId, $intModuleId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $sql = "SELECT COUNT(*) FROM $table WHERE user_id = %d AND module_id = %d AND credit = 'credit' AND ( (completion_status = 'completed' AND success_status = 'passed') OR (lesson_status = 'completed'))";

  $result = $wpdb->get_var(
    $wpdb->prepare($sql, [ $intUserId, $intModuleId ])
  );

  return (!empty($result)) ? $result : 0;
}

/**
 * Retrieves progression points of a user for a given module
 *
 * Returns the sum of added exp
 *
 * @param mixed $intUserId
 * @param mixed $intModuleId
 *
 * @return int
 */
function cluevo_get_user_module_progression_points($intUserId, $intModuleId) {
  global $wpdb;
  $tableLog = $wpdb->prefix . CLUEVO_DB_TABLE_USER_EXP_LOG;
  $tableProgress = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $sql = "SELECT COALESCE(SUM(exp_added), 0) AS points
    FROM $tableLog l
    INNER JOIN $tableProgress p ON
      l.source_module_id = p.module_id AND
      l.source_module_attempt_id = p.attempt_id AND
      l.user_id = p.user_id
    WHERE
      l.user_id = %d AND
      l.source_module_id = %d";

    $result = $wpdb->get_var(
      $wpdb->prepare($sql, [ $intUserId, $intModuleId ])
    );
  
  return (!empty($result)) ? (int)$result : 0;
}

/**
 * Retrieves the latest module progress entry of a user for given module id
 *
 * @param int $intUserId
 * @param int $intModuleId
 *
 * @return object|null
 */
function cluevo_get_module_progress($intUserId, $intModuleId) {
  global $wpdb;
  $module = cluevo_get_module($intModuleId);
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
  $sql = "SELECT * FROM $table WHERE module_id = %d AND user_id = %d ORDER BY attempt_id DESC LIMIT 1";
  return $wpdb->get_row($wpdb->prepare($sql, [ $intModuleId, $intUserId ]), ARRAY_A);
}

/**
 * Initializes module progress for a user and module
 *
 * @param int $intUserId
 * @param int $intModuleId
 * @param array $data
 *
 * @return object|null
 */
function cluevo_init_module_progress($intUserId, $intModuleId, $data, $strVersion) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $prefix = ($strVersion == "2004") ? "cmi.score" : "cmi.core.score";
  $scores = ["$prefix.raw" => 0, "$prefix.min" => 0, "$prefix.max" => 0, "$prefix.scaled" => 0];
  foreach ($scores as $score => $value) {
    if (array_key_exists($score, $data)) {
      $scores[$score] = $data[$score];
    }
  }

  $attemptId = cluevo_get_current_attempt_id($intUserId, $intModuleId) + 1;

  $sql = "INSERT IGNORE INTO $table SET score_raw = %s, score_min = %s, score_max = %s, score_scaled = %s, module_id = %d, user_id = %d, attempt_id = %d, completion_status = 'incomplete', success_status = 'unknown'";

  $wpdb->query($wpdb->prepare($sql, [ $scores["$prefix.raw"], $scores["$prefix.min"], $scores["$prefix.max"], $scores["$prefix.scaled"], $intModuleId, $intUserId, $attemptId ]));

  return cluevo_get_module_progress($intUserId, $intModuleId);

}

/**
 * Retrieves the current attempt id of a user for a given module
 *
 * Returns negative 1 if no attempt was found
 *
 * @param int $intUserId
 * @param int $intModuleId
 *
 * @return int
 */
function cluevo_get_current_attempt_id($intUserId, $intModuleId) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;

  $sql = "SELECT MAX(attempt_id) FROM $table WHERE user_id = %s AND module_id = %d";
  $result = $wpdb->get_var($wpdb->prepare($sql, [$intUserId, $intModuleId]));

  return (is_numeric($result)) ? $result : -1;
}

/**
 * Checks wheter a module is completed
 *
 * Determined by checking cmi.completion_status and cmi.succcess_status
 *
 * @param array $params
 *
 * @return bool
 */
function cluevo_is_module_completed($params) {
  if (!empty($params['cmi.completion_status']) && !empty($params['cmi.success_status'])) {
    if ($params['cmi.completion_status']['value'] == 'completed' && $params['cmi.success_status']['value'] == 'passed') {
      return true;
    }
  }

  return false;
}

/**
 * Retrieves the scorm parameter set of a given attempt for a user and module
 *
 * If no attempt id is supplied the latest attempt is assumed. Also initializes
 * various parameters like learner name and scores. Accesses the imsmanifest
 * to initialize some of this data
 *
 * @param int $intModuleId
 * @param int $intUserId
 * @param int $intAttemptId (optional)
 *
 * @return array
 */
function cluevo_get_module_parameters($intModuleId, $intUserId = null, $intAttemptId = null) {
  global $wpdb;

  $module = cluevo_get_module($intModuleId);
  $version = $module->scorm_version;
  $params = [];

  $dir = cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $module->module_name;
  $manifest = null;
  if (file_exists($dir)) {
    $it = new RecursiveDirectoryIterator($dir);
    foreach(new RecursiveIteratorIterator($it) as $file) {
      $name = basename($file);
      $curDir = $it->getBasename();
      if ($name == "imsmanifest.xml") {
        $manifest = $file;
        break;
      }
    }
  }

  if (!empty($manifest)) {
    $xml = simplexml_load_file($manifest);
    $ns = $xml->getNamespaces(true);
    foreach ($ns as $name => $value) {
      $xml->registerXPathNamespace($name, $value);
    }
    $objectives = json_decode(json_encode($xml->xpath("//imsss:objectives//@objectiveID")), TRUE);

    $objectiveIds = [];
    foreach ($objectives as $key => $attr) {
      $objectiveIds[] = $attr["@attributes"]["objectiveID"];
    }

    $objKeys = [
      "score._children" => 4,
      "score.scaled" => 0,
      "score.raw" => 0,
      "score.min" => 0,
      "score.max" => 0,
      "success_status" => "unknown",
      "completion_status" => "unknown",
      "progress_measure" => 0,
      "description" => ""
    ];

    $i = 0;
    $notFound = [];
    foreach ($objectiveIds as $id) {
      $found = false;
      foreach ($params as $parm => $value) {
        if (preg_match('/cmi.objectives\.\d*\.id/', $parm)) {
          if ($value == $id) {
            $found = true;
            $i++;
            break;
          }
        }
      }
      if (!$found) {
        $params["cmi.objectives.$i.id"] = [ "value" => $id ];
        foreach ($objKeys as $key => $default) {
          $params["cmi.objectives.$i.$key"] = [ "value" => $default ];
        }
        $i++;
      }
    }
    $params["cmi.objectives._children"] = [ "value" => "id,score,success_status,completion_status,description" ];
    $params["cmi.interactions._children"] = [ "value" => "id,type,objectives,timestamp,correct_responses,weighting,learner_response,result,latency,description" ];
  }

  $params["cmi.learner_name"] = [ "value" =>  __("Guest", "cluevo") ];
  $params["cmi.core.student_name"] = [ "value" => __("Guest", "cluevo") ];
  $params["cmi.core.student_id"] = [ "value" => get_current_user_id() ];
  $params["cmi.learner_id"] = [ "value" => get_current_user_id() ];
  //$params["cmi.core._children"] = [ "value" => "student_id,student_name,lesson_location,credit,lesson_status,entry,score,total_time,lesson_mode,exit,session_time"];
  if ($version == "2004") {
    $params["cmi.mode"] = ["value" => "normal" ];
  } else {
    $params["cmi.core.lesson_mode"] = [ "value" => "normal" ];
  }

  if (!empty($intUserId)) {

    if (array_key_exists("cmi.learner_name", $params) || !array_key_exists("cmi.core.student_name", $params)) {
      $user = cluevo_get_user($intUserId);
      if (!empty($user)) {
        $params["cmi.learner_name"] = [ "value" =>  $user->display_name ];
        $params["cmi.core.student_name"] = [ "value" => $user->display_name ];
      }
    }

    $tableName = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;
    $sql = "SELECT p.* FROM $tableName p WHERE module_id = %d AND user_id = %d AND attempt_id = %d";

    if (empty($intAttemptId)) {
      $intAttemptId = cluevo_get_current_attempt_id($intUserId, $intModuleId);
    }

    $results = $wpdb->get_results(
      $wpdb->prepare(
        $sql,
        [ $intModuleId, $intUserId, $intAttemptId ]
      ),
      ARRAY_A
    );

    foreach ($results as $row) {
      $params[$row["parameter"]] = $row;
    }
  }

  return $params;
}

/**
 * Retrieves all scorm parameters of a given user
 *
 * @param int $intUserId
 *
 * @return array
 */
function cluevo_get_users_module_parameters($intUserId) {
  global $wpdb;
  $tableName = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;

  $results = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $tableName . ' WHERE user_id = %d ORDER BY module_id', array($intUserId)));

  $params = [];
  foreach ($results as $p) {
    if (!array_key_exists($p->module_id, $params)) {
      $params[$p->module_id] = [];
    }

    $params[$p->module_id][$p->parameter] = get_object_vars($p);
  }

  return $params;
}
?>
