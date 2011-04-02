<?php use_helper('Javascript') ?>

<?php slot('submenu', get_partial('menu')) ?>
<?php slot('title', __('Import CSV File')) ?>

<div id="import_now" style="font-size:18px;color:#CC0000;">
<?php echo image_tag('/opCsvPlugin/images/loader.gif'); ?>

<?php echo __('Now Loading.'); ?> <?php echo __('Please do not reload.'); ?>
</div>
<div id="import_status">
</div>

<?php javascript_tag() ?>
(function(url, token, loading_id, status_id, options){
var s = 1;
options = options || {};
if ("interval" in options) {
  options.interval = 100;
}
Event.observe(window, 'load', function(event){
  var loading_alert = $(loading_id);
  var status_box = $(status_id);

  var hideLoading = function () {
    new Effect.Fade(loading_alert);
  }

  var output = function (msg, level) {
    status_box.innerHTML += '<div>' + msg + '</div>'
  }

  var outputMsgs = function (msg, level, resJSON) {
    if ('msgs' in resJSON) {
      for (var i = 0; i < resJSON.msgs.length; i++)
      {
        output(resJSON.msgs[i].escapeHTML(), 'info');
      }
    }
    output(msg, level);
  }

  var onComplete = function (response) {
    s += options.interval;
    if (response.status === 200) {
      var res = response.responseJSON;
      if (res !== null && 'status' in res) {
        if (res.status === 'COMPLETE') {
          outputMsgs('Complate!', 'info', res);
          hideLoading();
        } else if (res.status === 'CONTINUE') {
          outputMsgs('...', 'info', res);
          req();
        } else {
          if ('msg' in res) {
            output('Error: ' + res.msg.escapeHTML(), 'error');
          } else {
            output('Error', 'error');
          }
          hideLoading();
        }
      } else {
        output('Error: Bad response', 'error');
        hideLoading();
      }
    } else {
      output('Error: ' + response.status, 'error');
      hideLoading();
    }
  }

  var req = function() {
    new Ajax.Request(url, {
      method : 'post',
      onComplete : onComplete,
      parameters : { range: s + '-' + (s + 99), _csrf_token: token },
      evalJSON : true
    });
  }

  req();
});
})("<?php echo url_for('@op_csv_plugin_import_data?token='.$token) ?>", "<?php echo $csrfToken ?>", "import_now", "import_status");
<?php end_javascript_tag() ?>
