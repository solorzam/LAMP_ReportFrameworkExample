<?php

class Model extends \PDO
{
//    protected $connection = NULL;

    private $connection_id      = false;
    private $connections        = false;
    private $connections_count  = false;
    private $queryError         = false;
    private $queryErrorText     = '';
    private $lastQuery          = '';

    public function __construct(
        $host = NULL,
        $name = NULL,
        $user = NULL,
        $pass = NULL,
        $port = '3306')
    {
        $connection_start_time = microtime(true);

        # Determine username and passwords
        if ($host == null || $name == null) {
            $host = CENTRAL_DB_IP;
            $name = CENTRAL_DB_NAME;
            $user = CENTRAL_DB_USER;
            $pass = CENTRAL_DB_PASS;
        } else {
            $user = AUDIT_DB_USER;
            $pass = AUDIT_DB_PASS;
        }

        if (USE_SSH) {
            # for logging purposes
            $original_host = $host;
            $tunnel_file = '../tmp/tunneldown_' . $original_host;

            # default to central connection
            if ($host == CENTRAL_DB_IP) {
                $host = '127.0.0.1';
                $port = '3300';
            } # else determine SSH port
            else {
                // only parse once
                if (!$this->connections) {
                    $this->connections = explode('|', SSH_CONNECTIONS);
                    $this->connections_count = count($this->connections);
                }

                // loop over each connection type
                for ($x = 0; $x < $this->connections_count; $x += 2) {
                    if (stristr($host, $this->connections[$x])) {
                        $host = '127.0.0.1';
                        $port = $this->connections[$x + 1];
                        break;
                    }
                }
                unset($x);
            }

            try {
                parent::__construct("mysql:host={$host};port={$port};dbname={$name}", $user, $pass);

                # connection was successful, check for existing failure
                if (file_exists($tunnel_file)) {
                    $estimated = date('i:s', date('U') - filemtime($tunnel_file));
                    unlink($tunnel_file);

                    sendMail(EMAIL_ERROR_TO, APP_TITLE . ": SSH Tunnels Up",
                        "SSH Tunnel has been restored $host: $port -> $name ($original_host).
                        Estimated downtime: $estimated");
                }
            } catch (PDOException $e) {
                # Have we failed before?
                if (!file_exists($tunnel_file)) {
                    sleep(2);

                    # Second attempt
                    # This prevents that few millisec that autossh might be reconnecting after idle down
                    try {
                        parent::__construct("mysql:host={$host};port={$port};dbname={$name}", $user, $pass);
                    } catch (PDOException $e) {
                        touch($tunnel_file);

                        sendMail(EMAIL_ERROR_TO, APP_TITLE . ": SSH Tunnels Down",
                            "The SSH Tunnel appears to be down for $host: $port -> $name ($original_host)");

                        # Fail over don't use tunnel
                        parent::__construct("mysql:host={$original_host};port=3306;dbname={$name}", $user, $pass);
                    }
                } else {
                    # Fail over don't use tunnel
                    parent::__construct("mysql:host={$original_host};port=3306;dbname={$name}", $user, $pass);
                }
            }
        } else {
            # Don't use tunnel
            parent::__construct("mysql:host={$host};port=3306;dbname={$name}", $user, $pass);
        }

        # Check if connection is active by setting attributes
        try {
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        } catch (PDOException $e) {
            logToDisk('CONNECTION FAILED: ' . $host, 1);
            redirect('/maintenance.php');
        }

        # Advanced logging
        if (EVENT_DEBUGGING_LEVEL == 3) {
            $result = $this->getRow("SELECT CONCAT(connection_id(),' :: ',USER(),' :: ',database()) as connection;");
            if (is_object($result)) {
                $result = $result->fetch_assoc();
                if (isset($result['connection'])) {
                    $this->connection_id = $result['connection'];
                }
            }
        }

        # Create the query logging session variable
        if (QUERY_DEBUGGING
            || BENCHMARKING_LVL == 4) {
            if (!isset($_SESSION['queries'])) {
                $_SESSION['queries'] = array();
            }
            $_SESSION['queries'][] = "[".round((microtime(true) - $connection_start_time),4)."] Connecting to $host $port $name";
        }
        // $this->exec("set names utf8");
    }

        public function getError()
    {
        $this->errorInfo();
    }

    public function __destruct()
    {
        //PDO does not destruct the same way so there is nothing to do
    }

    /**
     * Query wrapper function
     * runs query only, does not return rows, logs query and handles errors
     * if variables are passed in query is prepared
     *
     * @param $query
     * @param $vars
     *
     * 
     * @return bool|mysqli_result|PDOStatement
     */
    public function q($query, $vars=false)
    {
        $this->queryError = false;
        $this->queryErrorText = '';
        $query_start_time = microtime(true);
        $q = false;

        logToDisk($this->connection_id.'->'.$query,2);

        try
        {
            $q = $this->prepare($query);

            if (empty($vars))
            {
                $q->execute();
            }
            else
            {
//                foreach($vars as $key => $value)
//                {
//                    $q->bindValue($key, $value);
//                }
//                $q->execute();
                $q->execute($vars);
            }
        }
        catch (PDOException $e)
        {
            $this->queryErrorText = $e;
            $this->queryError = true;
        }

        # convert query into something reportable since it might be prepared statement
        if ($vars
            && is_array($vars))
        {
            foreach($vars as $key => $value)
            {
                $query = str_replace($key, '"'.$value.'"', $query);
            }
        }

        # save query for debug
        $this->lastQuery = $query;

        if (QUERY_DEBUGGING
            || BENCHMARKING_LVL == 4)
        {
            if (!stristr($query, 'scans_incoming'))
            {
                if (!isset($_SESSION['queries']))
                {
                    $_SESSION['queries'] = array();
                }
                $_SESSION['queries'][] = "[".round((microtime(true) - $query_start_time),4)."] ".$query;
            }
        }

        if ($this->errorCode() != '00000'
            || $this->queryError)
        {
            $this->queryError = true;
            $this->reportError($query);
            logToDisk('SQL FAILED: ' . $query . ' ' . (!empty($this->queryErrorText) ? $this->queryErrorText : ' ' ),1);
            return false;
        }
        return $q;
    }

    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    public function lastQuerySuccessful()
    {
        return $this->queryError;
    }

    /*
     *  Convenience calls, these wrap the q method which enables query logging
     *  These are not as secure as using a prepare statement, but these are
     *  quick an easy and work fine for non user interfaced data
     */
    public function getRow($query, $vars = false)
    {
        $result = $this->q($query, $vars);        
        return ($result ? $result->fetch(\PDO::FETCH_ASSOC) : false);
    }

    public function getRows($query, $vars = false)
    {
        $result = $this->q($query, $vars);        
        return ($result ? $result->fetchAll(\PDO::FETCH_ASSOC) : array());
    }

    public function getObj($query, $vars = false)
    {
        $result = $this->q($query, $vars);        
        return ($result ? $result->fetch(\PDO::FETCH_OBJ) : false);
    }

    public function getObjs($query, $vars = false)
    {
        $result = $this->q($query, $vars);        
        return ($result ? $result->fetchAll(\PDO::FETCH_OBJ) : array());
    }

    public function getVal($query, $vars = false)
    {
        $result = $this->q($query, $vars);        
        return ($result ? $result->fetch(\PDO::FETCH_COLUMN) : false);
    }

    public function getVals($query, $vars = false)
    {
        $result = $this->q($query, $vars);        
        return ($result ? $result->fetchAll(\PDO::FETCH_COLUMN) : array());
    }
    

    /*
     * Misc Functions
     */
    public function hashPassword($password)
    {
        return  hash('sha256', $password);
    }

    public function buildInString($input)
    {
        $rtn = '';
        $counter = 0;

        // put into array if value is solitary for loop
        $input = (is_array($input) ? $input : array($input));

        if(is_array($input))
        {
            foreach($input as $string)
            {
                //if any values is array, object, or resource break
                if(is_array($string)
                    || is_resource($string)
                    || is_object($string))
                {
                    $rtn = '';
                    break;
                }
                $rtn .= self::quote($string);
                if($counter++ != count($input) - 1)
                {
                    $rtn .= ',';
                }
            }
        }
        return $rtn;
    }

    public function createTableFromSelect($tableName, $query, $params = array())
    {
        $this->q("CREATE TEMPORARY TABLE {$tableName} ENGINE=MEMORY AS ({$query} LIMIT 1)",
            $params);

        $create = $this->getRow("SHOW CREATE TABLE `{$tableName}`");

        if ($create["Create Table"])
        {
            $create = str_ireplace('ENGINE=MEMORY', '',
                      str_ireplace('TEMPORARY TABLE', 'TABLE', $create["Create Table"]));

            $this->q("DROP TABLE {$tableName}");
            $this->q($create);
            $this->q("INSERT INTO {$tableName} {$query}",
                $params);
            return true;
        }

        return false;
    }

    private function reportError($query)
    {
        if ($this->errorCode())
        {
            # user info
            $ip		= (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
            $url	= (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
            $ref	= (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

            # remove objects from session
            $sess = array();
            if (isset($_SESSION))
            {
                # drop all but last two queries
                if (isset($_SESSION['queries']))
                {
                    $_SESSION['queries'] = array_slice($_SESSION['queries'], -2);
                }

                foreach($_SESSION as $key => $val)
                {
                    if (!is_object($val))
                    {
                        $sess[$key] = $val;
                    }
                }
            }

            # get a session id from the object
            $sess['user_id'] = (isset($_SESSION['user']) ? $_SESSION['user']->getID() : '');

            # flatten arrays
            $sess 	= print_r($sess, true);
            $post 	= print_r($_POST, true);
            $get	= print_r($_GET, true);
            $error_display = print_r($this->errorInfo(), true);

            # override when we have a 00000 error (previous query okay params are bad)
            if ($this->errorCode() == 00000)
            {
                $error_display = 'A issue exists with the queries parameters.';
            }

            $body = "
                <h2>MySQL Query Error</h2>                
                <p><strong>Error:</strong> {$this->errorCode()} - {$error_display}</p>                
                <p><strong>Query:</strong> {$query}</p>
                <p>
                    <strong>IP Address:</strong> {$ip}<br/>
                    <strong>URL:</strong> {$url}<br/>
                    <strong>Referer:</strong> {$ref}<br/>
                </p>
                <hr><pre>Session: {$sess}</pre>
                <hr><pre>Post: {$post}</pre>
                <hr><pre>Get: {$get}</pre>
            ";

            # send email to it group
            sendMail(EMAIL_ERROR_TO, APP_TITLE.': MySQL Query Error', $body);
        }

        # return false to application
        return false;
    }
}