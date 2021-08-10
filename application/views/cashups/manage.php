<?php $this->load->view("partial/header"); ?>

<script type="text/javascript">
	$(document).ready(function() {
		// when any filter is clicked and the dropdown window is closed
		$('#filters').on('hidden.bs.select', function(e) {
			table_support.refresh();
		});

		// load the preset datarange picker
		<?php $this->load->view('partial/daterangepicker'); ?>

		$("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
			table_support.refresh();
		});

		<?php $this->load->view('partial/bootstrap_tables_locale'); ?>

		table_support.init({
			resource: '<?= site_url($controller_name); ?>',
			headers: <?= $table_headers; ?>,
			pageSize: <?= $this->config->item('lines_per_page'); ?>,
			uniqueId: 'cashup_id',
			queryParams: function() {
				return $.extend(arguments[0], {
					start_date: start_date,
					end_date: end_date,
					filters: $("#filters").val() || [""]
				});
			}
		});
	});
</script>

<?php $this->load->view('partial/print_receipt', array('print_after_sale' => false, 'selected_printer' => 'takings_printer')); ?>

<div class="btn-toolbar justify-content-end d-print-none mb-3" role="toolbar">
	<button class="btn btn-primary me-2" onclick="javascript:printdoc()">
		<i class="bi bi-printer pe-1"></i><?= $this->lang->line('common_print'); ?>
	</button>
	<button class="btn btn-primary modal-dlg" data-btn-submit="<?= $this->lang->line('common_submit') ?>" data-href="<?= site_url($controller_name . '/view'); ?>" title="<?= $this->lang->line($controller_name . '_new'); ?>">
		<i class="bi bi-tags pe-1"></i><?= $this->lang->line($controller_name . '_new'); ?>
	</button>
</div>

<div class="btn-toolbar mb-3" role="toolbar">
	<button id="delete" class="btn btn-outline-secondary d-print-none me-2">
		<i class="bi bi-trash pe-1"></i><?= $this->lang->line('common_delete'); ?>
	</button>
	<?= form_input(array('name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker')); ?>
	<?= form_multiselect('filters[]', $filters, '', array('id' => 'filters', 'data-none-selected-text' => $this->lang->line('common_none_selected_text'), 'class' => 'selectpicker show-menu-arrow', 'data-selected-text-format' => 'count > 1', 'data-style' => 'btn-outline-secondary', 'data-width' => 'fit')); ?>
</div>

<div id="table_holder">
	<table id="table"></table>
</div>

<?php $this->load->view("partial/footer"); ?>