<?php

class ErrorModel
{
	public static function Log($error='', $extra = null)
	{
        $user_id = isset($_SESSION['user']) ? $_SESSION['user']->getID() : 0;

        $extra = prepForLog($extra);

        $db = new Model();
		$db->q("INSERT INTO log_aos_error (`user_id`, `date`, `msg`, `dbg`) VALUES (':user_id', now(), ':error', ':debug')",
            array(':user_id' => $user_id,
                  ':error' => $error,
                  ':debug' => $extra));

        ActionTrackerChange('User experienced error: '.$error);
	}
}
