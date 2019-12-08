<?php

class IssueModel // extends Model
{
	//database variables
	private $ip, $db;
	
	//issue variables
	private $id, $projectid, $planid, $eid;
	
	public function __construct($projectid, $planid, $id=null, $eid=null)
	{
		//$pid 	= project id
		//$id 	= issue id
		//$eid	= employee id
		
		$this->projectid 	= $projectid;
		$this->planid 		= $planid;
		
		//connect to central database
		$db = new Model();
		
		$select = $db->getRow("select db, db_ip from projects where id = :project_id",
			array(':project_id' => $projectid));
		
		$this->db = $select['db'];
		$this->ip = $select['db_ip'];
		
		//if id is set then a new Issue object
		//will be created from an existing issue
		if($id != null)
		{
			//connect to project database
			$db = new Model($this->ip, $this->db);

			$query = "select id, employee_id from portal_issue_log where id = :id";
			$params = array(':id' => $id);

			if($eid)
			{
				$query .= " and employee_id = :employee_id";
				$params[':employee_id'] = $eid;
			}

			
			$select = $db->getRow($query,$params);
			
			if($select)
			{
				$this->id 			= $select["id"];
				$this->eid 			= $select["employee_id"];
			}
		}
	}
	
	public function setID($id)
	{
		$this->id = $id;
	}
	
	public function getProjectID()
	{
		return $this->projectid;
	}
	
	public function getPlanID()
	{
		return $this->planid;
	}
	
	public function getEmployeeID()
	{
		return $this->eid;
	}
	
	public function getID()
	{
		return $this->id;
	}
		
	public function getSummary()
	{
		//connect to project database
		$db = new Model($this->ip, $this->db);
		
		$select = $db->getVal("select summary from portal_issue_log where id = :id", array(':id' => $this->id));

		return $select;
	}
	
	public function getStatus()
	{
		//connect to project database
		$db = new Model($this->ip, $this->db);
		
		$select = $db->getVal("select status from portal_issue_log where id = :id", array(':id' => $this->id));

		return $select;
	}
	
	public function getCategory()
	{
		//connect to project database
		$db = new Model($this->ip, $this->db);
		
		$select = $db->getVal("select category from portal_issue_log where id = :id", 
            array(':id' => $this->id));
		
		return $select;
	}
	
	public function getLog()
	{
		//connect to project database
		$db = new Model($this->ip, $this->db);
		
		$select = $db->getVal("select log from portal_issue_log where id = :id", 
            array(':id' => $this->id));

		return $select;
	}
	
	public function addComment($category, $log)
	{
		//connect to project database
		$db = new Model($this->ip, $this->db);
		
		//update
		return $db->q("update portal_issue_log set next_action = 'HMS', category = :category, log = concat(log, :log), updated_at = now() where id = :id", 
            array(':category' => $category,
                  ':log'      => $log,
                  ':id'       => $this->id));
	}
	
	public function createIssue($name, $eid, $title, $category, $log)
	{
		//connect to project database
		$db = new Model($this->ip, $this->db);

		//insert new issue into portal_issue_log
		$insert = $db->q("INSERT INTO portal_issue_log (project_id, plan_id, created_at, created_by, 
                            employee_id, summary, status, next_action, category, log, updated_at) 
                          VALUES (:project_id, :plan_id, now(), :name, :eid, :title, 'Open', 'HMS', :category, :log, now())",
                    array(':project_id' => $this->projectid,
                          ':plan_id'    => $this->planid,
                          ':name'       => $name, 
                          ':eid'        => $eid, 
                          ':title'      => $title,
                          ':category'   => $category,
                          ':log'        => $log));
                        
		if($insert)
		{
			//get new issue id
			$select = $db->getVal("select max(id) from portal_issue_log where project_id = :project_id and plan_id = :plan_id",
                array(':project_id' => $this->projectid,
                      ':plan_id'    => $this->planid));
			
			if($select)
			{
				//set this object's id to the new id
				$this->id 	= $select;
				
				//return new id
				return $this->id;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	//checks where the project
	public static function Exists($ip, $db, $pr, $pl, $i, $e = null)
	{
		//connect to project database
		$db = new Model($ip, $db);

        $query = "select 1 from portal_issue_log where id = :i and project_id = :p";
        $params = array(':i' => $i,
                        ':pr' => $pr);

        if($pl != 0)
        {
            $query .= " and plan_id = :plan_id";
            $params[':plan_id'] = $pl;
        }
        if(isset($e))
        {
            $query .= " and employee_id = :employee_id";
            $params[':employee_id'] = $e;
        }

		$select = $db->getVal($query,$params);
		
		if($select)
		{
			return true;
		}
		else
		{
			if(empty($e))
			{
				$select = $db->getRow("select employee_id from portal_issue_log where id = :i and project_id = :pr",
                    array(':i'  => $i,
                          ':pr' => $pr));
				
				if($select['employee_id'] == 0 && $select['employee_id'] != null)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
	}
    
    public function isAllowed($issue_id, $employee_id=null)
    {
        $success = false;
        
        # connect to database
        $db = new Model($this->ip, $this->db);
        
        $query = "select id, employee_id from portal_issue_log where id = :issue_id
                    and project_id = :project_id and plan_id = :plan_id ";
        $params = array(':issue_id'   => $issue_id,
                        ':project_id' => $this->projectid,
                        ':plan_id'    => ($this->planid ? $this->planid : 0));
        
        if( isset( $employee_id ) )
        {
            $query .= " and employee_id = :employee_id";
            $params[':employee_id'] = $employee_id;
        }

        $select = $db->getRow($query, $params);

        if($select)
        {
            $success = true;
        }
        
        return $success;
    }
}

?>
