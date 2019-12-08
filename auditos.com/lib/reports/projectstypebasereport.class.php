<?php


class ProjectsTypeBaseReport extends BaseReport
{
    private $expectedFlags = array('project', 'grouper', 'status', 'type');
    public function __construct($projectId, $className)
    {
        // call BaseReport constructor, which connects to Central and project AuditDB databases
        parent::__construct($projectId, $className);
    }

    public function checkValidOption($search, $flags)
    {
        return (in_array($search, $this->expectedFlags, TRUE) && $flags[$search]);
    }



    public function  getAuditDbForGrouper($grouper)
    {
        return $this->centralDb->getRows("select DISTINCT db_ip, db from projects where grouper = '$grouper' ");

    }

}