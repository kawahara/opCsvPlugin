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
(function(url, token, loading_id, status_id){
Event.observe(window, 'load', function(event){
  var s = 1;
  var loading_alert = $(loading_id);
  var status_box = $(status_id);
  var hideLoading = function () {
    new Effect.Fade(loading_alert);
  }
  var console = function (msg, level) {
    status_box.innerHTML += '<div>' + msg + '</div>'
  }
  var req = function() {
    new Ajax.Request(url, {
      method : 'post',
      onComplete : function(response){
        s += 100;
        if (response.status === 200) {
          var res = response.responseJSON;
          if (res !== null && 'status' in res) {
            if (res.status === 'COMPLETE') {
              console('Complate!', 'info');
              hideLoading();
            } else if (res.status === 'CONTINUE') {
              console('...', 'info');
              req();
            } else {
              if ('msg' in res) {
                console('Error: ' + res.msg.escapeHTML(), 'error');
              } else {
                console('Error', 'error');
              }
              hideLoading();
            }
          } else {
            console('Error: Bad response', 'error');
            hideLoading();
          }
        } else {
          console('Error: ' + response.status, 'error');
          hideLoading();
        }
      },
      parameters : { range: s + '-' + (s + 99), _csrf_token: token },
      evalJSON : true
    });
  }
  req();
});
})("<?php echo url_for('@op_csv_plugin_import_data?token='.$token) ?>", "<?php echo $csrfToken ?>", "import_now", "import_status");
<?php end_javascript_tag() ?>
