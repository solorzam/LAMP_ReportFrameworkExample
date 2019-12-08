<?php

// Required for query caching functions
$query_cache = array();
$redis = false;

function isdefined($define,$value)
{
    if (!defined($define))
    {
        define($define, $value);
    }
}

spl_autoload_register(function($classname){
    $classname = strtolower($classname);

    if (stristr($classname,'controller'))
    {
        $classname = str_replace('controller','',$classname);
        @include_once(CONTROLLERS.$classname.'.controller.php');
        return true;
    }

    if (stristr($classname, 'model') && $classname != 'model' &&  $classname != 'cachemodel')
    {
        $classname = str_replace('model','',$classname);
        @include_once(MODELS.$classname.'.model.php');
        return true;
    }

    @include_once(LIB.$classname.'.class.php');
    @include_once(CLASSES.$classname.'.class.php');
    @include_once(REP_CLASEsS.$classname.'.class.php');
});




function array_to_string(array $array, $sep)
{
    $str = "";
    
    foreach($array as $item)
    {
        if( count( $array ) > 1 )
        {
            $str .= $item.$sep;
        }
        else
        {
            $str = $item;
        }
    }
    
    return $str;
}

function _INPUT($var)
{
    if (isset($_POST[$var]))
    {
        return $_POST[$var];
    }
    else
    {
        return false;
    }
}
function _GET($var)
{
    if (isset($_GET[$var]))
    {
        return $_GET[$var];
    }
    else
    {
        return false;
    }
}


function prepForLog($extra='')
{
    // handle the post array
    if (!empty($_POST))
    {
        $post = $_POST;

        unset($post['password'],
             $post['password1'],
             $post['password2']);
    }

    // filter the server array
    if (!empty($_SERVER))
    {
        $server = array();

        foreach($_SERVER as $key=>$val)
        {
            $debug_variables = "REQUEST_METHOD,REDIRECT_QUERY_STRING,REDIRECT_URL,REQUEST_METHOD,QUERY_STRING,HTTP_USER_AGENT";
            if (stristr($debug_variables,$key))
            {
                $server[$key] =	$val;
            }
        }
    }

    $debug 	= "IP: ".print_r($_SERVER['REMOTE_ADDR'], true);
    $debug 	.= "\nUN: ".(isset($_SESSION['user']) ? $_SESSION['user']->getName() : 'NA');
    $debug 	.= "\nUID: ".(isset($_SESSION['user']) ? $_SESSION['user']->getID() : 'NA');
    $debug 	.= "\nType: ".(isset($_SESSION['user']) ? $_SESSION['user']->getType() : 'NA');
    $debug 	.= "\nURL: ".print_r($_SERVER['REQUEST_URI'], true);
    $debug 	.= "\nREF: ".(!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    $debug 	.= isset($_SESSION['pop']) ? "\n\nPop: ".print_r($_SESSION['pop'], true) : '';
    $debug 	.= !empty($extra) ? "\n\nExtra Information: ".$extra : '';

    if (ACCESS_DEBUGGING)
    {
        //$debug 	.= !empty($_SESSION) ? "\n\nSess: ".serialize($_SESSION) : '';
        $debug 	.= !empty($server) ? "\n\nSERVER: ".print_r($server, true) : '';
        $debug 	.= !empty($post) ? "\n\nPOST: ".print_r($post, true) : '';
        $debug 	.= !empty($_GET) ? "\n\nGet: ".print_r($_GET, true) : '';
    }

    return $debug;
}

function writeLog($table, $message, $extraDetails = NULL, $userIdOverride = NULL)
{
    $db = new Model();

    $debug = prepForLog();
    $debug = $debug . $extraDetails;

    if (!is_null($userIdOverride)) {
        $user_id = $userIdOverride;
    } elseif (isset($_SESSION['user'])
        && is_object($_SESSION['user'])) {
        $user_id = $_SESSION['user']->getID();
    } else {
        $user_id = 0;
    }

    $db->q("insert into {$table} (date, user_id, msg, dbg) values (now(), :user_id, :message, :debug)",
        array(':user_id' => $user_id, ':message' => $message, ':debug' => $debug));
}

function logAccess()
{
    if((preg_match(NOT_LOGGED, $_SERVER['REQUEST_URI']) == 0 )
        || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'))
    {
		$message = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        writeLog('log_aos_access',$message);
	}
}
function logAuthenticated()
{
    $message = $_SERVER['HTTP_HOST'].'/authenticated';
    writeLog('log_aos_access',$message);
}


function logAction($message, $extraDetails = NULL, $userIdOverride = NULL)
{
    writeLog('log_aos_access', $message, $extraDetails, $userIdOverride);
}

function logSelfService($message, $error = false, $extraDetails = NULL, $userIdOverride = NULL)
{
    writeLog('log_aos_selfservice', $message, $extraDetails, $userIdOverride);
    if($error)
    {
        ErrorModel::Log($message);
    }
}

function logInBound($uid = NULL, $eid = NULL, $pid = NULL, $action = NULL)
{
	//connect to central database
    $db 	= new Model();

	$uid = isset($uid) ? $uid : 0;
	$eid = isset($eid) ? $eid : 0;
	$pid = isset($pid) ? $pid : 0;
	
	$db->q("insert into log_inbound (date, user_id, employee_id, project_id, action) 
              values(now(), :uid, :eid, :pid, :action)",
        array(':uid' => $uid, ':eid' => $eid,
                ':pid' =>$pid, ':action' => $action));
}

function logInBoundAuditDB($ip, $db, $userid, $type, $msg)
{
	//connect to central database
	$db = new Model($ip, $db);
	$db->q("insert into `log` (date, user_id, employee_id, log_type, msg) 
              values (now(), '0', :user_id, :type, :msg)",
        array(':user_id' => $userid, ':type' => $type, ':msg' => $msg));
}

function logDebug($msg)
{
	//connect to central database
    $db 	= new Model();
	$db->q("insert into log_debug (date, msg) values(now(), :msg)",
            array(':msg' => $msg));
}

function logError($type, $error)
{
	//connect to central database
    $db 	= new Model();
	
	//user information to capture
	$userid	= isset($_SESSION['user']) ? $_SESSION['user']->getID() : 0;
	$page	= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	
	//server information to capture
	$ip 	= $_SERVER['REMOTE_ADDR'];
	$method = $_SERVER['REQUEST_METHOD'];
	$port	= $_SERVER['REMOTE_PORT'];
	
	isset($_POST['password']) ? $_POST['password'] = '' : NULL;
	
	//create log message
	$message 	= "ERROR:\n".$type."\n\nUSER MESSAGE:\n".$error;
	$debug		= "IP: ".$ip."\nMethod: ".$method."\nPort: ".$port."\n\nURI:\n".$page;
	//$debug      .= "\n\nSESSION: \n".print_r($_SESSION, true);

    if (!empty($_POST))
    {
        $debug .= "\n\nPOST:\n".print_r($_POST, true);
    }

    if (!empty($_GET))
    {
        $debug .= "\n\nGET:\n".print_r($_GET, true);
    }

    if (!empty($_FILES))
    {
        $debug .= "\n\nFiles:\n".print_r($_FILES, true);
    }

	//insert into log table
	$db->q("insert into log_aos_error (user_id, date, msg, dbg) values (:user_id, (NOW()), :message, :debug)",
        array(':user_id' => $userid, ':message' => $message, ':debug' => $debug ));
}

function logIP()
{
	//connect to central database
    $db = new Model();
	$db->q("insert into log_aos_ips (date, ip) values (now(), :addr);", array(':addr' => $_SERVER['REMOTE_ADDR']));
}

function logToDisk($message, $level=1)
{
    if (EVENT_DEBUGGING_LEVEL)
    {
        if ($level <= EVENT_DEBUGGING_LEVEL)
        {
            $header = date('[Y-m-d h:i:s] ').$_SERVER['REMOTE_ADDR'].' :: '.$_SERVER['REQUEST_URI']." :: " . (memory_get_peak_usage(true)/1024/1024) ;
            file_put_contents(ROOT.'/tmp/debugging.log', $header.$message."\r\n", FILE_APPEND);
        }
    }
}

function recordBenchmark($and_clear = false)
{
    if (BENCHMARKING_LVL && preg_match(NOT_LOGGED, $_SERVER['REQUEST_URI']) == 0 )
    {
        $db = new Model();

        $user_id    = isset($_SESSION['user']) ? $_SESSION['user']->getID() : 0;
        $memory     = (memory_get_usage()/1024);
        $load_time  = round((microtime(true) - PAGE_START_TIME), 4);
        $method     = substr($_SERVER['REQUEST_METHOD'],0,1);
        $uri        = $_SERVER['REQUEST_URI'];
        $queries    = '';
        $request    = '';

        if (preg_match(BENCHMARK_QUERY_CUTOFF_EXCEPTIONS,$uri)) {
            $query_cutoff = 5;
        } else {
            $query_cutoff = BENCHMARKING_QUERY_CUTOFF;
        }

        if ($load_time >= $query_cutoff) {
            if (BENCHMARKING_LVL >= 4 && isset($_SESSION['queries']))
            {
                $queries =  implode("\n",array_values($_SESSION['queries']));
            }
            
            $db->q("insert into benchmark_aos (`date`, `user`, `memory`, `load`, `method`, `uri`, `queries`, `request`) 
                    values (NOW(), :user_id, :memory, :load_time, :method, :uri, :queries, :request)", 
                array(':user_id' => $user_id,':memory' => $memory,':load_time' => $load_time,
                      ':method' => $method,':uri' => $uri,':queries' => $queries,':request' => $request));
        } else {

            $db->q("insert into benchmark_aos (`date`, `user`, `memory`, `load`, `method`, `uri`, `queries`, `request`) 
                    values (NOW(), :user_id, :memory, :load_time, :method, :uri, :queries, :request)",
                array(':user_id' => $user_id,':memory' => $memory,':load_time' => $load_time,
                    ':method' => $method,':uri' => $uri,':queries' => '',':request' => $request));            
        }

        if ($and_clear)
        {
            $_SESSION['queries'] = array();
        }
    }
}

function showError($error)
{
	print("<div class=\"error\">{$error->getMessage()}</div>");
}

function includeDebug()
{
    if (QUERY_DEBUGGING
        && isset($_SESSION['queries'])
        && !stristr($_SERVER['HTTP_USER_AGENT'], 'SortSite'))
        {

        // bench marking
        $total_time = round((microtime(true) - PAGE_START_TIME), 4);

        $cache_calls = count(preg_grep('/\[CACHE\]/', $_SESSION['queries']));
        $db_calls = count($_SESSION['queries']) - $cache_calls;

        ?>
        <div id="debugs" style="padding: 30px; display: none;">
            <p>
                Memory: <?php echo number_format((memory_get_usage()/1024),0); ?>KB |
                Load Time: <?php echo $total_time; ?> |
                Database calls: <?php echo $db_calls; ?> |
                Cache Calls:  <?php echo $cache_calls; ?>
            </p>
            <hr/>
        <?php

        if (isset($_SESSION['pop'])) {
            print_array($_SESSION['pop']);
            echo "<hr/>";
        }

        foreach($_SESSION['queries'] as $query)
        {
            echo '<p class="hanging">'.$query.';</p>';
        }


        // clear only if not going to use in benchmarking
        if (BENCHMARKING_LVL != 4)
        {
            unset($_SESSION['queries']);
        }

        ?>
        </div>
        <script type="text/javascript">
            <!--
                $(function() {
                    $('#AuditOS_logo, #VerifyOS_logo').click(function(){
                        $('#debugs').toggle();
                    });
                });
            // -->
        </script>
    <?php
    }
}

function isPDF(&$content)
{
    return (substr($content,0,4) == "%PDF");
}

/*
 * Replace header lines with proper redirect
 */
function redirect($url,$code='302')
{
    // record benchmarking
    recordBenchmark(true);

    if (isset($_SESSION))
    {
        $_SESSION['abandon'] = true;
    }

    session_write_close();
    header("Location: {$url}", true, $code);
    systemExit();
}

function redirectPrevious()
{
    $url = preg_replace("/^\//", "", $_SESSION['uri']);
    redirect('/'.$url);
}



/*
 * Choose base URL
 */
function chooseAppURL($controller = '')
{
    if (DIR === 'm') {
        return PROTOCOL.MOBILE_URL.$controller;
    } else {
        return PROTOCOL.APP_URL.$controller;
    }
}


/*
 * This replaces exit so we can properly run end of script hooks
 */
function systemExit()
{
    die();
    //throw new SystemExit();
}

/*
 * Remote script execution
 */
function remote_call($path)
{
    logToDisk('Remote call taking place to '.$path);
    $result = file_get_contents(urlSanitize($path), FALSE,
        stream_context_create(array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        )));
    logToDisk('Remote call complete with result: '.$result);
    return $result;
}

function sendMailTools($to, $subject, $text, $opt = false)
{
    return sendMail($to, $subject, $text, $opt, false);
}


// simple outbound mail sender
// note: $to can be a comma-delimited list of email addresses
function sendMail($to, $subject, $text, $opt = false, $dmz = true)
{
	//load required library files
	require_once(LIB."phpmailer/class.phpmailer.php");
	require_once(LIB."phpmailer/phpmailer.lang-en.php");

	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->Host         = EMAIL_IP;
	$mail->SMTPAuth     = true;
	$mail->Username     = EMAIL_USER;
	$mail->From         = EMAIL_FROM;
    $mail->FromName     = EMAIL_FROM;
	$mail->Password     = EMAIL_PASS;
	$mail->ContentType  = "text/html";
    $mail->CharSet      = "UTF-8";

	foreach(preg_split("/\,\s?/", $to) as $mailTo)
	{
		$mail->AddAddress($mailTo);
	}

	if(isset($opt['from']))
	{
		$mail->From = $opt['from'];
	}

	if(isset($opt['cc']))
	{
		foreach(preg_split("/\,\s?/",$opt['cc']) as $mailTo)
		{
			$mail->AddCC($mailTo);
		}
	}

	if(isset($opt['bcc']))
	{
		foreach(preg_split("/\,\s?/",$opt['bcc']) as $mailTo)
		{
			$mail->AddBCC($mailTo);
		}
	}

	if(isset($opt['reply-to']))
	{
		$mail->AddReplyTo($opt['reply-to']);
	}

	if(isset($opt['from-name']))
	{
		$mail->FromName = $opt['from-name'];
	}

	$mail->Subject = $subject;
	$mail->Body = $text;
	$rv=$mail->Send();

	if(!$rv)
	{
      logError("Email send failure ", "");
	}

	return $rv;
}

function validateGoogleCaptcha()
{
    if (isset($_POST['g-recaptcha-response']))
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, GOOGLE_RECAPTCHA_URL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array(
                'secret'      => GOOGLE_RECAPTCHA_SECRET,
                'response'     => $_POST['g-recaptcha-response'],
                'remoteip'     => $_SERVER['REMOTE_ADDR']));
        $result = curl_exec($curl);
        curl_close($curl);

        if ($result)
        {
            $result = json_decode($result, true);
            if ($result['success'] == 'true')
            {
                return true;
            }
        }
    }

    return false;
}

/*********************************/
/*        Form Helpers           */
/*********************************/
function monthOptions($selected=false, $short=false)
{   // returns a set of option tags for months, with $selected selected if applicable
    $rv="";
    if(!$selected) {
        $selected="Month";
    }

    if ($short)
    {
        $months = array("Month"=>"Month","01"=>"Jan","02"=>"Feb","03"=>"Mar","04"=>"Apr","05"=>"May",
                      "06"=>"Jun","07"=>"Jul","08"=>"Aug","09"=>"Sept","10"=>"Oct","11"=>"Nov","12"=>"Dec");
    } else {
        $months = array("Month"=>"Month","01"=>"January","02"=>"February","03"=>"March","04"=>"April",
                      "05"=>"May","06"=>"June","07"=>"July","08"=>"August","09"=>"September",
                      "10"=>"October","11"=>"November","12"=>"December");
    }

    foreach($months as $number=>$name)
    {
        $rv.="<option value=\"{$number}\"".($selected==$number ? " selected=\"selected\"" : "").">".text($name)."</option>";
    }

    return $rv;
}
function dayOptions($selected=false)
{   // returns a string of option tags for days (1-31), with $selected selected if applicable
    $rv="";
    if(!$selected)
    {
        $selected="Day";
    }

    $days = array('Day' => text('Day'), '01'=>1,'02'=>2,'03'=>3,'04'=>4,'05'=>5,'06'=>6,'07'=>7,
                  '08'=>8,'09'=>9,'10'=>10,'11'=>11,'12'=>12,'13'=>13,'14'=>14,'15'=>15,
                  '16'=>16,'17'=>17,'18'=>18,'19'=>19,'20'=>20,'21'=>21,'22'=>22,'23'=>23,
                  '24'=>24,'25'=>25,'26'=>26,'27'=>27,'28'=>28,'29'=>29,'30'=>30,'31'=>31);
    foreach($days as $number=>$name)
    {
        $rv.="<option value=\"{$number}\"".($selected==$number ? " selected=\"selected\"" : "").">".$name."</option>";
    }

    return $rv;
}
function yearOptions($selected=false)
{   // returns a string of option tags for years (1900-present), with $selected selected if applicable
    $rv="";
    $years = array("Year" => text("Year"));
    $thisYear = 1*(date("Y"));

    if(!$selected)
    {
        $selected="Year";
    }

    for($i=$thisYear; $i>=1900; $i--)
    {
        $years[$i]=$i;
    }

    foreach($years as $number=>$name)
    {
        $rv.="<option value=\"{$number}\"".($selected==$number ? " selected=\"selected\"" : "").">".$name."</option>";
    }

    return $rv;
}


function loadAnalytics()
{
    if ($_SERVER['PHP_ENV'] == 'prod')
	{
		print("
        <script type=\"text/javascript\">
            <!--
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-17468164-1']);
            _gaq.push(['_setDomainName', '.".APP_URL."']);
            _gaq.push(['_trackPageview']);

            window.onload = function ()
            {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            }
            //-->
		</script>
		");
	}
}

function print_array($array)
{
    print("<pre>");
    print_r($array);
    print("</pre>");
}

function auto_version($file)
{
  if(strpos($file, '/') !== 0 || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file))
    return $file;

  $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
  return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $file);
}

function include_javascript($file)
{
    if (USE_MIN_FILES)
    {
        return '<script src="'.auto_version($file.'.min.js').'" type="text/javascript"></script>';
    }
    else
    {
        return '<script src="'.auto_version($file.'.js').'" type="text/javascript"></script>';
    }
}

function getDocCode($document)
{
    $docCodes = array(
                "Amnesty Letter"                => "Amnesty Letter",
                "Amnesty Term Confirmation"     => "Amnesty Term Confirmation",
                "Notification Letter"           => "Notification Letter",
                "Notification Letter 1"         => "Notification Letter 1",
                "Notification Letter 2"         => "Notification Letter 2",
                "Notification Letter 3"         => "Notification Letter 3",
                "Verification Letter"           => "Verification Letter",
                "Verification Letter 1"         => "Verification Letter 1",
                "Verification Letter 2"         => "Verification Letter 2",
                "Verification Letter 3"         => "Verification Letter 3",
                "Verification Letter 4"         => "Verification Letter 4",
                "Partial"                       => "Partial Notice",
                "Partial Response Final Letter" => "Partial Response Final Notice",
                "Term Confirmation"             => "Term Confirmation",
                "No Response Final Letter"      => "No Response Final Notice",
                "Final Notice"                  => "Final Notice",
                "Postcard"                      => "Postcard"
        );
    if (isset($docCodes[$document]))
    {
        return $docCodes[$document];
    }
    else
    {
        return $document;
    }
}

function setPopDefaults($project_id=false, $plan_id=false, $project=false)
{
    # set for when projects are passed in
    if ($project_id || is_object($project))
    {
        # Get project object exists
        if ($project_id)
        {
            $project = new ProjectModel($project_id, $plan_id);
        }

        if ($project && is_object($project))
        {
            $projectname    = $project->getProjectName();
            $planname       = $project->getPlanName();
            $projectstatus  = $project->getStatus();
            $projectpoe     = $project->isPoe();            
        }
        else
        {
            $projectname    = '';
            $planname       = '';
            $projectstatus  = '';
            $projectpoe     = false;
        }        
    }


    # are we dealing with initial defaults?
    if (!isset($_SESSION['pop']))
    {
        $_SESSION['pop']                        = array();
        $_SESSION['pop']['snapshot_desc']       = '';

        if (!empty($projectname))
        {            
            # Set the default project type
            $_SESSION['pop']['selected_name'] = $projectname . (empty($planname) === false ? ' / ' . $planname : '');
            $_SESSION['pop']['selected_type'] = ( $projectpoe ? 'Poe' : 'Custom' );
            $_SESSION['pop']['population_filter']   = $projectstatus;
        }
        else
        {            
            $_SESSION['pop']['selected_name'] = '';
            $_SESSION['pop']['selected_type'] = '';
            $_SESSION['pop']['population_filter']   = 'Active';
        }
    }
    elseif (isset($projectstatus))
    {
        $_SESSION['pop']['snapshot_desc']       = '';
        $_SESSION['pop']['population_filter']   = $projectstatus;
    }

    setPopStartDate();
    setPopEndDate();
    setPopPopulationFilter();
    setPopSelectedType(); 
}

function setPopStartDate()
{
    # start date and validation
    if (isset($_POST['start_date']))
    {
        $start_date = (int) strtotime($_POST['start_date']);
        if ($start_date > 1262304000) // 1/1/2010
        {
            $_SESSION['pop']['start_date'] = date('Y-m-d',$start_date);
        }
        else
        {
            $_SESSION['pop']['start_date'] = date('Y-m-01');
        }
    }
    elseif (!isset($_SESSION['pop']['start_date']))
    {
        $_SESSION['pop']['start_date'] = date('Y-m-01');
    }
}
function setPopEndDate()
{
    # end date and validation
    if (isset($_POST['end_date']))
    {
        $end_date = (int) strtotime($_POST['end_date']);
        if ($end_date < 1577836800) // 1/1/2020
        {
            $_SESSION['pop']['end_date'] = date('Y-m-d',$end_date);
        }
        else
        {
            $_SESSION['pop']['end_date'] = date('Y-m-t');
        }
    }
    elseif (!isset($_SESSION['pop']['end_date']))
    {
        $_SESSION['pop']['end_date'] = date('Y-m-t');
    }
}
function setPopPopulationFilter()
{
    # Population filter types
    if (isset($_POST['population_filter']))
    {
        $_SESSION['pop']['population_filter'] = $_POST['population_filter'];
    }
    elseif (!isset($_SESSION['pop']['population_filter']))
    {
        $_SESSION['pop']['population_filter'] = 'Active';
    }

    # verification and handling
    switch($_SESSION['pop']['population_filter'])
    {
        case 'Active':
            $_SESSION['pop']['snapshot_desc']       = ' for employees that are active';
            break;

        case 'Inactive':
            $_SESSION['pop']['snapshot_desc']       = ' for employees that are inactive';
            break;

        case 'Finished between':
            $_SESSION['pop']['snapshot_desc']       = ' for employees that finished between '.date('m/d/Y',strtotime($_SESSION['pop']['start_date'])).' and '.date('m/d/Y',strtotime($_SESSION['pop']['end_date']));
            break;

        case 'Started between':
            $_SESSION['pop']['snapshot_desc']       = ' for employees that started between '.date('m/d/Y',strtotime($_SESSION['pop']['start_date'])).' and '.date('m/d/Y',strtotime($_SESSION['pop']['end_date']));
            break;

        case 'Everyone':
        default:            
            $_SESSION['pop']['snapshot_desc']       = ' for all employees';
            $_SESSION['pop']['population_filter']   = 'Everyone';
            break;
    }
}
function setPopSelectedType()
{
    if (!isset($_SESSION['requests']))
    {
        return false;
    }

    # loop over projects at set some details
    # grouper? poe? mix?
    $project_keys         = '';
    $project_descriptions = array( 'C'=>'Custom', 'P'=>'Poe' );

    # loop over projects
    foreach($_SESSION['requests'] as $request)
    {
        # split on project_id:plan_id
        $ids = preg_split("/\:/", $request);

        # project and plan ids to use
        $project_id  = $ids[0];
        $plan_id     = isset($ids[1]) ? $ids[1] : NULL;

        if (in_array($project_id, $_SESSION['user']->getPoeProjects()))
        {
            $project_keys .= 'P';
        }
        else
        {
            $project_keys .= 'C';
        }

//            $project = new ProjectModel($project_id, $plan_id);
//            $project_keys .= ( $project->isPoe() ? 'P' : 'C' );
    }

    # single project
    if (strlen($project_keys) == 1)
    {
        $_SESSION['pop']['selected_type'] = $project_descriptions[$project_keys];
    }
    else
    {
        # multi project
        if (str_replace('C', '', $project_keys) == '')
        {
            $_SESSION['pop']['selected_type'] = 'Customs';
        }
        elseif (str_replace('P', '', $project_keys) == '')
        {
            $_SESSION['pop']['selected_type'] = 'Poes';
        }
        else
        {
            $_SESSION['pop']['selected_type'] = 'Mixed';
        }
    }
}


/**
 * Replace relative and absolute links to redirect controller.  Return text to echo.
 *
 * @param $text
 *
 * @return string
 */
function makeRedirectLinks($text)
{
    preg_match_all('/href=([\'"])(.*?)[\'"]/', $text, $matches);

    if ($matches)
    {
        $replacements = array();
        foreach ($matches[0] as $key=>$match)
        {
            if (!in_array($match,$replacements))
            {
                $quote       = $matches[1][$key];
                $replacement = $matches[2][$key];

                # Prevent checking this item again
                $replacements[] = $match;

                # Replace /FAQs with /redirect/url/FAQs
                if (substr($replacement,0,1) == '/' &&  !stristr($replacement,"/document"))
                {
                    $text = str_replace($match,'href='.$quote.'/redirect/url'.
                        $replacement.$quote, $text);
                }

                # Replace out the https://my.auditos.com part
                elseif (stristr($match,PROTOCOL.MY_URL))
                {
                    $text = str_replace($match,'href='.$quote.'/redirect/url'.
                        str_replace(PROTOCOL.MY_URL,'',$replacement).$quote, $text);
                }
            }
        }
    }
    return $text;
}

/**
 * Take database results return array if false, else make consistent as multi-dimensional array.
 * @param $db_results
 *
 * @return array
 */
function fix_array_results($db_results)
{
    if ($db_results)
    {
        if (!isset($db_results[0])) {
            $db_results = array(0=>$db_results);
        }
        return $db_results;
    }
    else
    {
        return array();
    }
}


/**
 * Return list of termination reasons for drop downs
 *
 * @return array
 */
function getTermReasons()
{
    $terms = array();

    $db = new Model();
    $results = $db->getRows("SELECT override, label FROM term_type_overrides where available_in_portal='Y';");

    foreach($results as $row)
    {
        $terms[$row['override']] = $row['label'];
    }

    return $terms;

//    return array(
//        'aged_out_of_plan' => 'Aged Out of Plan',
//        'recently_deceased' => 'Recently Deceased',
//        'recently_divorced' => 'Recently Divorced',
//        'ineligible' => 'Ineligible',
//        'recently_married' => 'Recently Married',
//        'active_military' => 'Active Military',
//        'other_coverage' => 'Other Coverage',
//    );
}


/**
 * Return friendly name of termination if found
 *
 * @param $list booleen - return array of available terms
 *
 * @return string
 */
function getTermReason($code)
{
    $reasons = getTermReasons();

    if (isset($reasons[$code]))
    {
        return $reasons[$code];
    }
    else
    {
        return $code;
    }
}

/**
 * Return HTML tag or what SSN will show
 *
 * @param $ssn - entered from page
 * @param $cssn - selected from database
 *
 * @return string
 */
function getSsnDisplay($ssn, $cssn)
{
    if($ssn == '')
    {
        if( strtolower($cssn) == 'n/a' )
        {
            return "N/A";
        }
        else if( $cssn != '' )
        {
            return "On File";
        }
        else
        {
            return"&nbsp;";
        }
    }
    else if( $ssn != '' )
    {
        return "Submitted";
    }
}

/**
 * Find target in set, return translation
 *
 * @param $set - array of keys
 * @param $target - key to match
 *
 * @return string
 */
function translateRelationship($set, $target)
{
    $translation = $target;
    foreach($set as $key => $rel)
    {
        if($key == $target) {
            $translation = $rel;
        }
    }

    return $translation;
}

/**
 * Format phone number for views
 * @param $num string in
 *
 * @return string out
 */
function formatPhoneNumber($num)
{
    $test = preg_replace('/[^0-9]/', '', $num);

    $len = strlen($test);

    if($len == 7)
    {
        $num = preg_replace('/([0-9]{3})([0-9]{4})/', '$1-$2', $num);
    }
    else if($len == 10)
    {
        $num = preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', '($1) $2-$3', $num);
    }
    else if($len == 11)
    {
        $num = preg_replace('/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/', '$1-$2-$3-$4', $num);
    }

    return $num;
}

/**
 * Return the description for that client term type
 *
 * @param $status string
 *
 * @return bool|string
 */

// updated some of the sms descriptions per the email from Rebecca
function getDisplayStatusDesc($status, $type='desc')
{
    $descriptions = array(
        'Complete With Termination'          => array(
            'desc' => 'No further action is needed. You voluntarily removed a dependent due to ineligibility.  Any remaining individuals have been verified.',
            'sms'  => 'You voluntarily removed an individual due to ineligibility. Any remaining individuals have been verified.'),

        'Partial Response'                   => array(
            'desc' => 'Some information has been received, however, additional documentation is required.',
            'sms'  => 'Some information has been received, however, additional documentation is required'),

        'Partial Response - Pending Review'  => array(
            'desc' => 'Some information has been received, however, additional documentation may be required. A status of &quot;Pending Review&quot; indicates that additional documentation has been received and is currently under review. Your status will be updated upon review of the documentation.',
            'sms'  => 'Some information has been received, however, additional documentation may be required.'),

        'Response Received - Pending Review' => array(
            'desc' => 'Documentation has been received and is currently under review. Your status will be updated upon review of the documentation.',
            'sms'  => 'Some information has been received, however, additional documentation may be required.'),

        'Complete'                           => array(
            'desc' => 'Your information was received and processed. No further action is necessary.',
            'sms'  => 'Your documentation was received and processed. No further action is needed at this time.'),

        'No Response'                        => array(
            'desc' => 'The support center has not received any documentation.',
            'sms'  => 'No documentation has been received at this time.'),

        'Amnesty Response'                   => array(
            'desc' => 'A response was received to the Amnesty Phase letter.',
            'sms'  => 'A response was received to the Amnesty Phase communication.'),

        'No Response Termination'            => array(
            'desc' => 'We did not receive a response by the final deadline of the verification program. Please note that additional consequences may apply.',
            'sms'  => 'No Response Termination'),

        'Insufficient Doc Termination'       => array(
            'desc' => 'We did not receive a complete response by the final deadline of the verification program. Please note that additional consequences may apply.',
            'sms'  => 'Insufficient Response Termination'),

        'Undefined'                          => array(
            'desc' => 'Please contact the support center to discuss your status.',
            'sms'  => 'Please contact the Verification Center to discuss your status.'),

        'No Amnesty Response'                => array(
            'desc' => 'No response was received to the Amnesty Phase letter.',
            'sms'  => 'No response was received to the Amnesty Phase communication.'),

        'Partial Documents'                  => array(
            'desc' => 'Some documentation has been received, however additional information is needed.',
            'sms'  => 'Some information has been received, however, additional documentation may be required.'),

        'Verified'                           => array(
            'desc' => 'All documentation has been received. No further action is needed.',
            'sms'  => 'Verified - No further action is needed.'),

        'Amnesty Termination'                => array(
            'desc' => 'You indicated that this individual did not meet the eligibility guidelines and should be removed from the plan(s) during the Amnesty Phase.',
            'sms'  => 'Amnesty Termination - No further action is needed.'),


        // dependents level only, sms not an option

        'Client Update'                      => array(
            'desc' => 'You and/or your dependent(s) have been removed from the verification review by the client/employer. No further action is needed.',
            'sms'  => ''),

        'Voluntary Termination'              => array(
            'desc' => 'You indicated that this individual did not meet the eligibility guidelines and should be removed from plan during the Verification Phase.',
            'sms'  => ''),

        'Voluntary Termination (Dental Only)'              => array(
            'desc' => 'As indicated, the individual will be removed from dental coverage only.',
            'sms'  => ''),

        'No Documents'                       => array(
            'desc' => 'No documents have been processed. If you have submitted documentation to verify eligibility, please allow 3-5 business days for processing. You will be notified when status updates have been made. You may log in at any time to monitor your account.',
            'sms'  => ''),

        'Pending Review'                     => array(
            'desc' => 'A status of &quot;Pending Review&quot; indicates that additional documentation has been received and is currently under review. Additional documentation may still be required.',
            'sms'  => ''),

        'Involuntary Termination'            => array(
            'desc' => 'The documents received indicate this individual does not meet the eligibility guidelines.',
            'sms'  => ''),

        'Verified - Pending Reinstatement' => array(
            'desc' => 'A status of &quot;Pending Reinstatement&quot; indicates that reinstatement is being considered by the client/employer.',
            'sms'  => ''),

        'Not Terminated'                     => array(
            'desc' => 'You responded to the Amnesty Phase letter and indicated this individual should not be removed. No termination was processed.',
            'sms'  => '')
    );

    if (isset($descriptions[$status]))
    {
        if ($type == 'desc')
        {
            return text($descriptions[$status]['desc']);
        }
        else if ($type == 'sms')
        {
            return $descriptions[$status]['sms'];
        }
    }

    return false;
}

function text($text, $parse = false, $translate = true)
{
    global $redis;
    $translated = false;

    # don't process empty
    if (empty($text))
    {
        return false;
    }

    # Translation needed
    if ($translate
        && isset($_SESSION['lang'])
        && $_SESSION['lang'] == 'ES')
    {
        # capture dates, handle internally
        if (strlen($text) == 10)
        {
            if (preg_match("/(\d{2})-(\d{2})-(\d{4})/", $text, $results))
            {
                return date("d-m-Y", strtotime($text));
            }
            elseif (preg_match("/(\d{4})-(\d{2})-(\d{2})/", $text, $results))
            {
                return date("d-m-Y", strtotime($text));
            }
            elseif (preg_match("/(\d{2})\/(\d{2})\/(\d{4})/", $text, $results))
            {
                return date("d/m/Y", strtotime($text));
            }
            elseif (preg_match("/(\d{4})\/(\d{2})\/(\d{2})/", $text, $results))
            {
                return date("d/m/Y", strtotime($text));
            }
        }

        # strip returns and tabs
        $text = str_replace(array("\n","\r","\t"),'', $text);

        # prepare for translation
        $hash             = hash('sha256', $text);
        $query_start_time = microtime(true);

        # connect to redis to check cache
        # if we failed before don't try again
        if (!is_object($redis))
        {
            $redis = connectToRedis();
        }

        if ($redis)
        {
            $translated = $redis->get('trans:'.$hash);

            # not found in redis cache
            # do translate lookup to internal service
            if (!$translated)
            {
                $translated = getTranslation($hash, $text);

                if ($translated)
                {
                    # if redis is available cache locally
                    if (!isset($_SESSION['no_redis']))
                    {
                        if ($translated->CACHE)
                        {
                            $debug_msg = 'CACHE TRANS ADD: ';
                        }
                        else
                        {
                            $debug_msg = 'CACHE LOCAL ADD: ';
                        }

                        $redis->set('trans:'.$hash,  $translated->TRANSLATION);
                    }
                    else
                    {
                        $debug_msg = 'CACHE NOT AVAILABLE: ';
                    }

                    # get text of translation
                    $translated = $translated->TRANSLATION;
                }
                else
                {
                    # nothing from cache, return original text
                    $debug_msg  = 'TRANSLATION NOT AVAILABLE: ';
                    $translated = false;
                }
            }
            else
            {
                $debug_msg = 'CACHE HIT: ';
            }
        }

        # add to the query debugging, provides a link to clear the cache if cache available
        if (isset($debug_msg) && (QUERY_DEBUGGING || BENCHMARKING_LVL == 4))
        {
            if (!isset($_SESSION['queries'])) { $_SESSION['queries'] = array(); }
            if (!isset($_SESSION['no_redis'])) {
                $_SESSION['queries'][] = "[".round((microtime(true) - $query_start_time),4)."] $debug_msg
                    <a href=\"/page/translation/&hash=$hash\" target=\"_blank\">$hash</a>: ".strip_tags(substr($text,0,50));
            } else {
                $_SESSION['queries'][] = "[".round((microtime(true) - $query_start_time),4)."] $debug_msg
                    $hash: ".strip_tags(substr($text,0,50));
            }
        }

        # Apply translated text to return string
        if ($translated)
        {
            $text = $translated;
        }
    }


    # Bracket replacement
    if ($parse
        && stristr($text,'[[')
        && isset($_SESSION['user'])
        && isset($_SESSION['project'])
        && is_object($_SESSION['user'])
        && is_object($_SESSION['project'])
    )
    {
        $preOffset  = 0;
        $result     = '';
        preg_match_all('/\[\[(.+?)\]\]/i', $text, $patterns, PREG_OFFSET_CAPTURE);

        if (isset($patterns[0]) && count($patterns[0]) > 0 && isset($patterns[1]) && count($patterns[1]) > 0)
        {
            for ($i = 0; $i < count($patterns[0]) && $i < count($patterns[1]); ++$i)
            {
                $fullToken = $patterns[0][$i][0];
                $partialToken = trim(strtolower($patterns[1][$i][0]));

                # Handle the previous substring.
                $preLength = $patterns[0][$i][1] - $preOffset;
                $postOffset = $preOffset + $preLength + strlen($fullToken);


                $result .= substr($text, $preOffset, $preLength);

                switch($partialToken)
                {
                case 'vendor_name':
                    $result .= $_SESSION['project']->getVendorName();
                    break;

                case 'project_name':
                case 'project_company_name':
                    $result .= $_SESSION['project']->getGeneralName();
                    break;

                case 'project_link':
                    $result .= $_SESSION['project']->getLink();
                    break;

                case 'project_address':
                    $result .= $_SESSION['project']->getAddress();
                    break;

                case 'project_email':
                    $result .= $_SESSION['project']->getEmail();
                    break;

                case 'project_fax':
                    $result .= formatPhoneNumber($_SESSION['project']->getFax());
                    break;

                case 'project_phone':
                    $result .= formatPhoneNumber($_SESSION['project']->getPhone());
                    break;

                case 'project_hours':
                case 'project_phone_hours':
                    $result .= $_SESSION['project']->getHours();
                    break;

                case 'project_audit_start_date':
                    $result .= text($_SESSION['project']->auditStart());
                    break;

                case 'project_audit_stop_date':
                    $result .= text($_SESSION['project']->auditStop());
                    break;

                case 'v_due_date':
                    $result .= text($_SESSION['project']->verificationDueDate());
                    break;

                case 'f_final_due_date':
                    $result .= text($_SESSION['project']->finalDueDate());
                    break;

                case 'v_mail_1_date':
                    $result .= text($_SESSION['project']->getMailDate1());
                    break;

                case 'v_mail_2_date':
                    $result .= text($_SESSION['project']->getMailDate2());
                    break;

                case 'v_mail_3_date':
                    $result .= text($_SESSION['project']->getMailDate3());
                    break;

                case 'employee_id':
                    $result .= $_SESSION['user']->getId();
                    break;

                case 'employee_name':
                    $result .= $_SESSION['user']->getName();
                    break;

                case 'employee_dob':
                    $result .= $_SESSION['user']->getDOB();
                    break;

                case 'employee_address':
                    $result .= $_SESSION['user']->getAddress();
                    break;

                case 'employee_street':
                    $result .= $_SESSION['user']->getStreet();
                    break;

                case 'employee_street2':
                    $result .= $_SESSION['user']->getStreet2();
                    break;

                case 'employee_city':
                    $result .= $_SESSION['user']->getCity();
                    break;

                case 'employee_state':
                    $result .= $_SESSION['user']->getState();
                    break;

                case 'employee_zip':
                    $result .= $_SESSION['user']->getZip();
                    break;

                case 'employee_email':
                    $result .= $_SESSION['user']->getEmail();
                    break;

                case 'employee_phone':
                    $result .= formatPhoneNumber($_SESSION['user']->getPhone());
                    break;

                case 'employee_poe_start_date':
                    $result .= text(date('F jS, Y',strtotime($_SESSION['user']->getPoeStartDate())));
                    break;

                case 'employee_poe_stop_date':
                    $result .= text(date('F jS, Y',strtotime($_SESSION['user']->getPoeStopDate())));
                    break;

                case 'employee_client_value_1':
                    $result .= $_SESSION['user']->getClientValue(1);
                    break;
                case 'employee_client_value_2':
                    $result .= $_SESSION['user']->getClientValue(2);
                    break;
                case 'employee_client_value_3':
                    $result .= $_SESSION['user']->getClientValue(3);
                    break;
                case 'employee_client_value_4':
                    $result .= $_SESSION['user']->getClientValue(4);
                    break;
                case 'employee_client_value_5':
                    $result .= $_SESSION['user']->getClientValue(5);
                    break;
                case 'employee_client_value_6':
                    $result .= $_SESSION['user']->getClientValue(6);
                    break;
                case 'employee_client_value_7':
                    $result .= $_SESSION['user']->getClientValue(7);
                    break;
                case 'employee_client_value_8':
                    $result .= $_SESSION['user']->getClientValue(8);
                    break;
                case 'employee_client_value_9':
                    $result .= $_SESSION['user']->getClientValue(9);
                    break;
                case 'employee_client_value_10':
                    $result .= $_SESSION['user']->getClientValue(10);
                    break;

                #Spanish Dates
                case 'spanish_project_audit_start_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->auditStart()));
                    break;
                case 'spanish_project_audit_stop_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->auditStop()));
                    break;
                case 'spanish_project_v_due_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->verificationDueDate()));
                    break;
                case 'spanish_project_f_final_due_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->finalDueDate()));
                    break;
                case 'spanish_project_v_mail_1_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->getMailDate1()));
                    break;
                case 'spanish_project_v_mail_2_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->getMailDate2()));
                    break;
                case 'spanish_project_v_mail_3_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->getMailDate3()));
                    break;
                case 'spanish_employee_poe_start_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->getPoeStartDate()));
                    break;
                case 'spanish_employee_poe_stop_date':
                    $result .= SpanishDate(strtotime($_SESSION['project']->getPoeStopDate()));
                    break;

                }

                # Set the offset/length to new.
                $preOffset = $postOffset;
            }

            # add in remaining text
            $result .= substr($text, $preOffset);

            # replace original text
            $text = $result;
        }
    }

    # BR to BR\
    $text = str_replace('<br>','<br />', $text);
    return $text;
}

function getTranslation($hash, $text, $cache = true)
{
    if (isset($_SESSION['project']))
    {
        $reference = 'project:'.$_SESSION['project']->getProjectID();
    } else
    {
        $reference = '';
    }

    $curl = curl_init();
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:', 'Referer: '.PROTOCOL.APP_URL));
    curl_setopt($curl, CURLOPT_URL, TRANS_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, array('hash'      => $hash,
                                                 'lang'      => $_SESSION['lang'],
                                                 'cache'     => $cache,
                                                 'reference' => $reference,
                                                 'string'    => $text));
    $translated = curl_exec($curl);
    $error      = curl_error($curl);
    curl_close($curl);

    # curl error checking
    if ((!empty($error)
            || substr($translated,0,5) === '<?xml')
        && is_numeric(substr($translated,(strpos($translated,'<h1>') + 4), 3)))
    {
        $error = 'CURL request failed';
    }
    else
    {
        # decode result
        $translated_raw = $translated;
        $translated = json_decode($translated);

        # json error checking
        if (!is_object($translated)
            || !isset($translated->TRANSLATION))
        {
            $error = 'JSON decoding failed: '.$translated_raw;
        }
        else
        {
            # result error checking
            if (!empty($translated->error))
            {
                $error = 'Translation Error: '.$translated_raw.'<br>'.$translated->error;
            }
        }
    }

    if (!$error)
    {
        if (substr($text,-1) == ' ')
        {
            $translated->TRANSLATION .= ' ';
        }
        if (substr($text,1) == ' ')
        {
            $translated->TRANSLATION = ' '.$translated->TRANSLATION;
        }
        return $translated;
    }
    else
    {
        if (!isset($_SESSION['translation_error_reported']))
        {

            $body = 'Hash: '.$hash.'<br><br>Error: <pre>'.htmlentities($error).
                '</pre><br><br>Original: <pre>'.htmlentities($text).'</pre>'.
                (isset($translated_raw) ? '<br>Encoding Before: '.mb_detect_encoding($translated_raw) : '').
                '<br>Encoding After: '.mb_detect_encoding($translated);
            sendMail(EMAIL_ERROR_TO,'Translation failure', $body);

            $_SESSION['translation_error_reported'] = true;
        }

        return false;
    }

}

function connectToRedis()
{
    global $redis;
    if (!isset($_SESSION)
        || !isset($_SESSION['no_redis'] ))
    {
        # connect to redis
        try
        {
            # C library
            $redis = new Redis();

            if(!$redis->connect(REDIS_SERVER, REDIS_PORT))
            {
                $redis = false;
                $_SESSION['no_redis'] = true;
            }

            return $redis;
        }
        catch (Exception $e)
        {
            # disable Redis for session
            $_SESSION['no_redis'] = true;

            return false;
        }
    }

    return false;
}

function flushTranslation($hash)
{
    $redis = connectToRedis();

    if ($redis)
    {
        if ($hash == 'all')
        {
            if ($redis->flushAll())
            {
                return true;
            }
        }
        else
        {
            if ($redis->del('trans:'.$hash))
            {
                return true;
            }
        }
    }

    return false;
}

function query_cache_key($query)
{
    return $key = hash('sha256', $query);
}

function query_cache_store($query, $results, $params = array())
{
    GLOBAL $query_cache;

    $query_cache[query_cache_key($query)]['parameters'] = $params;
    $query_cache[query_cache_key($query)]['result'] = $results;

    return true;
}

function query_cache_hit($query, $params = array())
{
    GLOBAL $query_cache;

    $key = query_cache_key($query);

    if (isset($query_cache[$key]))
    {
        $paramDiff = array_diff_assoc($query_cache[$key]['parameters'],$params);
        if(empty($paramDiff))
        {
            if (QUERY_DEBUGGING)
            {
                //not sure what to do here
                if (!isset($_SESSION['queries'])) { $_SESSION['queries'] = array(); }
                $_SESSION['queries'][] = "[CACHE] ".$query;
            }
            return $query_cache[$key]['result'];
        }
    }
    return false;
}



function ActionTrackerRead($page)
{
    if (USE_ACTIONTRACKER)
    {
        if (!isset($_SESSION['adm_preview'])
            && isset($_SESSION['user'])
            && $_SESSION['user']->getAccountType() == 'EMPLOYEE')
        {
            ActionTracker::sendAction('EID', $_SESSION['user']->getID(), APP_SHORT.':view', "The employee is on the $page page");
        }
    }
}

function ActionTrackerChange($msg)
{
    if (USE_ACTIONTRACKER)
    {
        if (!isset($_SESSION['adm_preview'])
            && isset($_SESSION['user'])
            && $_SESSION['user']->getAccountType() == 'EMPLOYEE')
        {
            ActionTracker::sendAction('EID', $_SESSION['user']->getID(), APP_SHORT.':change', $msg);
        }
    }
}

function returnStateArray()
{
    return array(
        'AL'=>"Alabama",
        'AK'=>"Alaska",
        'AS'=>"American Samoa",
        'AZ'=>"Arizona",
        'AR'=>"Arkansas",
        'CA'=>"California",
        'CO'=>"Colorado",
        'CT'=>"Connecticut",
        'DE'=>"Delaware",
        'DC'=>"District Of Columbia",
        'FM'=>"Federated St of Micronesia",
        'FL'=>"Florida",
        'GA'=>"Georgia",
        'GU'=>"Guam",
        'HI'=>"Hawaii",
        'ID'=>"Idaho",
        'IL'=>"Illinois",
        'IN'=>"Indiana",
        'IA'=>"Iowa",
        'KS'=>"Kansas",
        'KY'=>"Kentucky",
        'LA'=>"Louisiana",
        'ME'=>"Maine",
        'MD'=>"Maryland",
        'MA'=>"Massachusetts",
        'MH'=>"Marshall Islands",
        'MI'=>"Michigan",
        'MN'=>"Minnesota",
        'MS'=>"Mississippi",
        'MO'=>"Missouri",
        'MP'=>"Northern Mariana Islands",
        'MT'=>"Montana",
        'NE'=>"Nebraska",
        'NV'=>"Nevada",
        'NH'=>"New Hampshire",
        'NJ'=>"New Jersey",
        'NM'=>"New Mexico",
        'NY'=>"New York",
        'NC'=>"North Carolina",
        'ND'=>"North Dakota",
        'OH'=>"Ohio",
        'OK'=>"Oklahoma",
        'OR'=>"Oregon",
        'PA'=>"Pennsylvania",
        'PR'=>"Puerto Rico",
        'PW'=>"Palau",
        'RI'=>"Rhode Island",
        'SC'=>"South Carolina",
        'SD'=>"South Dakota",
        'TN'=>"Tennessee",
        'TX'=>"Texas",
        'UT'=>"Utah",
        'VI'=>"Virgin Islands",
        'VT'=>"Vermont",
        'VA'=>"Virginia",
        'WA'=>"Washington",
        'WV'=>"West Virginia",
        'WI'=>"Wisconsin",
        'WY'=>"Wyoming",
        'AA'=>"Armed Forces Americas",
        'AE'=>"Armed Forces Other",
        'AP'=>"Armed Forces Pacific"
    );
}


function humanFilesize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function getUserFullName($id)
{
    static $users = array();

    if ($id==0) { return false; }

    if (isset($users[$id]))
    {
        return $users[$id];
    }
    else
    {
        $db = new Model();
        $select = $db->getRow("select full_name from users where dc_id = :id",array(':id' => $id));
        if ($select)
        {
            $users[$id] = $select['full_name'];
            return $users[$id];
        }
    }

    return $id;
}

function getClientFullName($id)
{
    static $clients = array();

    if ($id==0) { return false; }

    if (isset($clients[$id]))
    {
        return $clients[$id];
    }
    else
    {
        $db = new Model();
        $select = $db->getRow("select name from clients where id = :id",array(':id' => $id));
        if ($select)
        {
            $clients[$id] = $select['name'];
            return $clients[$id];
        }
    }

    return $id;
}

function printAosModal($msg,$type,$allowTags = false)
{
    if(!$allowTags)
    {
        $msg = strip_tags($msg);
    }
    xPrint(("<div class=\"{$type}\">{$msg}</div>"),true);
}

function printAosError($error, $allowTags = false)
{
    printAosModal($error,'error',$allowTags);
}

function printAosSuccess($msg, $allowTags = false)
{
    printAosModal($msg,'success',$allowTags);
}

function printAosWarning($msg, $allowTags = false)
{
    printAosModal($msg,'warning',$allowTags);
}

function validEID($eid)
{
    if (is_numeric($eid)
        && strlen($eid) >= 2
        && strlen($eid) <= 12)
    {
        return true;
    }
    else
    {
        return false;
    }
}

function preCacheLogo($name)
{
    $path = realpath(LOGOS) .'/'. filenameSanitize($name);

    if (file_exists($path)
        && (date('U') - date('U', filemtime($path)) < 900))
    {
        // do nothing
    }
    else
    {
        $db = new Model();
        $data = $db->getVal("select file_data from templates where name = :name and type = 'weblogo' and deleted = '0000-00-00'",
            array(':name' => $name));

        if( empty($data) === false )
        {
            file_put_contents($path, $data);
        }
    }


    return $path;
}

/**
 * @param string $string
 */
function xssSanitize($string, $containsHtml = false)
{
    if($containsHtml)
    {
        if(strstr(strtolower($string), '<script>'))
        {
            return '';    
        }        
        return $string;        
    }   
    return htmlspecialchars($string);
}

function xEcho($string, $containsHtml = false)
{
    echo xssSanitize($string, $containsHtml);
}

function xPrint($string, $containsHtml = false)
{
    return print(xssSanitize($string, $containsHtml));
}

function pathSanitize($path)
{
    return $path;
}

function urlSanitize($path)
{
    return filter_var ( $path, FILTER_SANITIZE_URL);
}

function apikeySanitaize($string)
{
    return  preg_replace('/[^%a-zA-Z0-9 -]/', '', $string);
}

function filenameSanitize($string)
{
    $string = strip_tags($string);
    if (function_exists('mb_strtolower')) {
        $string = mb_strtolower($string, 'UTF-8');
    } else {
        $string = strtolower($string);
    }
    $string = preg_replace('/[^%a-z0-9 _\.-]/', '', $string);
    $string = preg_replace('/\s+/', '-', $string);
    $string = preg_replace('|-+|', '-', $string);
    $string = trim($string, '-');
    return $string;
}

/*
 * to identify local connections from production, for testing or internal views, etc
 */
function isConnectionLocal()
{
    if ($_SERVER['PHP_ENV'] != 'prod'
        || substr($_SERVER['SERVER_ADDR'],0,5) == '10.49'
        || substr($_SERVER['SERVER_ADDR'],0,5) == '10.10'
        || substr($_SERVER['SERVER_ADDR'],0,7) == '192.168')
    {
        return true;
    }

    return false;
}

function writeContactUsMessage($eid, $to, $from, $subject, $body)
{
    if (is_numeric($eid)
        && !empty($to)
        && !empty($from))
    {
        $db  = new Model();
        # this intentionally has to write the record into the database with an eid of 0, DC4 expects this
        $res = $db->q(
            "INSERT INTO emails_in (`eid`, `date`, `from`, `to`, `subject`, `body`) 
            VALUES ('0', now(), :from, :to, :subject, :body)",
            array(
                ':from'    => $from,
                ':to'      => $to,
                ':subject' => $subject,
                ':body'    => $body
            )
        );

        if ($res) {

            $databaseId = $db->lastInsertId();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, urlSanitize(DC4_PROCESS_URL . "?new_email_remote=" . intval($eid) . "&id=" . intval($databaseId)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response = curl_exec($ch);
            curl_close($ch);
            if ($response == 'ok') {
                return TRUE;
            }
        }
    }
    return false;
}

/**
 *  SpanishDate converts a date into a long date in spanish
 * @param Date field such as $FechaStamp = strtotime('2018-08-29');
 * @param $DayOfWeek field if day of the week is needed
 * @return Long date in Spanish string
 */
function SpanishDate($FechaStamp, $DayOfWeek=false)
{
    $ano = date('Y',$FechaStamp);
    $mes = date('n',$FechaStamp);
    $dia = date('d',$FechaStamp);
    $diasemana = date('w',$FechaStamp);
    $meses=array(1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio",
        "Agosto","Septiembre","Octubre","Noviembre","Diciembre");
    if($DayOfWeek)
    {
        // Long date with day of the week ... Lunes, Martes (Monday, Tuesday) ... etc.
        $diassemanaN= array("Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado");
        return $diassemanaN[$diasemana].", $dia de ". $meses[$mes] ." de $ano";
    }
    else{
        return "$dia de ". $meses[$mes] ." de $ano";
    }
}

/**
 *  Send Authentication/Verification Email
 * @param $type string
 * @param $key int
 * @param $email string
 * @param $url string
 *
 */
function sendAuthenticationEmail($type, $key, $email, $url='')
{
    $headerText = '';
    $subject = APP_TITLE;

    $contentText = "<p>Please Use your credentials to complete the email validation process.</p>
                    <p>Please return to the portal and log in again, you will be prompted to enter the code below</p>
                    <p>{$key}</p>";

    $endingText = "<p>After you have validated your email address you will be able to log into ".APP_NAME.".</p>";

    switch ($type) {
        case 'addpassword':
            $subject = APP_TITLE.": ".EMAIL_VALIDATION;
            $headerText = "<h2>".APP_NAME." - Account Validation</h2>
                           <p>You are receiving this email because you recently set a password to access your account on ".APP_URL."</p>";
            break;
        case 'resetpassword':
            $subject = APP_TITLE.": Password Reset Request";
            $headerText = "<h2>".APP_NAME." - Password Reset</h2>
                           <p>You are receiving this email because you recently requested your password be reset.</p>";
            break;
        case 'resendemail':
            $subject = APP_TITLE.": ".EMAIL_VALIDATION;
            $headerText = "<h2>".APP_NAME." - Account Validation</h2>
                           <p>Here is your ".APP_URL." account validation email.</p>";
            break;
        case 'addemail':
            $subject = APP_TITLE.": ".EMAIL_VALIDATION;
            $headerText = "<h2>".APP_NAME." - Account Validation</h2>
                           <p>You are receiving this email because you recently added email your account on ".APP_URL."</p>";
            break;
        case 'resetpasswordadmin':
            $subject = APP_TITLE.": Password Reset Request";
            $headerText = "<h2>".APP_NAME." - Password Reset</h2>
                           <p>You are receiving this email because you recently requested your password be reset.</p>";
            $contentText = "<p>To complete the password reset process, click on the link, and enter your email address and verification code.</p>
                            <p>Verification Code: {$key}</p>
                            <p><a href=\"{$url}/login/verifycode\">Click to Verify</a></p>
                            <br><br><br>";
            $endingText = "<p>After you have validated, you will be able to log into ".APP_NAME.".</p>";
            break;
        default:
            $headerText = "<h2>".APP_NAME." - Email Confirmation</h2>";
            $contentText = '';
    }

    $text = "   {$headerText}
                {$contentText}
                {$endingText}
            ";

    sendMail($email, $subject, $text);
}