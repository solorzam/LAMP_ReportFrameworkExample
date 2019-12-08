<?php

class ReportFactory
{
    public static function CreateReportInstance($reportClass, $params)
    {

        try {
            return new  $reportClass($params, $reportClass);
        } catch (Exception $exception) {
            $result = '*** EXCEPTION - Couldn NOT create report = $_POST[\'ReportSelected\']; exception = ' . $exception->getMessage();
            return array('exception' => $result);
        }
    }
}