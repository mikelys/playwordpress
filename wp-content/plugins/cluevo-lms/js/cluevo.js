var scorm = pipwerks.SCORM; //Shortcut
var cluevo_scorm_window = null;
var cluevoClosingLightbox = false;
// SCORM 2004
var scorm_api = {
  //ModuleComplete: false,
  ItemId: null,
  LastError: 0,
  Initialized: false,
  Initialize: function() {
    console.info('Cluevo API, lms init');
    this.ModuleRunning = true;
    this.ParmTypes = scormParameterTypes;
    this.Initialized = true;
    this._cluevo_progress = {};
    return true;
  },
  GetLastError: function() {
    var error = this.LastError;
    this.LastError = 0;
    return error;
  },
  GetErrorString: function(code) {
    return 'Error: ' + code;
  },
  GetValue: function(parameter) {
    this.LastError = 0;
    if (!this.Initialized) {
      this.LastError = scormErrors.GetValueBeforeInit;
      return '';
    }
    if (parameter.indexOf('._count') !== -1) {
      var pattern = '(.*)\\._count';
      var regex = new RegExp(pattern, 'g');
      var match = regex.exec(parameter);
      return this.CountParameter(match[1]);
    }
    if (this.ParmTypes.hasOwnProperty(parameter)) {
      if (this.ParmTypes[parameter].hasOwnProperty('mode')) {
        if (this.ParmTypes[parameter].mode == 'wo') {
          this.LastError = scormErrors.ElementIsWriteOnly;
          return '';
        }
      }
    }

    if (this.Values[parameter] !== undefined) {
      if (this.ParmTypes.hasOwnProperty(parameter)) {
        var type = this.ParmTypes[parameter].type;
        switch (type) {
          case 'string':
            return '' + this.Values[parameter];
            break;
          case 'real':
            var num = Number(this.Values[parameter]);
            var digits = this.ParmTypes[parameter].digits;
            return num.toPrecision(digits[1]);
            break;
          case 'integer':
            return parseInt(this.Values[parameter]);
            break;
        }
      }
      return this.Values[parameter];
    } else {
      if (this.ParmTypes.hasOwnProperty(parameter)) {
        if (this.ParmTypes[parameter].hasOwnProperty('default')) {
          return this.ParmTypes[parameter].default;
        }
      }
      if (parameter.indexOf('._count') !== -1) {
        return '0';
      }
      return '';
    }
  },
  CountParameter: function(parm) {
    var indexes = [];
    for (var key in this.Values) {
      if (key !== parm + '._count' && key !== parm + '._children') {
        if (key.indexOf(parm) > -1) {
          var pattern = parm + '\\.(\\d*)';
          var regex = new RegExp(pattern, 'g');
          var match = regex.exec(key);
          if (match.length > 0) {
            if (match[1] !== '') {
              if (indexes.indexOf(match[1]) === -1) {
                indexes.push(match[1]);
              }
            }
          }
        }
      }
    }
    return indexes.length;
  },
  Values: {},
  SetValue: function(parameter, value) {
    this.LastError = 0;
    if (!this.Initialized) {
      this.LastError = scormErrors.SetValueBeforeInit;
      return '';
    }
    this.Values[parameter] = value;
    return 'true';
  },
  Commit: function(input) {
    this.LastError = 0;
    if (this.ItemId) {
      var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + this.ItemId + '/parameters';
      var data = this.Values;
      if (!data.hasOwnProperty("cmi.score.scaled")) {
        if (data.hasOwnProperty("cmi.score.raw") && data.hasOwnProperty("cmi.score.max") && Number(data["cmi.score.max"]) > 0)
          data["cmi.score.scaled"] = data["cmi.score.raw"] / data["cmi.score.max"];
      }
      if (!data.hasOwnProperty("cmi.core.score.scaled")) {
        if (data.hasOwnProperty("cmi.core.score.raw") && data.hasOwnProperty("cmi.core.score.max") && Number(data["cmi.core.score.max"]) > 0)
          data["cmi.core.score.scaled"] = data["cmi.core.score.raw"] / data["cmi.core.score.max"];
      }
      var api = this;

      jQuery.ajax({
        url: url,
        method: 'PUT',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(data),
        beforeSend: function(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
        },
        success: function(response) {
          api._cluevo_progress = response;
        }
      });
      return 'true';
    } else {
      return 'false';
    }

    return 'true';
  },
  CommitData: function(input) {
    return this.Commit();
  },
  getHandle: function() {
    return this;
  },
  GetStudentName: function() {
    console.log("get student name");
  },
  GetDiagnostic: function() {
    var string = 'Diagnostic: ' + this.LastError;
    this.LastError = 0;
    return string;
  },
  Terminate: function() {
    this.Values = {};
    this.ModuleRunning = false;
    this.Initialized = false;
    this.LastError = 0;
    this.ItemId = null;
    scorm.connection.isActive = false;
    if (cluevo_scorm_window !== null) {
      cluevo_scorm_window.close();
      cluevo_scorm_window = null;
    }

    if (this._cluevo_progress && this._cluevo_progress.hasOwnProperty("completion_status") && this._cluevo_progress.hasOwnProperty("success_status") && this._cluevo_progress.hasOwnProperty("lesson_status")) {
      if ((this._cluevo_progress.completion_status == "completed" && this._cluevo_progress.success_status == "passed") || this._cluevo_progress.lesson_status == "passed") {
        location.reload(true);
      }
    }
    this._cluevo_progress = {};

    cluevoCloseLightbox();
    return 'true';
  },
  LMSInitialize: function() { return this.Initialize(); },
  LMSFinish: function() { return this.Terminate(); },
  LMSGetValue: function(key) { return this.GetValue(key); },
  LMSSetValue: function(key, value) { return this.SetValue(key, value); },
  LMSCommit: function() { return this.Commit(); },
  LMSGetLastError: function() { return this.GetLastError(); },
  LMSGetErrorString: function() { return this.GetLastErrorString(); },
  LMSGetDiagnostic: function() { return this.GetDiagnostic(); },
  _cluevo_progress: {}
};

//var API = API_1484_11;
var API_1484_11 = null;
var API = null;

function initCluevoLmsApi(itemId, module, skipPrompt) {
  skipPrompt = skipPrompt || false;
  if (cluevo_scorm_window === null) {
    var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + itemId + '/parameters';
    module = module || false;
    jQuery.ajax({
      url: url,
      method: 'GET',
      contentType: 'application/json',
      dataType: 'json',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
      },
      success: function(response) {
        if (response.hasOwnProperty('_scorm_version')) {
          scorm.version = response._scorm_version;
          if (scorm.version === "1.2") {
            API_1484_11 = null;
            API = scorm_api;
          } else {
            API = null;
            API_1484_11 = scorm_api;
          }
        }
        if (!skipPrompt && response.hasOwnProperty("cmi.suspend_data")) {
          if (response["cmi.suspend_data"].value && response["cmi.suspend_data"].value != "") {
            var startOver = showResumePrompt(itemId, response, function(value, parms) {
              if (value == true && parms) {
                response = parms;
              }
              var lmsConnected = scorm.init();
              var curApi = scorm.API.get();
              curApi.Values = {};
              for (var key in response) {
                if (response[key])
                  curApi.Values[key] = response[key].value;
              }
              curApi.ItemId = itemId;
              if (!lmsConnected) {
                // TODO: Handle lms connection failed
                console.error('LMS CONNECTION FAILED');
                cluevoAlert(cluevoStrings.message_title_error, cluevoStrings.lms_connection_error, 'error');
              } else {
                if (module !== false) {
                  cluevo_scorm_window = window.open(module);
                }
              }
            });
          }
        } else {
          var lmsConnected = scorm.init();
          var curApi = scorm.API.get();
          curApi.Values = {};
          for (var key in response) {
            if (response[key])
              curApi.Values[key] = response[key].value;
          }
          curApi.ItemId = itemId;
          if (!lmsConnected) {
            // TODO: Handle lms connection failed
            console.error('LMS CONNECTION FAILED');
            cluevoAlert(cluevoStrings.message_title_error, cluevoStrings.lms_connection_error, 'error');
          } else {
            if (module !== false) {
              cluevo_scorm_window = window.open(module);
            }
          }
        }
      }
    });
  } else {
    cluevoAlert(cluevoStrings.message_title_error, cluevoStrings.message_module_already_running, 'error');
  }
}

async function initIframe(itemId, module, next) {
  var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + itemId + '/parameters';
  module = module || false;
  var success = false;
  await jQuery.ajax({
    url: url,
    method: 'GET',
    contentType: 'application/json',
    dataType: 'json',
    beforeSend: function(xhr) {
      xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
    },
    success: function(response) {
      if (response.hasOwnProperty('_scorm_version')) {
        scorm.version = response._scorm_version;
        if (scorm.version === "1.2") {
          API_1484_11 = null;
          API = scorm_api;
        } else {
          API = null;
          API_1484_11 = scorm_api;
        }
      }
      if (response.hasOwnProperty("cmi.suspend_data")) {
        if (response["cmi.suspend_data"].value && response["cmi.suspend_data"].value != "") {
          var startOver = showResumePrompt(itemId, response, function(value, parms) {
            cluevoCloseLightbox();
            if (value == true && parms) {
              response = parms;
            }
            iframeInitSuccess(itemId, module, response, function(success, module) {
              if (success) {
                next(true, module);
              } else {
                next(false)
              }
            });
          });
        }
      } else {
        iframeInitSuccess(itemId, module, response, function(success, module) {
          if (success) {
            next(true, module);
          } else {
            next(false)
          }
        });
      }
    }
  });
  return success;
}

function iframeInitSuccess(itemId, module, response, next) {
  jQuery('iframe#cluevo-module-iframe').attr('src', jQuery('iframe#cluevo-module-iframe').data('src'));
  var lmsConnected = scorm.init();
  var curApi = scorm.API.get();
  curApi.Values = {};
  for (var key in response) {
    if (response[key])
      curApi.Values[key] = response[key].value;
  }
  curApi.ItemId = itemId;
  if (!lmsConnected) {
    // TODO: Handle lms connection failed
  } else {
    if (module !== false) {
      if (jQuery('#cluevo-module-iframe').length === 1) {
        jQuery('#cluevo-module-iframe').attr('src', module);
        jQuery([document.documentElement, document.body]).animate({
          scrollTop: jQuery("#cluevo-module-iframe").offset().top - 50 
        }, 500);
      } else {
        success = true;
        next(true, module);
      }
    } else {
      next(false);
      success = false;
      return false;
    }
  }
}

function initApiWithItem (itemId, callback) {
  var url = cluevoWpApiSettings.root + 'cluevo/v1/items/' + itemId;
  jQuery.ajax({
    url: url,
    method: 'GET',
    contentType: 'application/json',
    beforeSend: function(xhr) {
      xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
    },
    success: function(response) {
      callback(response)
    }
  });
}

jQuery(document).ready(function() {
  if (jQuery('iframe#cluevo-module-iframe').length >= 1) {
    var itemId = jQuery('iframe#cluevo-module-iframe').data('item-id');
    initCluevoLmsApi(itemId, false, true);
  }

  jQuery('.cluevo-module-link').click(function(e) {
    cluevoRemoveTileOverlays();
  });

  if (jQuery('video.cluevo-media-module').length > 0) {
    window.onbeforeunload = function(e) {
      var moduleId = jQuery('video.cluevo-media-module').data('module-id');
      var video = jQuery('video.cluevo-media-module:first')[0];
      if (video && video.played.length > 0) {
        var max = video.duration;
        var score = (video.ended) ? video.duration : video.currentTime;
        var data = {
          id: moduleId,
          max: max,
          score: score
        };

        var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + moduleId + '/progress';
        jQuery.ajax({
          url: url,
          method: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify(data),
          beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
          },
          success: function(response) {
            // TODO: Handle success
          }
        });
      }
    };
  }

  jQuery('.cluevo-module-link.cluevo-module-mode-popup').click(function(e) {
    var item = this;
    e.preventDefault();
    if (cluevo_scorm_window !== null) {
      cluevo_scorm_window.close();
      cluevo_scorm_window = null;
      scorm.connection.isActive = false;
    }
    var data = jQuery(this).data();
    var itemId = data.itemId;
    var type = data.moduleType;
    if (type === "scorm 2004") {
      var url = cluevoWpApiSettings.root + 'cluevo/v1/items/' + itemId;
      jQuery.ajax({
        url: url,
        method: 'GET',
        contentType: 'application/json',
        beforeSend: function(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
        },
        success: function(response) {
          if (response.access) {
            if (response.scos && response.scos.length > 0) {
              if (response.scos.length == 1) {
                initCluevoLmsApi(itemId, response.scos[0].href);
              } else {
                showScoSelect(itemId, response.scos, async function(e) {
                  var href = jQuery(this).attr('href');
                  initCluevoLmsApi(itemId, href);
                  return;
                });
                return;
              }
            } else {
              if (response.iframe_index) {
                initCluevoLmsApi(itemId, response.iframe_index);
              } else {
                cluevoAlert(cluevoStrings.message_title_error, cluevoStrings.error_loading_module, 'error');
              }
            }
          } else {
            let text = (data.access_denied_text != "") 
              ? data.access_denied_text
              : cluevoStrings.message_access_denied;
            cluevoAlert(cluevoStrings.message_title_access_denied, text, 'error');
          }
        }
      });
    } else {
      switch (type) {
        case "audio":
        case "video":
          var url = cluevoWpApiSettings.root + 'cluevo/v1/items/' + itemId;
          jQuery.ajax({
            url: url,
            method: 'GET',
            contentType: 'application/json',
            dataType: 'json',
            beforeSend: function(xhr) {
              xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
            },
            success: function(response) {
              switch (type) {
                case "audio":
                case "video":
                  var mediaWindow = window.open(response.iframe_index);
                  mediaWindow.onbeforeunload = function(e) {
                    var video = jQuery(mediaWindow.document).find('video').first()[0];
                    var moduleId = response.module_id;
                    if (video) {
                      var max = video.duration;
                      var score = (video.ended) ? video.duration : video.currentTime;
                      var data = {
                        id: moduleId,
                        max: max,
                        score: score
                      };

                      var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + moduleId + '/progress';
                      jQuery.ajax({
                        url: url,
                        method: 'POST',
                        contentType: 'application/json',
                        dataType: 'json',
                        data: JSON.stringify(data),
                        beforeSend: function(xhr) {
                          xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
                        },
                        success: function(response) {
                          // TODO: Handle success
                        }
                      });
                    }
                  };
                  break;
                default:
              }
            }
          });
          break;
        default:
      }
    }
  });

  jQuery('.cluevo-module-link.cluevo-module-mode-lightbox').click(function(e) {
    e.preventDefault();
    var data = jQuery(this).data();
    var itemId = data.itemId;
    var moduleId = data.moduleId;
    var type = data.moduleType;
    if (type === "scorm 2004") {
      initApiWithItem(itemId, async function(response) {
        scorm.connection.isActive = false;
        if (response.access) {
          if (response.scos && response.scos.length > 0) {
            if (response.scos.length == 1) {
              var success = await initIframe(itemId, response.scos[0].href, function(success, module) {
                if (success) {
                  cluevoOpenLightbox(data);
                  cluevoShowLightbox();
                  cluevoShowLightboxSpinner();
                var iframe = jQuery('<iframe src="' + module + '"></iframe>');
                iframe.on('load', handleIframeLoaded);
                iframe.appendTo('#cluevo-module-lightbox-overlay');
                  cluevoHideLightboxSpinner();
                }
              });
            } else {
              showScoSelect(itemId, response.scos, async function(e) {
                e.preventDefault();
                cluevoOpenLightbox(data);
                cluevoShowLightbox();
                cluevoShowLightboxSpinner();
                var success = await initIframe(itemId, e.target.href, function(success, module) {
                  if (success) {
                    var iframe = jQuery('<iframe src="' + module + '"></iframe>');
                    iframe.on('load', handleIframeLoaded);
                    iframe.appendTo('#cluevo-module-lightbox-overlay');
                    cluevoHideLightboxSpinner();
                  }
                });
              });
              return;
            }
          } else {
            var success = await initIframe(itemId, response.iframe_index, function(success) {
              cluevoOpenLightbox(data);
              cluevoShowLightbox();
              if (!success) {
                jQuery('#cluevo-module-lightbox-overlay').find('.cluevo-spinner-container').fadeOut(500, function() {
                  jQuery(this).remove();
                  jQuery('<div class="cluevo-error"><div class="cluevo-error-msg">' + cluevoStrings.error_loading_module + '</div><div class="cluevo-btn cluevo-error-close-button auto">' + cluevoStrings.error_message_close + '</div></div>').appendTo(jQuery('#cluevo-module-lightbox-overlay'));
                });
              } else {
                var iframe = jQuery('<iframe src="' + response.iframe_index + '"></iframe>');
                iframe.on('load', handleIframeLoaded);
                iframe.appendTo('#cluevo-module-lightbox-overlay');
                cluevoHideLightboxSpinner();
              }
            });
          }
        } else {
          let text = (data.access_denied_text != "") 
            ? data.access_denied_text
            : cluevoStrings.message_access_denied;
          cluevoAlert(cluevoStrings.message_title_access_denied, text, 'error');
        }
      });
    } else {
      if (type == "audio" || type == "video") {
        var url = cluevoWpApiSettings.root + 'cluevo/v1/items/' + itemId;
        jQuery.ajax({
          url: url,
          method: 'GET',
          contentType: 'application/json',
          dataType: 'json',
          beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
          },
          success: function(response) {
            switch (type) {
              case "audio":
              case "video":
                jQuery(
                  '<div id="cluevo-module-lightbox-overlay" data-module-id="' + response.module_id + '" class="cluevo-media"><video src="' + response.iframe_index + '" autoplay controls></video><div class="cluevo-close-button cluevo-btn cluevo-media">&times;</div></div>'
                ).css({ display: 'flex'}).appendTo('body');
                jQuery('body, html').addClass('cluevo-module-overlay-active');
                jQuery('#cluevo-module-lightbox-overlay').fadeIn();
                break;
              default:
            }
          }
        });
      }
    }
  });

  jQuery(document).on(
    'click',
    '#cluevo-module-lightbox-overlay div.cluevo-close-button, #cluevo-module-lightbox-overlay div.cluevo-error-close-button',
    function() {
      if (jQuery(this).hasClass('cluevo-media')) {
        var video = jQuery('#cluevo-module-lightbox-overlay').find('video').first()[0];
        var moduleId = jQuery('#cluevo-module-lightbox-overlay').data('module-id');
        if (video) {
          var max = video.duration;
          var score = (video.ended) ? video.duration : video.currentTime;
          var data = {
            id: moduleId,
            max: max,
            score: score
          };

          var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + moduleId + '/progress';
          jQuery.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(data),
            beforeSend: function(xhr) {
              xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
            },
            success: function(response) {
              // TODO: Handle succcess
            }
          });
        }
      }
      cluevoCloseLightbox();
    }
  );

  if(jQuery('#cluevo-module-iframe').length > 0) {
    var data = jQuery('#cluevo-module-iframe').data();
    var itemId = data.itemId
    initApiWithItem(itemId, async function(response) {
      scorm.connection.isActive = false;
      if (response.access) {
        if (response.scos && response.scos.length > 0) {
          if (response.scos.length == 1) {
            jQuery('#cluevo-module-lightbox-overlay .cluevo-spinner-container').show();
            var success = await initIframe(itemId, response.scos[0].href, function(success) {
              if (success) {
                jQuery('#cluevo-module-lightbox-overlay .cluevo-sco-select-container').hide();
              }
            });
          } else {
            showScoSelect(itemId, response.scos, async function(e) {
              e.preventDefault();
              jQuery('#cluevo-module-lightbox-overlay .cluevo-spinner-container').show();
              var success = await initIframe(itemId, jQuery(this).attr('href'));
              if (success) {
                jQuery('#cluevo-module-lightbox-overlay .cluevo-sco-select-container').hide();
              }
            });
          }
          return;
        } else {
          initIframe(itemId, response.iframe_index);
        }
      } else {
        let text = (data.access_denied_text != "") 
          ? data.access_denied_text
          : cluevoStrings.message_access_denied;
        cluevoAlert(cluevoStrings.message_title_access_denied, text, 'error');
      }
    });
  }
});

function cluevoRemoveTileOverlays() {
  jQuery('.cluevo-sco-select-container').remove();
  jQuery('.cluevo-module-tile-overlay').remove();
}

async function showScoSelect(itemId, list, startFunc) {
  cluevoRemoveTileOverlays();
  let el = '<div class="cluevo-sco-select-container"><h2>' + cluevoStrings.sco_select_title + '</h2><ul>';
  var items = list.map(function(el) {
    return '<li><a href="' + el.href + '">' + el.title + '</a></li>';
  });
  el += items.join('\n');
  el += "</ul></div>";
  var sel = jQuery(el);
  sel.on('click', 'a', startFunc);
  sel.on('click', 'a', function(e) {
    e.stopPropagation();
    e.preventDefault();
    jQuery(sel).remove();
  });
  jQuery('.cluevo-content-item-link[data-item-id="' + itemId + '"] .cluevo-post-thumb:first').append(sel);
  jQuery(sel).fadeIn();
}

async function showResumePrompt(itemId, data, callback) {
  cluevoRemoveTileOverlays();
  var el = '<div class="cluevo-module-tile-overlay"><h2>' + cluevoStrings.start_over_dialog_header + '</h2><div class="cluevo-prompt-btns-container"><div class="cluevo-btn yes">' + cluevoStrings.start_over_opt_reset + '</div><div class="cluevo-btn no">' + cluevoStrings.start_over_opt_resume + '</div></div></div>';
  var dialog = jQuery(el);
  dialog.on("click", ".cluevo-btn.yes", async function() {
    var url = cluevoWpApiSettings.root + 'cluevo/v1/modules/' + itemId + '/new-attempt';
    await jQuery.ajax({
      url: url,
      method: 'GET',
      contentType: 'application/json',
      dataType: 'json',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', cluevoWpApiSettings.nonce);
      },
      success: function(response) {
        callback(true, response);
      }
    });
  });
  dialog.on("click", ".cluevo-btn.no", function() { 
    callback(false);
  });
  dialog.on("click", ".cluevo-btn", function(e) {
    e.stopPropagation();
    e.preventDefault();
    jQuery(dialog).remove();
  });
  if (jQuery('.cluevo-content-item-link[data-item-id="' + itemId + '"] .cluevo-post-thumb:first').length > 0) {
    jQuery('.cluevo-content-item-link[data-item-id="' + itemId + '"] .cluevo-post-thumb:first').append(dialog);
  } else {
    cluevoOpenLightbox(null, '', dialog);
    cluevoShowLightbox();
  }
  jQuery(dialog).fadeIn();
}

function handleIframeLoaded() {
  jQuery('#cluevo-module-lightbox-overlay').find('.cluevo-spinner-container').fadeOut(500, function() {
    jQuery(this).remove();
  });
}
jQuery('.iframe-sco-select').change(function(e) {
  var itemId = jQuery('iframe#cluevo-module-iframe').data('item-id');
  if (jQuery(this).val() != 0) {
    jQuery('iframe#cluevo-module-iframe').attr('src', jQuery(this).val());
    initCluevoLmsApi(itemId);
  }
});

