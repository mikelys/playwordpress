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
  }
});

Vue.component('tabs', {
  data: function() {
    return {
      tabs: [],
    };
  },
  methods: {
    selectTab: function(id) {
      console.log("selecting", id);
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
    if (this.tabs) {
      this.tabs[0].isActive = true;
    }
  }
});

var settingsApp = new Vue({
  el: '#cluevo-settings-page'
});
