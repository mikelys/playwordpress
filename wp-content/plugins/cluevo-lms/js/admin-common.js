jQuery(document).ready(function() {
  jQuery('.cluevo-form-submit-btn').click(function(e) {
    jQuery(this).parents('form:first').submit();
  });
  jQuery('.cluevo-btn.disabled').click(function(e) {
    e.preventDefault();
  });
});
