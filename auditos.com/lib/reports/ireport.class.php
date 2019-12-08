<?php


interface IReport
{
    public function generate($flags, $options);
    public function getTempTableName();

}