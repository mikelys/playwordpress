<?php
if (is_admin()) {
  function cluevo_init_user_management_page()
  {
    wp_register_script(
      "vue-js",
      "https://cdn.jsdelivr.net/npm/vue/dist/vue.min.js",
      "",
      "",
      true
    );
    wp_enqueue_script('vue-js');
    wp_register_script( 'lodash-js', plugins_url('/js/lodash.min.js', plugin_dir_path(__FILE__)), null, false, false );  // utilities
    wp_add_inline_script( 'lodash-js', 'window.lodash = _.noConflict();', 'after' ); // gutenberg compatibility

    wp_register_script(
      'cluevo-admin-user-view',
      plugins_url('/js/user.admin.js', plugin_dir_path(__FILE__)),
      array("vue-js", "lodash-js"),
      CLUEVO_VERSION,
      true
    );
    wp_localize_script( 'cluevo-admin-user-view',
      'lang_strings', array(
        'no_users_found' => esc_html__("No users found.", "cluevo"),
        'users_heading' => esc_html__("Users", "cluevo"),
        'groups_heading' => esc_html__("Groups", "cluevo"),
        'delete_user' => esc_html__("Really delete this user?", "cluevo"),
        'delete_group' => esc_html__("Really delete this group?", "cluevo"),
        'add_user_button_label' => esc_html__('Add User', "cluevo"),
        'add_group_button_label' => esc_html__('Add Group', "cluevo"),
        'add_user_dialog_title' => esc_html__('Add User', "cluevo"),
        'add_group_dialog_title' => esc_html__('Add Group', "cluevo"),
        'search_user' => esc_html__('Find user', "cluevo"),
        'name' => esc_html__('Name', "cluevo"),
        'selected_users' => esc_html__('Selected users:', "cluevo"),
        'add_users' => esc_html__('Add users', "cluevo"),
        'header_name' => esc_html__('Username', "cluevo"),
        'add_group' => esc_html__('Create Group', "cluevo"),
        'edit_group' => esc_html__('Save Group', "cluevo"),
        'header_role' => esc_html__('Role', "cluevo"),
        'header_groups' => esc_html__('Groups', "cluevo"),
        'header_date_last_seen' => esc_html__('Last Seen', "cluevo"),
        'header_date_added' => esc_html__('Date Created', "cluevo"),
        'header_date_modified' => esc_html__('Date Modified', "cluevo"),
        'header_date_role_since' => esc_html__('Role Since', "cluevo"),
        'header_tools' => esc_html__('Tools', "cluevo"),
        'button_label_remove_from_group' => esc_html__('X', "cluevo"),
        'button_label_promote' => esc_html__('Promote to trainer', "cluevo"),
        'button_label_demote' => esc_html__('Demote to user', "cluevo"),
        'option_select_a_group' => esc_html__('Select a Group', "cluevo"),
        'cancel' => esc_html__('Cancel', "cluevo"),
        'new_group_name_label' => esc_html__('Name', "cluevo"),
        'new_group_desc_label' => esc_html__('Description', "cluevo"),
        'headline_group_information' => esc_html__('Group Information', "cluevo"),
        'members' => esc_html__("Members", "cluevo"),
        'header_group_name' => esc_html__("Group Name", "cluevo"),
        'header_group_description' => esc_html__("Description", "cluevo"),
        'header_member_count' => esc_html__("Members", "cluevo"),
        'header_trainer_count' => esc_html__("Trainer", "cluevo"),
        'confirm_delete_group' => esc_html__("Really delete this group?", "cluevo"),
        'name_trainer' => esc_html__("Trainer", "cluevo"),
        'name_student' => esc_html__("Student", "cluevo"),
        'user_selector_label' => esc_html__("User", "cluevo"),
        'group_selector_label' => esc_html__("Group", "cluevo"),
        'no_perm' => esc_html__("No Access", "cluevo"),
        'perm_visible' => esc_html__("Visible", "cluevo"),
        'perm_access' => esc_html__("Full Access", "cluevo"),
        'or' => esc_html__("or", "cluevo"),
        'module_not_executable_warning' => esc_html__("Warning: This module is visible but can't be opened by this user/group.", "cluevo"),
        'perms_overridden' => esc_html__("Warning: This permission is currently overridden by a group permission: ", "cluevo"),
        'select_group_or_user' => esc_html__("Select a group or user to create a permission structure for.", "cluevo"),
        'button_save' => esc_html__("Apply Permissions", "cluevo"),
        'legend_locked' => esc_html__("No Access. The element is not visible and can't be accessed.", "cluevo"),
        'legend_visible' => esc_html__("Visible. The element is visible but can't be accessed.", "cluevo"),
        'legend_unlocked' => esc_html__("Open. The element is visible and can be accessed.", "cluevo"),
        'legend_course' => esc_html__("Course", "cluevo"),
        'legend_chapter' => esc_html__("Chapter", "cluevo"),
        'legend_module' => esc_html__("Module", "cluevo"),
        'tab_title_permissions' => esc_html__("Permissions", "cluevo"),
        'email_group_info' => esc_html__("This is a e-mail group. Every user that has an e-mail address from this domain is automatically a member of this group.", "cluevo")
      )
    );
    wp_localize_script( 'cluevo-admin-user-view',
      'misc_strings', array(
        'reporting_page' => CLUEVO_ADMIN_PAGE_REPORTS,
        'progress_tab' => CLUEVO_ADMIN_TAB_REPORTS_PROGRESS,
        'scorm_tab' => CLUEVO_ADMIN_TAB_REPORTS_SCORM_PARMS
      )
    );
    wp_localize_script( 'cluevo-admin-user-view', 'cluevoWpApiSettings', array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );  // needed for ajax requests

    wp_enqueue_script('cluevo-admin-user-view');
    do_action('cluevo_init_admin_page');
  }

  function cluevo_render_user_management_page()
  {
    cluevo_init_user_management_page();
  ?>
  <div class="cluevo-admin-page-container">
    <div class="cluevo-admin-page-title-container">
      <h1><?php esc_html_e("User Administration", "cluevo"); ?></h1>
      <img class="plugin-logo" src="<?php echo esc_url(plugins_url("/assets/logo-white.png", plugin_dir_path(__FILE__)), ['http', 'https']); ?>" />
    </div>
    <div class="cluevo-admin-page-content-container">
      <div id="user-admin-app" />
    </div>
  </div>

  <?php
  }
}
?>
