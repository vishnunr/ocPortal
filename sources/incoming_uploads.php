<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core
 */

/**
 * Function to clear old uploads, that are older then 2 days
 */
function clear_old_uploads()
{
	//get the unix timestamp corresonding to the two days ago condition
	$two_days_ago=strtotime('-2 days');
	//get the incoming uploads that are older than two days
	$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'incoming_uploads WHERE i_date_and_time<'.strval($two_days_ago));

	//if there are older uploads records found start processing them
	if(count($rows)>0)
	{
		//browse through files
		foreach($rows as $upload)
		{
			if(!empty($upload['i_save_url']))
			{
				if(file_exists($upload['i_save_url']))
				{
					//delete file if it exists
					@unlink($upload['i_save_url']);
				}

				//Note: it is possible some db records to be left without corresponding files. So we need to clean them too.
				$GLOBALS['SITE_DB']->query_delete('incoming_uploads',array('id'=>$upload['id']),'',1);
			}
		}
	}
}

/**
 * Function to process the file upload process
 */
function incoming_uploads_script()
{
	if (array_key_exists('Filedata',$_FILES))
	{
		$is_uploaded=false;
		if (is_uploaded_file($_FILES['Filedata']['tmp_name']))
		{
			$is_uploaded=true;
		} else
		{
			header('HTTP/1.1 500 File Upload Error');

			@error_log('ocPortal: '.do_lang('ERROR_UPLOADING_'.strval($_FILES['Filedata']['error'])),0);

			exit('ocPortal: '.do_lang('ERROR_UPLOADING_'.strval($_FILES['Filedata']['error'])));
		}

		$max_length = 255;
		$field_type_test = $GLOBALS['SITE_DB']->query_value('db_meta','m_type',array('m_name'=>'i_orig_filename'));
		if ($field_type_test == 'ID_TEXT') $max_length = 80; // Legacy
		$name = substr($_FILES['Filedata']['name'],max(0,strlen($_FILES['Filedata']['name'])-$max_length));
		$savename = 'uploads/incoming/'.uniqid('').'.dat';

		if (!file_exists(get_custom_file_base().'/uploads/incoming'))
		{
			@mkdir(get_custom_file_base().'/uploads/incoming',0777);
			fix_permissions(get_custom_file_base().'/uploads/incoming',0777);
			sync_file(get_custom_file_base().'/uploads/incoming');
		}

		if (($is_uploaded) && (file_exists($_FILES['Filedata']['tmp_name']))) // file_exists check after is_uploaded_file to avoid race conditions
		{
			header('Content-type: text/plain');

			@move_uploaded_file($_FILES['Filedata']['tmp_name'],get_custom_file_base().'/'.$savename) OR intelligent_write_error(get_custom_file_base().'/'.$savename);
			require_code('files');

			if (get_param_integer('base64',0)==1)
			{
				$new=base64_decode(file_get_contents(get_custom_file_base().'/'.$savename));
				$myfile=@fopen(get_custom_file_base().'/'.$savename,'wb') OR intelligent_write_error(get_custom_file_base().'/'.$savename);
				fwrite($myfile,$new);
				fclose($myfile);
			}

			fix_permissions(get_custom_file_base().'/'.$savename);
			sync_file(get_custom_file_base().'/'.$savename);

			$member_id=get_member();

			$file_db_id=$GLOBALS['SITE_DB']->query_insert('incoming_uploads',array('i_submitter'=>$member_id,'i_date_and_time'=>time(),'i_orig_filename'=>$name,'i_save_url'=>$savename),true,false);

			//echo "File is valid, and was successfully uploaded.\n";
			@ini_set('ocproducts.xss_detect','0');
			echo strval($file_db_id);
		} else
		{
			//echo "Possible file upload attack!\n";
			header('HTTP/1.1 500 File Upload Error');
		}
	} else
	{
		//header('Content-type: text/plain'); @print('No file ('.serialize($_FILES).')');
		header('HTTP/1.1 500 File Upload Error');

		// Test harness
		$title=get_page_title('UPLOAD');
		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_upload(do_lang_tempcode('FILE'),'','Filedata',true,NULL,NULL,false));
		$hidden=new ocp_tempcode();
		$out2=globalise(do_template('FORM_SCREEN',array('TITLE'=>$title,'SUBMIT_NAME'=>do_lang_tempcode('PROCEED'),'TEXT'=>'','HIDDEN'=>$hidden,'URL'=>find_script('incoming_uploads',true),'FIELDS'=>$fields)),NULL,'',true);
		$out2->evaluate_echo();
	}

	exit();
}