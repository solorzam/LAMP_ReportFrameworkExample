<?php


class PerformanceTypeBaseReport extends BaseReport
{
    private $expectedFlags = array('start_date', 'end_date');
    public function __construct($projectId, $className)
    {
        // call BaseReport constructor, which connects to Central and project AuditDB databases
        parent::__construct($projectId, $className);
    }

    public function checkValidOption($search, $flags)
    {
        return (in_array($search, $this->expectedFlags, TRUE) && $flags[$search]);
    }
}