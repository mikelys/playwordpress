var cluevo_module_install_type = 'file';

jQuery(document).ready(function() {

  jQuery('p.cluevo-add-demos a').click(function(e) {
    if (!confirm(strings.msg_install_demos)) {
      e.preventDefault();
      return;
    }
  });

  jQuery('#submit').click(function(e) {
    if (!jQuery(this).hasClass("disabled")) {
      jQuery(this).parents('form:first').submit();
    }
  });

  jQuery('#add-module-form').submit(function(e) {
    console.log("stop", jQuery(this).serialize());
    //e.preventDefault();
    //return false;
  });

  jQuery('.cluevo-btn.del-module').click(function(e) {
    if (confirm(strings.confirm_module_delete) !== true) {
      e.preventDefault();
    }
  });

  jQuery('#module-file-upload').on('change', function(e) {
    jQuery('#module-dl-url').val('');
    jQuery('.cluevo-selected-file').html(e.target.value);
    jQuery('#selected-file').val(e.target.value);
    console.log("module-file-upload change");
    if (jQuery(this).val() != '') {
      jQuery('#submit').removeClass('disabled');
    } else {
      jQuery('#submit').addClass('disabled');
    }
  });

  jQuery('#selected-file').on('input', function(e) {
    console.log("selected file input");
    if (jQuery('#module-file-upload').val() != '') {
      console.log("resetting file selection");
      jQuery(this).val('');
      jQuery('#submit').addClass('disabled');
      jQuery('#module-file-upload').val('');
    } else {
      if (jQuery(this).val() != '') {
        jQuery('#submit').removeClass('disabled');
      } else {
        jQuery('#submit').addClass('disabled');
      }
    }
  });

  jQuery('#module-dl-url').on('input', function(e) {
    console.log("resetting file input");
    if (jQuery('#module-file-upload').val() != '') {
      jQuery('#module-file-upload').val('');
    }

    if (jQuery(this).val() != '') {
      jQuery('#submit').removeClass('disabled');
    } else {
      jQuery('#submit').addClass('disabled');
    }
  });


  jQuery('.cluevo-admin-notice.is-dismissible').each(function(i, notice) {
    jQuery(notice).append('<button type="button" class="notice-dismiss" />');
    jQuery(notice).on('click', 'button', function() {
      jQuery(this).parents('.cluevo-admin-notice:first').fadeOut();
    });
  });

  jQuery('.cluevo-btn.edit-module-name').click(function(e) {
    var old = jQuery(this).parents('tr:first').find('td.title').text();
    var name = prompt(strings.rename_module_prompt, old);
    name = name.trim();
    if (name && name != old && name != "") {
      console.log("ok", name);
      var id = jQuery(this).data('id');
      console.log("id", id);
      var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + id + '/name';
      var cell = jQuery(this).parents('tr:first').find('td.title');
      jQuery.ajax({
        url: url,
        method: 'POST',
        data: JSON.stringify({ name: name }),
        contentType: 'application/json',
        dataType: 'json',
        beforeSend: function(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
        },
        success: function(response) {
          if (response === true) {
            jQuery(cell).text(name);
          } else {
            alert(strings.rename_module_error);
            console.error("failed to rename module");
          }
        }
      });
    }
  });

  jQuery('.cluevo-add-module-overlay').click(function(e) {
    e.stopPropagation();
    if (e.target != this)
      return;

    jQuery(this).fadeOut();
    reset_module_upload_ui();
  });

  jQuery('.cluevo-add-module-overlay button.close').click(function(e) {
    jQuery(this).parents('.cluevo-add-module-overlay:first').fadeOut();
    reset_module_upload_ui();
  });

  jQuery('.cluevo-add-module-overlay .module-list .module-type').click(function(e) {
    var index = jQuery(this).data('moduleIndex');
    jQuery('.cluevo-add-module-overlay .module-type-selection').hide();
    jQuery('.cluevo-add-module-overlay .cluevo-btn.select-type').css('display', 'inline-block');
    jQuery('.cluevo-add-module-overlay .module-description-container .module-type').css('display', 'none');
    jQuery('.cluevo-add-module-overlay .module-description-container .module-type[data-module-index="' + index + '"]').show();
  });

  jQuery('.cluevo-btn.add-module').click(function(e) {
    jQuery('.cluevo-add-module-overlay').fadeIn();
  });

  jQuery('.cluevo-add-module-overlay .module-description-container .module-type input[type="submit"]').click(function(e) {
    jQuery('.cluevo-add-module-overlay .module-description-container').hide();
    jQuery('.cluevo-add-module-overlay .upload-progress .cluevo-progress-container').removeClass('indeterminate');
    jQuery('.cluevo-add-module-overlay .upload-progress').show();
    var form = jQuery(this).parents('form:first');
    var formData = new FormData(form[0]);
    var action = jQuery(this).attr("action");
    jQuery.ajax({
      type: 'POST',
      url: cluevoWpApiSettings.root + 'cluevo/v1/modules/upload',
      data: formData,
      enctype: 'multipart/form-data',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
      },
      xhr: function() {
				// Custom XMLHttpRequest
				var appXhr = jQuery.ajaxSettings.xhr();

				// Check if upload property exists, if "yes" then upload progress can be tracked otherwise "not"
				if(appXhr.upload) {
					// Attach a function to handle the progress of the upload
          appXhr.upload.addEventListener('progress', function(e) {
            if(e.lengthComputable) {
              var currentProgress = (e.loaded / e.total) * 100; // Amount uploaded in percent
              jQuery('.cluevo-add-module-overlay .upload-progress .cluevo-progress-container span.cluevo-progress').width(100 - currentProgress+'%');

              if( currentProgress == 100 ) {
                jQuery('.cluevo-add-module-overlay .upload-progress .cluevo-progress-container').toggleClass('indeterminate');
                jQuery('.cluevo-add-module-overlay .upload-progress .progress-text').text(strings.upload_success);
              }
            }
          }, false);
				}
				return appXhr;
			},
      processData: false,
      contentType: false,
      cache: false,
      success: function(result) {
        if (result) {
          var notices = [];
          var errors = [];
          if (result.handled) {
            if (result.messages && result.messages.length > 0) {
              notices = result.messages.map(function(t) {
                return '<div class="cluevo-notice cluevo-notice-notice"><p>' + t + '</p></div>';
              })
            }
            if (result.errors && result.errors.length > 0) {
              errors = result.errors.map(function(t) {
                return '<div class="cluevo-notice cluevo-notice-error"><p>' + t + '</p></div>';
              })
            }
            notices = notices.concat(errors).join("\n");
            jQuery('.cluevo-add-module-overlay .upload-progress .cluevo-progress-container').hide();
            jQuery('.cluevo-add-module-overlay .upload-progress .progress-text').text(strings.module_upload_finished);
            jQuery('.cluevo-add-module-overlay .upload-progress .result-container').html(notices);
            jQuery('.cluevo-add-module-overlay .upload-progress .result-container').show();
            jQuery('.cluevo-add-module-overlay .cluevo-btn.continue').css('display', 'inline-block');
            cluevo_update_module_table(result.module);
          } else {
            var error = '<div class="cluevo-notice cluevo-notice-error"><p>' + strings.upload_error + '</p></div>';
            jQuery('.cluevo-add-module-overlay .upload-progress .progress-text').text(strings.module_upload_failed);
            jQuery('.cluevo-add-module-overlay .upload-progress .result-container').html(error);
            jQuery('.cluevo-add-module-overlay .upload-progress .result-container').show();
            jQuery('.cluevo-add-module-overlay .cluevo-btn.continue').css('display', 'inline-block');
          }
        }
      },
      error: function(error) {
        console.error(error);
        if (error.responseJSON) {
          if (error.responseJSON.errors && error.responseJSON.errors.length > 0) {
            var  errors = error.responseJSON.errors.map(function(t) {
                return '<div class="cluevo-notice cluevo-notice-error"><p>' + t + '</p></div>';
              })
            errors = errors.join("\n");
            jQuery('.cluevo-add-module-overlay .upload-progress .cluevo-progress-container').hide();
            jQuery('.cluevo-add-module-overlay .upload-progress .progress-text').text(strings.module_upload_failed);
            jQuery('.cluevo-add-module-overlay .upload-progress .result-container').html(errors);
            jQuery('.cluevo-add-module-overlay .upload-progress .result-container').show();
            jQuery('.cluevo-add-module-overlay .cluevo-btn.continue').css('display', 'inline-block');
          }
        } else {
          jQuery('.cluevo-add-module-overlay .upload-progress .cluevo-progress-container').hide();
          jQuery('.cluevo-add-module-overlay .upload-progress .progress-text').text(strings.module_upload_failed);
          var error = '<div class="cluevo-notice cluevo-notice-error"><p>' + strings.upload_error + '</p></div>';
          jQuery('.cluevo-add-module-overlay .upload-progress .result-container').html(error);
          jQuery('.cluevo-add-module-overlay .upload-progress .result-container').show();
          jQuery('.cluevo-add-module-overlay .cluevo-btn.continue').css('display', 'inline-block');
        }
      }
    });
    return false;
  });

  jQuery('.cluevo-add-module-overlay .cluevo-btn.select-type').click(function() {
    reset_module_upload_ui();
    return;
    jQuery(this).hide();
    jQuery('.cluevo-add-module-overlay .module-type-selection').show();
    jQuery('.cluevo-add-module-overlay .module-description-container .module-type').hide();
  });

  jQuery('.cluevo-add-module-overlay .cluevo-btn.continue').click(reset_module_upload_ui);

  jQuery('.cluevo-add-module-overlay .module-description-container .module-type input[name="module-file"]').on('change', function(e) {
    var fileField = jQuery(this).parents('.input-switch:first').find('input[name="module-file"]');
    var urlField = jQuery(this).parents('.input-switch:first').find('input[name="module-dl-url"]');
    var submitButton = jQuery(this).parents('.module-type:first').find('input[type="submit"]');
    let max = jQuery(this).parents('.cluevo-add-module-overlay:first').data('max-upload-size');
    urlField.val(e.target.value);
    jQuery(this).parents(".module-type:first").find(".cluevo-notice.cluevo-filesize").addClass("hidden");
    if (max < this.files[0].size) {
      jQuery(this).parents(".module-type:first").find(".cluevo-notice.cluevo-filesize").removeClass("hidden");
      return;
    }
    if (jQuery(this).val() != '') {
      submitButton.removeClass('disabled');
      submitButton.attr('disabled', false);
    } else {
      submitButton.addClass('disabled');
      submitButton.attr('disabled', 'disabled');
    }
  });

  jQuery('.cluevo-add-module-overlay .module-description-container .module-type input[name="module-dl-url"], .cluevo-add-module-overlay .module-description-container .module-type textarea[name="module-dl-url"]').on('input', function(e) {
    var fileField = jQuery(this).parents('form:first').find('input[name="module-file"]');
    var urlField = jQuery(this);
    var submitButton = jQuery(this).parents('form:first').find('input[type="submit"]');
    if (fileField.length > 0 && fileField.val() != '') {
      console.log("resetting file selection", fileField);
      jQuery(fileField).val('');
      //submitButton.addClass('disabled');
      //submitButton.attr('disabled', 'disabled');
      fileField.val('');
    }
    console.log("url field value", urlField.val());
    if (urlField.val() != '' && isUrl(urlField.val())) {
      console.log("enabling button", submitButton);
      submitButton.removeClass('disabled');
      submitButton.attr('disabled', false); 
    } else {
      submitButton.addClass('disabled');
      submitButton.attr('disabled', 'disabled');
    }
  });

});

function cluevo_update_module_table(module) {
  var rows = jQuery('table.cluevo-scorm-modules tr[data-module-id="' + module.module_id + '"]');
  if (rows.length > 0) {
    console.log("row exists, update");
  } else {
    console.log("appending");
    var row = jQuery('<tr data-module-id="' + module.module_id + '">'
      + '<td>' + module.module_id + '</td>'
      + '<td class="title left column-title has-row-actions column-primary" data-id="' + module.module_id + '">' + module.module_name + '</td>'
      + '<td class="type left">' + module.type_name + '</td>'
      + '<td>' + strings.refresh_to_enable + '</td>'
      + '</tr>').appendTo(jQuery('table.cluevo-scorm-modules tbody'));
    console.log("appended", row);
  }
}

function isUrl(string) {
  var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
    '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
    '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
    '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
    '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
    '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
  return !!pattern.test(string);
}

function reset_module_upload_ui() {
  jQuery('.cluevo-add-module-overlay .upload-progress .cluevo-progress-container').show();
  jQuery('.cluevo-add-module-overlay .module-type-selection').show();
  jQuery('.cluevo-add-module-overlay .upload-progress .progress-text').text('');
  jQuery('.cluevo-add-module-overlay .upload-progress .result-container').html('');
  jQuery('.cluevo-add-module-overlay .upload-progress').hide();
  jQuery('.cluevo-add-module-overlay .cluevo-btn.select-type').hide();
  jQuery('.cluevo-add-module-overlay .module-description-container').show();
  jQuery('.cluevo-add-module-overlay .module-description-container .module-type').hide();
  jQuery('.cluevo-add-module-overlay form').trigger('reset');
  jQuery('.cluevo-add-module-overlay .cluevo-btn.continue').hide();
  jQuery('.cluevo-add-module-overlay .cluevo-btn.continue').hide();
  jQuery('.cluevo-add-module-overlay .cluevo-notice.cluevo-filesize').hide();
  jQuery('.cluevo-add-module-overlay input[type="submit"]').addClass("disabled");
  jQuery('.cluevo-add-module-overlay input[type="submit"]').attr("disabled", true);
}
