jQuery(window).ready(function() {
  jQuery('.cluevo-competence-toggle-modules').click(function(e) {
    e.preventDefault();
    jQuery(this).parents('.cluevo-competence-container:first').find('.cluevo-competence-modules').toggleClass('cluevo-active');
    jQuery(this).parents('.cluevo-competence-container:first').find('.cluevo-post-thumb img').toggleClass('cluevo-blur');
  });

  jQuery('.cluevo-level-container .cluevo-exp-sub-container .cluevo-competences').click(function(e) {
    jQuery('#cluevo-polygraph').toggleClass('cluevo-active');
  });
});
