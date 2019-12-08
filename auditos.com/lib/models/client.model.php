<?php

class ClientModel // extends Model
{
	private $id, $name, $email, $org, $active, $filebox, $group, $project, $projects = array(), $plans = array();

    private $poeProjects = array();
    private $menuList = array();

    // specific permissions
    private $show_voluntary_terminations = true;
    private $show_issue_log = true;
    private $hasSelfServiceAccess = false;
	
	//constructor
	public function __construct($cid)
	{
		$db = new Model();
		
		$select = $db->getRow("select id, name, email, organization, active, account_type,
          filebox, permission_group, eid from clients where id = :cid",
			array(':cid' => $cid));
		
		if($select)
		{
			$this->id 	    = $select['id'];
			$this->name 	= $select['name'];
			$this->email	= $select['email'];
			$this->org  	= $select['organization'];
            $this->filebox	= $select['filebox'];
            $this->active	= $select['active'];
			$this->account	= $select["account_type"];
            $this->group	= strtolower(trim($select["permission_group"]));

            // used for email subject line
            $this->eid	    = $select["eid"];

            // hides voluntary terms and distributes values to involuntary
            // used strictly on stats/termination pages
            // controlled by client group level access
//            if ($this->group == 'calpers_core')
//            {
//                $this->show_voluntary_terminations = false;
//            }
//            else if ($this->group == 'calpers_employer')
//            {
//                $this->show_voluntary_terminations = false;
//                $this->show_issue_log              = false;
//            }
		}
		
		//get all projects this client is associated with
		$this->projects = $db->getRows("
                    select
                        projects.id 		            as project_id,
                        projects.name                   as project_name,
                        UPPER(projects.grouper)	        as project_grouper,
                        projects.project_type           as project_type,
                        projects.status                 as project_status,
                        projects.db	                    as project_db,
                        projects.db_ip                  as project_dbip,
                        plans.id                        as plan_id,
                        plans.name		                as plan_name,
                        clients_projects.self_service	as self_service,
                        clients_projects.ee_file_upload	as ee_file_upload,
                        hide_client_id
                    from (clients_projects, projects)
                        left join plans on clients_projects.plan_id = plans.id
                    where
                        clients_projects.project_id = projects.id and
                        clients_projects.client_id = :id and
                        projects.status in ('Active','Runout', 'Inactive')
                    order by projects.grouper, projects.name ASC;",
			array(':id' => $this->id));



        $sorted_projects = array();

        if( empty($this->projects[0]) )
        {
            $this->projects = false;
        }
        else
        {
            # get project start dates and sort them highest to lowest
            if( $this->projects )
            {
                foreach($this->projects as $project)
                {
                    # get project id and plan id
                    $project_ip = $project['project_dbip'];
                    $project_db = $project['project_db'];
                    $project_id = $project['project_id'];
                    $plan_id    = $project['plan_id'];

                    # cache
					$dbCache = new CacheModel( $project_ip, $project_db );
					$select = $dbCache->getRow("select g_audit_start_date, project_type, status from projects where id = :project_id",
						array(':project_id' => $project_id));

                    # go head and figure out which projects are poe's
                    if (stristr($select['project_type'],'poe') == true) {
                        $this->poeProjects[] = $project_id;
                    }

                    # add start date to projects array
                    array_push($sorted_projects, (array('start_date' => $select['g_audit_start_date']) + $project));

                    # if plan id set, get it
                    if( empty($plan_id) === false )
                    {
						$db = new Model( $project_ip, $project_db );
                        array_push($this->plans, $db->getRow("select * from plans where id = :plan_id",
														array(':plan_id' => $plan_id)));
                    }
                }
                // no longer sorting by start date keeping alphabetical
//                $this->projects = $this->sortProjects( $sorted_projects );
                $this->projects = $sorted_projects;
            }
        }
	}
    
//    private function sortProjects($projects)
//        {
//            $pass = true;
//
//            for($k = 1; $k < count($projects) && $pass; $k++)
//            {
//                $pass = false;
//
//                for($i = 0; $i < count($projects) - $k; $i++)
//                {
//                    if($projects[$i]['start_date'] < $projects[$i + 1]['start_date'])
//                    {
//                        $tmp = $projects[$i];
//                        $projects[$i] = $projects[$i + 1];
//                        $projects[$i + 1] = $tmp;
//                        $pass = true;
//                    }
//                }
//            }
//
//            return $projects;
//        }


    public function returnMenuItems()
    {
        if (empty($this->menuList))
        {
            foreach($this->projects as $project)
            {
                # Build the drop down, but switch based on project type
                if (empty($project['project_grouper']) === false)
                {
                    if( isset($this->menuList[$project['project_grouper']]) === false )
                    {
                        $this->menuList[$project['project_grouper']] = array();
                    }
                    array_push($this->menuList[$project['project_grouper']], $project);
                }
                else
                {
                    # build diff name if plan involved
                    $optionName = '';
                    if( empty($plan) === false )
                    {
                        $optionName = $project['project_name'] . ' - ' . $project['plan_name'];
                    }

                    $listing = array();
                    $listing[$optionName] = $project;
                    array_push($this->menuList, $listing);
                }
            }
        }

        return $this->menuList;

        /*
         * We now have this
        Array
        (
            [grouper] => Array
                (
                    [0] => Array
                        (
                            [start_date] => 2012-04-02
                            [project_id] => 5
                            [project_name] => DEV_Verification: Templates
                            [project_grouper] => VERIDEV
                            [project_type] => Custom
                            [project_status] => Active
                            [project_db] => dc4_audit_1_test
                            [project_dbip] => jefes-maudite.cktools.net
                            [plan_id] =>
                            [plan_name] =>
                        )
                )
         */
    }

	//accessors
	public function getID()
	{
		return $this->id;
	}

    public function getType()
    {
        return "Employer";
    }

	public function getName()
	{
		return $this->name;
	}
	
	public function getEmail()
	{
		return $this->email;
	}
	
	public function getOrg()
	{
		return $this->org;
	}
	
	public function getProject()
	{
		return $this->project;
	}
	
	public function getProjects()
	{
		return $this->projects;
	}

    public function getProjectDBModel($id)
    {
        foreach($this->projects as $project)
        {
            if ($project['project_id'] == $id)
            {
                return new Model($project['project_dbip'], $project['project_db']);
            }
        }
        return false;
    }

    public function getPoeProjects()
    {
        return $this->poeProjects;
    }

	public function getPlans()
	{
		return $this->plans;
	}
	
	public function getAccountType()
	{
		return $this->account;
	}

    public function getGroup()
    {
        return $this->group;
    }

    public function isInGroup($group)
    {
        if ($this->group == $group)
        {
            return true;
        }
        return false;
    }
	
	//mutators
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function setEmail($email)
	{
		$this->email = $email;
	}

	//checks (true or false) methods
	public function isActive()
	{
		if($this->active == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

    public function isPoe()
        {            
            if (strtoupper(trim($this->project['project_type'])) == 'POE')
            {
                return true;
            }
            else
            {
                return false;
            }
        }

    // specific use, if they have eid then use that in a contact line subject
    public function hasEid()
    {
        if(!empty($this->eid) && $this->eid != 0)
        {
            return $this->eid;
        }
        return false;
    }

    public function hasFileBox()
    {
        if ($this->filebox == 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

	public function setInactive()
	{
		$db = new Model();
		
		$update = $db->q("update clients set active = 0 where id = :id",
			array(':id' => $this->id));

		return ($update ? true : false);

	}
	
	public function setEmailKey($key, $action)
	{
		$db = new Model();
		
		$select = $db->getRow("select * from client_keys where client_id = :client_id and client_action = :action",
			array(':client_id' 	=> $this->id,
				  ':action'	    => $action));

		$rowsAffected = 0;
		if($select)
		{
			$update = $db->q("update client_keys set client_key = :key, created = now()
			    			where client_id = :id and client_action = :action",
			array(':key'    => $key,
				  ':id'     => $this->id,
				  ':action' => $action));
			$rowsAffected = $update->rowCount();
		}
		else
		{
			$insert = $db->q("insert into client_keys (client_id, client_key, client_action, created)
			    				values (:id, :client_key, :client_action, now())",
				array(':id'     => $this->id,
					  ':client_key'    => $key,
					  ':client_action' => $action));
			$rowsAffected = $insert->rowCount();
		}
		
		return ($rowsAffected == 1 ? true : false);
	}
	
	//gets the first project id in the $this->projects array
	//this is considered the clients "current" project since
	//it has the highest id
	public function getCurrentProjectID()
	{
		$projects = $this->projects;
		
		if(isset($projects[0]))
		{
			sort($projects);
			return $projects[0]['project_id'];
		}
		else
		{
			return $projects['project_id'];
		}
	}
	
	//gets the first project's plan id in the $this->projects array
	//if the plan id is 0 then false is returned
	public function getCurrentPlanID()
	{
		$plans = $this->projects;
		
		if(isset($plans[0]))
		{
			sort($plans);
			if($plans[0]['plan_id'] != 0)
			{
				return $plans[0]['plan_id'];
			}
			else
			{
				return false;
			}
		}
		else
		{
			if($plans['plan_id'] != 0)
			{
				return $plans['plan_id'];
			}
			else
			{
				return false;
			}
		}
	}
	
	public function getProjectName($pid)
	{
		$db = new Model();

		return $db->getVal("select name from projects where id = :pid",
			array(':pid' => $pid));
	}
	
	public function getPlanName($pid)
	{
		$db = new Model();
		
		return $db->getVal("select name from plans where id = :pid",
			array(':pid' => $pid));
	}

    public function canSeeIssues()
    {
        return $this->show_issue_log;
    }

    public function canSeeVoluntaryTerms()
    {
        return $this->show_voluntary_terminations;
    }

    public function canSeeClientID($projectid, $planid)
    {
        foreach($this->projects as $project)
        {
            if ($project['project_id'] === $projectid)
            {
                # has all plans, or plan id matches
                if ((empty($project['plan_id']))
                    || ($project['plan_id'] === $planid))
                {
                    // could be boolean, could be 0
                    if ($project['hide_client_id'] == 0)
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
            }
        }
        return false;
    }

    public function canSeeSelfService($projectId)
    {
        foreach($this->projects as $project)
        {
            if($project['project_id'] === $projectId)
            {
                if($project['self_service'])
                {
                    return true;
                }
            }
        }
        return false;
    }

	public function canSeeEeFileUpload($projectId)
	{
		foreach($this->projects as $project)
		{
			if($project['project_id'] === $projectId)
			{
				if($project['ee_file_upload'])
				{
					return true;
				}
			}
		}
		return false;
	}
	
	public function changeEmail($email)
	{
		$db = new Model();
		
		$update = $db->q("update clients set email = :email where id = :id",
			array(':email' => $email,
				  ':id'    => $this->id));

		return ($update ? true: false);
	}
	
	public function resetAccountPassword($email, $password)
	{
		//connect to database
		$db = new Model();
		
		$update = $db->q("update clients set password = :hash where id = :id",
			array(':hash' => $db->hashPassword($password),
				  ':id' => $this->id));

		return ($update ? true: false);
	}

	public function updateLoginTime()
	{
		$db = new Model();
		$update = $db->q("update clients set last_login = NOW() where id = :id",
			array(':id' => $this->id));
		return true;
	}
	
	//static methods
	public static function isLocked($email, $failures=5 /*attempts*/, $window=30 /*minutes*/)
	{	// Checks to see if this client is currently locked out due to $failures failures in the last $window minutes
		$rv=false;
		if($id=ClientModel::getClientID($email))
		{
			$db = new Model();
			// Clean out old entries (to keep the table from growing too big).
			$db->q("delete from log_aos_auth_failure WHERE system='' AND date < DATE_SUB(NOW(), INTERVAL 120 MINUTE)");
			// Check for failed logins
			if($result=$db->getVal("SELECT count(*) as c FROM log_aos_auth_failure WHERE user_id= :id AND system='' AND date > DATE_SUB(NOW(), INTERVAL :window MINUTE)",
				array(':id' 	=> $id,
					  ':window' => $window)))
			{
				if($result >= $failures)
					$rv=true; 
			}
		}
		return $rv;
	}

	public static function isIPLocked($ip, $failures=100, $window=5)
	{
		$isLocked = false;

        // ip block, cookie is used for pen testing to disable lockout
        if (!USE_IPBLOCK
            || isset($_COOKIE['speakEasy']))
        {
            return false;
        }

		$db = new Model();
		
		// Clean out old entries (to keep the table from growing too big).
		$db->q("delete from log_aos_ips WHERE date < DATE_SUB(NOW(), INTERVAL 120 MINUTE)");
		
		if($result = $db->getVal("select count(*) as count from log_aos_ips where ip = :ip and date > date_sub(now(), interval :window minute)",
				array(':ip' 	=> $ip,
					  ':window' => $window)))
		{
			if($result >= $failures)
			{
				$isLocked = true;
				sendMail(EMAIL_ERROR_TO, APP_TITLE." - IP Blocked Warning", "The IP address <strong>{$ip}</strong> was blocked. The address has been blocked for 5 mintues.");
			}
		}
		
		return $isLocked;
	}

	public static function authenticate($email, $password)
	{
		//connect to database
		$db = new Model();
		
		$select = $db->getRow("SELECT id, `password` FROM clients 
		        WHERE email = :email 
		            AND `password` = :hash",
			array(':email' 			 => $email,
				  ':hash'        => $db->hashPassword($password)));
;
		if( !empty($select)
            && isset($select['password']) )
		{
            $client = new ClientModel( $select['id'] );
            return $client;
		}
		else
		 {
            $id = ClientModel::getClientID($email);

            $db->q("INSERT INTO log_aos_auth_failure (`date`, `system`, `user_id`, `dbg`) SELECT NOW(), '', :id, ''",
                array(':id' => $id));

            return false;
		}
	}

    public static function verifyPassword($email, $password)
    {
        //connect to database
        $db = new Model();

        $select = $db->getVal("SELECT id FROM clients 
                WHERE email = :email 
                AND `password` = :hash",
			array(':email' 			 => $email,
				  ':hash'        => $db->hashPassword($password)));

		return ($select >= 1 ? true : false);
    }
	
	public static function resetpassword($key, $password)
	{
		$db = new Model();
		$id = $db->getVal("select client_id from client_keys where client_key = :key",
			array(':key' => $key));
		
		if($id)
		{
			$update = $db->q("UPDATE clients SET `password` = :hash where id = :id",
				array(':id' => $id,
					  ':hash' => $db->hashPassword($password)));
		
			if($update)
			{
				//remove key so it can not be used again
				$delete = $db->q("delete from client_keys where client_key = :key",
					array(':key' => $key));

				return ($delete ? true : false);
			}
		}
		return false;
	}
	
	public static function getClientID($email)
	{
		//connect to database
		$db = new Model();
		
		$select = $db->getVal("select id from clients where email = :email",
			array(':email' => $email));
		
		return($select ? $select : false);
	}

	public function getDocument($did)
	{
		$db = new Model($this->dbip, $this->dbname);
		
		$table = $db->getVal("select tbl from mailers where id = :did",
			array(':did' => $did));
		
		if($table)
		{
			$select = $db->getVal("select file_data from {$table} where id = :did",
				array(':did' => $did));

			if($select)
			{
				return true;
			}
		}
		return false;
	}
      
}