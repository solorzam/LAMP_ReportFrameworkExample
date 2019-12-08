<?php

class StaticPageModel // extends Model
{
    public $grouper;

    public function __construct($grouper='benefit_resources')
    {
        $this->grouper = $grouper;
    }

    public function getLinks()
    {
        $db = new Model();
        $select = $db->getRows("SELECT id, label 
            FROM static_pages 
            WHERE grouper = :grouper 
              AND `deleted` = 0 
            ORDER BY `order`",
            array(':grouper' => $this->grouper));
        
        return $select;
    }

    public function getContent($id)
    {
        $db = new Model();
        $select = $db->getRow("SELECT label, content 
              FROM static_pages 
              WHERE label = :id
                AND grouper = :grouper  
                AND `deleted` = 0 
              LIMIT 1",
            array(':id' => $id, ':grouper' => $this->grouper));

        return $select;
    }
}