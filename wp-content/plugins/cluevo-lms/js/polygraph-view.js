// The raw data to observe

// A resusable polygon graph component
Vue.component('cluevo-polygraph', {
  props: ['stats'],
  template: `<g>
    <circle cx="100" cy="100" r="80"></circle>
    <polygon :points="points"></polygon>
    <axis-label
      v-for="(stat, index) in stats"
      :stat="stat"
      :index="index"
      :total="stats.length">
    </axis-label>
  </g>`,
  computed: {
    // a computed property for the polygon's points
    points: function () {
      var total = this.stats.length
      return this.stats.map(function (stat, i) {
        var point = valueToPoint(stat.value, i, total)
        return point.x + ',' + point.y
      }).join(' ')
    }
  },
  components: {
    // a sub component for the labels
    'axis-label': {
      props: {
        stat: Object,
        index: Number,
        total: Number
      },
      template: `<text :x="point.x" :y="point.y">{{stat.label}}</text>`,
      computed: {
        point: function () {
          return valueToPoint(
            +this.stat.value + 10,
            this.index,
            this.total
          )
        }
      }
    }
  }
})

// math helper...
  function valueToPoint (value, index, total) {
    var x     = 0
    var y     = -value * 0.8
    var angle = Math.PI * 2 / total * index
    var cos   = Math.cos(angle)
    var sin   = Math.sin(angle)
    var tx    = x * cos - y * sin + 100
    var ty    = x * sin + y * cos + 100
    return {
      x: tx,
      y: ty
    }
  }

jQuery(window).ready(function() {
  if (jQuery('#cluevo-polygraph').length > 0) {
    new Vue({
      el: '#cluevo-polygraph',
      data: {
        newLabel: '',
        stats: []
      },
      created: function() {
        this.init();
      },
      methods: {
        init: function() {
          var app = this;
          return fetch(cluevoWpApiSettings.root + 'cluevo/v1/user/competences/points', {
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
              app.stats = data;
            })
            .catch(function(error) {
              console.error(error);
            });
        }
      }
    })
  }
});
