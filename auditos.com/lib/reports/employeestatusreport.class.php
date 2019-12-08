<?php

class EmployeeStatusReport
    extends  ProjectsTypeBaseReport
    implements IReport
{

    public function __construct($projectId, $className)
    {
        // call BaseReport constructor, which connects to Central and project AuditDB databases
        parent::__construct($projectId, $className);
    }

    public function getEmployees($project_id)
    {
        // Get Employees
        $resultSet = $this->AuditDb->getRows("select * from employees where project_id =:project_id", array(':project_id' => $project_id) );
        if(isset($resultSet) && $resultSet)
        {
            return $resultSet;
        }
        else
        {
            return array('ResultSet'=>"employees not found");
        }

    }


    public function generate($flags, $options)
    {
        # Build PROJECT query
        $searchOption = 'project';
        $opt = $this->checkValidOption($searchOption, $flags);
        if($opt &&  isset($options['project_id']) && $options['project_id'])
        {
            $sqlArg[':project_id'] = $options['project_id'];
            $opt = $this->checkValidOption('status', $flags);
            if ($opt &&  isset($options['report_statuses']) && $options['report_statuses'])
            {
                //$sqlArg[':statuses'] =  "'" . str_replace(",", "','", $options['report_statuses']) . "'";
                $sqlArg[':statuses'] = $options['report_statuses'];
                $build = 'AND status IN ( :statuses ) ';
            }
            $qry = "select concat(status,if(substatus!='',concat(' - ',substatus),'')) as emp_status, count(*) as count 
              from employees 
              where project_id=:project_id " .
                $build.
              "group by emp_status order by emp_status";
        }
        else
        {
            # Build GROUPER Query
            $searchOption = 'grouper';
            $optGrp = $this->checkValidOption('grouper', $flags);
            if ($optGrp &&  isset($options['report_grouper']) && $options['report_grouper'])
            {
                $sqlArg[':grouper'] = $options['report_grouper'];
                $opt = $this->checkValidOption('status', $flags);
                if ($opt &&  isset($options['report_statuses']) && $options['report_statuses'])
                {
                    //$sqlArg[':statuses'] =  "'" . str_replace(",", "','", $options['report_statuses']) . "'";
                    $sqlArg[':statuses'] = $options['report_statuses'];
                    $build = 'AND e.status IN ( :statuses ) ';
                }

                $qry = "SELECT p.grouper,  p.name, CONCAT(e.status,IF(e.substatus!='',CONCAT(' - ',e.substatus),'')) AS emp_status, COUNT(*) AS COUNT 
                    FROM employees e JOIN projects p ON e.project_id = p.id
                    WHERE p.grouper=:grouper " .
                    $build
                    . "GROUP BY emp_status 
                    ORDER BY emp_status";
            }
            else
            {
                return array('exception'=>"report option not valid = " . $searchOption);
            }
        }

        # Execute query (Base class method)
        return $this->execureQry($optGrp, $options['report_grouper'], $qry, $sqlArg);

    }

    public function getTempTableName()
    {
        return $this->TempTableName;
    }

}