var bus = new Vue({});

Vue.prototype.$lang_strings = window.lang_strings;
Vue.prototype.$misc_strings = window.misc_strings;

Vue.component("user-group-badge", {
  props: [ "group", "edit" ],
  methods: {
    handle_click: function(group) {
      this.$emit('quick-edit-group-membership');
    }
  },
  computed: {
    is_email_group: function() {
      if (this.group && this.group.hasOwnProperty('group_name')) {
        return this.group.group_name.indexOf('@') === 0;
      }
      return false;
    }
  },
  template:
  `<div class="cluevo-group-badge-container" @click="handle_click(this.group)" :class="{ 'trainer': this.group.is_trainer }">
    <span class="cluevo-group-badge">{{ this.group.group_name }}</span>
    <div class="editor" v-show="edit">
      <button @click.stop="handle_click(this.group)"><span class="dashicons dashicons-yes"></span></button>
      <button 
        v-if="!is_email_group"
        @click.stop="$emit('remove-user-from-group', this.group)"
      >
          <span class="dashicons dashicons-no"></span>
      </button>
      <button
        v-if="this.group.is_trainer"
        @click.stop="$emit('demote-user', this.group)"
      >
          <span class="dashicons dashicons-welcome-learn-more"></span>
          <span class="dashicons dashicons-arrow-down-alt"></span>
          {{ this.$lang_strings.button_label_demote }}
        </button>
      <button
        v-if="!this.group.is_trainer"
        @click.stop="$emit('promote-user', this.group)"
      >
          <span class="dashicons dashicons-welcome-learn-more"></span>
          <span class="dashicons dashicons-arrow-up-alt"></span>
          {{ this.$lang_strings.button_label_promote}}
        </button>
    </div>
  </div>
  `
});

Vue.component('cluevo-spinner', {
  template: `
    <div class="cluevo-spinner">
      <div class="segment pink"></div>
      <div class="segment purple"></div>
      <div class="segment teal"></div>
    </div>
  `
});

Vue.component('group-container', {
  props: ['group'],
  data: function() {
    return {
      adding_group: false,
      edit_group: null
    }
  },
  template: `
    <tr>
      <td>{{ group.group_id }}</td>
      <td class="left">{{ group.group_name }}</td>
      <td class="left">{{ group.group_description }}</td>
      <td>{{ group.users.length }}</td>
      <td>{{ group.trainers.length }}</td>
      <td>{{ group.date_added }}</td>
      <td>{{ group.date_modified }}</td>
      <td class="left">
        <div class="tools">
          <div class="cluevo-btn" v-if="group.protected != 1" v-on:click="$emit('del-group', group)"><span class="dashicons dashicons-trash"></span></div>
          <div class="cluevo-btn disabled" v-else><span class="dashicons dashicons-trash"></span></div>
          <div class="cluevo-btn" v-on:click="$emit('edit-group', group)"><span class="dashicons dashicons-edit"></span></div>
          <div class="cluevo-btn" v-on:click="$emit('edit-group-perms', group)"><span class="dashicons dashicons-admin-network"></span></div>
          <!-- <div class="cluevo-btn" v-on:click="$emit('edit-group-metadata', group)"><span class="dashicons dashicons-info"></span></div> -->
        </div>
      </td>
    </tr>
  `
});

Vue.component('user-container', {
  props: ['user', 'strings', 'active'],
  data: function() {
    return {
      adding_group: false,
      edit_group: null
    }
  },
  computed: {
    possible_groups: function() {
      let result = [];
      let user = this.user;
      lodash.forEach(this.$groups, function(g) {
        let found = false;
        lodash.forEach(user.groups, function(curGroup) {
          if (curGroup.group_id == g.group_id)
            found = true;
        });
        if (!found && g && g.group_name && g.group_name.indexOf('@') != 0)
          result.push(g);
      });
      return result;
    }
  },
  methods: {
    demote_user: function(user, group) {
      let comp = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/' + user.user_id + '/groups/' + group.group_id + '/demote', {
        method: 'POST',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          comp.$emit('refresh', user);
          comp.$emit('refresh-group', group);
        })
    },
    promote_user: function(user, group) {
      let comp = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/' + user.user_id + '/groups/' + group.group_id + '/promote', {
        method: 'POST',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          comp.$emit('refresh', user);
        })
    },
    handle_badge_click: function(g) {
      if (this.edit_group == g.group_id)
        this.edit_group = null;
      else
        this.edit_group = g.group_id;
    },
    handle_add_user_to_group(event) {
      if (event.target.value == 0)
        return;

      let comp = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/' + this.user.user_id + '/groups/' + event.target.value + '/add', {
        method: 'POST',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          comp.adding_group = false;
          comp.edit_group = null;
          comp.$emit('refresh', comp.user);
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    remove_user_from_group: function(user, group) {
      let comp = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/' + this.user.user_id + '/groups/' + group.group_id + '/remove', {
        method: 'DELETE',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          comp.adding_group = false;
          comp.edit_group = null;
          comp.$emit('refresh', comp.user);
        })
        .catch(function(error) {
          console.error(error);
        });
    }
  },
  template: `
    <tr>
      <td>{{ user.user_id }}</td>
      <td class="left">{{ user.display_name }}</td>
      <td class="left">
        <div class="user-groups-cell">
          <user-group-badge v-for="(g, i) in user.groups"
            :group="g" :key="'badege_u_' + user.user_id + '_g_' + g.group_id"
            :edit="edit_group == g.group_id"
            v-on:remove-user-from-group="remove_user_from_group(user, g)"
            v-on:demote-user="demote_user(user, g)"
            v-on:promote-user="promote_user(user, g)"
            v-on:quick-edit-group-membership="handle_badge_click(g)"
            v-on:add-user-to-group="handle_add_user_to_group"></user-group-badge>
          <div
            v-if="!adding_group && possible_groups.length > 0"
            @click="adding_group = !adding_group"
            class="cluevo-btn add-to-group">
              <span class="dashicons dashicons-plus"></span>
          </div>
          <select v-if="possible_groups.length > 0 && adding_group" @change="handle_add_user_to_group">
            <option :value="0">{{ this.$lang_strings.option_select_a_group }}</option>
            <option v-for="group in possible_groups" :value="group.group_id" v-html="group.group_name"></option>
          </select>
          <div 
            v-if="adding_group && possible_groups.length > 0"
            @click="adding_group = !adding_group"
            class="cluevo-btn auto"
          >
              {{ $lang_strings.cancel }}
          </div>
        </div>
      </td>
      <td class="left">{{ user.role_display_name }}</td>
      <td class="left">{{ user.role_since }}</td>
      <td class="left">{{ user.date_last_seen }}</td>
      <td class="left">{{ user.date_added }}</td>
      <td class="left">{{ user.date_modified }}</td>
      <td class="left">
        <div class="tools">
          <div class="cluevo-btn" v-on:click="$emit('del-user', user)"><span class="dashicons dashicons-trash"></span></div>
          <a class="cluevo-btn" :href="'?page=' + this.$misc_strings.reporting_page + '&tab=' + this.$misc_strings.scorm_tab + '&user=' + user.user_id"><span class="dashicons dashicons-admin-settings"></span></a>
          <a class="cluevo-btn" :href="'?page=' + this.$misc_strings.reporting_page + '&tab=' + this.$misc_strings.progress_tab + '&user=' + user.user_id"><span class="dashicons dashicons-chart-area"></span></a>
          <div class="cluevo-btn" v-on:click="$emit('edit-user-perms', user)"><span class="dashicons dashicons-admin-network"></span></div>
          <!-- <div class="cluevo-btn" v-on:click="$emit('edit-user', user)"><span class="dashicons dashicons-edit"></span></div> -->
          <!-- <div class="cluevo-btn" v-on:click="$emit('edit-comp-metadata', competence)"><span class="dashicons dashicons-info"></span></div> -->
        </div>
      </td>
    </tr>
  `
});

Vue.component('user-app-header', {
  template:
  `<tr>
      <th>#</th>
      <th class="left">{{ this.$lang_strings.header_name }}</th>
      <th class="left">{{ this.$lang_strings.header_groups }}</th>
      <th class="left">{{ this.$lang_strings.header_role }}</th>
      <th class="left">{{ this.$lang_strings.header_date_role_since }}</th>
      <th class="left">{{ this.$lang_strings.header_date_last_seen }}</th>
      <th class="left">{{ this.$lang_strings.header_date_added }}</th>
      <th class="left">{{ this.$lang_strings.header_date_modified }}</th>
      <th class="left">{{ this.$lang_strings.header_tools }}</th>
    </tr>`
});

Vue.component('group-header', {
  template:
  `<tr>
      <th>#</th>
      <th class="left">{{ this.$lang_strings.header_group_name }}</th>
      <th class="left">{{ this.$lang_strings.header_group_description }}</th>
      <th>{{ this.$lang_strings.header_member_count }}</th>
      <th>{{ this.$lang_strings.header_trainer_count }}</th>
      <th>{{ this.$lang_strings.header_date_added }}</th>
      <th>{{ this.$lang_strings.header_date_modified }}</th>
      <th class="left">{{ this.$lang_strings.header_tools }}</th>
    </tr>`
});

Vue.component('tab', {
  props: {
    id: { default: null },
    title: { required: true }
  },
  data: function() {
    return { 
      isActive: false,
      isVisible: true
    };
  },
  template:
  `<section class="cluevo-tab" v-show="isActive">
    <slot />
  </section>`
});

Vue.component('tabs', {
  data: function() {
    return {
      tabs: []
    };
  },
  props: [ 'activeTabId' ],
  methods: {
    selectTab: function(index) {
      this.selectTabById(this.tabs[index].id);

    },
    selectTabById: function(id) {
      for (var tab of this.tabs) {
        tab.isActive = (tab.id == id);
        if (tab.isActive) {
          this.$emit('tab-changed', tab.id);
        }
      }
    }
  },
  created: function() {
    this.tabs = this.$children;
  },
  mounted: function() {
    bus.$on('change-tab', this.selectTabById);
    if (this.activeTabId) {
      this.selectTabById(this.activeTabId);
    } else {
      this.selectTab(0);
    }
  },
  template:
  `<div class="cluevo-tabs">
    <h2 class="nav-tab-wrapper cluevo">
      <a v-for="(tab, index) in tabs" v-html="tab.title" @click="selectTab(index, $event)" class="nav-tab" :class="{ 'nav-tab-active': tab.isActive }"></a>
    </h2>
    <div class="tabs">
      <slot />
    </div>
  </div>`
});

Vue.component('user-add-dialog', {
  data: function() {
    return {
      users: [],
      checked_users: [],
      search: '',
      checked_all: false
    }
  },
  computed: {
    checked_usernames: function() {
      if (this.checked_users) {
        return this.checked_users.map(function(el) {
          return el.display_name;
        }).join(', ');
      }
    }
  },
  template:
    `<transition name="modal">
      <div class="modal-mask">
        <div class="modal-wrapper" v-on:click.self="$emit('close')">
          <div class="modal-container">

            <div class="modal-header">
              <h3>{{ this.$lang_strings.add_user_dialog_title }}</h3>
              <button class="close" @click="$emit('close')"><span class="dashicons dashicons-no-alt"></span></button>
            </div>

            <div class="modal-body">
              <input type="text" name="search" v-model="search" @keyup="search_users(search)" :placeholder="this.$lang_strings.search_user" />
              <transition name="fade">
                <table class="cluevo-admin-table limit" v-if="users.length > 0">
                  <thead>
                    <tr>
                      <th class="check">
                        <input type="checkbox" name="check-all" @click="toggle_all" />
                      </th>
                      <th class="id">#</th>
                      <th class="left name">{{ this.$lang_strings.name }}</th>
                    </tr>
                  </thead>
                  <transition-group name="fade" tag="tbody">
                   <tr v-for="user in users" :key="user.data.ID">
                    <td class="check"><input type="checkbox" :value="user.data" v-model="checked_users"/></td>
                    <td class="id">{{ user.data.ID }}</td>
                    <td class="name left">{{ user.data.display_name }}</td>
                   </tr> 
                  </transition-group>
                </table>
              </transition>
              <transition name="fade">
                <p v-show="checked_users.length > 0">{{ this.$lang_strings.selected_users }} {{ checked_usernames }}</p>
              </transition>
            </div>
            <div class="modal-footer">
              <button class="cluevo-btn cluevo-btn-primary auto" v-on:click="$emit('add-lms-users', checked_users)"">{{ checked_users.length }} {{ this.$lang_strings.add_users }}</button>
            </div>
          </div>
        </div>
      </div>
      </transition>`,
  created: function() {
    this.search_users('');
  },
  methods: {
    search_users: lodash.debounce(function(input) {
      var app = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/wordpress', {
        method: 'POST',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        },
        body: JSON.stringify({ search: input })
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          app.users = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    }, 300),
    toggle_all() {
      this.checked_all = !this.checked_all;
      if (this.checked_all) {
        this.checked_users = this.users.map(function(u, i) {
          return u.data;
        });
      } else {
        this.checked_users = [];
      }
    },
  }
});

Vue.component('group-add-dialog', {
  props: [ "group" ],
  data: function() {
    return {
      name: '',
      description: '',
      users: [],
      checked_users: [],
      search: '',
      checked_all: false
    }
  },
  computed: {
    checked_usernames: function() {
      if (this.checked_users) {
        var usernames = this.checked_users.map(function(el) {
          if (el) {
            return el.display_name;
          }
        });
        if (usernames.length > 0) {
          var string = usernames.join(', ');
          return string;
        }
      }
    },
    is_email_group: function() {
      return this.name.indexOf('@') === 0;
    }
  },
  template:
    `<transition name="modal">
      <div class="modal-mask">
        <div class="modal-wrapper" v-on:click.self="$emit('close')">
          <div class="modal-container">

            <div class="modal-header">
              <h3>{{ this.$lang_strings.add_group_dialog_title }}</h3>
              <button class="close" @click="$emit('close')"><span class="dashicons dashicons-no-alt"></span></button>
            </div>

            <div class="modal-body">
              <h4>{{ this.$lang_strings.headline_group_information }}</h4>
              <div v-if="is_email_group" class="cluevo-notice">{{ this.$lang_strings.email_group_info }}</div>
              <div class="group-info">
                <label>{{ this.$lang_strings.new_group_name_label }}
                  <input type="text" name="name" v-model="name" />
                </label>
                <label>{{ this.$lang_strings.new_group_desc_label }}
                  <input type="text" name="description" v-model="description" />
                </label>
              </div>
              <hr />
              <h4>{{ this.$lang_strings.members }}</h4>
              <input type="text" name="search" v-model="search" @keyup="search_users(search)" :placeholder="this.$lang_strings.search_user" />
              <transition name="fade">
                <table class="cluevo-admin-table limit" v-if="users.length > 0">
                  <thead>
                    <tr>
                      <th class="check">
                        <input type="checkbox" name="check-all" @click="toggle_all" :disabled="is_email_group" />
                      </th>
                      <th class="id">#</th>
                      <th class="left name">{{ this.$lang_strings.name }}</th>
                    </tr>
                  </thead>
                  <transition-group name="fade" tag="tbody">
                   <tr v-for="user in users" :key="user.user_id">
                    <td class="check"><input type="checkbox" :value="user" v-model="checked_users" :disabled="is_email_group"/></td>
                    <td class="id">{{ user.user_id }}</td>
                    <td class="name left">{{ user.display_name }}</td>
                   </tr> 
                  </transition-group>
                </table>
              </transition>
              <transition name="fade">
                <p v-show="checked_users.length > 0">{{ this.$lang_strings.selected_users }} {{ checked_usernames }}</p>
              </transition>
            </div>
            <div class="modal-footer">
              <button v-if="group === null" class="cluevo-btn cluevo-btn-primary auto" @click="handle_add_group">{{ this.$lang_strings.add_group }}</button>
              <button v-if="group !== null" class="cluevo-btn cluevo-btn-primary auto" @click="handle_edit_group">{{ this.$lang_strings.edit_group }}</button>
            </div>
          </div>
        </div>
      </div>
      </transition>`,
  created: function() {
    if (this.group) {
      let vm = this;
      this.search_users_now('').then(function() {
        vm.name = vm.group.group_name;
        vm.description = vm.group.group_description;
        var users = [];
        for (var u of vm.group.users) {
          for(var user of vm.users) {
            if (user.user_id == u) {
              users.push(user);
            }
          }
        }
        vm.checked_users = users;
      });
    } else {
      this.search_users('');
    }
  },
  methods: {
    search_users_now: function(input) {
      var app = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users', {
        method: 'POST',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        },
        body: JSON.stringify({ search: input })
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          app.users = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    search_users: lodash.debounce(function(input) {
      this.search_users_now(input);
    }, 300),
    handle_add_group: function() {
      let vm = this;
      let users = this.checked_users.map(function(u, i) {
        return u.ID;
      });
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/groups/create', {
        method: 'POST',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        },
        body: JSON.stringify({
          users: users,
          name: vm.name,
          description: vm.description
        })
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          if (data !== false) {
            vm.$emit('group-added');
          } else {
            // display error, false = group creation failed, or users could not be added to group
          }
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    toggle_all() {
      this.checked_all = !this.checked_all;
      if (this.checked_all) {
        this.checked_users = this.users.map(function(u, i) {
          return u;
        });
      } else {
        this.checked_users = [];
      }
    },
    handle_edit_group: function() {
      let group = this.group;
      group.group_name = this.name;
      group.group_description = this.description;
      group.users = this.checked_users.map(function(u, i) {
        return u.ID;
      });
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/groups', {
        method: 'POST',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        },
        body: JSON.stringify(group)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.userApp.wp_users = data;
        })
        .catch(function(error) {
          console.error(error);
        });
      this.$emit('refresh', this.group);
    }
  }
});

Vue.component('group-selector', {
  props: [ "groups", "value" ],
  methods: {
    update: function(key, value) {
      var result = this.groups.filter(function(g) { return g.group_id == value; });
      if (result.length == 1) {
        this.$emit('input', result.pop());
      }
    }
  },
  template: `
    <label> {{ this.$lang_strings.group_selector_label }}
      <select name="group-selector" v-bind:value="value" @input="update('input', $event.target.value)">
        <option :value="null"></option>
        <option v-for="group in groups" :value="group.group_id" :selected="value && value.group_id == group.group_id" v-html="group.group_name"></option>
      </select>
    </label>
  `
});

Vue.component('user-selector', {
  props: [ "users", "value" ],
  template: `
    <label> {{ this.$lang_strings.user_selector_label }}
      <select name="user-selector" v-bind:value="value" @change="$emit('input', $event.target.value)">
        <option :value="null"></option>
        <option v-for="user in users" :value="user.user_id" v-html="user.display_name"></option>
      </select>
    </label>
  `
});

Vue.component('access-level', {
  props: [ "levels", "value", "effective" ],
  methods: {
    handleInput: function(value) {
      if (value != this.value) {
        this.$emit('input', value);
        this.$emit('access-level-changed', value);
      }
    },
  },
  template: `
    <div class="access-level-container">
      <input v-bind:value="value">
        <label v-for="level in levels" :class="[{ 'active': (level == value || (level == 0 && value === null)), 'effective': effective && effective.access_level == level }]" @click="handleInput(level)">
          <span v-if="level == 0" class="dashicons dashicons-lock"></span>
          <span v-if="level == 1" class="dashicons dashicons-visibility"></span>
          <span v-if="level == 2" class="dashicons dashicons-unlock"></span>
        </label>
        <label @click="handleInput(null)">
          <span class="dashicons dashicons-no-alt"></span>
        </label>
      </input>
  </div>`
});

Vue.component('lms-perm-item', {
  props: [ "item", "parent_access_level" ],
  computed: {
    perms_overridden: function() {
      if (typeof this.effective_access_level == "object" && this.effective_access_level !== null) {
        if (this.item && this.effective_access_level.access_level && this.effective_access_level.access_level >= this.item.access_level) return true;
      }
      return false;
    },
    effective_access_level: function() {
      if (this.item && this.item.highest_group_access_level) {
        if (this.item.access_level <= this.item.highest_group_access_level.access_level) {
          return this.item.highest_group_access_level;
        }
      }
      return this.item.access_level;
    }
  },
  methods: {
    handle_level_change: function(event) {
      this.item.access_level = event;
      this.$emit('perm-changed', this.item);
      bus.$emit('perm-changed', this.item);
    },
    handle_child_level_change: function(item) {
      if (this.item.access_level < 2 && item.access_level > 0) {
        this.handle_level_change(2);
      }
    },
    switch_to_group: function() {
      bus.$emit('switch-to-group', this.effective_access_level.group.group_id);
    }
  },
  watch: {
    parent_access_level: function(newLevel, oldLevel) {
      if (newLevel === null) {
        this.handle_level_change(null);
        this.item.children = this.item.children.map(function(c, i) {
          c.access_level = null;
          return c;
        });
      } else {
        if (newLevel < 2) {
          this.handle_level_change(0);
          this.item.children = this.item.children.map(function(c, i) {
            c.access_level = 0;
            return c;
          });
        }
      }
    }
  },
  template: `
    <div class="lms-perm-item" :class="'level-' + item.level"">
      <div class="perm-title">
        <access-level :levels="[0, 1, 2]" v-model="item.access_level" @access-level-changed="handle_level_change" :effective="effective_access_level || null" />
        <div class="perm-item-name">{{ item.name }}</div>
        <div class="module-warning" v-if="perms_overridden">
          <span class="dashicons dashicons-warning"></span>
          <span>{{ this.$lang_strings.perms_overridden }}</span>
          <span class="effective-group" v-if="this.effective_access_level.group" @click="switch_to_group">{{ this.effective_access_level.group.group_name }}</span>
        </div>
        <div class="module-warning" v-if="item.access_level == 1 && item.type == 'module'">
          <span class="dashicons dashicons-warning"></span>
          <span>{{ this.$lang_strings.module_not_executable_warning }}</span>
        </div>
      </div>
      <div class="perm-children">
        <lms-perm-item v-if="item.children.length > 0" v-for="child in item.children" :item="child" :key="child.item_id" @perm-changed="handle_child_level_change" :parent_access_level="item.access_level"></lms-perm-item>
        <slot></slot>
      </div>
    </div>
  `
});

Vue.component('lms-access-preview', {
  props: [ "items" ],
  template: `
    <div class="access-preview-container">
    </div>
  `
});

Vue.component('lms-preview-item', {
  props: [ "item" ],
  template:`
    <div class="lms-preview-item" :class="'level-' + item.level" v-if="item.access_level >= 1">
      <div class="item-name">{{ item.name }}</div>
      <div class="item-children">
        <div v-for="child in item.children" :key="child.item_id">
          <lms-preview-item v-if="child.access_level >= 1" :item="child"></lms-preview-item>
          <slot></slot>
        </div>
      </div>
    </div>
  `
});

Vue.component('perm-comp', {
  data: function() {
    return {
      users: [],
      groups: [],
      cur_group: null,
      cur_user: null,
      permissions: [],
      loading: true
    };
  },
  props: [ 'user', 'group' ],
  watch: {
    group: function(newVal, oldVal) {
      this.cur_group = newVal;
      this.load_perms('group', newVal.group_id);
    },
    user: function(newVal, oldVal) {
      this.cur_user = newVal.user_id;
      this.load_perms('user', newVal.user_id);
    }
  },
  mounted: function() {
    bus.$on('perm-changed', this.save_perm);
    bus.$on('group-added', this.reset);
    bus.$on('user-added', this.reset);
    bus.$on('group-removed', this.reset);
    bus.$on('user-removed', this.reset);
  },
  template: `
    <div class="permission-admin-app">
      <div class="selectors">
        <group-selector
          v-if="groups.length > 0"
          :groups="groups"
          v-model="cur_group"
          @input="load_perms('group', cur_group.group_id)"
        />
        <div class="group-user-separator">{{ this.$lang_strings.or }}</div>
        <user-selector
          v-if="users.length > 0"
          :users="users"
          v-model="cur_user"
          @input="load_perms('user', cur_user)"
        />
        <fieldset>
          <legend>Berechtigungsstufen</legend>
          <div>
            <span class="dashicons dashicons-lock"></span> {{ this.$lang_strings.legend_locked }}
          </div>
          <div>
            <span class="dashicons dashicons-visibility"></span> {{ this.$lang_strings.legend_visible }}
          </div>
          <div>
            <span class="dashicons dashicons-unlock"></span> {{ this.$lang_strings.legend_unlocked }}
          </div>
          <div class="types">
            <div>
              <span class="square course"></span> {{ this.$lang_strings.legend_course }}
            </div>
            <div>
              <span class="square chapter"></span> {{ this.$lang_strings.legend_chapter }}
            </div>
            <div>
              <span class="square module"></span> {{ this.$lang_strings.legend_module }}
            </div>
          </div>
        </fieldset>
      </div>
      <transition name="fade" mode="out-in">
        <div class="cluevo-notice cluevo-notice-info" v-if="cur_group && cur_group.group_description !== ''">
          <p class="cluevo-notice-title">{{ this.$lang_strings.group_selector_label }}: {{ cur_group.group_name }}</p>
          <p>{{ cur_group.group_description }}</p>
        </div>
      </transition>
      <transition name="fade" mode="out-in">
        <cluevo-spinner v-if="loading" key="perm-spinner" />
        <div class="permissions-container" v-if="!loading && (cur_user !== null || cur_group !== null)" key="perm-container">
          <div class="perm-items" v-for="item in permissions" :key="item.item_id">
            <div class="main-item-container">
              <access-level :levels="[0, 1, 2]" v-model="item.access_level" @access-level-changed="handle_level_change(item, $event)" />
              <h2>{{ item.name }}</h2>
            </div>
            <lms-perm-item v-for="child in item.children" :item="child" :key="child.item_id" @perm-changed="handle_child_level_change(item, child)" :parent_access_level="item.access_level"></lms-perm-item>
          </div>
        </div>
        <div class="cluevo-admin-notice cluevo-notice-info" v-if="!loading && cur_group === null && cur_user === null" key="perm-notice">
          <p>{{ this.$lang_strings.select_group_or_user }}</p>
        </div>
      </transition>
    </div>
  `,
  created: function() {
    return this.load_users()
      .then(this.load_groups())
      .catch(function(error) {
        console.error(error);
      });
  },
  methods: {
    reset: function() {
      return this.load_users()
        .then(this.load_groups())
        .catch(function(error) {
          console.error(error);
        });
    },
    load_groups: function() {
      let vm = this;
      this.loading = true;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/groups', {
        method: 'GET',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          vm.groups = data;
          vm.loading = false;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    load_users: function() {
      let vm = this;
      this.loading = true;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users', {
        method: 'GET',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          vm.users = data.filter(function(d) { return d; } );
          vm.loading = false;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    load_perms: function(type, value) {
      if (value) {
        let url = '';
        switch(type) {
          case "group":
            url = cluevoWpApiSettings.root + 'cluevo/v1/admin/permissions/groups/' + value
            this.cur_user = null;
            break;
          case "user":
            url = cluevoWpApiSettings.root + 'cluevo/v1/admin/permissions/users/' + value;
            this.cur_group = null;
            break;
          default:
            url = null;
        }
        let vm = this;
        this.loading = true;
        return fetch(url, {
          headers: {
            "Content-Type": "application/json; charset=utf-8",
            'X-WP-Nonce': cluevoWpApiSettings.nonce
          },
        })
          .then(function (response) {
            return response.json();
          })
          .then(function(data) {
            vm.permissions = data;
            vm.loading = false;
          })
          .catch(function(error) {
            console.error(error);
          });
      } else {
        this.permissions = [];
      }
    },
    handle_level_change(item, level) {
      item.access_level = level;
      bus.$emit('perm-changed', item);
    },
    save_perm: function(item) {
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/permissions/save', {
        method: 'POST',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        },
        body: JSON.stringify({ perm: item})
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data === true) {
            console.log("success");
          } else {
            console.warn("error");
          }
        })
    },
    handle_child_level_change(parent, child) {
      if (parent.access_level < 2 && child.access_level > 0) {
        this.handle_level_change(parent, 2);
      }
    }
  }
});

var userApp = new Vue({
  el: '#user-admin-app',
  data: function() {
    return {
      users: [],
      groups: [],
      wp_users: [],
      strings: {},
      cur_user: null,
      editing: false,
      editing_group: null,
      adding: false,
      adding_group: false,
      ready: false,
      lang_strings: window.lang_strings,
      misc_strings: window.misc_strings,
      activeTab: null,
      edit_perm_group: null,
      edit_perm_user: null
    };
  },
  mounted: function() {
    bus.$on('switch-to-group', this.handle_switch_to_group);
  },
  template: `
    <div class="user-admin-app">
      <div class="cluevo-admin-notice cluevo-notice-error" v-if="users.length == 0">
        <p>{{ lang_strings.no_users_found }}</p>
      </div>
      <user-add-dialog v-if="adding" v-on:add-lms-users="add_lms_users" v-on:close="adding = false" />
      <group-add-dialog
        v-if="adding_group || editing_group !== null"
        v-on:group-added="handle_group_added"
        v-on:close="adding_group = false; editing_group = null"
        :group="editing_group"
        v-on:refresh="refresh_group"
      />
      <tabs :activeTabId="activeTab" @tab-changed="(tab) => this.activeTab = tab">
        <tab :title="$lang_strings.tab_title_permissions" id="perms">
          <perm-comp :group="edit_perm_group" :user="edit_perm_user" />
        </tab>
        <tab :title="users.length + ' ' + this.$lang_strings.users_heading" id="users">
          <div v-if="ready">
            <div class="buttons">
              <div id="add-lms-user" class="cluevo-btn auto" v-on:click="add_user">{{ lang_strings.add_user_button_label }}</div>
              <div id="add-lms-group" class="cluevo-btn auto" v-on:click="add_group">{{ lang_strings.add_group_button_label }}</div>
            </div>
            <table class="cluevo-admin-table" v-show="users.length > 0 && !editing">
              <tr class="no-hover">
                <td colspan="2"></td>
                <td colspan="7">
                  <div class="legend">
                    <div><div class="trainer"></div>{{ this.$lang_strings.name_trainer }}</div>
                    <div><div class="user"></div>{{ this.$lang_strings.name_student }}</div>
                  </div>
                </td>
              </tr>
              <user-app-header />
              <tbody>
                <user-container
                  v-for="user in users"
                  v-bind:user="user"
                  v-bind:key="user.user_id"
                  v-bind:strings="strings"
                  v-bind:active="user== cur_user"
                  v-on:del-user="delete_user"
                  v-on:edit-user="edit_user"
                  v-on:edit-user-perms="edit_user_perms"
                  v-on:refresh="refresh_user"
                />
              </tbody>
            </table>
          </div>
          <cluevo-spinner v-else />
        </tab>
        <tab :title="groups.length + ' ' + this.$lang_strings.header_groups" id="groups">
          <div v-if="ready">
            <div class="buttons">
              <div id="add-lms-group" class="cluevo-btn auto" v-on:click="add_group">{{ lang_strings.add_group_button_label }}</div>
            </div>
            <table class="cluevo-admin-table" v-show="groups.length > 0">
              <group-header />
              <group-container
                v-for="group in groups"
                v-bind:group="group"
                v-bind:key="group.group_id"
                v-on:del-group="delete_group"
                v-on:edit-group="edit_group"
                v-on:edit-group-perms="edit_group_perms"
                v-on:refresh="refresh_group"
              />
            </table>
          </div>
          <cluevo-spinner v-else />
        </tab>
      </tabs>
    </div>
  `,
  created: function() {
    this.init();
  },
  methods: {
    init: function() {
      let vm = this;
      vm.ready = false;
      this.load_users()
        .then(this.load_wp_users)
        .then(this.load_groups)
        .then(function() {
           vm.ready = true;
        });
    },
    load_users: function() {
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users', {
        method: 'GET',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.userApp.users = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    load_wp_users: function() {
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/wordpress', {
        method: 'POST',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        },
        body: JSON.stringify({ search: '' })
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.userApp.wp_users = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    load_groups: function() {
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/groups', {
        method: 'GET',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.userApp.groups = data;
          Vue.prototype.$groups = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    add_user: function() {
      this.adding = true;
    },
    add_group: function() {
      this.adding_group = true;
    },
    handle_group_added: function() {
      this.adding_group = false;
      bus.$emit('group-added');
      this.init();
    },
    refresh_user: function(user) {
      let app = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/' + user.user_id, {
        method: 'VIEW',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          let users = app.users.map(function(u, i) {
            if (u.user_id == user.user_id)
              u.groups = data.groups;
          });
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    add_lms_users: function(users) {
      let app = this;
      let ids = users.map(function(el) {
        return el.ID;
      })
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/make/many', {
        method: 'POST',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        },
        body: JSON.stringify({users: ids})
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          return app.load_users();
        })
        .then(function() {
          app.adding = false;
        })
        .catch(function(error) {
          console.error(error);
          this.adding = false;
        });
    },
    edit_user: function(user) {
      //this.editing = true;
    },
    edit_group: function(group) {
      this.editing_group = group;
    },
    edit_group_perms: function(group) {
      this.edit_perm_group = group;
      bus.$emit('change-tab', 'perms');
    },
    edit_user_perms: function(user) {
      this.activeTab = 'perms';
      this.edit_perm_user = user;
      bus.$emit('change-tab', 'perms');
    },
    delete_user: function(user) {
      let app = this;
      if (confirm(lang_strings.delete_user)) {
        return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/delete/' + user.ID, {
          method: 'DELETE',
          headers: {
            "Content-Type": "application/json; charset=utf-8",
            'X-WP-Nonce': cluevoWpApiSettings.nonce
          }
        })
          .then(function (response) {
            return response.json();
          })
          .then(function(data) {
            bus.$emit('user-removed');
            return app.init();
          })
          .then(function() {
            app.adding = false;
          })
          .catch(function(error) {
            console.error(error);
            this.adding = false;
          });
      }
    },
    delete_group: function(group) {
      if (confirm(this.$lang_strings.confirm_delete_group)) {
        let app = this;
        return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/groups/delete/' + group.group_id, {
          method: 'DELETE',
          headers: {
            "Content-Type": "application/json; charset=utf-8",
            'X-WP-Nonce': cluevoWpApiSettings.nonce
          }
        })
          .then(function (response) {
            return response.json();
          })
          .then(function(data) {
            bus.$emit('group-removed');
            return app.init();
          })
          .catch(function(error) {
            console.error(error);
          });
      }
    },
    refresh_group: function(group) {
      let app = this;
      return fetch(cluevoWpApiSettings.root + 'cluevo/v1/admin/users/groups/' + group.group_id, {
        method: 'GET',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': cluevoWpApiSettings.nonce
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          let groups = app.groups.map(function(g, i) {
            if (g.group_id == group.group_id)
              app.groups[i] = data;
          });
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    handle_tab_change: function(id) {
      this.activeTab = id;
    },
    handle_switch_to_group: function(id) {
      this.edit_perm_group = this.groups.find(g => g.group_id == id);
    }
  }
});
