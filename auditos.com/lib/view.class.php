<?php

class View {
    public $render = true;
    public $header = true;
    public $footer = true;
	protected $controller, $action;
	protected $vars = array();
	protected $inbound = array(), $outbound = array();

	public function __construct($controller, $action)
	{
		$this->controller 	= $controller;
		$this->action		= $action;
	}

	public function set($name, $value)
	{
		$this->vars[$name] = $value;
	}

	public function render()
	{
		/*
		 * To bypass rendering set a variable on template called render to false
		 */
		if ($this->render)
		{
            extract($this->vars);

            //set uri variable to current page url
            if ($_SERVER['REQUEST_URI'] != '/page/notfound/'
                && ($this->controller != 'LoginController'
                || $this->controller != 'IndexController'))
            {
                $_SESSION['uri'] = $_SERVER['REQUEST_URI'];
            }

            //log access
            logAccess();

            // Universal template variables
            // these are overwritten in the DIR / headers.php file
            //grab user and project objects from session
            $user 		= isset($_SESSION['user']) ? $_SESSION['user'] : null;
            $project 	= isset($_SESSION['project']) ? $_SESSION['project'] : null;

            if ($this->header)
            {
                // include header and template variables for env
                include(VIEWS.'/headers.php');

                //include header template
                include(VIEWS.'/html-header.php');
            }

            //requested view
            if (file_exists(VIEWS.$this->controller.'/'.$this->action.'.php'))
            {
                include(VIEWS.$this->controller.'/'.$this->action.'.php');
            }

            if ($this->footer)
            {
                //include footer template
                if (!in_array($this->controller, array('login','index','general'))) {
                    include(VIEWS.'/app-footer.php');

                    // include debug at bottom
                    includeDebug();
                } else {
                    include(VIEWS."/page-footer.php");
                }

                // general html footer
                include(VIEWS.'/html-footer.php');
            }
        }

        recordBenchmark(true);

        //stop executing since everything loaded correctly
        systemExit();
	}

	public function change($view)
    {
        $this->action = $view;
    }

	public function processQuery($q)
	{
		if(isset($q[0]) == false && empty($q) == false)
		{
			return array($q);
		}
		else
		{
			return $q;
		}
	}
	
	public function translateInbound($code)
	{
		$type = false;
		switch($code)
		{
			case 'U':
				$type = 'Upload';
				break;
            case 'A':
                $type = 'Admin Upload';
                break;
			case 'M':
				$type = 'Mail';
				break;
			case 'F':
				$type = 'Fax';
				break;
		}
		
		return $type;
	}

    public function serveDoc($type, $content, $filename = 'report')
    {
        //log access
        logAccess();

        header("Pragma: public", true);
        header("Cache-Control: private");
        switch(strtolower($type))
        {
            case 'pdf':
                header("Content-type: application/pdf");
                break;
            case 'doc':
                header("Content-type: application/doc");
                break;
            case 'csv':
                header("Content-type: text/csv");
                break;
            case 'xls':
                header("Content-type: application/excel");
                break;
            case 'xlsx':
                header("Content-type: application/excel");
                break;
            default:
                header("Content-type: text/html");
        }
        header('Content-Disposition: attachment; filename="'.$filename.'.'.$type.'"');
        xEcho ($content,true);

        return true;
    }
}