Vue.component('competence-container', {
  props: [ 'competence', 'strings', 'active' ],
  template: `
    <tr v-on:click="$emit('select-comp', competence.competence_id)" v-bind:class="{ active: active }">
      <td>{{ competence.competence_id }}</td>
      <td class="left">{{ competence.competence_name }}</td>
      <!-- <td class="left">{{ competence.competence_type }}</td> -->
      <td>{{ competence.areas.length }}</td>
      <td>{{ competence.modules.length }}</td>
      <!-- <td><a :href="'post.php?post=' + competence.metadata_id + '&action=edit'">{{ competence.metadata_id }}</a></td> -->
      <td class="left">{{ competence.user_added }}</td>
      <td>{{ competence.date_added }}</td>
      <td class="left">{{ competence.user_modified }}</td>
      <td>{{ competence.date_modified }}</td>
      <td class="left">
        <div class="tools">
          <div class="cluevo-btn" v-on:click="$emit('del-comp', competence)"><span class="dashicons dashicons-trash"></span></div>
          <div class="cluevo-btn" v-on:click="$emit('edit-comp', competence)"><span class="dashicons dashicons-edit"></span></div>
          <div class="cluevo-btn" v-on:click="$emit('edit-comp-metadata', competence)"><span class="dashicons dashicons-wordpress"></span></div>
        </div>
      </td>
    </tr>
  `
});

Vue.component('competence-app-header', {
  template: '#competence-app-header-template'
});

Vue.component('competence-editor', {
  props: [ 'competence', 'creating' ],
  data: function() {
    return {
      editing_modules: false,
      editing_areas: false,
      modules: [],
      areas: [],
      edited: false
    }
  },
  template: '#competence-editor-template',
  created: function() {
    var editor = this;
    get_modules()
      .then(function(data) {
        var tmpList = editor.competence.modules.map(function(m, i) {
          return m.module_id;
        });
        var list = data.map(function(m, i) {
          m.checked = (tmpList.indexOf(m.module_id) > -1) ? true : false;
          editor.competence.modules.forEach(function(module) {
            if (module.module_id == m.module_id)
              m.competence_coverage = module.competence_coverage * 100;
          });
          return m;
        });
        editor.modules = list;
        return get_areas();
      })
      .then(function(data) {
        var tmpList = editor.competence.areas.map(function(a, i) {
          return a.competence_area_id;
        });
        var list = data.map(function(a, i) {
          a.checked = (tmpList.indexOf(a.competence_area_id) > -1) ? true : false;
          return a;
        });
        editor.areas = list;
      });
  },
  methods: {
    toggle_edit_modules: function() {
      this.editing_modules = !this.editing_modules;
    },
    toggle_edit_areas: function() {
      this.editing_areas = !this.editing_areas;
    },
    save_modules: function() {
      this.edited = true;
      var editor = this;
      var modules = [];
      this.modules.forEach(function(m) {
        if (m.checked) {
          modules.push([ m.module_id, m.competence_coverage / 100 ]);
          editor.competence.modules.forEach(function(c) {
            if (c.module_id === m.module_id) {
              c.competence_coverage = m.competence_coverage / 100;
            }
          });
        }
      });
      fetch(compApiSettings.root + 'cluevo/v1/competence/competences/' + this.competence.competence_id + '/modules', {
        method: 'PUT',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
        body: JSON.stringify(modules)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          if (data === true) {
            editor.editing_modules = false;
            var newList = [];
            editor.modules.forEach(function(m) {
              if (m.checked) {
                var c = JSON.parse(JSON.stringify(m));
                c.competence_coverage /= 100;
                newList.push(c);
              }
            });
            editor.competence.modules = newList;
            editor.$emit('updated', editor.competence);
          }
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    save_competence: function() {
      var editor = this;
      this.edited = true;
      fetch(compApiSettings.root + 'cluevo/v1/competence/competences/' + this.competence.competence_id, {
        method: 'POST',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
        body: JSON.stringify(this.competence)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          console.log(data);
          if (data === true) {
            editor.$emit('updated', editor.competence);
          }
        });
    },
    save_areas: function() {
      this.edited = true;
      var editor = this;
      var areas = [];
      this.areas.forEach(function(a) {
        if (a.checked)
          areas.push(a.competence_area_id);
      });
      fetch(compApiSettings.root + 'cluevo/v1/competence/competences/' + this.competence.competence_id + '/areas', {
        method: 'PUT',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
        body: JSON.stringify(areas)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          editor.editing_areas = false;
          if (data === true) {
            var list = [];
            editor.areas.forEach(function(a) {
              if (a.checked)
                list.push(a);
            });
            editor.competence.areas = list;
            editor.$emit('updated', editor.competence);
          }
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    create_competence: function() {
      var editor = this;
      this.competence.areas = this.areas.filter(function(a) {
        return a.checked === true;
      });
      var modules = [];
      this.modules.forEach(function(m) {
        if (m.checked) {
          modules.push([ m.module_id, m.competence_coverage / 100 ]);
          editor.competence.modules.forEach(function(c) {
            if (c.module_id === m.module_id) {
              c.competence_coverage = m.competence_coverage / 100;
            }
          });
        }
      });
      this.competence.modules = modules;
      fetch(compApiSettings.root + 'cluevo/v1/competence/competences', {
        method: 'PUT',
        credentials: 'include',
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          'X-WP-Nonce': compApiSettings.nonce
        },
        body: JSON.stringify(this.competence)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          console.log("created", data);
          if (data == false) {
            alert(window.lang_strings.competence_create_error);
          } else {
            if (data > 0) {
              editor.competence.competence_id = data;
              editor.$emit('created', editor.competence);
            }
          }
        })
        .catch(function(error) {
          console.error(error);
        });
    }
  }
});

var compApp = new Vue({
  el: '#competence-admin-app',
  data: function() {
    return {
      competences: [],
      modules: [],
      areas: [],
      strings: {},
      cur_comp: null,
      editing: false,
      creating: false,
      lang_strings: window.lang_strings
    }
  },
  template: `
    <div class="competence-app">
      <h2><span v-if="competences.length > 0">{{ competences.length}}</span> {{ strings.competences_heading }}</h2>
      <div class="cluevo-admin-notice cluevo-notice-info" v-if="competences.length == 0">
        <p>{{ lang_strings.no_comps_found }}</p>
      </div>
      <button class="cluevo-btn auto cluevo-btn-primary" @click="create_competence">{{ strings.create_competence }}</button>
      <div class="comp-table-container">
        <table class="cluevo-admin-table" v-if="competences.length > 0">
          <competence-app-header />
          <competence-container
            v-for="comp in competences"
            v-bind:competence="comp"
            v-bind:key="comp.competence_id"
            v-bind:strings="strings"
            v-bind:active="comp == cur_comp"
            v-on:del-comp="delete_competence"
            v-on:edit-comp="edit_competence"
            v-on:edit-comp-metadata="edit_competence_metadata"
          />
        </table>
      </div>
      <competence-editor
        v-if="cur_comp !== null && editing === true"
        v-bind:competence="cur_comp"
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
      this.load_comps()
        .then(this.load_areas)
        .then(this.load_modules)
        .then(this.load_strings);
    },
    load_comps: function() {
      return fetch(compApiSettings.root + 'cluevo/v1/competence/competences/')
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.compApp.competences = data;
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
          this.compApp.strings = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    load_areas: function() {
      return fetch(compApiSettings.root + 'cluevo/v1/competence/areas/')
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.compApp.areas = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    load_modules: function() {
      return fetch(compApiSettings.root + 'cluevo/v1/modules/')
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          this.compApp.modules = data;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    delete_competence: function(comp) {
      if (confirm(this.strings.delete_competence.formatUnicorn( { name: comp.competence_name }))) {
        console.warn("deleting", comp.competence_name);
        var app = this;
        fetch(compApiSettings.root + 'cluevo/v1/competence/competences/' + comp.competence_id, {
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
              var result = app.competences.filter(function(a) {
                return a.competence_id != comp.competence_id;
              });
              console.log("result", result);
              app.competences = result;
            }
          })
          .catch(function(error) {
            console.error(error);
          });
      }
    },
    edit_competence: function(comp) {
      var app = this;
      fetch(compApiSettings.root + 'cluevo/v1/competence/competences/' + comp.competence_id)
        .then(function (response) {
          return response.json();
        })
        .then(function(data) {
          app.cur_comp = data;
          app.editing = true;
        })
        .catch(function(error) {
          console.error(error);
        });
    },
    edit_competence_metadata: function(comp) {
      var app = this;
      window.location = "/wp-admin/post.php?post=" + comp.metadata_id + "&action=edit";
    },
    updated: function(competence) {
      var app = this;
      this.competences.forEach(function(c, i) {
        if (c.competence_id == competence.competence_id) {
          app.competences[i] = competence;
        }
      });
    },
    created: function(competence) {
      this.competences.push(competence);
    },
    cancel_editing: function() {
      this.editing = false;
      this.creating = false;
      this.cur_comp = null;
    },
    create_competence: function() {
      console.log("creating");
      var app = this;
      fetch(compApiSettings.root + 'cluevo/v1/competence/competences/new', {
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
          app.cur_comp = data;
          app.editing = true;
          app.creating = true;
        })
        .catch(function(error) {
          console.error(error);
        });
    }
  }
});
