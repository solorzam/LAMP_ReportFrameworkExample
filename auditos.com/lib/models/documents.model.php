<?php

/*
 * DEPRICATED
 * DAMIEN 3/19/18
 */
//include("employee.model.php");
die();
class DocumentsModel extends EmployeeModel
{
	private $eid, $path, $name, $type, $tmp, $size, $pages;
	public $error;
	
	public function __construct($eid, $path, $info)
	{
		parent::__construct($eid);
		
		$this->eid 		= $eid;
		$this->path 	= $path;
        $this->name 	= substr(
                            str_replace('__', '_',
                                str_replace(array(',','^',':','[',']','{','}','`','+','=','|'),'_',
                                    str_replace(array('.pdf','$','#','<','>'), '',
                                        str_replace(array("'",'"'),'',
                                            stripslashes(
                                                trim($info["name"])
                                            )
                                        )
                                    )
                                )
                            ), 0, 30).'.pdf';
		$this->type 	= $info["type"];
		$this->tmp 		= $info["tmp_name"];
		$this->size 	= $info["size"];
        $this->pages    = 1;
	}
	
	//public methods
	public function upload()
	{
		# connect to central database
		$db = new Model();

		# get contents of file
		$contents = file_get_contents($this->path);

        # page count, defaults to 1
        if (!stristr($this->name, 'jpg'))
        {
            $this->pages = preg_match_all("/\/Page\W/", $contents, $devnull);
        }

        # fix name
        if (empty($this->name))
        {
            $this->name = 'document.pdf';
        }

		# get current max id value
		$oldmax = $db->getVal("select max(id) from scans_incoming");
		
		if(empty($oldmax))
		{
			return false;
			systemExit();
		}
		

		
		//insert new document into scans_incoming
//        if (ini_get('magic_quotes_gpc') == 1)
//        {
//            $contents = addslashes($contents);
//            $insert = $db->q("insert into scans_incoming (eid, file_name, date, scan_type, file_data) values ('".
//                $this->eid."', '".
//                $this->name."', now(), 'U', '{$contents}')");
//        }
//        else
//        {
            $insert = $db->q("insert into scans_incoming (eid, file_name, date, scan_type, pages, file_data) 
								values (:employee_id, :file_name, now(), 'U', :pages, :contents)",
				array(':employee_id'	=> $this->eid,
					  ':file_name' 	 	=> $this->name,
					  ':pages' 			=> $this->pages,
					  ':contents' 		=> $contents));
//        }

	
		if(empty($insert))
		{
			return false;
			systemExit();
		}
		
		$newmax = $db->lastInsertId();
		
		if($newmax > $oldmax)
		{
            if (DIR == 'm') {
                $fromMobile = '&mobile=true';
            } else {
                $fromMobile = '';
            }

			//execute dc4 sync script
			$success = remote_call(DOC_UP_URL.$newmax.$fromMobile);

            if($success != 'success')
            {
                ErrorModel::Log("Document Upload", "Error on Remote Upload");
                return false;
            }
            else
            {
                return true;
            }
		}
		else
		{
			ErrorModel::Log("Document Upload", "New Max ID is not greater than Old Max ID: New Max = {$newmax} and Old Max = {$oldmax}");
			return false;
		}
	}
}
