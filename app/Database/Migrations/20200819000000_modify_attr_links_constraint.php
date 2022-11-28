<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_modify_attr_links_constraint extends Migration
{
	public function up(): void
	{
		error_log('Migrating modify_attr_links_constraint');

		helper('migration');
		execute_script(APPPATH . 'Database/Migrations/sqlscripts/3.3.2_modify_attr_links_constraint.sql');

		error_log('Migrating modify_attr_links_constraint');
	}

	public function down(): void
	{
	}
}
