<?php slot('submenu', get_partial('menu')) ?>
<?php slot('title', __('Import CSV File')) ?>

<?php echo $form->renderFormTag(url_for('@op_csv_plugin_import_file')) ?>
<table>
<?php echo $form ?>
<td colspan="2"><input type="submit" value="<?php echo __('Import') ?>" />
</table>
</form>
