<?php

abstract class Controller {

	private $controller, $template, $action;
	
	public function __construct($controller, $action)
	{
        // to expose to controllers what view is coming
        $this->action = $action;

		//log out after 10 minutes of inactivity
		if(isset($_SESSION['time']))
		{
			$session_life = time() - $_SESSION['time'];
			
			if($session_life > SESSION_TIMEOUT && $controller != 'LogoutController')
			{
                // login login bug fix
                if (isset($_SESSION['user']) && $_SESSION['user']->getAccountType() == 'CUSTOM')
                {
                    $url = chooseAppURL('/login/');
                }
                else
                {
                    $url = chooseAppURL();
                }

				# unset session variables
				unset($_SESSION['user']);
				unset($_SESSION['project']);
				unset($_SESSION['time']);
				unset($_SESSION['uri']);

				# set inactivity error and log it
				$_SESSION['error'] = ERR_INACTIVITY;
				ErrorModel::Log(ERR_INACTIVITY);

				# set session to empty array just to be sure
				$_SESSION = array();
				
				# redirect to specified location
				redirect($url);
			}
			else
			{
				$_SESSION['time'] = time();
			}
		}
		else
		{
            // don't reset if sitting on login screen
			if(preg_match("/^\/login/", $_SERVER['REQUEST_URI']) == 0)
			{
				$_SESSION['time'] = time();
			}
		}

		# check that someone is logged in
		# if not redirect back to base url
		if(!isset($_SESSION['user']) &&
            preg_match(NOT_SECURE, $_SERVER['REQUEST_URI']) == 0)
		{
			# build return url
            $url = chooseAppURL();

			# redirect
            redirect($url);
		}

		# check that someone is logged in
		# if not redirect back to base url
		if(!isset($_SESSION['user_active']) &&
			preg_match(NOT_SECURE, $_SERVER['REQUEST_URI']) == 0)
		{
            # build return url
            $url = chooseAppURL();

            # redirect
            redirect($url);
		}



        if( isset($_SESSION['user']) )
        {
            if ($_SESSION['user']->getAccountType() === 'EMPLOYEE')
            {
                if (isset($_SESSION['pass_required']) && !isset($_SESSION['pass_set']))
                {
                    if ($controller != 'LoginController'
                        && $controller != 'IndexController'
                        && $controller != 'ValidateController')
                    {
                        $url = PROTOCOL . MY_URL . '/login/password';
                        $_SESSION['error'] = 'You did you not enter in a password';
                        redirect($url);
                    }
                }
            }
        }

		$this->controller 	= strtolower(preg_replace("(Controller)", "", $controller));
		$this->template		= new View($this->controller, $action);
	}
    
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function action()
    {
        return $this->action;
    }

    public function parseArgs($args)
    {
        $a = array();

        foreach($args as $arg)
        {
            if(empty($arg) === false)
            {
                array_push($a, $arg);
            }
        }

        return $a;
    }

    function __destruct()
    {
        if (!isset($_SESSION['abandon']))
        {
            if (is_object($this->template))
            {
                $this->template->render();
            }
        }
    }
	
	public function set($name, $value)
	{
		$this->template->set($name, $value);
	}
    
    # initialize request
    protected function requestInit($args)
    {
        # sort so project ids are at the top
        sort($args);

        if( $this->checkProjectAccess($this->user, $args) === false )
        {
            //set new error session variable
            $_SESSION['error'] = ERR_NOACCESS_PROJECT_PLAN;
            ErrorModel::Log(ERR_NOACCESS_PROJECT_PLAN);
            redirect($_SESSION['uri']);
        }
    }

    protected function checkProjectAccess(ClientModel $user, array $projects)
    {
        # result of check
        # defaults to false
        $result = false;

        # $user_projects - the projects in the user model
        # $user_plans - the plans in the user model
        $user_projects  = array();
        $user_plans     = array();

        $dbProjects = $user->getProjects();

        if( isset($dbProjects['project_id']) === false )
        {
            foreach($user->getProjects() as $project)
            {
                array_push($user_projects, $project['project_id']);
            }
        }
        else
        {
            array_push($user_projects, $dbProjects['project_id']);
        }

        foreach($user->getPlans() as $plan)
        {
            if( isset( $plan['id'] ) )
            {
                array_push($user_plans, $plan['id']);
            }
        }

        # requested projects and plans
        $requested_projects = array();
        $requested_plans    = array();

        # build requested projects and plans arrays
        foreach($projects as $project)
        {
            # split on project_id:plan_id
            $ids = preg_split("/\:/", $project);

            # project and plan ids to use
            $project_id  = $ids[0];
            $plan_id     = isset($ids[1]) ? $ids[1] : null;

            # add project id to requested_projects array
            # only if project_id has not yet been added
            if( array_search($project_id, $requested_projects) === false )
            {
                array_push($requested_projects, $project_id);
            }

            # if plan_id is set add it to the requested_plan array
            if( isset($plan_id) )
            {
                array_push($requested_plans, $plan_id);
            }
        }

        # check projects
        foreach($requested_projects as $requested_project)
        {
            //$id = $user_project['project_id'];
            $id = $requested_project;

            if( in_array($id, $user_projects) )
            {
                $result = true;
            }
            else
            {
                return false;
            }
        }

        # check plans
        if( empty( $user_plans ) === false )
        {
            foreach($requested_plans as $requested_plan)
            {
                $id = $requested_plan;

                if( array_search($id, $user_plans) !== false )
                {
                    $result = true;
                }
                else
                {
                    return false;
                }
            }
        }

        return $result;
    }

    public function calcPercentage($a, $b)
	{
        if( $b !== 0 )
        {
            return number_format((($a / $b) * 100), 1, '.', '');
        }
	}
	
	public function sanitize_output($data)
	{
		return htmlentities($data);
	}
	
	public function sanitize_input($data)
	{
		/*
		$blacklist = "/(\<a\s+href\=.*?>|\<img\s+src\=.*\>|\<\/a\>|\<b\>|\<\/b\>|\<i\>|\<\/i\>|\<em\>|\<\/em\>|\<ul\>|\<\/ul\>|\<li\>|\<\/li\>|\<strong\>|\<\/strong\>|\<iframe\s+src\=.*\>(\<|[a-zA-Z0-9])+\/iframe\>|\<iframe|\<\/iframe\>|\<script\>|\<\/script\>|alert|javascript|onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup|class|style|target|)/i";
		
		if(preg_match($blacklist, $data) == 1)
		{
			$data = preg_replace($blacklist, '', $data);
		}
		*/
		if (is_array($data))
        {
            return false;
        }

		$data = strip_tags($data);
		
		$data = preg_replace('/(\<|\>)/', '', $data);

		return $data;
	}
	
	public function isChecked($value)
	{
		if($value == 'on')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function alertMalicious()
	{
		    //get user and project session variables
			$user		= (isset($_SESSION["user"])?$_SESSION["user"]:'');
			$project 	= (isset($_SESSION["project"])?$_SESSION["project"]:'');
			
			//record specific information about the activity
            $url		= $_SERVER['REQUEST_URI'];
            $ref		= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'n/a';
			$date		= date('m/d/Y g:i:s a', time());
			$post		= print_r($_POST, true);
            $server 	= print_r($_SERVER, true);

            //if just a bad document id, going to change direction
            if (substr($url,0,20) == '/documents/download/')
            {
                //compose email parts
                if (is_object($project))
                {
                    $to = $project->getPMEmail();
                    $to .= ',damien.burns@hms.com';
                }

                $subject = APP_TITLE.": Incorrectly linked document";
                $message = "
                    User "
                    .(is_object($user)?$user->getID():'')." - "
                    .(is_object($user)?$user->getName():'')
                    ." clicked a link to {$url} on {$date}.  It would
                    appear this document id is invalid please update the project and the
                    correct document id.  The bad link
                    appears on {$ref}.<br />";

				if (is_object($project))
                {
                    $message .= "
                        Project: ".$project->getProjectName()."<br />
                        Plan: ".$project->getPlanName()."<br />
                        PM: ".$project->getPMEmail()."<br />";
                }
            }
            else
            {
                //compose email parts
                $to = EMAIL_ERROR_TO;
                $subject = APP_TITLE.": Malicious Activity";
                $message = "
                    There was an attempt to access a url {$url} by user  "
                    .(is_object($user)?$user->getID():'')." - "
                    .(is_object($user)?$user->getName():'')
                    ." on {$date}.
                    <br />";

                if (is_object($project))
                {
                    $message .= "
                        Project: ".$project->getProjectName()."<br />
                        Plan: ".$project->getPlanName()."<br />
                        PM: ".$project->getPMEmail()."<br />";
                }

                $message .= "<br />
                    Server:<br /><pre>{$server}</pre><br />
                    Post:<br /><pre>{$post}</pre>";
            }


        //send email to IT team
        sendMail($to, $subject, $message);
	}

	public function randomString()
	{
		$string = "";
		$charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$length = mt_rand(5, 10);
		for($i = 0; $i < $length; $i++)
		{
			$string .= $charset[(mt_rand(0, (strlen($charset)-1)))];
		}
		return $string;
	}
}