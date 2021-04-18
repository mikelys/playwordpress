<?php
if (is_admin()) {
  function cluevo_init_competence_page() {
    // development version
    //wp_register_script(
      //"vue-js",
      //"https://cdn.jsdelivr.net/npm/vue/dist/vue.js",
      //"",
      //"",
      //true
    //);

    // production version
    wp_register_script(
      "vue-js",
      "https://cdn.jsdelivr.net/npm/vue",
      "",
      "",
      true
    );
    wp_enqueue_script('vue-js');

    wp_register_script(
      'cluevo-admin-competence-utilities',
      plugins_url('/js/competence.utilities.js', plugin_dir_path(__FILE__)),
      "",
      CLUEVO_VERSION,
      true
    );
    wp_localize_script( 'cluevo-admin-competence-utilities',
      'compApiSettings',
      array(
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' )
      )
    );
    wp_enqueue_script('cluevo-admin-competence-utilities');
    do_action('cluevo_init_admin_page');
  }

  function cluevo_render_competences_tab() {
    wp_register_script(
      'cluevo-admin-competence-view',
      plugins_url('/js/competence.admin.js', plugin_dir_path(__FILE__)),
      array("vue-js"),
      CLUEVO_VERSION,
      true
    );
    wp_localize_script( 'cluevo-admin-competence-view',
      'compApiSettings',
      array(
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' )
      )
    );
    wp_localize_script( 'cluevo-admin-competence-view',
      'lang_strings', array(
        'no_comps_found' => __("No competences found.", "cluevo"),
        'competence_create_error' => __("Failed to create competence. A competence with this name may already exist.", "cluevo")
      )
    );
    wp_enqueue_script('cluevo-admin-competence-view');
  ?>
  <script type="text/x-template" id="competence-app-header-template">
    <tr>
      <th>#</th>
      <th class="left"><?php esc_html_e("Name", "cluevo"); ?></th>
      <th><?php esc_html_e("Competence Groups", "cluevo"); ?></th>
      <th><?php esc_html_e("Modules", "cluevo"); ?></th>
      <th class="left"><?php esc_html_e("Created by", "cluevo"); ?></th>
      <th><?php esc_html_e("Date Created", "cluevo"); ?></th>
      <th class="left"><?php esc_html_e("Modified by", "cluevo"); ?></th>
      <th><?php esc_html_e("Date Modified", "cluevo"); ?></th>
      <th class="left"><?php esc_html_e("Tools", "cluevo"); ?></th>
    </tr>
  </script>
  <script type="text/x-template" id="competence-editor-template">
    <transition name="modal">
    <div class="modal-mask" @click.self="$emit('close')">
      <div class="modal-wrapper">
        <div class="modal-container">

          <div class="modal-header">
            <h3 v-once v-if="!creating"><?php esc_html_e("Edit Competence", "cluevo"); ?>: {{ competence.competence_name }}</h3>
            <h3 v-if="creating"><?php esc_html_e("Create Competence", "cluevo"); ?></h3>
            <button class="close" @click="$emit('close')"><span class="dashicons dashicons-no-alt"></span></button>
          </div>

          <div class="modal-body">
            <div class="competence-editor">
              <table class="name">
                <tr>
                  <td><label><?php esc_html_e("Name", "cluevo"); ?></label></td>
                  <td class="input"><input type="text" name="competence_name" v-model="competence.competence_name" /></td>
                </tr>
                <!-- <tr>
                  <td><label><?php esc_html_e("Type", "cluevo"); ?></label></td>
                  <td>
                    <label><input type="radio" name="competence_type" value="system" :checked="competence.competence_type == 'system'" v-model="competence.competence_type"> <?php esc_html_e("System", "cluevo"); ?></label>&nbsp;
                    <label><input type="radio" name="competence_type" value="user" :checked="competence.competence_type == 'user'" v-model="competence.competence_type"> <?php esc_html_e("User", "cluevo"); ?></label>
                  </td>
                </tr> -->
              </table>
              <div class="input-field submit" v-if="!creating">
                <div class="cluevo-btn auto cluevo-btn-primary" @click="save_competence"><?php esc_html_e("Save", "cluevo"); ?></div>
              </div>
              <div class="details-container">
                <div class="modules">
                  <h5>{{ competence.modules.length }} <?php esc_html_e("Modules", "cluevo"); ?></h5>
                  <p class="hint" v-if="competence.modules.length == 0 && !editing_modules && !creating">&#x24d8; <?php esc_html_e("Not assigned to any modules", "cluevo"); ?></p>
                  <table v-if="!editing_modules && competence.modules.length > 0 && !creating">
                    <tr>
                      <th><?php esc_html_e("Module", "cluevo"); ?></th>
                      <th class="right"><?php esc_html_e("Coverage", "cluevo"); ?></th>
                    </tr>
                    <tr v-for="item in competence.modules">
                      <td class="ellipsis">{{ item.module_name }}</td>
                      <td class="right">{{ item.competence_coverage * 100 }}%</td>
                    </tr>
                  </table>
                  <table v-if="editing_modules || creating" class="edit-modules">
                    <tr>
                      <th><?php esc_html_e("Module", "cluevo"); ?></th>
                      <th class="right"><?php esc_html_e("Coverage", "cluevo"); ?></th>
                    </tr>
                    <tr v-for="item in modules">
                      <td>
                        <label>
                          <input type="checkbox" name="modules[]" :value="item.module_id" v-model="item.checked" />
                          <span>{{ item.module_name }}</span>
                        </label>
                      </td>
                      <td class="right">
                        <input type="number" min="0" max="100" v-model="item.competence_coverage" /> %
                      </td>
                    </tr>
                  </table>
                  <div class="buttons" v-if="!creating">
                    <div class="cluevo-btn auto cluevo-btn-secondary" name="edit-modules" v-if="!this.editing_modules" @click.prevent="toggle_edit_modules"><?php esc_attr_e("Edit", "cluevo"); ?></div>
                    <div class="cluevo-btn auto cluevo-btn-secondary" name="cancel-save-modules" v-if="this.editing_modules" @click.prevent="toggle_edit_modules"><?php esc_attr_e("Cancel", "cluevo"); ?></div>
                    <div class="cluevo-btn auto cluevo-btn-primary" name="save-modules" v-if="this.editing_modules" @click.prevent="save_modules"><?php esc_attr_e("Save", "cluevo"); ?></div>
                  </div>
                </div>
                <div class="areas">
                  <h5>{{ competence.areas.length }} <?php esc_html_e("Competence Groups", "cluevo"); ?></h5>
                  <p class="hint" v-if="competence.areas.length == 0 && !editing_areas && !creating">&#x24d8; <?php esc_html_e("Not assigned to any competence groups.", "cluevo"); ?></p>
                  <p class="hint" v-if="creating && areas && areas.length == 0"><?php esc_html_e("No competence groups have been created yet.", "cluevo"); ?></p>
                  <ul v-if="!editing_areas && competence.areas.length > 0 && !creating">
                    <li v-for="item in competence.areas">{{ item.competence_area_name }}</li>
                  </ul>
                  <ul v-if="editing_areas || creating" class="edit-areas">
                    <li v-for="item in areas">
                    <label><input type="checkbox" name="areas[]" :value="item.competence_area_id" v-model="item.checked" /> {{ item.competence_area_name }}</label>
                    </li>
                  </ul>
                  <div class="buttons" v-if="!creating">
                    <div class="cluevo-btn auto cluevo-btn-secondary" name="edit-areas" v-if="!this.editing_areas" @click.prevent="toggle_edit_areas"><?php esc_attr_e("Edit", "cluevo"); ?></div>
                    <div class="cluevo-btn auto cluevo-btn-secondary" name="cancel-save-areas" v-if="this.editing_areas" @click.prevent="toggle_edit_areas"><?php esc_attr_e("Cancel", "cluevo"); ?></div>
                    <div class="cluevo-btn auto cluevo-btn-primary" name="save-areas" v-if="this.editing_areas" @click.prevent="save_areas"><?php esc_attr_e("Save", "cluevo"); ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <div class="cluevo-btn auto cluevo-btn-secondary" name="cancel-edit-competence" @click="$emit('close')"><?php esc_attr_e("Close", "cluevo"); ?></div>
            <div class="cluevo-btn auto cluevo-btn-primary" name="create-competence" @click="create_competence" v-if="creating"><?php esc_attr_e("Save", "cluevo"); ?></div>
          </div>
        </div>
      </div>
    </div>
    </transition>
  </script>
  <div id="competence-admin-app"></div>
  <?php
  }

  function cluevo_render_competence_areas_tab() {
    wp_register_script(
      'cluevo-admin-competence-area-view',
      plugins_url('/js/competence-area.admin.js', plugin_dir_path(__FILE__)),
      array("vue-js"),
      CLUEVO_VERSION,
      true
    );
    wp_localize_script( 'cluevo-admin-competence-area-view',
      'compApiSettings',
      array(
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' )
      )
    );
    wp_localize_script( 'cluevo-admin-competence-area-view',
      'lang_strings', array(
        'no_areas_found' => __("No competence groups found.", "cluevo"),
        'area_create_error' => __("Failed to create competence group. A competence group with this name may already exist.", "cluevo")
)
    );
    wp_enqueue_script('cluevo-admin-competence-area-view');
    if (!empty($_POST["competence_area_id"])) {
      $id = (int)$_POST["competence_area_id"];
      $name = sanitize_text_field($_POST["competence_area_name"]);
      $type = sanitize_text_field($_POST["competence_area_type"]);
      cluevo_update_competence_area($id , $name, $type);
    }
  ?>
  <script type="text/x-template" id="competence-area-app-header-template">
    <tr>
      <th>#</th>
      <th class="left"><?php esc_html_e("Name", "cluevo"); ?></th>
      <!-- <th class="left"><?php esc_html_e("Type", "cluevo"); ?></th> -->
      <th><?php esc_html_e("Competence Groups", "cluevo"); ?></th>
      <th><?php esc_html_e("Modules", "cluevo"); ?></th>
      <!-- <th><?php esc_html_e("Metadata", "cluevo"); ?></th> -->
      <th class="left"><?php esc_html_e("Created by", "cluevo"); ?></th>
      <th><?php esc_html_e("Date Created", "cluevo"); ?></th>
      <th class="left"><?php esc_html_e("Modified by", "cluevo"); ?></th>
      <th><?php esc_html_e("Date Modified", "cluevo"); ?></th>
      <th class="left"><?php esc_html_e("Tools", "cluevo"); ?></th>
    </tr>
  </script>
  <script type="text/x-template" id="competence-area-editor-template">
    <transition name="modal">
    <div class="modal-mask" @click.self="$emit('close')">
      <div class="modal-wrapper">
        <div class="modal-container">

          <div class="modal-header">
            <h3 v-once v-if="!creating"><?php esc_html_e("Edit Competence Group", "cluevo"); ?>: {{ area.competence_area_name }}</h3>
            <h3 v-if="creating"><?php esc_html_e("Create Competence Group", "cluevo"); ?></h3>
            <button class="close" @click="$emit('close')"><span class="dashicons dashicons-no-alt"></span></button>
          </div>

          <div class="modal-body">
            <div class="competence-editor">
              <table class="name">
                <tr>
                  <td><label><?php esc_html_e("Name", "cluevo"); ?></label></td>
                  <td class="input"><input type="text" name="competence_area_name" v-model="area.competence_area_name" /></td>
                </tr>
                <!-- <tr>
                  <td><label><?php esc_html_e("Type", "cluevo"); ?></label></td>
                  <td>
                    <label><input type="radio" name="competence_area_type" value="system" :checked="area.competence_area_type == 'system'" v-model="area.competence_area_type"> <?php esc_html_e("System", "cluevo"); ?></label>&nbsp;
                    <label><input type="radio" name="competence_area_type" value="user" :checked="area.competence_area_type == 'user'" v-model="area.competence_area_type"> <?php esc_html_e("User", "cluevo"); ?></label>
                  </td>
                </tr> -->
              </table>
              <div v-if="!creating" class="input-field submit">
                <div class="cluevo-btn auto cluevo-btn-primary" @click="save_area"><?php esc_html_e("Save", "cluevo"); ?></div>
              </div>
              <div class="details-container">
                <div class="modules">
                  <h5>{{ area.modules.length }} <?php esc_html_e("Modules", "cluevo"); ?></h5>
                  <p class="hint" v-if="area.modules.length == 0 && !editing_modules">&#x24d8; <?php esc_html_e("Not assigned to any modules.", "cluevo"); ?></p>
                  <ul>
                    <li v-for="module in area.modules">{{ module.module_name }}</li>
                  </ul>
                </div>
                <div class="competences">
                  <h5>{{ area.competences.length }} <?php esc_html_e("Competences", "cluevo"); ?></h5>
                  <p class="hint" v-if="area.competences.length == 0 && !editing_comps && !creating">&#x24d8; <?php esc_html_e("No competences have been assigned yet.", "cluevo"); ?></p>
                  <p class="hint" v-if="creating && comps && comps.length == 0"><?php esc_html_e("No competences have been created yet.", "cluevo"); ?></p>
                  <ul v-if="!editing_comps && area.competences.length > 0 && !creating">
                    <li v-for="item in area.competences">{{ item.competence_name }}</li>
                  </ul>
                  <ul v-if="editing_comps || creating" class="edit-comps">
                    <li v-for="item in comps">
                    <label><input type="checkbox" name="comps[]" :value="item.competence_id" v-model="item.checked" /> {{ item.competence_name }}</label>
                    </li>
                  </ul>
                  <div class="buttons" v-if="!creating">
                    <div class="cluevo-btn auto cluevo-btn-secondary" name="edit-comps" v-if="!this.editing_comps" @click.prevent="toggle_edit_comps"><?php esc_attr_e("Edit", "cluevo"); ?></div>
                    <div class="cluevo-btn auto cluevo-btn-secondary" name="cancel-save-areas" v-if="this.editing_areas" @click.prevent="toggle_edit_comps"><?php esc_attr_e("Cancel", "cluevo"); ?></div>
                    <div class="cluevo-btn auto cluevo-btn-primary" name="save-comps" v-if="this.editing_comps" @click.prevent="save_comps"><?php esc_attr_e("Save", "cluevo"); ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <div class="cluevo-btn auto cluevo-btn-secondary" name="cancel-edit-area" @click="$emit('close')"><?php esc_attr_e("Close", "cluevo"); ?></div>
            <div class="cluevo-btn auto cluevo-btn-primary" name="create-competence_area" @click="create_area" v-if="creating"><?php esc_attr_e("Save", "cluevo"); ?></div>
          </div>
        </div>
      </div>
    </div>
    </transition>
  </script>
  <div id="competence-area-admin-app">
  </div>
  <?php
  }

  function cluevo_render_competence_areas_page() {
    cluevo_init_competence_page();
    $active_tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : CLUEVO_ADMIN_TAB_COMPETENCE_MAIN;
  ?>
  <div class="cluevo-admin-page-container">
    <div class="cluevo-admin-page-title-container">
      <h1><?php esc_html_e("CLUEVO Competence", "cluevo"); ?></h1>
      <img class="plugin-logo" src="<?php echo esc_url(plugins_url("/assets/logo-white.png", plugin_dir_path(__FILE__)), ['http', 'https']); ?>" />
    </div>
  <div class="cluevo-admin-page-content-container">
    <h2 class="nav-tab-wrapper cluevo">
      <a href="<?php echo esc_url(admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_COMPETENCE . "&tab=" . CLUEVO_ADMIN_TAB_COMPETENCE_MAIN), ['http', 'https']); ?>" class="nav-tab <?php echo $active_tab == CLUEVO_ADMIN_TAB_COMPETENCE_MAIN ? 'nav-tab-active' : ''; ?>"><?php esc_html_e("Competences", "cluevo"); ?></a>
      <a href="<?php echo esc_url(admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_COMPETENCE . "&tab=" . CLUEVO_ADMIN_TAB_COMPETENCE_AREAS), ['http', 'https']); ?>" class="nav-tab <?php echo $active_tab == CLUEVO_ADMIN_TAB_COMPETENCE_AREAS ? 'nav-tab-active' : ''; ?>"><?php esc_html_e("Competence Groups", "cluevo"); ?></a>
    </h2>
  <?php 
    switch ($active_tab) {
    case CLUEVO_ADMIN_TAB_COMPETENCE_MAIN:
      cluevo_render_competences_tab();
      break;
    case CLUEVO_ADMIN_TAB_COMPETENCE_AREAS:
      cluevo_render_competence_areas_tab();
      break;
    default:
      cluevo_render_competences_tab();
      break;
    }
  ?>
  </div>
  </div>
  <?php
  }
}
