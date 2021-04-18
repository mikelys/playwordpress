jQuery(window).ready(function() {
  jQuery('.cluevo-blocked').click(function(e) {
    e.preventDefault();
    var item = this;
    jQuery(item)
      .find('.cluevo-meta-item.cluevo-access')
      .addClass('cluevo-alert');
    setTimeout(function() {
      jQuery(item)
        .find('.cluevo-meta-item.cluevo-access')
        .removeClass('cluevo-alert');
    }, 1000);
  });
  jQuery('.cluevo-content-list-style-switch .cluevo-btn').click(function(e) {
    var value = ""
    var prefix = "cluevo-content-list-style-";
    if (jQuery(this).hasClass('cluevo-content-list-style-row')) {
      value = prefix + "row";
      if (!jQuery('.cluevo-content-list').hasClass('cluevo-content-list-style-row')) {
        jQuery('.cluevo-content-list').addClass('cluevo-content-list-style-row');
      }
      jQuery('.cluevo-content-list-style-col').removeClass('active');
    }
    if (jQuery(this).hasClass('cluevo-content-list-style-col')) {
      value = prefix + "col";
      jQuery('.cluevo-content-list').removeClass('cluevo-content-list-style-row');
      jQuery('.cluevo-content-list-style-row').removeClass('active');
    }

    if (!jQuery(this).hasClass('active')) {
      jQuery(this).addClass('active');
    }

    var d = new Date();
    d.setTime(d.getTime() + (365* 24 * 60 * 60 * 1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = "cluevo-content-list-style=" + value + ";" + expires + ";path=/";
  })

  jQuery('.cluevo-content-item-link.access-denied').click(function(e) {
    e.preventDefault();

    let text = (jQuery(this).data("access-denied-text") != "") 
      ? jQuery(this).data("access-denied-text")
      : cluevoStrings.message_access_denied;
    cluevoAlert(cluevoStrings.message_title_access_denied, text, 'error');
  });
});

function cluevoAlert(title, message, type) {
  var box = jQuery('<div class="cluevo-alert-overlay cluevo-dismiss-click-area"><div class="cluevo-alert"><div class="cluevo-type-corner ' + type + '"></div><p class="cluevo-alert-title">' + title + '</p><p>' + message + '</p><div class="cluevo-alert-close-button cluevo-dismiss-click-area">╳</div></div></div>');
  jQuery(box).on('click', '.cluevo-alert-close-button', function(e) {
    e.stopPropagation();
    if (jQuery(this).hasClass("cluevo-dismiss-click-area")) {
      jQuery('.cluevo-alert-overlay').fadeOut(200, function() { jQuery(this).remove(); });
    }
  });
  jQuery(box).on('click', function(e) {
    e.stopPropagation();
    if (e.target != this) return;
    if (jQuery(this).hasClass("cluevo-dismiss-click-area")) {
      jQuery('.cluevo-alert-overlay').fadeOut(200, function() { jQuery(this).remove(); });
    }
  });
  box.appendTo('body')
    .animate({
      'opacity': 1
    }, 200);
}
