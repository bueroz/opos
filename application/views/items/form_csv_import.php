<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open_multipart('items/import_csv_file/', array('id' => 'csv_form', 'class' => 'form-horizontal')); ?>
<fieldset id="item_basic_info">
	<div class="form-group form-group-sm">
		<div class="col-xs-12">
			<a href="<?= site_url('items/generate_csv_file'); ?>"><?= $this->lang->line('common_download_import_template'); ?></a>
		</div>
	</div>

	<div class="form-group form-group-sm">
		<div class='col-xs-12'>
			<div class="fileinput fileinput-new input-group" data-provides="fileinput">
				<div class="form-control" data-trigger="fileinput"><i class="bi bi-file-earmark fileinput-exists"></i><span class="fileinput-filename"></span></div>
				<span class="input-group-addon input-sm btn btn-outline-secondary btn-file"><span class="fileinput-new"><?= $this->lang->line('common_import_select_file'); ?></span><span class="fileinput-exists"><?= $this->lang->line('common_import_change_file'); ?></span><input type="file" id="file_path" name="file_path" accept=".csv"></span>
				<a href="#" class="input-group-addon input-sm btn btn-outline-secondary fileinput-exists" data-dismiss="fileinput"><?= $this->lang->line('common_import_remove_file'); ?></a>
			</div>
		</div>
	</div>
</fieldset>
<?= form_close(); ?>

<script type="text/javascript">
	//validation and submit handling
	$(document).ready(function() {
		$('#csv_form').validate($.extend({
			submitHandler: function(form) {
				$(form).ajaxSubmit({
					success: function(response) {
						dialog_support.hide();
						table_support.handle_submit('<?= site_url('items'); ?>', response);
					},
					dataType: 'json'
				});
			},

			errorLabelContainer: '#error_message_box',

			rules: {
				file_path: 'required'
			},

			messages: {
				file_path: "<?= $this->lang->line('common_import_full_path'); ?>"
			}
		}, form_support.error));
	});
</script>