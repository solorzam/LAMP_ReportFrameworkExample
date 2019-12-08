<?php

require_once("view.class.php");
require_once("controller.class.php");

class Router
{
	private $controller;
	private $action;
	private $arguments 	= array();
	
	public function __construct($uri)
	{
        $uriAccessToCheck = $uri;

        if( $uri == '/')
        {
                $url = $uri;
                $this->controller 	= 'IndexController';
                $this->action		= 'index';
        }
        else
        {
                $url = preg_split("/\//", $uri, 0, PREG_SPLIT_NO_EMPTY);
                $this->controller 	= isset($url[0]) ? ucwords($url[0]).'Controller' : null;
                $this->action		= isset($url[1]) ? $url[1] : 'index';
        }

        # build arguments array from url
        if( is_array($url) && count($url) > 2 )
        {
            $this->arguments = array_slice( $url, 2 );
        }

        # build arguments array with post data
        $this->arguments = array_merge( $this->arguments, $_POST );

        # Determine which session we are going to use
        # main login page uses my cookie
        if (($this->controller == 'IndexController')
            || DIR == 'my'
            || DIR == 'm')
        {
            define('SESSION_NAME', 'AOS-MY');
        } else {
            define('SESSION_NAME', 'AOS');
        }

        sessionManager::start();

        if (!sessionManager::check())
        {
//            session_destroy();
//            redirect('/login/reauth');
            sessionManager::regenerate();
        }

        // fix bug in framework
        if (isset($_SESSION['abandon']))
        {
            unset($_SESSION['abandon']);
        }

        # A user might be authenticated but not active in this case remove the user/project objects
        # this isn't the same as when a user jumps from one portal to the other
        preg_match("/\/(login)\/?$/", $_SERVER['REQUEST_URI'], $uri);
        if( isset($uri[1]) )
        {
            // user active is set, but false
            if(isset($_SESSION['user_active']) && !$_SESSION['user_active'])
            {
                unset($_SESSION['user']);
                unset($_SESSION['project']);
            }
        }

        # logged in user
        if(isset($_SESSION['user']))
        {
            $user = $_SESSION['user'];

            # prevent employers from jumping to employee portal for some urls
            if($user->getAccountType() == 'CUSTOM' && $_SERVER['HTTP_HOST'] == MY_URL)
            {
                if( $_SESSION['user_active'] && $this->controller != 'ValidateController' &&
                    $_SERVER['REQUEST_URI'] != '/favicon.ico' &&
                    $_SERVER['REQUEST_URI'] != '/page/notfound/')
                {
                    //set error for display back to user
                    $_SESSION['error'] = "That action is considered malicious. Your IP address and information has been recorded.";

                    //log attempted url
                    $attempted = MY_URL.$_SERVER['REQUEST_URI'];
                    ErrorModel::Log("Attempted Portal Jump: {$attempted}");

                    //send email alerting to malicious activity
                    $this->alertMalicious($attempted, APP_URL);

                    unset($_SESSION['user']);
                    unset($_SESSION['projects']);

                    $url = PROTOCOL.APP_URL.$_SESSION['uri'];
                    redirect($url,'301');
                }
            }

            $allowedURIs = array(
                '\/',
                '\/login\/.*',
                '\/includes\/.*',
                '\/demos\/.*',
                '\/static\/.*',
                '\/favicon\.ico',
                '\/page\/.*',
                '\/microsoft\/?(.*)'
            );

            # prevent employees from jumping into employer portal unless specifically allowed
            if( $user->getAccountType() == 'EMPLOYEE' && $_SERVER['HTTP_HOST'] == APP_URL )
            {
                $isAllowed = false;

                foreach($allowedURIs as $pattern)
                {
                    if( preg_match('/' . $pattern . '/', $uriAccessToCheck) == 1 )
                    {
                        $isAllowed = true;
                    }
                }

                if($_SESSION['user'] && $isAllowed === false)
                {
                    //set error for display back to user
                    $_SESSION['error'] = "That action is considered malicious. Your IP address and information has been recorded.";

                    //log attempted url
                    $attempted = PROTOCOL.APP_URL.$_SERVER['REQUEST_URI'];
                    ErrorModel::Log("Attempted Portal Jump: {$attempted}");

                    //send email alerting to malicious activity
                    $this->alertMalicious($attempted, MY_URL);

                    unset($_SESSION['user']);
                    unset($_SESSION['projects']);

                    $url =  PROTOCOL.APP_URL.$_SESSION['uri'];
                    redirect($url,'301');
                }
            }
        }

        # Continue loading
        $load = $this->getController();

        # No controller, 404
        if($load === false)
        {
            unset($_SESSION['error']);
            include(ROOT.'/public/404.php');
        }
	}
        
	public function getController()
	{

		if(class_exists($this->controller, true) && method_exists($this->controller, $this->action))
		{
            $class = new $this->controller($this->controller, $this->action);

            if(method_exists($class, 'init'))
            {
                $class->init();
            }

			call_user_func_array(array($class, $this->action), $this->arguments);

			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function alertMalicious($attempt, $from)
	{
        //record specific information about the activity
        $ip 		= $_SERVER['REMOTE_ADDR'];
        $method 	= $_SERVER['REQUEST_METHOD'];
        $port		= $_SERVER['REMOTE_PORT'];
        $url		= $_SERVER['REQUEST_URI'];
        $ref		= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'n/a';
        $date		= date('m/d/Y g:i:s a', time());
        $post		= print_r($_POST, true);
        $get		= print_r($_GET, true);
        $files		= print_r($_FILES, true);
        $server 	= print_r($_SERVER, true);

        $session 	= $_SESSION;

        //get user and project session variables
        if (is_object($_SESSION["user"])) {
            unset($session['user']);
            $user = $_SESSION["user"];
            $user_id = $user->getID();
        } else {
            $user_id = 'n/a';
        }

        if (is_object($_SESSION["project"])) {
            unset($session['project']);
            $project_id = $_SESSION["project"]->getProjectID();
            $plan_id    = $_SESSION["project"]->getPlanID();
        } else {
            $project_id = 'n/a';
            $plan_id = 'n/a';
        }

        $session 	= print_r($session,true);


        //compose email parts
        $to = EMAIL_ERROR_TO;
        $subject = APP_TITLE.": Attempted Portal Jump";
        $message = "
            There was an attempt to access a url: <strong>$attempt</strong> from: <strong>$from</strong> by user $user_id on {$date}.
            <br />
            <br />
            <strong>Attempt Details</strong>
            <br />
            Attempted Project: $project_id<br />
		    Attempted Plan: $plan_id<br /><br />
            User Details:<br />
            IP: {$ip}<br />
            Method: {$method}<br />
            Port: {$port}<br /><br />
            Requested URL: {$url}<br />
            Referrer: {$ref}<br /><br />
            Session:<br /><pre>{$session}</pre><br />
            Server:<br /><pre>{$server}</pre><br />
            Post:<br /><pre>{$post}</pre><br />
            Get:<br /><pre>{$get}</pre>
            Files:<br /><pre>{$files}</pre>";

        //send email to IT team
        sendMail($to, $subject, $message);
	}
}
