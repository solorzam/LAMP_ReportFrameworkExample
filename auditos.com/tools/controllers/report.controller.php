<?php

/*
    This controller is only for testing proof of concept with reports_test.php in DC4
*/

class ReportController extends Controller
{
    // Current instance of authentication credentials.
    protected $ipAddress = null;
    protected $apiKey = null;
    // protected $aDb;
    protected $_db = null;
    //protected $tbl;
    protected $result;


    public function init()
    {
        // all methods inherit this
        // will bypass including the header and footer files
        $this->template->render = false;
        $this->template->header = false;
        $this->template->footer = false;
    }


    protected function db()
    {
        if (!$this->_db)
        {
            $this->_db = new Model();
        }
        return $this->_db;
    }


    public function run()
    {
/// TODO: There will be an array of  arguments that exposed that we will need to parse through, the report name and project id is givien here as an example
        if(!isset($_POST['api_key']))
        {
            echo(json_encode(array('exception' => 'No API Key provided.')));
            return;
        }
        if(!isset($_POST['report_selected']))
        {
            echo(json_encode(array('exception' => 'No report selected.')));
            return;
        }
        if(!isset($_POST['project_id']))
        {
            echo(json_encode(array('exception' => 'No project was chosen.')));
            return;
        }

        try {
            DocumentScan::checkPrimaryAuthentication($_SERVER['REMOTE_ADDR'], $_POST['api_key']);

            $report = ReportFactory::CreateReportInstance($_POST['report_selected'], $_POST['project_id']);

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
    }
}