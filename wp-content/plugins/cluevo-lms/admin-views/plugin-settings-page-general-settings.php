<?php
function cluevo_register_settings_section($strSlug, $strTitle, $strDescription = "") {
  if (empty($strSlug)) return false;
  if (empty($strTitle)) return false;

  $strSlug = sanitize_title($strSlug);

  if (!array_key_exists($strSlug, $GLOBALS["cluevo_settings"])) {
    $GLOBALS["cluevo_settings"][$strSlug] = [ "title" => $strTitle, "description" => $strDescription, "sub_sections" => []];
  } else {
    $GLOBALS["cluevo_settings"][$strSlug]["title"] = $strTitle;
    $GLOBALS["cluevo_settings"][$strSlug]["description"] = $strDescription;
  }
}

function cluevo_register_settings_sub_section($strSection, $strSlug, $strTitle, $strDescription = "") {
  if (empty($strSection)) return false;
  if (empty($strSlug)) return false;
  if (empty($strTitle)) return false;

  $strSection = sanitize_title($strSection);
  $strSlug = sanitize_title($strSlug);

  if (!array_key_exists($strSection, $GLOBALS["cluevo_settings"])) return false;

  if (!array_key_exists($strSlug, $GLOBALS["cluevo_settings"][$strSection]["sub_sections"])) {
    $GLOBALS["cluevo_settings"][$strSection]["sub_sections"][$strSlug] = [ "title" => $strTitle, "description" => $strDescription, "settings" => [] ];
  } else {
    $GLOBALS["cluevo_settings"][$strSection]["sub_sections"]["title"] = $strTitle;
    $GLOBALS["cluevo_settings"][$strSection]["sub_sections"]["description"] = $strDescription;
  }
}

function cluevo_register_setting($strSection, $strSubSection, $strSlug, $strTitle, $strDescription = "", $renderCallback = null, $sanitizeCallback = null) {
  if (empty($strSection)) return false;
  if (empty($strSubSection)) return false;
  if (empty($strSlug)) return false;
  if (empty($strTitle)) return false;
  if (empty($renderCallback)) return false;

  $strSection = sanitize_title($strSection);
  $strSubSection = sanitize_title($strSubSection);
  $strSlug = sanitize_title($strSlug);

  add_action("cluevo_settings_setting_callback_{$strSlug}", $renderCallback);

  if (!array_key_exists($strSlug, $GLOBALS["cluevo_settings"][$strSection]["sub_sections"][$strSubSection]["settings"])) {
    $GLOBALS["cluevo_settings"][$strSection]["sub_sections"][$strSubSection]["settings"][$strSlug] = [
      "title" => $strTitle, 
      "description" => $strDescription,
      "render_callback" => $renderCallback,
      "sanitize_callback" => $sanitizeCallback
    ];
  } else {
    return false;
  }
  //print_r($GLOBALS["cluevo_settings"]);
}

class CluevoSettingsPage {

  public static function init() {

    $GLOBALS["cluevo_settings"] = [];

    do_action("cluevo_register_general_settings");

    cluevo_register_settings_section("general-settings", __("General Settings", "cluevo"));
    cluevo_register_settings_section("security", __("Security", "cluevo"), __("You can set various security related options here", "cluevo"));

    cluevo_register_settings_sub_section("general-settings", "module-display", __("Module Display", "cluevo"), __("You can customize the way modules are displayed on your pages.", "cluevo"));
    cluevo_register_setting("general-settings", "module-display", "cluevo-modules-display-mode", __("Display Mode", "cluevo"), __("We recommended the display mode Lightbox where each module opens on the same page.", "cluevo"), "CluevoSettingsPage::render_display_mode");
    cluevo_register_setting("general-settings", "module-display", "cluevo-modules-display-position", __("Position", "cluevo"), __("This setting specifies where you want your modules to display on your pages. This is only applicable when using the iframe display mode.", "cluevo"), "CluevoSettingsPage::render_display_position");

    cluevo_register_settings_sub_section("general-settings", "user-levels", __("User Settings", "cluevo"));
    cluevo_register_setting("general-settings", "user-levels", "cluevo-auto-add-new-users", __("New users are LMS users by default", "cluevo"), null, "CluevoSettingsPage::render_auto_add_new_users");
    cluevo_register_setting("general-settings", "user-levels", "cluevo-max-level", __("Max. Level", "cluevo"), null, "CluevoSettingsPage::render_max_level");
    cluevo_register_setting("general-settings", "user-levels", "cluevo-exp-first-level", __("Exp. required for first level up", "cluevo"), null, "CluevoSettingsPage::render_first_level_exp");
    cluevo_register_setting("general-settings", "user-levels", "cluevo-level-titles", __("Titles", "cluevo"), null, "CluevoSettingsPage::render_level_titles", "cluevo_sanitize_level_titles");

    cluevo_register_settings_sub_section("general-settings", "advanced", __("Advanced", "cluevo"));
    cluevo_register_setting("general-settings", "advanced", "cluevo-delete-data-on-uninstall", __("Delete all data when uninstalling", "cluevo"), __("By enabling this option the uninstaller removes all CLUEVO data when you uninstall the plugin", "cluevo"), "CluevoSettingsPage::render_uninstall_data_handling");

    cluevo_register_settings_sub_section("security", "module-security", __("Module Security", "cluevo"));
    cluevo_register_setting("security", "module-security", "cluevo-basic-module-security", __("Enable basic module security", "cluevo"), __("Prohibits access to modules from outside your site", "cluevo"), "CluevoSettingsPage::render_basic_module_security");

    cluevo_register_settings_sub_section("security", "login", __("Login System", "cluevo"));
    cluevo_register_setting("security", "login", "cluevo-login-enabled", __("Enable Login Page", "cluevo"), __("Enables the CLUEVO login page", "cluevo"), "CluevoSettingsPage::render_enable_login_page");
    cluevo_register_setting("security", "login", "cluevo-login-page", __("Login Page", "cluevo"), __("Use this page to login", "cluevo"), "CluevoSettingsPage::render_set_login_page");

    cluevo_register_setting("security", "module-security", "cluevo-force-https-embeds", __("Force HTTPS for modules", "cluevo"), __("This option forces HTTPS for module embeds, regardless of how your WordPress site URL is configured.", "cluevo"), "CluevoSettingsPage::render_force_https_module_embeds");

    add_action("add_option_cluevo-basic-module-security", "CluevoSettingsPage::handle_basic_security_added", 10, 2);
    add_action("update_option_cluevo-basic-module-security", "CluevoSettingsPage::handle_basic_security_change", 10, 2);
  }

  public static function handle_basic_security_change($old, $new) {
    if (empty($new)) {
      self::remove_basic_security();
    } else {
      if ($new == "on") {
        self::add_basic_security();
      }
    }
  }

  public static function handle_basic_security_added($opt, $value) {
    if (empty($value)) {
      self::remove_basic_security();
    } else {
      if ($value == "on") {
        self::add_basic_security();
      }
    }
  }

  public static function add_basic_security() {
    $dirs = [];
    $dirs[] = cluevo_get_conf_const("CLUEVO_ABS_MODULE_PATH");
    $dirs[] = cluevo_get_conf_const("CLUEVO_ABS_MODULE_ARCHIVE_PATH");
    // see http://tltech.com/info/referrer-htaccess/
    // for cond: can't compare one server var with another
    $content = '<IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteCond %{HTTP_HOST}@@%{HTTP_REFERER} !^([^@]*)@@https?://\1/.*
      RewriteRule ^ - [F]
    </IfModule>';
    foreach ($dirs as $dir) {
      $file = $dir . ".htaccess";
      @file_put_contents($file, $content);
    }
  }

  public static function remove_basic_security() {
    $dirs = [];
    $dirs[] = cluevo_get_conf_const("CLUEVO_ABS_MODULE_PATH");
    $dirs[] = cluevo_get_conf_const("CLUEVO_ABS_MODULE_ARCHIVE_PATH");
    foreach ($dirs as $dir) {
      $file = $dir . ".htaccess";
      if (file_exists($file)) {
        @unlink($file);
      }
    }
  }

  public static function render_enable_login_page() {
    $curOpt = get_option("cluevo-login-enabled", "");
    $checked = ($curOpt === "on") ? "checked" : "";
    echo '<input type="checkbox" name="cluevo-login-enabled" ' . $checked . ' />';
  }

  public static function render_set_login_page() {
    $ids = get_all_page_ids();
    $curOpt = get_option("cluevo-login-page", 0); 
    echo '<select size="1" name="cluevo-login-page">';
    echo '<option value="0">' . esc_html__("CLUEVO Login", "cluevo") . '</option>';
    echo '<option value="-1">' . esc_html__("WordPress Login", "cluevo") . '</option>';
    if (!empty($ids)) {
      foreach ($ids as $id) {
        $selected = ($id == $curOpt) ? "selected" : "";
        echo '<option value="' . esc_attr($id) . '" ' . $selected . '>' . esc_html(get_the_title($id)) . '</option>';
      }
    }
    echo '</select>';
  }

  /**
   * Outputs a checkbox to enable/disable automatically adding new wp users as lms users
   *
   */
  public static function render_auto_add_new_users() {
    $curOpt = get_option("cluevo-auto-add-new-users", "on");
    $checked = ($curOpt === "on") ? "checked" : "";
    echo '<input type="checkbox" name="cluevo-auto-add-new-users" ' . $checked . ' />';
  }

  public static function render_force_https_module_embeds() {
    $curOpt = get_option("cluevo-force-https-embeds", "");
    $checked = ($curOpt === "on") ? "checked" : "";
    echo '<input type="checkbox" name="cluevo-force-https-embeds" ' . $checked . ' />';
  }

  public static function render_display_mode() {
    $curOpt = get_option("cluevo-modules-display-mode");
    ?>
    <div class="cluevo-radio-group">
      <div>
        <label><input type="radio" name="cluevo-modules-display-mode" value="Popup" <?php if ($curOpt === "Popup") echo "checked" ?>/> Popup</label> <?php esc_html_e("Make sure your users allow popups for your site", "cluevo"); ?>
      </div>
      <div>
        <label><input type="radio" name="cluevo-modules-display-mode" value="Iframe" <?php if ($curOpt === "Iframe") echo "checked" ?>/> Iframe</label> <?php esc_html_e("This display mode loads each module on it's own page", "cluevo"); ?>
      </div>
      <div>
        <label><input type="radio" name="cluevo-modules-display-mode" value="Lightbox" <?php if ($curOpt === "Lightbox") echo "checked" ?>/> Lightbox</label> <?php esc_html_e("This mode opens your modules in an overlay on the same page", "cluevo"); ?>
      </div>
    </div>
  <?php 
  }

  /**
   * Outputs a dropdown to select a display position
   *
   */
  public static function render_display_position() {
    $curOpt = get_option("cluevo-modules-display-position");
  ?>
    <div class="cluevo-radio-group">
      <div>
        <label><input type="radio" name="cluevo-modules-display-position" value="start" <?php if ($curOpt === "start") echo "checked" ?>/> <?php esc_html_e("Top of the page", "cluevo"); ?></label>
      </div>
      <div>
        <label><input type="radio" name="cluevo-modules-display-position" value="end" <?php if ($curOpt === "end") echo "checked" ?>/> <?php esc_html_e("Bottom of the page", "cluevo"); ?></label>
      </div>
    </div>
  <?php
  }

  /**
   * Outputs a checkbox to select whether modules are only scored once or not
   *
   */
  function cluevo_display_modules_only_score_once() {
    ?>
      <label for="modules-only-score-once">
          <input id="modules-only-score-once" type="checkbox" value="1" name="cluevo-modules-only-score-once" <?php checked( get_option( 'cluevo-modules-only-score-once', false) ); ?>>
      </label>
      <?php
  }

  /**
   * Outputs a textarea containing levels and the corresponding titles
   *
   */
  public static function render_level_titles() {
    $titles =  get_option('cluevo-level-titles');
    if (empty($titles)) $titles = new StdClass();
    $displayTitles = '';
    if (!empty($titles)) {
      foreach ($titles as $lvl => $title) {
        $displayTitles .= "$lvl: $title\n";
      }
    }
  ?>
    <div is="cluevo-user-titles" id="cluevo-level-titles" :titles="<?php echo esc_attr(json_encode($titles)); ?>" inline-template>
      <div class="cluevo-user-titles">
        <input type="hidden" name="cluevo-level-titles" :value="titleText" />
        <table class="cluevo-admin-table cluevo-level-table">
          <tr>
            <th>Level</th>
            <th class="left">Title</th>
            <th class="left">Tools</th>
          </tr>
          <template v-for="title of list" inline-template>
            <tr>
              <td class="left"><input type="number" v-model="title.level" min="0" /></td>
              <td class="left"><input type="text" v-model="title.name" /></td>
              <td class="left">
              <input type="button" @click="removeTitle(title)" class="cluevo-btn cluevo-btn-small" value="<?php esc_attr_e("Remove", "cluevo"); ?>" />
              </td>
            </tr>
          </template>
          <tr>
            <td>
              <input type="number" v-model="newLevel" min="0" />
            </td>
            <td>
              <input type="text" v-model="newName" />
            </td>
            <td>
            <input type="button" class="cluevo-btn" @click="addTitle" :disabled="!isValidInput" value="<?php esc_attr_e("Add Title", "cluevo"); ?>" />
            </td>
          </tr>
        </table>
      </div>
    </div>
  <?php
  }

  /**
   * Outputs an input field to enter the amount of exp needed for the first level up
   *
   */
  public static function render_first_level_exp() {
  ?>
    <input type="text" name="cluevo-exp-first-level" value="<?php echo esc_attr( get_option('cluevo-exp-first-level', CLUEVO_DEFAULT_FIRST_LEVEL_EXP) ); ?>" />
  <?php
  }

  /**
   * Outputs an input field to enter the max. possible level
   *
   */
  public static function render_max_level() {
  ?>
   <input type="number" name="cluevo-max-level" value="<?php echo esc_attr( get_option('cluevo-max-level', CLUEVO_DEFAULT_MAX_LEVEL) ); ?>" />
  <?php
  }

  /**
   * Outputs a checkbox to enable/disable basic module security
   *
   */
  public static function render_basic_module_security() {
    $curOpt = get_option("cluevo-basic-module-security", "");
    $checked = ($curOpt === "on") ? "checked" : "";
    echo '<input type="checkbox" name="cluevo-basic-module-security" ' . $checked . ' />';
    $dirs = [];
    $dirs[] = cluevo_get_conf_const("CLUEVO_ABS_MODULE_PATH");
    $dirs[] = cluevo_get_conf_const("CLUEVO_ABS_MODULE_ARCHIVE_PATH");
    $out = " (";
    foreach ($dirs as $dir) {
      $file = $dir . ".htaccess";
      if (file_exists($file)) {
        $out .= esc_html(basename($dir)) . ": " . esc_html__("secured", "cluevo")  . ", ";
      } else {
        $out .= esc_html(basename($dir)) . ": " . esc_html__("not secured", "cluevo")  . ", ";
      }
    }
    $out = trim($out, ", ") . ")";
    echo $out;
  }

  /**
   * Outputs a checkbox to select whether cluevo data is retained or deleted on uninstall
   *
   */
  public static function render_uninstall_data_handling() {
    $curOpt = get_option("cluevo-delete-data-on-uninstall", "");
    $checked = ($curOpt === "on") ? "checked" : "";
    echo '<input type="checkbox" name="cluevo-delete-data-on-uninstall" ' . $checked . ' />';
  }

  public static function save_settings() {
    if (!empty($_POST["cluevo-save-settings"])) {
      $updated = false;
      foreach ($GLOBALS["cluevo_settings"] as $sectionSlug => $section) {
        foreach ($section["sub_sections"] as $subSlug => $sub) {
          foreach ($sub["settings"] as $slug => $setting) {
            if (array_key_exists($slug, $_POST)) {
              if (!empty($setting["sanitize_callback"])) {
                add_filter("sanitize_option_{$slug}", $setting["sanitize_callback"]);
              }
              $result = update_option($slug, $_POST[$slug]);
              $updated = ($result === true) ? true : $updated;
            } else {
              $result = update_option($slug, "");
              $updated = ($result === true) ? true : $updated;
            }
          }
        }
      }
      if ($updated) {
        $url = add_query_arg("saved", "true"); 
        header("Location: {$url}");
        die($url);
      }
    }
  }

  public static function render() {
    if (!is_array($GLOBALS["cluevo_settings"])) return;
    //echo "<pre>";
    //echo __("Display Mode", "cluevo");
    //print_r($GLOBALS["cluevo_settings"]);
    //echo "</pre>";

    wp_register_script(
      "vue-js",
      "https://cdn.jsdelivr.net/npm/vue/dist/vue.min.js",
      "",
      "",
      true
    );
    wp_enqueue_script('vue-js');

    wp_register_script(
      'cluevo-admin-settings-view',
      plugins_url('/js/settings.admin.js', plugin_dir_path(__FILE__)),
      array("vue-js", "cluevo-admin-settings-titles"),
      CLUEVO_VERSION,
      true
    );
    wp_enqueue_script("cluevo-admin-settings-view");

    wp_register_script(
      'cluevo-admin-settings-titles',
      plugins_url('/js/settings.titles.js', plugin_dir_path(__FILE__)),
      array("vue-js"),
      CLUEVO_VERSION,
      true
    );
    wp_enqueue_script("cluevo-admin-settings-titles");
    $page = CLUEVO_ADMIN_PAGE_GENERAL_SETTINGS;
    do_action('cluevo_init_admin_page');
?>
<div class="cluevo-admin-page-container cluevo-general-settings">
  <div class="cluevo-admin-page-title-container">
    <h1><?php esc_html_e("General Settings", "cluevo"); ?></h1>
    <img class="plugin-logo" src="<?php echo plugins_url("/assets/logo-white.png", plugin_dir_path(__FILE__)); ?>" />
  </div>
  <div class="cluevo-admin-page-content-container">
  <?php if (!empty($_GET["saved"])) { ?>
    <div class="cluevo-admin-notice cluevo-notice-success">
      <p><?php esc_html_e("Saved", "cluevo"); ?></p>
    </div>
    <?php } ?>
    <div id="cluevo-settings-page">
      <form action="<?php echo esc_html(admin_url("admin.php?page=$page")); ?>" method="post">
        <?php wp_nonce_field( "cluevo-save-settings", "cluevo-save-settings-nonce" ); ?>
        <div is="tabs" inline-template class="cluevo-tabs">
          <div class="cluevo-tabs">
            <h2 class="nav-tab-wrapper cluevo">
              <a v-for="(tab, index) in tabs" @click="selectTab(tab.id, $event)" class="nav-tab" :class="{ 'nav-tab-active': tab.isActive }" >{{ tab.title }}</a>
            </h2>
            <div class="tabs">
            <?php foreach ($GLOBALS["cluevo_settings"] as $sectionSlug => $section) { ?>
              <div is="tab" 
                inline-template
                id="<?php echo esc_attr($sectionSlug); ?>"
                title="<?php echo esc_attr($section["title"]); ?>"
              >
                <section v-show="isActive" class="cluevo-tab-content">
                <div class="cluevo-settings-tab-before-description"><?php do_action("cluevo_settings_tab_before_description-$sectionSlug"); ?></div>
                <?php if (!empty($section["description"])) { ?>
                  <p class="cluevo-settings-section-description <?php echo esc_attr($sectionSlug); ?>">
                  <?php echo esc_html($section["description"]); ?>
                  </p>
                <?php } ?>
                <div class="cluevo-settings-tab-after-description"><?php do_action("cluevo_settings_tab_after_description-$sectionSlug"); ?></div>
                <?php if (!empty($section["sub_sections"])) { ?>
                  <?php foreach ($section["sub_sections"] as $subSlug => $subSection) { ?>
                  <details>
                    <summary><?php echo esc_attr($subSection["title"]); ?></summary>
                      <?php do_action("cluevo_settings_sub_section_start-$sectionSlug-$subSlug"); ?>
                      <div class="cluevo-setting-sub-section-content <?php echo esc_attr($subSlug); ?>">
                      <?php if (!empty($subSection["description"])) { ?>
                        <p class="cluevo-settings-sub-section-description <?php echo esc_attr($subSlug); ?>">
                        <?php echo esc_html($subSection["description"]); ?>
                        </p>
                      <?php } ?>
                    <?php //if (is_callable($subSection["callback"])) $subSection["callback"](); ?>
                      <?php if (!empty($subSection["settings"])) { ?>
                      <?php foreach ($subSection["settings"] as $slug => $setting) { ?>
                      <div class="cluevo-setting <?php echo esc_attr($slug); ?>">
                        <div class="cluevo-setting-content">
                          <h3><?php echo esc_html($setting["title"]); ?></h3>
                          <div class="cluevo-setting-callback <?php echo esc_attr($slug); ?>"><?php do_action("cluevo_settings_setting_callback_$slug"); ?></div>
                        </div>
                        <?php if (!empty($setting["description"])) { ?>
                          <p class="cluevo-settings-setting-description <?php echo esc_attr($slug); ?>">
                          <?php echo esc_html($setting["description"]); ?>
                          </p>
                        <?php } ?>
                      </div>
                      <?php } ?>
                    <?php } ?>
                    </div>
                    <?php do_action("cluevo_settings_sub_section_end-$sectionSlug-$subSlug"); ?>
                  </details>
                <?php } ?>
              <?php } ?>
                </section>
              </div>
            <?php } ?>
            </div>
          </div>
        </div>
        <input type="submit" name="cluevo-save-settings" value="<?php echo esc_attr_e("Save", "cluevo"); ?>" class="cluevo-btn primary cluevo-settings-save-button" />
      </form>
    </div>
  </div>
</div>
<?php } // /render
} // /CluevoSettingsPage

if (is_admin()) {
  add_action("admin_init", "CluevoSettingsPage::init");
  add_action("admin_init", "CluevoSettingsPage::save_settings");
}

