This is a sample of a larger project that I created at Healthcare Management Systems.

It was to build a report framework that was easily extensible at incorporate new reports as they are being created. This makes use http protocol microservices so that the reports can be created from any application. The API receives the request and builds the report and responds with an excel file back to the requester. The purpose of using a microservice endpoint is to be able to decouple the application that calls the report API from the actual report builder, and to make the report framework easily extensible. 
The requestor application passes all the arguments needed by the reporting framework in order to build the report. Example:

function isJSON($string){
    return is_string($string) && is_object(json_decode($string)) ? true : false;
}


function curlAPI($url, $data)
{
    $curl = curl_init();

    try
    {
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect: '));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $res = curl_exec($curl);
        curl_close($curl);

        // decode
        try
        {
            //return json_decode($res);
            return $res;
        }
        catch (Exception $e)
        {
            cliLog("JSON_DECODE_ERROR: " . $res);
        }
    }
    catch (Exception $e)
    {
        cliLog("CURL_ERROR: " . curl_error($curl));
        curl_close($curl);
    }

    return false;
}


// Front end user would choose EmployeeReport (class) to run
$results = curlAPI(AUDITOS_REPORT_URL, array(
        'api_key'          => 'bd991a6d-618c-4339-b2ca-101e67614e4e',
        'report_selected'  => 'EmployeeStatusReport',
        'project_id' 	   => '3',
        'user_id'          => $_SESSION['user']['name'],
        'report_grouper'   => 'Anthem',
        'report_statuses'  => 'complete',
        'project' 	       => false,
        'multi' 	       => false,
        'grouper' 	       => true,
        'status'           => true,
        'type'             => false,
        'start_date'       => false,
        'end_date'         => false,
    )
);


$resultRows = json_decode($results, true);

$helper = new \DC\Views\Helpers\DBTable();

echo "<table class=pad style=\"width:93%;margin:inherit;\"\>";
if(isset($resultRows) && $resultRows)
{
    if (is_array($resultRows) and count($resultRows) > 1)
    {
        $helper::headerCells($resultRows[0]);
        foreach ($resultRows as $row) {
            $helper::cells($row);
        }
    }
    else
    {
        foreach ($resultRows as $k=>$v) {
            echo '<tr><td>' . $k . '</td><td>' . $v . '</td></tr>';
        }
    }
}
else
{
    echo '<tr><td>Response to API call is empty</td></tr>';
}
echo "</table>";

This calls the service report.controller in the framework and instantiates the report from a report Factory class, e.g.

       try {
            DocumentScan::checkPrimaryAuthentication($_SERVER['REMOTE_ADDR'], $_POST['api_key']);

            $report = ReportFactory::CreateReportInstance($_POST['report_selected'], 
                      $_POST['project_id']);

            if(isset($report) && is_object($report))
            {
               // $result = $report->getEmployees($_POST['project_id']);

              # Generate report
                $options['project_id'] = $_POST['project_id'];
                $options['report_selected'] = $_POST['report_selected'];
                $options['report_grouper'] = $_POST['report_grouper'];
                $options['report_statuses'] = $_POST['report_statuses'];


                if($_POST['project']) $flags['project'] = true;
                if($_POST['multi'])  $flags['multi'] = true;
                if($_POST['grouper'])  $flags['grouper'] = true;
                if($_POST['status'])  $flags['status'] = true;
                if($_POST['type'])  $flags['type'] = true;
                if($_POST['start_date'])  $flags['start_date'] = true;
                if($_POST['end_date'])  $flags['end_date'] = true;

               $result = $report->generate($flags, $options);

               # get Temp Table Name
               // $this->result=array('TempTableName' => $report->getTempTableName());

                $this->result =  is_object($result) ? $result : (object) $result;

            }
            else
            {
                $this->result = (object)$report;
            }


            // json only response
            header('Content-type: application/json');
            echo json_encode($this->result);
        }

        catch (Exception $exception) {
            // Rollback here.
            header('Content-type: application/json');
            $result = '*** EXCEPTION - Couldn NOT create report = $_POST[\'ReportSelected\']; exception = ' .$exception->getMessage();
            echo json_encode(array('exception'=>$result));
        }

Factory class instantiates the report name dynamically that is passed in:

class ReportFactory
{
    public static function CreateReportInstance($reportClass, $params)
    {

        try {
            return new  $reportClass($params, $reportClass);
        } catch (Exception $exception) {
            $result = '*** EXCEPTION - Could NOT create report = 
                 $_POST[\'ReportSelected\']; exception = ' . $exception->getMessage();
            return array('exception' => $result);
        }
    }
}

