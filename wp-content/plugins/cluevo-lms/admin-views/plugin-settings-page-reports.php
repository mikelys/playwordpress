<?php
if (is_admin()) {
  /**
   * Returns progress entries for the given arguments
   *
   * Possible arguments are: user_id, module_id, attempt_id, success_status, completion_status, lesson_status
   * Result can be paginated if pagination arguments are passed
   *
   * @param mixed $args
   * @param int $intPage
   * @param mixed $intPerPage
   *
   * @return array
   */
  function cluevo_get_modules_progress_entries($args = [], $intPage = 0, $intPerPage = null) {
    global $wpdb;
    $progressTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
    $userTable = $wpdb->users;
    $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;
    $moduleTypeTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_TYPES;

    $sql = "SELECT p.*,
        u.display_name,
        u.user_login,
        u.user_nicename,
        u.user_email,
        m.module_name,
        m.metadata_id,
        m.scorm_version,
        LOWER(t.type_name) AS type_name
      FROM $progressTable p
      INNER JOIN $userTable u
        ON p.user_id = u.ID
      INNER JOIN $moduleTable m
        ON p.module_id = m.module_id
      LEFT JOIN $moduleTypeTable t
        ON m.type_id = t.type_id";

    $valid = [ "user_id", "attempt_id", "success_status", "completion_status", "lesson_status", "module_id" ];
    $where = [];
    $parms = [];
    foreach ($args as $arg => $value) {
      if (in_array($arg, $valid) && (!empty($value) || ($arg == "attempt_id" && $value > -1))) {
        if (empty($parms))
          $sql .= " WHERE ";
        $where[] = "p.$arg = %s";
        $parms[] = $value;
      }
    }

    $sql .= implode(" AND ", $where);

    if (!empty($intPerPage)) {
      $limit = $intPage * $intPerPage;
      $sql .= " LIMIT $limit, $intPerPage";
    }
    
    if (!empty($parms)) {
      $result = $wpdb->get_results(
        $wpdb->prepare($sql, $parms)
      );
    } else {
      $result = $wpdb->get_results($sql);
    }
    return $result;
  }

  /**
   * Returns possible filter values for progress entries
   *
   * @param string $strFieldValue
   * @param string $strFieldLabel
   * @param mixed $args (optional) Possible keys: user_id, attempt_id, success_status, completion_status, lesson_status, module_id
   *
   * @return array|null
   */
  function cluevo_get_progress_filter_content($strFieldValue, $strFieldLabel, $args = []) {
    global $wpdb;
    $progressTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
    $userTable = $wpdb->users;
    $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

    if (strpos($strFieldLabel, ".") === false)
      $strFieldLabel = "p.$strFieldLabel";

    $sql = "SELECT  p.$strFieldValue AS value, $strFieldLabel AS label
      FROM $progressTable p
      INNER JOIN $userTable u
        ON p.user_id = u.ID
      INNER JOIN $moduleTable m
        ON p.module_id = m.module_id";

    $valid = [ "user_id", "attempt_id", "success_status", "completion_status", "lesson_status", "module_id" ];
    $where = [];
    $parms = [];

    foreach ($args as $arg => $value) {
      if ($arg == $strFieldValue)
        continue;

      if (in_array($arg, $valid) && (!empty($value) || ($arg == "attempt_id" && $value > -1))) {
        if (empty($parms))
          $sql .= " WHERE ";

        if (strpos($arg, ".") === false)
          $where[] = "p.$arg = %s";
        else
          $where[] = "$arg = %s";
        $parms[] = $value;
      }
    }

    $sql .= implode(" AND ", $where);
    $sql .= " GROUP BY p.$strFieldValue";

    if (!empty($parms)) {
      return $wpdb->get_results(
        $wpdb->prepare($sql, $parms )
      );
    }

    return $wpdb->get_results($sql);
  }

  /**
   * Returns an array with pagination information
   *
   * @param mixed $args (optional) Possible keys: module_id, user_id, attempt_id, success_status, completion_status, lesson_status
   * @param int $intPerPage (optional)
   */
  function cluevo_get_progress_pagination($args = [], $intPerPage = 100) {
    global $wpdb;
    $progressTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES_PROGRESS;
    $userTable = $wpdb->users;
    $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

    $sqlFields = "COUNT(*)";
    $sql = "SELECT $sqlFields
      FROM $progressTable p
      INNER JOIN $moduleTable m
        ON p.module_id = m.module_id
      INNER JOIN $userTable u
        ON p.user_id = u.ID";

    $valid = [ "module_id", "user_id", "attempt_id", "success_status", "completion_status", "lesson_status" ];
    $where = [];
    $parms = [];
    foreach ($args as $arg => $value) {
      if (in_array($arg, $valid) && (!empty($value) || ($arg == "attempt_id" && $value > -1))) {
        if (empty($parms))
          $sql .= " WHERE ";
        $where[] = "p.$arg = %s";
        $parms[] = $value;
      }
    }

    $sql .= implode(" AND ", $where);

    if (!empty($parms)) {
      $rows = $wpdb->get_var(
        $wpdb->prepare($sql, $parms)
      );
    } else {
      $rows = $wpdb->get_var($sql);
    }

    $pages = ceil($rows / $intPerPage);

    return [ "pages" => $pages, "items_per_page" => $intPerPage, "items" => $rows ];
  }
  /**
   * Retrieves scorm parameters from the database
   *
   * @param array $args (optional) Possible keys: module_id, user_id, attempt_id, parameter
   * @param int $intPage (optional)
   * @param int $intPerPage (optional)
   *
   * @return array|null
   */
  function cluevo_get_parameters($args = [], $intPage = 0, $intPerPage = null) { //, $args = [], $intModuleId = null, $intUserId = null, $intAttemptId = null) {
    global $wpdb;
    $parmTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;
    $userTable = $wpdb->users;
    $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

    $sqlFields = "p.*, LEFT(p.value, 50) AS value, m.module_name, u.display_name, u.user_nicename, u.user_email, user_login";
    $sql = "SELECT $sqlFields
      FROM $parmTable p
      INNER JOIN $moduleTable m
        ON p.module_id = m.module_id
      INNER JOIN $userTable u
        ON p.user_id = u.ID";

    $valid = [ "module_id", "user_id", "attempt_id", "parameter" ];
    $where = [];
    $parms = [];
    foreach ($args as $arg => $value) {
      if (in_array($arg, $valid) && (!empty($value) || ($arg == "attempt_id" && $value > -1))) {
        if (empty($parms))
          $sql .= " WHERE ";

        $where[] = "p.$arg = %s";
        $parms[] = $value;
      }
    }

    $sql .= implode(" AND ", $where);
    $sql .= " ORDER BY p.id ASC";

    if (!empty($intPerPage)) {
      $limit = $intPage * $intPerPage;
      $sql .= " LIMIT $limit, $intPerPage";
    }

    if (!empty($parms)) {
    $result = $wpdb->get_results(
      $wpdb->prepare($sql, $parms)
    );
    } else {
      $result = $wpdb->get_results($sql);
    }

    return $result;

  }

  /**
   * Retrieves a list of available parameters
   *
   * @param mixed $args (optional) Possible keys: module_id, user_id, attempt_id
   *
   * @return array|null
   */
  function cluevo_get_available_parameters($args = []) {
    global $wpdb;
    $parmTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;
    $userTable = $wpdb->users;
    $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

    $sqlFields = "p.parameter";
    $sql = "SELECT $sqlFields
      FROM $parmTable p
      INNER JOIN $moduleTable m
        ON p.module_id = m.module_id
      INNER JOIN $userTable u
        ON p.user_id = u.ID";

    $valid = [ "module_id", "user_id", "attempt_id" ];
    $where = [];
    $parms = [];
    foreach ($args as $arg => $value) {
      if (in_array($arg, $valid) && (!empty($value) || ($arg == "attempt_id" && $value > -1))) {
        if (empty($parms))
          $sql .= " WHERE ";

        $where[] = "p.$arg = %d";
        $parms[] = $value;
      }
    }

    $sql .= implode(" AND ", $where);
    $sql .= " GROUP BY p.parameter";
    $sql .= " ORDER BY p.parameter ASC";

    if (!empty($parms)) {
    $result = $wpdb->get_results(
      $wpdb->prepare($sql, $parms)
    );
    } else {
      $result = $wpdb->get_results($sql);
    }

    return $result;
  }

  /**
   * Returns parameter pagination information for the given arguments
   *
   * @param mixed $args (optional) Possible keys: module_id, user_id, attempt_id, parameter
   * @param int $intPerPage (optional)
   *
   * @return array
   */
  function cluevo_get_parameter_pagination($args = [], $intPerPage = 100) {
    global $wpdb;
    $parmTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;
    $userTable = $wpdb->users;
    $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

    $sqlFields = "COUNT(*)";
    $sql = "SELECT $sqlFields
      FROM $parmTable p
      INNER JOIN $moduleTable m
        ON p.module_id = m.module_id
      INNER JOIN $userTable u
        ON p.user_id = u.ID";


    $valid = [ "module_id", "user_id", "attempt_id", "parameter" ];
    $where = [];
    $parms = [];
    foreach ($args as $arg => $value) {
      if (in_array($arg, $valid) && (!empty($value) || ($arg == "attempt_id" && $value > -1))) {
        if (empty($parms))
          $sql .= " WHERE ";
        $where[] = "p.$arg = %s";
        $parms[] = $value;
      }
    }

    $sql .= implode(" AND ", $where);

    if (!empty($parms)) {
      $rows = $wpdb->get_var(
        $wpdb->prepare($sql, $parms)
      );
    } else {
      $rows = $wpdb->get_var($sql);
    }

    $pages = ceil($rows / $intPerPage);

    return [ "pages" => $pages, "items_per_page" => $intPerPage, "items" => $rows ];
  }

  /**
   * Retrieves a list of possible filter values for parameters
   *
   * @param mixed $strFieldValue Column in the database for values
   * @param mixed $strFieldLabelColumn in the database to use for the label
   * @param mixed $args (optional) Possible keys: user_id, attempt_id, success_status, completion_status, lesson_status, module_id
   *
   * @return array|null
   */
  function cluevo_get_parameters_filter_content($strFieldValue, $strFieldLabel, $args = []) {
    global $wpdb;
    $parmTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULE_PARMS;
    $userTable = $wpdb->users;
    $moduleTable = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;

    if (strpos($strFieldLabel, ".") === false)
      $strFieldLabel = "p.$strFieldLabel";

    $sql = "SELECT  p.$strFieldValue AS value, $strFieldLabel AS label
      FROM $parmTable p
      INNER JOIN $userTable u
        ON p.user_id = u.ID
      INNER JOIN $moduleTable m
        ON p.module_id = m.module_id
      WHERE ";

    $valid = [ "user_id", "attempt_id", "success_status", "completion_status", "lesson_status", "module_id" ];
    $where = [ "m.type_id = " . CLUEVO_SCORM_MODULE_TYPE_ID ];
    $parms = [];

    foreach ($args as $arg => $value) {
      if ($arg == $strFieldValue)
        continue;

      if (in_array($arg, $valid) && (!empty($value) || ($arg == "attempt_id" && $value > -1))) {

        if (strpos($arg, ".") === false)
          $where[] = "p.$arg = %s";
        else
          $where[] = "$arg = %s";
        $parms[] = $value;
      }
    }

    $sql .= implode(" AND ", $where);
    $sql .= " GROUP BY p.$strFieldValue";

    if (!empty($parms)) {
      return $wpdb->get_results(
        $wpdb->prepare($sql, $parms )
      );
    }

    return $wpdb->get_results($sql);
  }

  function cluevo_render_reports_page() {
    $active_tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alphanum_dash($_GET["tab"]) : CLUEVO_ADMIN_TAB_REPORTS_MAIN;
    do_action('cluevo_init_admin_page');
  ?>
  <div class="cluevo-admin-page-container">
    <div class="cluevo-admin-page-title-container">
      <h1><?php esc_html_e("Reporting", "cluevo"); ?></h1>
      <img class="plugin-logo" src="<?php echo esc_url(plugins_url("/assets/logo-white.png", plugin_dir_path(__FILE__)), ['http', 'https']); ?>" />
    </div>
    <div class="cluevo-admin-page-content-container">
    <h2 class="nav-tab-wrapper cluevo">
      <a href="<?php echo esc_url(admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_REPORTS. "&tab=" . CLUEVO_ADMIN_TAB_REPORTS_MAIN), ['http', 'https']); ?>" class="nav-tab <?php echo $active_tab == CLUEVO_ADMIN_TAB_REPORTS_MAIN ? 'nav-tab-active' : ''; ?>"><?php esc_html_e("Reports", "cluevo"); ?></a>
      <a href="<?php echo esc_url(admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_REPORTS. "&tab=" . CLUEVO_ADMIN_TAB_REPORTS_PROGRESS), ['http', 'https']); ?>" class="nav-tab <?php echo $active_tab == CLUEVO_ADMIN_TAB_REPORTS_PROGRESS ? 'nav-tab-active' : ''; ?>"><?php esc_html_e("Progress", "cluevo"); ?></a>
      <a href="<?php echo esc_url(admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_REPORTS. "&tab=" . CLUEVO_ADMIN_TAB_REPORTS_SCORM_PARMS), ['http', 'https']); ?>" class="nav-tab <?php echo $active_tab == CLUEVO_ADMIN_TAB_REPORTS_SCORM_PARMS ? 'nav-tab-active' : ''; ?>"><?php esc_html_e("SCORM Parameters", "cluevo"); ?></a>
    </h2>
  <?php 
    switch ($active_tab) {
    case CLUEVO_ADMIN_TAB_REPORTS_MAIN:
      cluevo_render_reports_report_tab();
      break;
    case CLUEVO_ADMIN_TAB_REPORTS_PROGRESS:
      cluevo_render_reports_progress_tab();
      break;
    case CLUEVO_ADMIN_TAB_REPORTS_SCORM_PARMS:
      cluevo_render_reports_scorm_parms_tab();
      break;
    default:
      cluevo_render_reports_report_tab();
      break;
    }
  ?>
  </div>
  <?php
  }

  function cluevo_render_reports_report_tab() {
    wp_register_script( 'cluevo-admin-table-filter', plugins_url('/js/admin-table-filter.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, true );
    wp_enqueue_script( "cluevo-admin-table-filter" );
    $items = null;
    $pagination = null;
    $itemId = (!empty($_GET["item"]) && is_numeric($_GET["item"])) ? (int)$_GET["item"] : null;
    $moduleId = (!empty($_GET["module"]) && is_numeric($_GET["module"])) ? (int)$_GET["module"] : null;
    $page = (isset($_GET["cur-page"]) && is_numeric($_GET["cur-page"]) && (int)$_GET["cur-page"] >= 0) ? (int)$_GET["cur-page"] : 0;
    $perPage = 10;
    $active_tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : CLUEVO_ADMIN_TAB_REPORTS_MAIN;
    $active_page = CLUEVO_ADMIN_PAGE_REPORTS;

    if (!empty($moduleId)) {
      //FIXME: it's completely random that this works because the parameters are shared between the two pages. It's probably better to link directly to the page instead of rendering it here.
      cluevo_render_reports_progress_tab(null);
    } else {
      $pagination = cluevo_get_learning_structure_item_children_pagination($itemId, $perPage);
      $items = cluevo_get_learning_structure_item_children($itemId, null, $page, $perPage);
      $nextPage = ($page + 1 < $pagination["pages"]) ? $page + 1 : null;

      foreach ($items as $key => $item) {
        $details = cluevo_get_progress_summary_for_modules($item->modules);
        $items[$key]->details = $details;
      }

      $parentId = null;
      if (!empty($itemId)) {
        $parent = cluevo_get_learning_structure_item($itemId);
        $parentId = $parent->parent_id;
      }

      global $wpdb;
      $totalUsers = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->users);
  ?>
  <h1><?php echo esc_html($pagination["items"]) . " " . esc_html__("records found", "cluevo"); ?></h1>
  <?php if (!empty($items)) { ?>
    <?php if ($parentId !== null) { ?>
    <a href="<?php echo esc_url(admin_url("admin.php?page=$active_page&tab=$active_tab&item=$parentId"), ['http', 'https']); ?>" class="button">⮤ <?php echo esc_html($parent->name); ?></a>
    <?php } ?>
    <form action="<?php echo esc_url(admin_url("admin.php?page=$active_page"), ['http', 'https']); ?>">
      <input type="hidden" name="page" value="<?php echo esc_attr($active_page); ?>" />
      <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>" />
      <input type="hidden" name="item" id="item" value="<?php echo esc_attr($itemId); ?>" />
      <table class="cluevo-admin-table cluevo-filtered-table">
        <tr>
          <th>#</th>
          <th class="left"><?php esc_html_e("Type", "cluevo"); ?></th>
          <th class="left"><?php esc_html_e("Name", "cluevo"); ?></th>
          <th><?php esc_html_e("Modules", "cluevo"); ?></th>
          <th><?php esc_html_e("Users", "cluevo"); ?></th>
          <th><?php esc_html_e("Attempts", "cluevo"); ?></th>
          <th>&#8960; <?php esc_html_e("Points", "cluevo"); ?></th>
          <th>&#8960; <?php esc_html_e("Points %", "cluevo"); ?></th>
          <th><?php esc_html_e("Completed", "cluevo"); ?></th>
          <th class="left"><?php esc_html_e("Best User", "cluevo"); ?></th>
          <th class="left"><?php esc_html_e("Worst User", "cluevo"); ?></th>
        </tr>
      <?php foreach ($items as $item) { ?>
        <tr>
          <td><?php echo esc_html($item->item_id); ?></td>
          <td class="left"><?php if (empty($item->module) || $item->module < 0) { esc_html_e($item->type, "cluevo"); } else { esc_html_e("Module", "cluevo"); } ?></td>
          <?php if (empty($item->module) || $item->module < 0) { ?>
          <td class="left cluevo-table-filter" data-target="#item" data-id="<?php echo esc_html($item->item_id); ?>"><?php echo esc_html(mb_strimwidth($item->name, 0, 40, "...")); ?></td>
          <?php } else { ?>
          <td class="left"><a href="<?php echo esc_url(admin_url("admin.php?page=$active_page&tab=" . CLUEVO_ADMIN_TAB_REPORTS_PROGRESS . "&module=$item->module_id"), ['http', 'https']); ?>"><?php echo esc_html(mb_strimwidth($item->name, 0, 20, "...")); ?></a></td>
          <?php } ?>
          <td><?php echo esc_html(count($item->modules)); ?></td>
          <td><?php echo esc_html($item->details->users); ?> / <?php echo esc_html($totalUsers); ?></td>
          <td><?php echo esc_html($item->details->attempts); ?></td>
          <td><?php echo esc_html(number_format($item->details->avg_score_raw, 2)); ?></td>
          <td><?php echo esc_html(number_format($item->details->avg_score_scaled * 100, 2)); ?>%</td>
          <td><?php echo esc_html($item->details->completed_count); ?></td>
          <td class="left"><?php echo esc_html($item->details->best_user); ?></td>
          <td class="left"><?php echo ($item->details->worst_user != $item->details->best_user) ? esc_html($item->details->worst_user) : ""; ?></td>
        </tr>
    <?php } ?>
      </table>
    </form>
    <div class="cluevo-admin-table-pagination">
      <a class="cluevo-btn <?php if ($page < 0 || empty($page)) echo "disabled"; ?>" href="<?php echo esc_url(remove_query_arg("cur-page"), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-skipback"></span></a>
      <a class="cluevo-btn <?php if ($page < 0 || empty($page)) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", "" . $page - 1), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-back"></span></a>
      <div class="cluevo-select-page">
        Seite
        <select name="cur-page">
        <?php for($i = 0; $i < $pagination["pages"]; $i++) { ?>
          <option value="<?php echo esc_attr($i); ?>" <?php if ($i == $page) echo "selected"; ?>><?php echo esc_html($i + 1); ?></option>
        <?php } ?>
        </select>
        von <?php echo esc_html($pagination["pages"]); ?>
      </div>
      <a class="cluevo-btn <?php if (empty($nextPage)) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", "$nextPage"), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-forward"></span></a>
      <a class="cluevo-btn <?php if (empty($nextPage) && $nextPage < $pagination["pages"]) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", $pagination["pages"] - 1), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-skipforward"></span></a>
    </div>
  <?php } else { ?>
      <div class="cluevo-admin-notice cluevo-notice-info">
        <p><?php esc_html_e("No reports found.", "cluevo"); ?></p>
      </div>
  <?php } ?>
  <?php }
  }

  function cluevo_render_reports_progress_tab() {
    $userId = (!empty($_GET["user"]) && is_numeric($_GET["user"])) ? (int)$_GET["user"] : null;
    $moduleId = (!empty($_GET["module"]) && is_numeric($_GET["module"])) ? (int)$_GET["module"] : null;
    $attemptId = (isset($_GET["attempt"]) && is_numeric($_GET["attempt"]) && (int)$_GET["attempt"] >= 0) ? (int)$_GET["attempt"] : null;
    $successStatus = (!empty($_GET["success-status"])) ? cluevo_strip_non_alpha($_GET["success-status"]) : null;
    $completionStatus = (!empty($_GET["completion-status"])) ? cluevo_strip_non_alpha($_GET["completion-status"]) : null;
    $lessonStatus = (!empty($_GET["lesson-status"])) ? cluevo_strip_non_alpha_blank($_GET["lesson-status"]) : null;
    $page = (isset($_GET["cur-page"]) && is_numeric($_GET["cur-page"]) && (int)$_GET["cur-page"] >= 0) ? (int)$_GET["cur-page"] : 0;
    $perPage = 100;
    $pagination = cluevo_get_progress_pagination( [ "module_id" => $moduleId, "user_id" => $userId, "attempt_id" => $attemptId ], $perPage );

    $entries = cluevo_get_modules_progress_entries([ "user_id" => $userId, "module_id" => $moduleId, "attempt_id" => $attemptId, "module_id" => $moduleId, "success_status" => $successStatus, "completion_status" => $completionStatus, "lesson_status" => $lessonStatus ], $page, $perPage);
    $modules = cluevo_get_progress_filter_content( "module_id", "m.module_name", [ "user_id" => $userId, "attempt_id" => $attemptId, "module_id" => $moduleId ] );
    $users = cluevo_get_progress_filter_content( "user_id", "u.display_name", [ "module_id" => $moduleId, "attempt_id" => $attemptId ] );
    $attempts = cluevo_get_progress_filter_content( "attempt_id", "attempt_id", [ "user_id" => $userId, "module_id" => $moduleId, "module_id" => $moduleId ] );
    $successFilter = cluevo_get_progress_filter_content( "success_status", "success_status", [ "user_id" => $userId, "module_id" => $moduleId, "module_id" => $moduleId, "attempt_id" => $attemptId ] );
    $completionFilter = cluevo_get_progress_filter_content( "completion_status", "completion_status", [ "user_id" => $userId, "module_id" => $moduleId, "module_id" => $moduleId, "attempt_id" => $attemptId ] );
    $lessonFilter = cluevo_get_progress_filter_content( "lesson_status", "lesson_status", [ "user_id" => $userId, "module_id" => $moduleId, "module_id" => $moduleId, "attempt_id" => $attemptId ] );
    $active_tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : CLUEVO_ADMIN_TAB_REPORTS_MAIN;
    $active_page = CLUEVO_ADMIN_PAGE_REPORTS;

    $nextPage = ($page + 1 < $pagination["pages"]) ? $page + 1 : null;
    wp_register_script( 'cluevo-admin-table-filter', plugins_url('/js/admin-table-filter.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, true );
    wp_enqueue_script( "cluevo-admin-table-filter" );
  ?>
  <h1><?php echo esc_html($pagination["items"]) . " " . esc_html__("records found", "cluevo"); ?></h1>
  <?php if (!empty($entries)) { ?>
    <form action="<?php echo esc_url(admin_url("admin.php?page=$active_page&tab=$active_tab"), ['http', 'https']); ?>">
      <input type="hidden" name="page" value="<?php echo esc_attr($active_page); ?>" />
      <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>" />
      <input type="hidden" name="cur-page" id="cur-page" value="<?php echo esc_attr($page); ?>" />
      <table class="cluevo-filtered-table cluevo-admin-table">
        <tr class="filter-row">
        <td><?php cluevo_render_admin_table_filter("user", __("all users", "cluevo"), 0, $users, $userId); ?></td>
        <td><?php cluevo_render_admin_table_filter("module", __("all modules", "cluevo"), 0, $modules, $moduleId); ?></td>
        <td><?php cluevo_render_admin_table_filter("attempt", __("all attempts", "cluevo"), -1, $attempts, $attemptId); ?></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td><?php cluevo_render_admin_table_filter("completion-status", __("all", "cluevo"), 0, $completionFilter, $completionStatus); ?></td>
        <td><?php cluevo_render_admin_table_filter("success-status", __("all", "cluevo"), 0, $successFilter, $successStatus); ?></td>
        <td><?php cluevo_render_admin_table_filter("lesson-status", __("all", "cluevo"), 0, $lessonFilter, $lessonStatus); ?></td>
        <td></td>
        <td><div class="cluevo-btn auto cluevo-btn-secondary cluevo-reset-filters"><?php esc_attr_e("Reset Filters", "cluevo"); ?></div></td>
        </tr>
        <tr>
          <th class="left"><?php esc_html_e("User", "cluevo"); ?></th>
          <th class="left"><?php esc_html_e("Module", "cluevo"); ?></th>
          <th><?php esc_html_e("Attempt", "cluevo"); ?></th>
          <th><?php esc_html_e("Start", "cluevo"); ?></th>
          <th><?php esc_html_e("Last Activity", "cluevo"); ?></th>
          <th><?php esc_html_e("Min. Pts.", "cluevo"); ?></th>
          <th><?php esc_html_e("Max. Pts.", "cluevo"); ?></th>
          <th><?php esc_html_e("Points", "cluevo"); ?></th>
          <th><?php esc_html_e("Points %", "cluevo"); ?></th>
          <th><?php esc_html_e("Compl. Status", "cluevo"); ?></th>
          <th><?php esc_html_e("Success Status", "cluevo"); ?></th>
          <th><?php esc_html_e("Lesson Status (SCORM 1.2)", "cluevo"); ?></th>
          <th><?php esc_html_e("Credit", "cluevo"); ?></th>
          <th><?php esc_html_e("Tools", "cluevo"); ?></th>
        </tr>
    <?php foreach ($entries as $row) { ?>
        <tr>
          <td class="left cluevo-table-filter" data-target="#filter-user" data-id="<?php echo esc_attr($row->user_id); ?>"><?php echo esc_html($row->display_name); ?></td>
          <td class="left cluevo-table-filter" data-target="#filter-module" data-id="<?php echo esc_attr($row->module_id); ?>"><?php echo esc_html(mb_strimwidth($row->module_name, 0, 30, "...")); ?></td>
          <td class="cluevo-table-filter" data-target="#filter-attempt" data-id="<?php echo esc_attr($row->attempt_id); ?>"><?php echo esc_html($row->attempt_id); ?></td>
          <td><?php echo esc_html($row->date_started); ?></td>
          <td><?php echo esc_html($row->date_modified); ?></td>
          <td><?php echo esc_html(number_format($row->score_min, 2)); ?></td>
          <td><?php echo esc_html(number_format($row->score_max, 2)); ?></td>
          <td><?php echo esc_html(number_format($row->score_raw, 2)); ?></td>
          <td><?php echo esc_html(number_format($row->score_scaled * 100, 2)); ?>%</td>
          <td class="status <?php echo esc_attr($row->completion_status); ?> <?php echo ($row->scorm_version == "1.2") ? "invalid" : ""; ?> cluevo-table-filter" data-target="#filter-completion-status" data-id="<?php echo esc_attr($row->completion_status); ?>"><?php echo esc_html($row->completion_status); ?></td>
          <td class="status <?php echo esc_attr($row->success_status); ?> <?php echo ($row->scorm_version == "1.2") ? "invalid" : ""; ?> cluevo-table-filter" data-target="#filter-success-status" data-id="<?php echo esc_attr($row->success_status); ?>"><?php echo esc_html($row->success_status); ?></td>
          <td class="status <?php echo esc_attr($row->lesson_status); ?> <?php echo ($row->scorm_version != "1.2") ? "invalid" : ""; ?> cluevo-table-filter" data-target="#filter-lesson-status" data-id="<?php echo esc_attr($row->lesson_status); ?>"><?php echo esc_html($row->lesson_status); ?></td>
          <td class="credit-status <?php echo esc_attr($row->credit); ?>"><?php echo ($row->credit == "credit") ? "✔" : "✘"; ?></td>
          <td>
            <?php if (strpos($row->type_name, 'scorm') === false) { ?>
              <p class="cluevo-btn disabled" title="<?php esc_attr_e("No SCORM parameters available for this module type", "cluevo"); ?>" href="<?php echo esc_url(admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_REPORTS . "&tab=" . CLUEVO_ADMIN_TAB_REPORTS_SCORM_PARMS . "&module=" . $row->module_id), ['http', 'https']); ?>"><span class="dashicons dashicons-admin-settings"></span></p>
            <?php } else { ?>
              <a class="cluevo-btn" title="<?php esc_attr_e("Browse SCORM parameters", "cluevo"); ?>" href="<?php echo esc_url(admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_REPORTS . "&tab=" . CLUEVO_ADMIN_TAB_REPORTS_SCORM_PARMS . "&module=" . $row->module_id), ['http', 'https']); ?>"><span class="dashicons dashicons-admin-settings"></span></a>
            <?php } ?>
          </td>
        </tr>
    <?php } ?>
      </table>
    </form>
    <div class="cluevo-admin-table-pagination">
      <a class="cluevo-btn <?php if ($page < 0 || empty($page)) echo "disabled"; ?>" href="<?php echo esc_url(remove_query_arg("cur-page"), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-skipback"></span></a>
      <a class="cluevo-btn <?php if ($page < 0 || empty($page)) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", "" . $page - 1), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-back"></span></a>
      <div class="cluevo-select-page">
        Seite
        <select name="cur-page">
        <?php for($i = 0; $i < $pagination["pages"]; $i++) { ?>
          <option value="<?php echo esc_attr($i); ?>" <?php if ($i == $page) echo "selected"; ?>><?php echo esc_html($i + 1); ?></option>
        <?php } ?>
        </select>
        von <?php echo esc_html($pagination["pages"]); ?>
      </div>
      <a class="cluevo-btn <?php if (empty($nextPage)) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", "$nextPage"), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-forward"></span></a>
      <a class="cluevo-btn <?php if (empty($nextPage) && $nextPage < $pagination["pages"]) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", $pagination["pages"] - 1), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-skipforward"></span></a>
    </div>
  <?php } else { ?>
      <div class="cluevo-admin-notice cluevo-notice-info">
        <p><?php esc_html_e("No progress records found.", "cluevo"); ?></p>
      </div>
  <?php } ?>
  <?php
  }

  function cluevo_render_reports_scorm_parms_tab() {
    $userId = (!empty($_GET["user"]) && is_numeric($_GET["user"])) ? (int)$_GET["user"] : null;
    $moduleId = (!empty($_GET["module"]) && is_numeric($_GET["module"])) ? (int)$_GET["module"] : null;
    $attemptId = (isset($_GET["attempt"]) && is_numeric($_GET["attempt"]) && (int)$_GET["attempt"] >= 0) ? (int)$_GET["attempt"] : null;
    $parameter = (!empty($_GET["parameter"])) ? cluevo_strip_non_scorm_parm_chars($_GET["parameter"]) : null;
    $page = (isset($_GET["cur-page"]) && is_numeric($_GET["cur-page"]) && (int)$_GET["cur-page"] >= 0) ? (int)$_GET["cur-page"] : 0;
    $perPage = 100;

    $users = cluevo_get_parameters_filter_content( "user_id", "u.display_name", [ "module_id" => $moduleId, "parameter" => $parameter, "attempt_id" => $attemptId ] );
    $modules = cluevo_get_parameters_filter_content( "module_id", "m.module_name", [ "user_id" => $userId, "parameter" => $parameter, "attempt_id" => $attemptId ] );
    $attempts = cluevo_get_parameters_filter_content( "attempt_id", "attempt_id", [ "user_id" => $userId, "parameter" => $parameter, "module_id" => $moduleId ] );
    $availableParms = cluevo_get_parameters_filter_content( "parameter", "parameter", [ "module_id" => $moduleId, "user_id" => $userId, "attempt_id" => $attemptId ] );
    $parameters = cluevo_get_parameters([ "module_id" => $moduleId, "user_id" => $userId, "attempt_id" => $attemptId, "parameter" => $parameter ], $page, $perPage);
    $pagination = cluevo_get_parameter_pagination([ "module_id" => $moduleId, "user_id" => $userId, "attempt_id" => $attemptId, "parameter" => $parameter ], $perPage);
    $nextPage = ($page + 1 < $pagination["pages"]) ? $page + 1 : null;
    $active_tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : CLUEVO_ADMIN_TAB_REPORTS_MAIN;
    $active_page = CLUEVO_ADMIN_PAGE_REPORTS;

    wp_register_script( 'cluevo-admin-table-filter', plugins_url('/js/admin-table-filter.js', __DIR__), array(), CLUEVO_VERSION, true );
    wp_enqueue_script( "cluevo-admin-table-filter" );
  ?>
    <h1><?php echo esc_html($pagination["items"]) . " " . esc_html__("records found", "cluevo"); ?></h1>
    <div class="cluevo-module-progress-filter-container">
    </div>

    <?php if (!empty($parameters)) { ?>
    <form action="<?php echo esc_url(admin_url("admin.php?page=$active_page&tab=$active_tab"), ['http', 'https']); ?>">
      <input type="hidden" name="page" value="<?php echo esc_attr($active_page); ?>" />
      <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>" />
      <input type="hidden" name="cur-page" id="cur-page" value="<?php echo esc_attr($page); ?>" />
      <table class="cluevo-admin-table cluevo-filtered-table">
        <tr class="filter-row">
          <td></td>
          <td><?php cluevo_render_admin_table_filter("module", __("all modules", "cluevo"), 0, $modules, $moduleId); ?></td>
          <td><?php cluevo_render_admin_table_filter("user", __("all users", "cluevo"), 0, $users, $userId); ?></td>
          <td><?php cluevo_render_admin_table_filter("attempt", __("all attempts", "cluevo"), -1, $attempts, $attemptId); ?></td>
          <td><?php cluevo_render_admin_table_filter("parameter", __("all parameters", "cluevo"), 0, $availableParms, $parameter); ?></td>
          <td></td>
          <td></td>
          <td><div class="cluevo-btn auto cluevo-btn-secondary cluevo-reset-filters"><?php esc_attr_e("Reset Filters", "cluevo"); ?></div></td>
        </tr>
        <tr>
          <th>#</th>
          <th class="left"><?php esc_html_e("Module", "cluevo"); ?></th>
          <th class="left"><?php esc_html_e("User", "cluevo"); ?></th>
          <th><?php esc_html_e("Attempt", "cluevo"); ?></th>
          <th class="left"><?php esc_html_e("Parameter", "cluevo"); ?></th>
          <th><?php esc_html_e("Worth", "cluevo"); ?></th>
          <th><?php esc_html_e("Created", "cluevo"); ?></th>
          <th><?php esc_html_e("Modified", "cluevo"); ?></th>
          <!-- <th><?php esc_html_e("Tools", "cluevo"); ?></th> -->
        </tr>
        <?php foreach ($parameters as $p) { ?>
        <tr>
          <td><?php echo esc_html($p->id); ?></td>
          <td class="left cluevo-table-filter" data-target="#filter-module" data-id="<?php echo esc_attr($p->module_id); ?>"><?php echo esc_html(mb_strimwidth($p->module_name, 0, 20, "...")); ?></td>
          <td class="left cluevo-table-filter" data-target="#filter-user" data-id="<?php echo esc_attr($p->user_id); ?>"><?php echo esc_html($p->display_name); ?></td>
          <td class="cluevo-table-filter" data-target="#filter-attempt" data-id="<?php echo esc_attr($p->attempt_id); ?>"><?php echo esc_html($p->attempt_id); ?></td>
          <td class="left cluevo-table-filter" data-target="#filter-parameter" data-id="<?php echo esc_attr($p->parameter); ?>"><?php echo esc_html($p->parameter); ?></td>
          <td><?php echo esc_html(mb_strimwidth($p->value, 0, 20, "...")); ?></td>
          <td><?php echo esc_html($p->date_added); ?></td>
          <td><?php echo esc_html($p->date_modified); ?></td>
          <!-- <td></td> -->
        </tr>
        <?php } ?>
      </table>
    </form>
    <div class="cluevo-admin-table-pagination">
      <a class="cluevo-btn <?php if ($page < 0 || empty($page)) echo "disabled"; ?>" href="<?php echo esc_url(remove_query_arg("cur-page"), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-skipback"></span></a>
      <a class="cluevo-btn <?php if ($page < 0 || empty($page)) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", "" . $page - 1), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-back"></span></a>
      <div class="cluevo-select-page">
        Seite
        <select name="cur-page">
        <?php for($i = 0; $i < $pagination["pages"]; $i++) { ?>
          <option value="<?php echo esc_attr($i); ?>" <?php if ($i == $page) echo "selected"; ?>><?php echo esc_html($i + 1); ?></option>
        <?php } ?>
        </select>
        von <?php echo esc_html($pagination["pages"]); ?>
      </div>
      <a class="cluevo-btn <?php if (empty($nextPage)) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", "$nextPage"), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-forward"></span></a>
      <a class="cluevo-btn <?php if (empty($nextPage) && $nextPage < $pagination["pages"]) echo "disabled"; ?>" href="<?php echo esc_url(add_query_arg("cur-page", $pagination["pages"] - 1), ['http', 'https']); ?>"><span class="dashicons dashicons-controls-skipforward"></span></a>
    </div>
    <?php } else { ?>
      <div class="cluevo-admin-notice cluevo-notice-info">
        <p><?php esc_html_e("No SCORM parameters found.", "cluevo"); ?></p>
      </div>
    <?php } ?>
  <?php
  }
}
?>
