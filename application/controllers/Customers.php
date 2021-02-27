<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Persons.php");

class Customers extends Persons
{
	private $_list_id;

	public function __construct()
	{
		parent::__construct('customers');

		$this->load->library('mailchimp_lib');

		$CI =& get_instance();

		$this->_list_id = $CI->encryption->decrypt($CI->Appconfig->get('mailchimp_list_id'));
	}

	public function index()
	{
		$data['table_headers'] = $this->xss_clean(get_customer_manage_table_headers());

		$this->load->view('people/manage', $data);
	}

	/*
	Gets one row for a customer manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$person = $this->Customer->get_info($row_id);

		// retrieve the total amount the customer spent so far together with min, max and average values
		$stats = $this->Customer->get_stats($person->person_id);
		if(empty($stats))
		{
			//create object with empty properties.
			$stats = new stdClass;
			$stats->total = 0;
			$stats->min = 0;
			$stats->max = 0;
			$stats->average = 0;
			$stats->avg_discount = 0;
			$stats->quantity = 0;
		}

		$data_row = $this->xss_clean(get_customer_data_row($person, $stats));

		echo json_encode($data_row);
	}

	/*
	Returns customer table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = $this->input->get('search');
		$limit  = $this->input->get('limit');
		$offset = $this->input->get('offset');
		$sort   = $this->input->get('sort');
		$order  = $this->input->get('order');

		$customers = $this->Customer->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Customer->get_found_rows($search);

		$data_rows = array();
		foreach($customers->result() as $person)
		{
			// retrieve the total amount the customer spent so far together with min, max and average values
			$stats = $this->Customer->get_stats($person->person_id);
			if(empty($stats))
			{
				//create object with empty properties.
				$stats = new stdClass;
				$stats->total = 0;
				$stats->min = 0;
				$stats->max = 0;
				$stats->average = 0;
				$stats->avg_discount = 0;
				$stats->quantity = 0;
			}

			$data_rows[] = $this->xss_clean(get_customer_data_row($person, $stats));
		}

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Customer->get_search_suggestions($this->input->get('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Customer->get_search_suggestions($this->input->post('term'), FALSE));

		echo json_encode($suggestions);
	}

	/*
	Loads the customer edit form
	*/
	public function view($customer_id = -1)
	{
		$info = $this->Customer->get_info($customer_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['person_info'] = $info;

		if(empty($info->person_id) || empty($info->date) || empty($info->employee_id))
		{
			$data['person_info']->date = date('Y-m-d H:i:s');
			$data['person_info']->employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
		}

		$employee_info = $this->Employee->get_info($info->employee_id);
		$data['employee'] = $this->xss_clean($employee_info->first_name . ' ' . $employee_info->last_name);

		$tax_code_info = $this->Tax_code->get_info($info->sales_tax_code_id);
		$tax_code_id = $tax_code_info->tax_code_id;

		if($tax_code_info->tax_code != NULL)
		{
			$data['sales_tax_code_label'] = $this->xss_clean($tax_code_info->tax_code . ' ' . $tax_code_info->tax_code_name);
		}
		else
		{
			$data['sales_tax_code_label'] = '';
		}

		$packages = array('' => $this->lang->line('items_none'));
		foreach($this->Customer_rewards->get_all()->result_array() as $row)
		{
			$packages[$this->xss_clean($row['package_id'])] = $this->xss_clean($row['package_name']);
		}
		$data['packages'] = $packages;
		$data['selected_package'] = $info->package_id;

		if($this->config->item('use_destination_based_tax') == '1')
		{
			$data['use_destination_based_tax'] = TRUE;
		}
		else
		{
			$data['use_destination_based_tax'] = FALSE;
		}

		// retrieve the total amount the customer spent so far together with min, max and average values
		$stats = $this->Customer->get_stats($customer_id);
		if(!empty($stats))
		{
			foreach(get_object_vars($stats) as $property => $value)
			{
				$info->$property = $this->xss_clean($value);
			}
			$data['stats'] = $stats;
		}

		// retrieve the info from Mailchimp only if there is an email address assigned
		if(!empty($info->email))
		{
			// collect mailchimp customer info
			if(($mailchimp_info = $this->mailchimp_lib->getMemberInfo($this->_list_id, $info->email)) !== FALSE)
			{
				$data['mailchimp_info'] = $this->xss_clean($mailchimp_info);

				// collect customer mailchimp emails activities (stats)
				if(($activities = $this->mailchimp_lib->getMemberActivity($this->_list_id, $info->email)) !== FALSE)
				{
					if(array_key_exists('activity', $activities))
					{
						$open = 0;
						$unopen = 0;
						$click = 0;
						$total = 0;
						$lastopen = '';

						foreach($activities['activity'] as $activity)
						{
							if($activity['action'] == 'sent')
							{
								++$unopen;
							}
							elseif($activity['action'] == 'open')
							{
								if(empty($lastopen))
								{
									$lastopen = substr($activity['timestamp'], 0, 10);
								}
								++$open;
							}
							elseif($activity['action'] == 'click')
							{
								if(empty($lastopen))
								{
									$lastopen = substr($activity['timestamp'], 0, 10);
								}
								++$click;
							}

							++$total;
						}

						$data['mailchimp_activity']['total'] = $total;
						$data['mailchimp_activity']['open'] = $open;
						$data['mailchimp_activity']['unopen'] = $unopen;
						$data['mailchimp_activity']['click'] = $click;
						$data['mailchimp_activity']['lastopen'] = $lastopen;
					}
				}
			}
		}

		$this->load->view("customers/form", $data);
	}

	/*
	Adds Tags to customer controller
	*/

	public function tags($customer_id = -1)
	{
		$data['person_id'] = $customer_id;


		$definition_ids = json_decode($this->input->post('definition_ids'), TRUE);


		$data['definition_values'] = $this->Tag->get_tags_by_customer($customer_id) + $this->Tag->get_values_by_definitions($definition_ids);


		$data['definition_names'] = $this->Tag->get_definition_names();



		foreach($data['definition_values'] as $definition_id => $definition_value)
		{
			$tag_value = $this->Tag->get_tag_value($customer_id, $definition_id);


			$tag_id = (empty($tag_value) || empty($tag_value->tag_id)) ? NULL : $tag_value->tag_id;
	
			$values = &$data['definition_values'][$definition_id];
			$values['tag_id'] = $tag_id;
			$values['tag_value'] = $tag_value;
			$values['selected_value'] = '';

			if ($definition_value['definition_type'] == DROPDOWN)
			{
				$values['values'] = $this->Tag->get_definition_values($definition_id);
				$link_value = $this->Tag->get_link_value($customer_id, $definition_id);
				$values['selected_value'] = (empty($link_value)) ? '' : $link_value->tag_id;
			}

			if (!empty($definition_ids[$definition_id]))
			{
				$values['selected_value'] = $definition_ids[$definition_id];
			}

			unset($data['definition_names'][$definition_id]);
		}

		$this->load->view('tags/item', $data);
	}

	/*
	Inserts/updates a customer
	*/
	public function save($customer_id = -1)
	{
		$first_name = $this->xss_clean($this->input->post('first_name'));
		$last_name = $this->xss_clean($this->input->post('last_name'));
		$email = $this->xss_clean(strtolower($this->input->post('email')));

		// format first and last name properly
		$first_name = $this->nameize($first_name);
		$last_name = $this->nameize($last_name);

		$person_data = array(
			'first_name' => $first_name,
			'last_name' => $last_name,
			'gender' => $this->input->post('gender'),
			'email' => $email,
			'phone_number' => $this->input->post('phone_number'),
			'address_1' => $this->input->post('address_1'),
			'address_2' => $this->input->post('address_2'),
			'city' => $this->input->post('city'),
			'state' => $this->input->post('state'),
			'zip' => $this->input->post('zip'),
			'country' => $this->input->post('country'),
			'comments' => $this->input->post('comments')
		);

		$date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $this->input->post('date'));

		$customer_data = array(
			'consent' => $this->input->post('consent') != NULL,
			'account_number' => $this->input->post('account_number') == '' ? NULL : $this->input->post('account_number'),
			'tax_id' => $this->input->post('tax_id'),
			'company_name' => $this->input->post('company_name') == '' ? NULL : $this->input->post('company_name'),
			'discount' => $this->input->post('discount') == '' ? 0.00 : $this->input->post('discount'),
			'discount_type' => $this->input->post('discount_type') == NULL ? PERCENT : $this->input->post('discount_type'),
			'package_id' => $this->input->post('package_id') == '' ? NULL : $this->input->post('package_id'),
			'taxable' => $this->input->post('taxable') != NULL,
			'date' => $date_formatter->format('Y-m-d H:i:s'),
			'employee_id' => $this->input->post('employee_id'),
			'sales_tax_code_id' => $this->input->post('sales_tax_code_id') == '' ? NULL : $this->input->post('sales_tax_code_id')
		);

		if($this->Customer->save_customer($person_data, $customer_data, $customer_id))
		{
			// save customer to Mailchimp selected list
			$this->mailchimp_lib->addOrUpdateMember($this->_list_id, $email, $first_name, $last_name, $this->input->post('mailchimp_status'), array('vip' => $this->input->post('mailchimp_vip') != NULL));

			// New customer
			if($customer_id == -1)
			{

			// Save tags for new customer
				/*
				change default new customer creation ID to the ID in the customer data to be sent to database 
				*/

			$customer_id = $customer_data['person_id'];

			$tag_links = $this->input->post('tag_links') != NULL ? $this->input->post('tag_links') : array();
			$tag_ids = $this->input->post('tag_ids');
			$this->Tag->delete_link($customer_id);

			foreach($tag_links as $definition_id => $tag_id)
			{
				$definition_type = $this->Tag->get_info($definition_id)->definition_type;
				if($definition_type != DROPDOWN)
				{
					$tag_id = $this->Tag->save_value($tag_id, $definition_id, $customer_id, $tag_ids[$definition_id], $definition_type);
				}
				$this->Tag->save_link($customer_id, $definition_id, $tag_id);
			}
				echo json_encode(array('success' => TRUE,
								'message' => $this->lang->line('customers_successful_adding') . ' ' . $first_name . ' ' . $last_name,
								'id' => $this->xss_clean($customer_data['person_id'])));
			}
			else // Existing customer
			{
				// Update Tags for existing Customer
			
			$tag_links = $this->input->post('tag_links') != NULL ? $this->input->post('tag_links') : array();
			$tag_ids = $this->input->post('tag_ids');
			$this->Tag->delete_link($customer_id);

			foreach($tag_links as $definition_id => $tag_id)
			{
				$definition_type = $this->Tag->get_info($definition_id)->definition_type;
				if($definition_type != DROPDOWN)
				{
					$tag_id = $this->Tag->save_value($tag_id, $definition_id, $customer_id, $tag_ids[$definition_id], $definition_type);
				}
				$this->Tag->save_link($customer_id, $definition_id, $tag_id);
			}
				echo json_encode(array('success' => TRUE,
								'message' => $this->lang->line('customers_successful_updating') . ' ' . $first_name . ' ' . $last_name,
								'id' => $customer_id));
			}
		}
		else // Failure
		{
			echo json_encode(array('success' => FALSE,
							'message' => $this->lang->line('customers_error_adding_updating') . ' ' . $first_name . ' ' . $last_name,
							'id' => -1));
		}
	}

	/*
	AJAX call to verify if an email address already exists
	*/
	public function ajax_check_email()
	{
		$exists = $this->Customer->check_email_exists(strtolower($this->input->post('email')), $this->input->post('person_id'));

		echo !$exists ? 'true' : 'false';
	}

	/*
	AJAX call to verify if an account number already exists
	*/
	public function ajax_check_account_number()
	{
		$exists = $this->Customer->check_account_number_exists($this->input->post('account_number'), $this->input->post('person_id'));

		echo !$exists ? 'true' : 'false';
	}

	/*
	This deletes customers from the customers table
	*/
	public function delete()
	{
		$customers_to_delete = $this->input->post('ids');
		$customers_info = $this->Customer->get_multiple_info($customers_to_delete);

		$count = 0;

		foreach($customers_info->result() as $info)
		{
			if($this->Customer->delete($info->person_id))
			{
				// remove customer from Mailchimp selected list
				$this->mailchimp_lib->removeMember($this->_list_id, $info->email);

				$count++;
			}
		}

		if($count == count($customers_to_delete))
		{
			echo json_encode(array('success' => TRUE,
				'message' => $this->lang->line('customers_successful_deleted') . ' ' . $count . ' ' . $this->lang->line('customers_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('customers_cannot_be_deleted')));
		}
	}

	// /*
	// Customers import from csv spreadsheet
	// */
	// public function csv()
	// {
	// 	$name = 'import_customers.csv';
	// 	$data = file_get_contents('../' . $name);
	// 	force_download($name, $data);
	// }

	// public function csv_import()
	// {
	// 	$this->load->view('customers/form_csv_import', NULL);
	// }

	// public function do_csv_import()
	// {
	// 	if($_FILES['file_path']['error'] != UPLOAD_ERR_OK)
	// 	{
	// 		echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('customers_csv_import_failed')));
	// 	}
	// 	else
	// 	{
	// 		if(($handle = fopen($_FILES['file_path']['tmp_name'], 'r')) !== FALSE)
	// 		{
	// 			// Skip the first row as it's the table description
	// 			fgetcsv($handle);
	// 			$i = 1;

	// 			$failCodes = array();

	// 			while(($data = fgetcsv($handle)) !== FALSE)
	// 			{
	// 				// XSS file data sanity check
	// 				$data = $this->xss_clean($data);

	// 				$consent = $data[3] == '' ? 0 : 1;

	// 				if(sizeof($data) >= 16 && $consent)
	// 				{
	// 					$email = strtolower($data[4]);
	// 					$person_data = array(
	// 						'first_name'	=> $data[0],
	// 						'last_name'		=> $data[1],
	// 						'gender'		=> $data[2],
	// 						'email'			=> $email,
	// 						'phone_number'	=> $data[5],
	// 						'address_1'		=> $data[6],
	// 						'address_2'		=> $data[7],
	// 						'city'			=> $data[8],
	// 						'state'			=> $data[9],
	// 						'zip'			=> $data[10],
	// 						'country'		=> $data[11],
	// 						'comments'		=> $data[12]
	// 					);

	// 					$customer_data = array(
	// 						'consent'			=> $consent,
	// 						'company_name'		=> $data[13],
	// 						'discount'			=> $data[15],
	// 						'discount_type'		=> $data[16],
	// 						'taxable'			=> $data[17] == '' ? 0 : 1,
	// 						'date'				=> date('Y-m-d H:i:s'),
	// 						'employee_id'		=> $this->Employee->get_logged_in_employee_info()->person_id
	// 					);
	// 					$account_number = $data[14];

	// 					// don't duplicate people with same email
	// 					$invalidated = $this->Customer->check_email_exists($email);

	// 					if($account_number != '')
	// 					{
	// 						$customer_data['account_number'] = $account_number;
	// 						$invalidated &= $this->Customer->check_account_number_exists($account_number);
	// 					}
	// 				}
	// 				else
	// 				{
	// 					$invalidated = TRUE;
	// 				}

	// 				if($invalidated)
	// 				{
	// 					$failCodes[] = $i;
	// 				}
	// 				elseif($this->Customer->save_customer($person_data, $customer_data))
	// 				{
	// 					// save customer to Mailchimp selected list
	// 					$this->mailchimp_lib->addOrUpdateMember($this->_list_id, $person_data['email'], $person_data['first_name'], '', $person_data['last_name']);
	// 				}
	// 				else
	// 				{
	// 					$failCodes[] = $i;
	// 				}

	// 				++$i;
	// 			}

	// 			if(count($failCodes) > 0)
	// 			{
	// 				$message = $this->lang->line('customers_csv_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);

	// 				echo json_encode(array('success' => FALSE, 'message' => $message));
	// 			}
	// 			else
	// 			{
	// 				echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('customers_csv_import_success')));
	// 			}
	// 		}
	// 		else
	// 		{
	// 			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('customers_csv_import_nodata_wrongformat')));
	// 		}
	// 	}
	// }

	/*
	Customers import from csv spreadsheet
	*/
	public function csv()
	{
		$name = 'import_customers.csv';
		$allowed_tags = $this->Tag->get_definition_names(FALSE);
		$data = generate_import_customers_csv($allowed_tags);
		force_download($name, $data, TRUE);
	}

	public function csv_import()
	{
		$this->load->view('customers/form_csv_import', NULL);
	}

	public function do_csv_import()
	{
		if($_FILES['file_path']['error'] != UPLOAD_ERR_OK)
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('customers_csv_import_failed')));
		}
		else
		{
			if(file_exists($_FILES['file_path']['tmp_name']))
			{
				$line_array	= get_csv_file($_FILES['file_path']['tmp_name']);
				$failCodes	= array();
				$keys		= $line_array[0];


				$this->db->trans_begin();
				for($i = 1; $i < count($line_array); $i++)
				{
					$invalidated	= FALSE;

					$line = array_combine($keys,$this->xss_clean($line_array[$i]));	//Build a XSS-cleaned associative array with the row to use to assign values

					//check for consent in file upload, y == yes , empty field == no
					$consent = $line['Consent'] == '' ? 0 : 1;

					if($consent)
					{
						$email = strtolower($line['Email']);
						$person_data = array(
							'first_name'	=> $line['First Name'],
							'last_name'		=> $line['Last Name'],
							'gender'		=> $line['Gender'],
							'email'			=> $email,
							'phone_number'	=> $line['Phone Number'],
							'address_1'		=> $line['Address 1'],
							'address_2'		=> $line['Address2'],
							'city'			=> $line['City'],
							'state'			=> $line['State'],
							'zip'			=> $line['Zip'],
							'country'		=> $line['Country'],
							'comments'		=> $line['Comments']
						);

						$customer_data = array(
							'consent'			=> $consent,
							'company_name'		=> $line['Company'],
							'discount'			=> $line['Discount'],
							'discount_type'		=> $line['Discount_Type'],
							'taxable'			=> $line['Taxable'] == '' ? 0 : 1,
							'date'				=> date('Y-m-d H:i:s'),
							'employee_id'		=> $this->Employee->get_logged_in_employee_info()->person_id
						);
						$account_number = $line['Account Number'];

						// don't duplicate people with same email
						$invalidated = $this->Customer->check_email_exists($email);

						if($account_number != '')
						{
							$customer_data['account_number'] = $account_number;
							$invalidated &= $this->Customer->check_account_number_exists($account_number);
						}
					}
					else
					{
						$invalidated = TRUE;
					}

				//Save to database
					if(!$invalidated && $this->Customer->save_customer($person_data, $customer_data))
					{
						// save tags to customer
						$this->save_tag_data($line, $customer_data);
						// save customer to Mailchimp selected list
						$this->mailchimp_lib->addOrUpdateMember($this->_list_id, $person_data['email'], $person_data['first_name'], '', $person_data['last_name']);
					}
				//Insert or update item failure
					else
					{
						$failed_row = $i+1;
						$failCodes[] = $failed_row;
						log_message("ERROR","CSV Item import failed on line ". $failed_row .". This item was not imported.");
					}
				}

				if(count($failCodes) > 0)
				{
					$message = $this->lang->line('customers_csv_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);
					$this->db->trans_rollback();
					echo json_encode(array('success' => FALSE, 'message' => $message));
				}
				else
				{
					$this->db->trans_commit();
					echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('customers_csv_import_success')));
				}
			}
			else
			{
				echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('customers_csv_import_nodata_wrongformat')));
			}
		}
	}

		/**
	 * Checks the entire line of data in an import file for errors
	 *
	 * @param	array	$line
	 * @param 	array	$customer_data
	 *
	 * @return	bool	Returns FALSE if all data checks out and TRUE when there is an error in the data
	 */
	private function data_error_check($line, $person_data)
	{
	//Check for empty required fields
		$check_for_empty = array(
			$person_data['first_name'],
			$person_data['last_name']
		);

		foreach($check_for_empty as $key => $val)
		{
			if (empty($val))
			{
				log_message("ERROR","Empty required value");
				return TRUE;	//Return fail on empty required fields
			}
		}


	//Build array of fields to check for numerics
		$check_for_numeric_values = array(
			$person_data['gender'],
			$customer_data['discount'],
			$customer_data['discount_type']
		);

	//Check for non-numeric values which require numeric
		foreach($check_for_numeric_values as $value)
		{
			if(!is_numeric($value) && $value != '')
			{
				log_message("ERROR","non-numeric: '$value' when numeric is required");
				return TRUE;
			}
		}

	//Check Tag Data
		$definition_names = $this->Tag->get_definition_names();
		unset($definition_names[-1]);

		foreach($definition_names as $definition_name)
		{
			if(!empty($line['tag_' . $definition_name]))
			{
				$tag_data 	= $this->Tag->get_definition_by_name($definition_name)[0];
				$tag_type		= $tag_data['definition_type'];
				$tag_value 	= $line['tag_' . $definition_name];

				if($tag_type == 'DROPDOWN')
				{
					$dropdown_values 	= $this->Tag->get_definition_values($tag_data['definition_id']);
					$dropdown_values[] 	= '';

					if(in_array($tag_value, $dropdown_values) === FALSE && !empty($tag_value))
					{
						log_message("ERROR","Value: '$tag_value' is not an acceptable DROPDOWN value");
						return TRUE;
					}
				}
				else if($tag_type == 'DECIMAL')
				{
					if(!is_numeric($tag_value) && !empty($tag_value))
					{
						log_message("ERROR","'$tag_value' is not an acceptable DECIMAL value");
						return TRUE;
					}
				}
				else if($tag_type == 'DATETIME')
				{
					if(strtotime($tag_value) === FALSE && !empty($tag_value))
					{
						log_message("ERROR","'$tag_value' is not an acceptable DATETIME value.");
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}
			/**
	 * Saves tag data found in the CSV import.
	 *
	 * @param line
	 * @param failCodes
	 * @param tag_data
	 */
	private function save_tag_data($line, $customer_data )
	{
		$definition_names = $this->Tag->get_definition_names();
		unset($definition_names[-1]);

		foreach($definition_names as $definition_name)
		{
		//Create tag value
			if(!empty($line['tag_' . $definition_name]) || $line['tag_' . $definition_name] == '0')
			{
				$tag_data = $this->Tag->get_definition_by_name($definition_name)[0];

			//CHECKBOX Tag types (zero value creates tag and marks it as unchecked)
				if($tag_data['definition_type'] == 'CHECKBOX')
				{
				//FALSE and '0' value creates checkbox and marks it as unchecked.
					if(strcasecmp($line['tag_' . $definition_name],'FALSE') == 0 || $line['tag_' . $definition_name] == '0')
					{
						$line['tag_' . $definition_name] = '0';
					}
					else
					{
						$line['tag_' . $definition_name] = '1';
					}

					$status = $this->Tag->save_value($line['tag_' . $definition_name], $tag_data['definition_id'], $customer_data['person_id'], FALSE, $tag_data['definition_type']);
				}

			//All other Tag types (0 value means tag not created)
				elseif(!empty($line['tag_' . $definition_name]))
				{
					$status = $this->Tag->save_value($line['tag_' . $definition_name], $tag_data['definition_id'], $customer_data['person_id'], FALSE, $tag_data['definition_type']);
				}

				if($status === FALSE)
				{
					return FALSE;
				}
			}
		}
	}
}
?>
