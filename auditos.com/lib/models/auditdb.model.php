<?php

class AuditdbModel extends Model
{
	private $ip, $db;
	
	public function __construct($projectid)
	{
		// Connect to central database.
		$db = new Model();

		$select = $db->getRow('SELECT `db_ip`, `db` FROM `projects` 
								WHERE `id` = :project_id', 
					array(':project_id' => $projectid));
		
		if ($select)
		{
			$this->ip = $select['db_ip'];
			$this->db = $select['db'];
		}
		
		parent::__construct($this->ip, $this->db);
	}
}
