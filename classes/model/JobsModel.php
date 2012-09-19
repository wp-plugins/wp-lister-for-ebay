<?php

class JobsModel extends WPL_Model {

	public $key = '';
	public $item = '';

	function JobsModel( $job = null )
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_jobs';

		// return nothing if params are empty
		if ( ! $job ) return;

		// handle string as job_key
		if ( is_string( $job ) ) {
			$this->item = $this->getItemByKey( $job );
			return $this;
		}

		// handle object as new job object
		if ( is_object( $job ) ) {
			return $this->insertJob( $job->jobname, $job->tasklist );
		}

	}
	

	function getAll() {
		global $wpdb;	
		$jobs = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
		", ARRAY_A);		

		foreach( $jobs as &$job ) {
			$job['tasklist'] = $this->decodeObject( $job['tasklist'] );
		}

		return $jobs;		
	}


	function getItem( $id ) {
		global $wpdb;	
		$item = $wpdb->get_row("
			SELECT * 
			FROM $this->tablename
			WHERE job_id = '$id'
		", ARRAY_A);		

		$item['tasklist'] = $this->decodeObject( $item['tasklist'], true );

		return $item;		
	}

	function getItemByKey( $key ) {
		global $wpdb;	
		$item = $wpdb->get_row("
			SELECT * 
			FROM $this->tablename
			WHERE job_key = '$key'
		", ARRAY_A);		

		$item['tasklist'] = $this->decodeObject( $item['tasklist'], true );

		$this->key = $key;
		return $item;		
	}


	function deleteItem( $id ) {
		global $wpdb;
		$wpdb->query("
			DELETE
			FROM $this->tablename
			WHERE job_id = '$id'
		");
	}


	function insertJob( $jobname, $tasklist )
	{
		global $wpdb;

		// get current user id
		$user = wp_get_current_user();

		// generate job key
		$key = md5( $jobname . rand() );
		
		// insert row into db
		$data = array();
		$data['job_key'] = $key;
		$data['job_name'] = $jobname;
		$data['tasklist'] = $this->encodeObject($tasklist);
		$data['date_created'] = date( 'Y-m-d H:i:s' );
		$data['user_id'] = $user->ID;

		$wpdb->insert($this->tablename, $data);

		$this->logger->info("insertJob( $jobname ) - key $key" );
		$this->key = $key;					
		return $key;
	}

	function updateJob($id, $data) {
		global $wpdb;	
		$result = $wpdb->update( $this->tablename, $data, array( 'job_id' => $id ) );

		return $result;		

	}

	function completeJob() {
		global $wpdb;	

		$data = array();
		$data['success'] = 'complete';
		$data['date_finished'] = date( 'Y-m-d H:i:s' );
		$result = $wpdb->update( $this->tablename, $data, array( 'job_key' => $this->key ) );

		// update item data
		$this->item = $this->getItemByKey( $this->key );

		return $result;		

	}



}
