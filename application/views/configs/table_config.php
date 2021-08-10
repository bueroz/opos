<?= form_open('config/save_tables/', array('id' => 'table_config_form', 'class' => 'form-horizontal')); ?>

<?php
$title_table['config_title'] = $this->lang->line('config_table_configuration');
$this->load->view('configs/config_header', $title_table);
?>

<ul id="table_error_message_box" class="error_message_box"></ul>

<div class="form-check form-switch mb-3">
	<input class="form-check-input" type="checkbox" id="switch-tables" checked="<?= $this->config->item('dinner_table_enable'); ?>">
	<label class="form-check-label" for="switch-tables"><?= $this->lang->line('config_dinner_table_enable'); ?></label>
</div>

<label for="tables" class="form-label">Tables</label>
<div class="row" id="tables">
	<div class="col-12 col-lg-6">
		<div class="input-group mb-3">
			<span class="input-group-text">1</span>
			<input type="text" aria-label="Table Name" class="form-control">
		</div>
	</div>

	<div class="col-12 col-lg-6">
		<div class="input-group mb-3">
			<span class="input-group-text">2</span>
			<input type="text" aria-label="Table Name" class="form-control">
		</div>
	</div>

	<div class="col-12 col-lg-6">
		<div class="input-group mb-3">
			<span class="input-group-text">3</span>
			<input type="text" aria-label="Table Name" class="form-control">
		</div>
	</div>

	<div class="col-12 col-lg-6">
		<div class="input-group mb-3">
			<span class="input-group-text">4</span>
			<input type="text" aria-label="Table Name" class="form-control">
		</div>
	</div>

	<div class="col-12 col-lg-6">
		<div class="input-group mb-3">
			<span class="input-group-text">5</span>
			<input type="text" aria-label="Table Name" class="form-control">
		</div>
	</div>
</div>


<div class="d-flex justify-content-end">
	<button class="btn btn-primary" name="submit_table"><?= $this->lang->line('common_submit'); ?></button>
</div>

<br><br><br>
<div id="dinner_tables">
	<?php $this->load->view('partial/dinner_tables', array('dinner_tables' => $dinner_tables)); ?>
</div>

<?= form_close(); ?>

<script type="text/javascript">
	//validation and submit handling
	$(document).ready(function() {

		var enable_disable_dinner_table_enable = (function() {
			var dinner_table_enable = $("#dinner_table_enable").is(":checked");
			$("input[name*='dinner_table']:not(input[name=dinner_table_enable])").prop("disabled", !dinner_table_enable);
			if (dinner_table_enable) {
				$(".add_dinner_table, .remove_dinner_table").show();
			} else {
				$(".add_dinner_table, .remove_dinner_table").hide();
			}
			return arguments.callee;
		})();

		$("#dinner_table_enable").change(enable_disable_dinner_table_enable);

		var table_count = <?= sizeof($dinner_tables); ?>;

		var hide_show_remove = function() {
			if ($("input[name*='dinner_tables']:enabled").length > 1) {
				$(".remove_dinner_tables").show();
			} else {
				$(".remove_dinner_tables").hide();
			}
		};

		var add_dinner_table = function() {
			var id = $(this).parent().find('input').attr('id');
			id = id.replace(/.*?_(\d+)$/g, "$1");
			var block = $(this).parent().clone(true);
			var new_block = block.insertAfter($(this).parent());
			var new_block_id = 'dinner_table_' + ++id;
			$(new_block).find('label').html("<?= $this->lang->line('config_dinner_table'); ?> " + ++table_count).attr('for', new_block_id).attr('class', 'control-label col-xs-2');
			$(new_block).find('input').attr('id', new_block_id).removeAttr('disabled').attr('name', new_block_id).attr('class', 'form-control input-sm').val('');
			hide_show_remove();
		};

		var remove_dinner_table = function() {
			$(this).parent().remove();
			hide_show_remove();
		};

		var init_add_remove_tables = function() {
			$('.add_dinner_table').click(add_dinner_table);
			$('.remove_dinner_table').click(remove_dinner_table);
			hide_show_remove();
			// set back disabled state
			enable_disable_dinner_table_enable();
		};
		init_add_remove_tables();

		var duplicate_found = false;
		// run validator once for all fields
		$.validator.addMethod('dinner_table', function(value, element) {
			var value_count = 0;
			$("input[name*='dinner_table']:not(input[name=dinner_table_enable])").each(function() {
				value_count = $(this).val() == value ? value_count + 1 : value_count;
			});
			return value_count < 2;
		}, "<?= $this->lang->line('config_dinner_table_duplicate'); ?>");

		$.validator.addMethod('valid_chars', function(value, element) {
			return value.indexOf('_') === -1;
		}, "<?= $this->lang->line('config_dinner_table_invalid_chars'); ?>");

		$('#table_config_form').validate($.extend(form_support.handler, {
			submitHandler: function(form) {
				$(form).ajaxSubmit({
					beforeSerialize: function(arr, $form, options) {
						$("input[name*='dinner_table']:not(input[name=dinner_table_enable])").prop("disabled", false);
						return true;
					},
					success: function(response) {
						$.notify({
							message: response.message
						}, {
							type: response.success ? 'success' : 'danger'
						});
						$("#dinner_tables").load('<?= site_url("config/ajax_dinner_tables"); ?>', init_add_remove_tables);
					},
					dataType: 'json'
				});
			},

			errorLabelContainer: "#table_error_message_box",

			rules: {
				<?php
				$i = 0;

				foreach ($dinner_tables as $dinner_table => $table) {
				?>
					<?= 'dinner_table_' . ++$i ?>: {
						required: true,
						dinner_table: true,
						valid_chars: true
					},
				<?php
				}
				?>
			},

			messages: {
				<?php
				$i = 0;

				foreach ($dinner_tables as $dinner_table => $table) {
				?>
					<?= 'dinner_table_' . ++$i ?>: "<?= $this->lang->line('config_dinner_table_required'); ?>",
				<?php
				}
				?>
			}
		}));
	});
</script>