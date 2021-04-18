Vue.component('competence-area-container', {
  props: [ 'area', 'strings', 'active' ],
  template: `
    <tr v-on:click="$emit('select-comp', area.competence_area_id)" v-bind:class="{ active: active }">
      <td>{{ area.competence_area_id }}</td>
      <td class="left">{{ area.competence_area_name }}</td>
      <!-- <td class="left">{{ area.competence_area_type }}</td> -->
      <td>{{ area.competences.length }}</td>
      <td>{{ area.modules.length }}</td>
      <!-- <td><a :href="'post.php?post=' + area.metadata_id + '&action=edit'">{{ area.metadata_id }}</a></td> -->
      <td class="left">{{ area.user_added }}</td>
      <td>{{ area.date_added }}</td>
      <td class="left">{{ area.user_modified }}</td>
      <td>{{ area.date_modified }}</td>
      <td class="left">
        <div class="tools">
          <div class="cluevo-btn" v-on:click="$emit('del-area', area)"><span class="dashicons dashicons-trash"></span></div>
          <div class="cluevo-btn" v-on:click="$emit('edit-area', area)"><span class="dashicons dashicons-edit"></span></div>
          <div class="cluevo-btn" v-on:click="$emit('edit-metadata', area)"><span class="dashicons dashicons-wordpress"></span></div>
        </div>
      </td>
    </tr>
  `
});

Vue.component('competence-area-app-header', {
  template: '#competence-area-app-header-template'
});

Vue.component('competence-area-editor', {
  props: [ 'area', 'creating' ],
  data: function() {
    return {
      editing_modules: false,
      editing_comps: false,
      modules: [],
      comps: [],
      edited: false
    }
  },
  template: '#competence-area-editor-template',
  created: function() {
    var editor = this;
    return get_comps()
      .then(function(data) {
        var tmpList = editor.area.competences.map(function(c, i) {
          return c.competence_id;
        });
        var list = data.map(function(c, i) {
          c.checked = (tmpList.indexOf(c.competence_id) > -1) ? true : false;
          return c;
        });
        editor.comps = list;
      });
  },
  methods: {
    toggle_edit_comps: function() {
      this.editing_comps = !this.editing_comps;
    },
    save_area: function() {
      var editor = this;
      this.edited = true;
      fetch(compApiSettings.root + 'cluevo/v1/competence/areas/' + this.area.competence_area_id, {
        method: 'POST',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
        body: JSON.stringify(this.area)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          console.log(data);
          if (data === true) {
            editor.$emit('updated', editor.area);
          }
        });
    },
    save_comps: function() {
      this.edited = true;
      var editor = this;
      var comps = [];
      this.comps.forEach(function(c) {
        if (c.checked)
          comps.push(c.competence_id);
      });
      fetch(compApiSettings.root + 'cluevo/v1/competence/areas/' + this.area.competence_area_id + '/competences', {
        method: 'PUT',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
        body: JSON.stringify(comps)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          editor.editing_comps = false;
          if (data === true) {
            fetch(compApiSettings.root + 'cluevo/v1/competence/areas/' + editor.area.competence_area_id, {
              method: 'GET',
              credentials: 'include',
              headers: {
                "Content-Type": "application/json; charset=utf-8",
                'X-WP-Nonce': compApiSettings.nonce
              }
            })
              .then(function (response) {
                return response.json();
              })
              .then(function(data) {
                editor.area.modules = data.modules;
                editor.area.competences = data.competences;
                editor.editing_comps = false;
                editor.$emit('updated', editor.area);
              });
          }
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    create_area: function() {
      var editor = this;
      this.area.competences = this.comps.filter(function(c) {
        return c.checked === true;
      });
      fetch(compApiSettings.root + 'cluevo/v1/competence/areas', {
        method: 'PUT',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
        body: JSON.stringify(this.area)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          console.log("created", data);
          if (data == false) {
            alert(window.lang_strings.area_create_error);
          } else {
            if (data > 0) {
              editor.area.competence_area_id = data;
              editor.$emit('created', editor.area);
            }
          }
        })
        .catch(function(error) {
          console.error(error);
        });
    }
  }
});

var areaApp = new Vue({
  el: '#competence-area-admin-app',
  data: function() {
    return {
      competences: [],
      areas: [],
      strings: {},
      cur_area: null,
      editing: false,
      creating: false,
      lang_strings: window.lang_strings
    }
  },
  template: `
    <div class="competence-area-app">
      <h2><span v-if="areas.length > 0">{{ areas.length }}</span> {{ strings.competence_areas_heading }}</h2>
      <div class="cluevo-admin-notice cluevo-notice-info" v-if="areas.length == 0">
        <p>{{ lang_strings.no_areas_found }}</p>
      </div>
      <button class="cluevo-btn auto cluevo-btn-primary" @click="create_area">{{ strings.create_area}}</button>
      <table class="cluevo-admin-table" v-if="areas.length > 0">
        <competence-area-app-header />
        <competence-area-container
          v-for="area in areas"
          v-bind:area="area"
          v-bind:key="area.competence_area_id"
          v-bind:strings="strings"
          v-bind:active="area == cur_area"
          v-on:del-area="delete_area"
          v-on:edit-area="edit_area"
          v-on:edit-metadata="edit_metadata"
        />
      </table>
      <competence-area-editor
        v-if="cur_area !== null && editing === true"
        v-bind:area="cur_area"
        v-bind:creating="creating"
        v-on:close="cancel_editing"
        v-on:updated="updated"
        v-on:created="created"
      />
    </div>
  `,
  created: function() {
    this.init();
  },
  methods: {
    init: function() {
      this.load_areas()
        .then(this.load_strings);
    },
    load_areas: function() {
      return fetch(compApiSettings.root + 'cluevo/v1/competence/areas/')
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.areaApp.areas = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    load_strings: function() {
      return fetch(compApiSettings.root + 'cluevo/v1/strings/competence/')
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.areaApp.strings = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    delete_area: function(area) {
      if (confirm(this.strings.delete_area.formatUnicorn( { name: area.competence_area_name }))) {
        console.warn("deleting", area.competence_area_name);
        var app = this;
        fetch(compApiSettings.root + 'cluevo/v1/competence/areas/' + area.competence_area_id, {
          method: 'DELETE',
          credentials: 'include',
          headers: {
            "Content-Type": "application/json; charset=utf-8",
            'X-WP-Nonce': compApiSettings.nonce
          }
        })
          .then(function (response) {
            return response.json();
          })
          .then(function(data) {
            if (data > 0) {
              console.log("deleted");
              var result = app.areas.filter(function(a) {
                return a.competence_area_id != area.competence_area_id;
              });
              console.log("result", result);
              app.areas = result;
            }
          })
          .catch(function(error) {
            console.error(error);
          });
      }
    },
    edit_area: function(area) {
      var app = this;
      fetch(compApiSettings.root + 'cluevo/v1/competence/areas/' + area.competence_area_id)
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          app.cur_area = data;
          app.editing = true;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    updated: function(area) {
      var app = this;
      this.areas.forEach(function(a, i) {
        if (a.competence_area_id == area.competence_area_id) {
          console.log("updating", a, area);
          app.areas[i] = area;
        }
      });
    },
    created: function(area) {
      this.areas.push(area);
    },
    cancel_editing: function() {
      this.editing = false;
      this.cur_area = null;
    },
    create_area: function() {
      console.log("creating");
      var app = this;
      fetch(compApiSettings.root + 'cluevo/v1/competence/areas/new', {
        method: 'GET',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          app.cur_area = data;
          app.editing = true;
          app.creating = true;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    edit_metadata: function(area) {
      window.location = "/wp-admin/post.php?post=" + area.metadata_id + "&action=edit";
    }
  }
});
