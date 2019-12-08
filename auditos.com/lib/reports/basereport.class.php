<?php


class BaseReport
{

    protected  $TempTableName;

    //database variables
    protected $ip, $db, $centralDb, $AuditDb;

    //Project variables
    protected $projectId;

    protected $classNameConstructed;

    public function __construct($projectId, $className)
    {

        $this->classNameConstructed = $className;

        $this->projectId 	= $projectId;

        //connect to central database
        $this->centralDb = new Model();

        $select = $this->centralDb->getRow("select db, db_ip from projects where id = :project_id",
            array(':project_id' => $projectId));

        $this->db = $select['db'];
        $this->ip = $select['db_ip'];

        if($this->db != null)
        {
            //connect to project database
            $this->AuditDb = new Model($this->ip, $this->db);
        }
    }

    public function getReportName()
    {
        return $this->classNameConstructed;
    }

    public function execureQry($optionTYpe, $options, $qry, $sqlArg)
    {
        # Execute query
        if ($optionTYpe &&  isset($options['report_grouper']) && $options['report_grouper'])
        {
            //return array('exception'=> "Inside Grouper");
            $resultSet = array();
            $distinctDbs = $this->getAuditDbForGrouper($options['report_grouper']);
            foreach ($distinctDbs as $distinctDb) {
                $currentAuditDb = new Model($distinctDb['db_ip'], $distinctDb['db']);
                $tempRows = $currentAuditDb->getRows($qry, $sqlArg);

                if(is_array($tempRows)) {
                    $resultSet = array_merge($tempRows, $resultSet);
                }
            }
        }
        else {
            $resultSet = $this->AuditDb->getRows($qry, $sqlArg);
        }

        if(isset($resultSet) && $resultSet)
        {
            //return array('ReportName'=>  $this->getReportName());
            $this->createDataFile($resultSet);
            return $resultSet;
        }
        else
        {
            $res= '';
            foreach($sqlArg as $key => $val)
            {
                $res .= $key . ' => ' . $val . ', ';
            }
            return array('ResultSet'=>   $res . ' QUERY: '.$qry);
        }

    }

    public function createDataFile($data)
    {

        $report =$this->getReportName();
        $friendlyName = $report;
        // return array('ReportName'=>  $this->getReportName());

        // Choose a temp filename
        // Locally: /srv/www/auditos.com/tmp/reports
        $tmpCSVFilename = REPORTS . 'rpt_'.$report.'_'.date('U').'.csv';
        $tmpXLSFilename = REPORTS . 'rpt_'.$report.'_'.date('U').'.xlsx';

       //return array('tmpCSVFilename' => $tmpCSVFilename);
       // $return['$tmpXLSFilename'] =$tmpXLSFilename;

        // Garbage Collection
//        if ($handle = opendir(REPORTS))
//        {
//            while (false !== ($file = readdir($handle))) {
//                if (substr($file, 0, 3) === 'rpt') {
//                    if (filemtime(REPORTS . $file) <= time() - 3600) {
//                        // delete files older than 1 hour - 3600 seconds
//                        unlink(REPORTS . $file);
//                        $cleanUpList[] = REPORTS . $file;
//                    }
//                }
//            }
//
//            if (isset($cleanUpList) && is_array($cleanUpList)) {
//                logAction("Report cleanup: " . implode($cleanUpList));
//            }
//            closedir($handle);
//        }

        // Process data from report
        if (!empty($data))
        {
            if (empty($content))
            {
                $content[] = array_keys($data[0]);
            }

            // get contents
            foreach($data as $row)
            {
                $content[] = $row;

            }

            // log what is taking place
            logAction("Generated data for $report");

            // if we don't have a report, make an empty array with that it is empty.
            if (is_array($content) && !empty($content))
            {

                // create CSV
                $handle = fopen($tmpCSVFilename, 'w');
                foreach($content as $row)
                {
                    if (is_array($row))
                    {
                        fputcsv($handle, $row);
                    }
                }
                fclose($handle);

                // if successful then try and create the xls
                if (file_exists($tmpCSVFilename))
                {
                    // create EXCEL
                    // memory fail safe, estimate memory usage, each cell takes 1 kb
                    // estimate 85mb, realistically we wont know
                    if (((count($content) * count($content[0])) * 1) < 85000)
                    {
                        require_once('../lib/classes/PHPExcel.php');
                        require_once('../lib/classes/PHPExcel/IOFactory.php');

                        //return array('tmpXLSFilename' => $tmpXLSFilename);
                        // setup excel basics
                        $objPHPExcel = new PHPExcel();
                        $objPHPExcel->getProperties()->setCreator(APP_URL)
                            ->setLastModifiedBy(APP_URL)
                            ->setTitle($friendlyName)
                            ->setSubject($friendlyName)
                            ->setDescription("")
                            ->setKeywords("")
                            ->setCategory("");
                        $worksheet = $objPHPExcel->getActiveSheet()->setTitle($friendlyName);

                        // quick way but doens't format per cell
                        //$worksheet->fromArray($content,NULL,'A1');

                        $row = 1;
                        foreach ($content as $values)
                        {
                            $col = 'A';
                            foreach($values as $cell)
                            {
                                // Padded with zeros
                                if (is_numeric($cell) && substr($cell,0,1) == '0')
                                {
                                    $worksheet->getStyle($col.$row)->getNumberFormat()->setFormatCode(str_repeat('0',strlen($cell)));
                                    $worksheet->getCell($col.$row)->setValueExplicit($cell);
                                }

                                // basic numeric
                                elseif (is_numeric($cell))
                                {
                                    $worksheet->getCell($col.$row)->setValue($cell);
                                }

                                // everything else
                                else
                                {
                                    $worksheet->getCell($col.$row)->setValueExplicit($cell, PHPExcel_Cell_DataType::TYPE_STRING);
                                }

                                // auto size column
                                $worksheet->getColumnDimension($col)->setAutoSize(true);
                                ++$col;
                            }
                            ++$row;
                        }



                        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                        $objWriter->save($tmpXLSFilename);

                        // test if xls was successful
                        if (!file_exists($tmpXLSFilename))
                        {
                            logAction("Failed to generate xls for $report");
                        }
                    }
                }
                else
                {
                    logAction("Failed to generate csv part for $report");
                }
            }
            else
            {
                logAction("No results for $report");
            }
        }
    }


}