<?php

class EmployeeModel // extends Model
{
    private $id, $clientid, $projectid, $planid, $name, $status, $substatus,
        $clientstatus, $street, $street2, $city, $state, $zip, $dob,
        $phone, $account, $dbip, $dbname, $email, $company_email, $sms, $sms_key, $sms_active, $portal_email,
        $project_type, $project_name, $project_grouper, $portal_docs, $uploads,
        $relationships = array();

    # Client specific toggles
    private $roche, $anthem;

    private $templates;

    private $displayableTermTypes = array('Dental Only');

    private $poe_inactive_reason;

    // allowDownloads
    public function __construct($eid, $inactive = false)
    {
        // connect to central database, get all details there are not many columns
        $db     = new Model();
        $select = $db->getRow("select * from employees where id = :eid",
                                array(':eid' => $eid));
        if (!$select)
        {
            return false;
        }

        // get employee information from central database
        $this->id           = $select["id"];
        $this->clientid     = $select["client_id"];
        $this->projectid    = $select["project_id"];
        $this->planid       = $select["plan_id"];
        $this->name         = $select["name"];
        $this->zip          = $select["zip"];
        $this->dob          = $select["dob"];
        $this->status       = $select["status"];
        $this->substatus    = $select["substatus"];
        $this->clientstatus = $select["client_status"];
        $this->poestatus    = $select["poe_status"];
        $this->account      = "EMPLOYEE";
        $this->uploads      = true;

        if ($inactive)
        {
            $projectStatus = "in ('active', 'runout', 'inactive')";
        }
        else
        {
            $projectStatus = "= 'active'";
        }

        // determine audit database
        $select = $db->getRow("SELECT db_ip, db, project_type, grouper, name FROM projects WHERE id = :project_id AND status {$projectStatus}",
                        array(':project_id'     => $this->projectid));
        if (!$select)
        {
            return false;
        }

        // get project database name and ip address info
        $this->dbip         = $select["db_ip"];
        $this->dbname       = $select["db"];
        $this->project_type = $select["project_type"];
        $this->project_grouper = $select["grouper"];
        $this->project_name = $select["name"];

        // connect to audit database
        $db     = new Model($this->dbip, $this->dbname);
        $select = $db->getRow("select poe_status, poe_start_date, poe_stop_date, 
                                             sms, email_portal, email, street, street2, 
                                             city, state, sms, sms_portal_key, sms_portal_status, 
                                             phone, portal_docs, amnesty 
                                      from employees 
                                      where force_void <> '1' and id = :eid",
                            array(':eid' => $eid));

        if (!$select) return false;

        // get details from audit db
        $this->street         = $select["street"];
        $this->street2        = $select["street2"];
        $this->city           = $select["city"];
        $this->state          = $select["state"];
        $this->sms            = $select["sms"];
        $this->sms_key        = $select["sms_portal_key"];
        $this->sms_active     = $select["sms_portal_status"];
        $this->phone          = $select["phone"];
        $this->poe_status     = $select["poe_status"];
        $this->poe_start_date = ($select["poe_start_date"] != '0000-00-00' ? $select["poe_start_date"] : false);
        $this->poe_stop_date  = ($select["poe_stop_date"] != '0000-00-00' ? $select["poe_stop_date"] : false);
        $this->portal_docs    = ($select["portal_docs"] == 1 ? true : false);
        $this->amnesty        = ($select["amnesty"] == 1 ? true : false);
        $this->company_email  = $select["email"];
        $this->portal_email   = $select["email_portal"];

        // Set the primary display email
        if ($this->hasPortalEmail())
        {
            $this->email = $this->getPortalEmail();
        }
        elseif ($this->hasCompanyEmail())
        {
            $this->email = $this->getCompanyEmail();
        }
        else
        {
            $this->email = null;
        }

        // get rules for the relationships
        $select = $db->getRows("select rel, notes from rules where plan_id = :plan_id",
                    array(':plan_id' => $this->planid));
        if (empty($select))
        {
            return false;
        }

        foreach ($select as $rel)
        {
            $this->relationships[$rel['rel']] = $rel['notes'];
        }

        # Roche C2V Employee
        if (stristr($this->project_name, 'Click to Verify'))
        {
            $this->roche = true;
        }
        else
        {
            $this->roche = false;
        }

        # Anthem Employee
        if ($this->project_grouper == 'Anthem')
        {
            $this->anthem = true;
        }
        else
        {
            $this->anthem = false;
        }
    }


    public function getID()
    {
        return $this->id;
    }

    public function getType()
    {
        return "Employee";
    }


    public function getClientID()
    {
        return $this->clientid;
    }

    public function getProjectID()
    {
        return $this->projectid;
    }

    public function getPlanID()
    {
        return $this->planid;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAddress()
    {
        if (!empty($this->street2))
        {
            $address = $this->street . "<br />" . $this->street2 . "<br />" . $this->city . ", " . $this->state . " "
                . $this->zip;
        }
        else
        {
            $address = $this->street . "<br />" . $this->city . ", " . $this->state . " " . $this->zip;
        }

        return $address;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function getStreet2()
    {
        return $this->street2;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getZip()
    {
        return $this->zip;
    }

    public function getDOB()
    {
        return $this->dob;
    }

    public function getAccountType()
    {
        return $this->account;
    }

    public function getPoeStartDate()
    {
        return $this->poe_start_date;
    }

    public function getPoeStopDate()
    {
        return $this->poe_stop_date;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPortalEmail()
    {
        return $this->portal_email;
    }

    public function getCompanyEmail()
    {
        return $this->company_email;
    }

    public function getCentralEmail()
    {
        // special hook to get email from central table
        // used in login procedures

        //connect to central database
        $db = new Model();

        $select = $db->getRow("select email_portal from employees where id = :id",
            array(':id'=>$this->id));

        return $select['email_portal'];
    }

    public function getContactEmails()
    {
        $emails = array();

        if ($this->hasPortalEmail())
        {
            $emails[] = $this->getPortalEmail();
        }

        if ($this->hasCompanyEmail()
            && $this->getCompanyEmail() != $this->getPortalEmail()
        )
        {
            $emails[] = $this->getCompanyEmail();
        }

        return implode(',', $emails);
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function getSms()
    {
        return $this->sms;
    }

    // not sure if/when needed
    public function getSmsKey()
    {
        return $this->sms_key;
    }

    public function getSmsActive()
    {
        return $this->sms_active;
    }

    public function getRelationships()
    {
        return $this->relationships;
    }

    public function getPortalDocs()
    {
        return $this->portal_docs;
    }

    public function getDBIP()
    {
        return $this->dbip;
    }

    public function getDBName()
    {
        return $this->dbname;
    }


    /*
     * Special Getters
     */

    public function getStatus()
    {
        return $this->status;
    }

    public function getSubStatus()
    {
        return $this->substatus;
    }

    public function getClientStatus()
    {
        return $this->clientstatus;
    }

    public function getPoeStatus()
    {
        return $this->poestatus;
    }

    public function getStatuses()
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRow("select status, substatus, client_status 
                                     from employees 
                                     where id = :employee_id",
                        array(':employee_id'=>$this->id));

        // recache them, this func is called at the top of pages so cache will stay for single page
        $this->status       = $select["status"];
        $this->substatus    = $select["substatus"];
        $this->clientstatus = $select["client_status"];

        return $select;
    }

    public function getDisplayStatus()
    {
        $status = $this->getStatuses();
        if (in_array($status['status'], array('open', 'amnesty', 'partial')))
        {
            if ($status['status'] == 'open' && $status['substatus'] == 'pending processing')
            {
                return 'Response Received - Pending Review';
            }
            elseif (in_array(
                    $status['substatus'],
                    array('pending processing', 'pending audit', 'pending create', 'pending documents')
                ))
            {
                return 'Partial Response - Pending Review';
            }
        }

        if (stristr(strtolower($status['client_status']), 'complete'))
        {
            if ($this->hasReinstatement())
            {
                $status['client_status'] .= ' - Pending Reinstatement';
            }
        }

        # Roche override
        if ($this->RocheC2V())
        {
            $status['client_status'] = str_replace('Amnesty','',$status['client_status']);
            if ($status['client_status'] == "Response")
            {
                $status['client_status'] == "Complete";
            }
        }

        return $status['client_status'];
    }

    public function getValidationKey()
    {
        $db = new Model($this->dbip, $this->dbname);

        $portalKey = $db->getVal("select email_portal_key 
                                         from employees 
                                         where force_void <> '1' 
                                           and id = :employee_id",
                        array(':employee_id' => $this->id));

        return trim($portalKey);
    }

    public function validationKeyExists()
    {
        return ($this->getValidationKey() == '') ? false : true;
    }

    public function getDependents()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("select * from dependents 
                                  where employee_id = :employee_id",
                    array(':employee_id' => $this->id));

        // query okay
        if ($select)
        {
            // loop over dependents
            foreach ($select as $key => $dependent)
            {
                // Reinstatement
                if (strtolower($dependent['status']) == 'valid'
                    && strtolower($dependent['drop_status']) == 'pending reinstatement'
                )
                {
                    $select[$key]['client_status'] .= ' - Pending Reinstatement';
                }
                
                # Roche override
                if ($this->RocheC2V())
                {
                    $select[$key]['client_status'] = str_replace('Amnesty', '', $select[$key]['client_status']);
                }
                //set new key so it is not used anywhere but where we want it
                $select[$key]['client_status_display'] =  $select[$key]['client_status'];
                
                if (in_array($dependent['term_type'],$this->displayableTermTypes))
                {
                    $select[$key]['client_status_display'] .= " ({$dependent['term_type']})";
                }
                
            }
            return $select;
        }

        return false;
    }

    public function getDependentsIDs()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("select id from dependents where employee_id = :employee_id",
                    array(':employee_id' => $this->id));

        // query okay
        if ($select)
        {
            // flatten
            foreach ($select as $row)
            {
                $result[] = $row['id'];
            }

            return $result;
        }

        return false;
    }

    public function getFilteredDependents()
    {
        $dependents = $this->getDependents();

        // loop over dependents
        foreach ($dependents as $dep => $dependent)
        {
            if (strtolower($dependent['client_status']) == 'client update')
            {
                unset($dependents[$dep]);
            }
        }

        return $dependents;
    }

    public function getDependent($did)
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRow("select * from dependents where id = :dependent_id and employee_id = :employee_id",
                    array(':dependent_id' => $did, ':employee_id' => $this->id));

        return $select;
    }

    public function getDependentStatus($did)
    {
        //connect to database
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getVal("select client_status from dependents where id = :dependent_id and employee_id = :employee_id",
                    array(':dependent_id' => $did, ':employee_id' => $this->id));

        return $select ? $select : '';
    }

    public function getPoeInactiveReason()
    {
        return $this->poe_inactive_reason;
    }

    public function getClientValue($number)
    {
        if (is_numeric($number))
        {
            $db     = new Model($this->dbip, $this->dbname);

            switch($number)
            {
            case '1';
                return $db->getVal("select client_value_1 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '2';
                return $db->getVal("select client_value_2 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '3';
                return $db->getVal("select client_value_3 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '4';
                return $db->getVal("select client_value_4 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '5';
                return $db->getVal("select client_value_5 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '6';
                return $db->getVal("select client_value_6 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '7';
                return $db->getVal("select client_value_7 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '8';
                return $db->getVal("select client_value_8 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '9';
                return $db->getVal("select client_value_9 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            case '10';
                return $db->getVal("select client_value_10 from employees where id = :employee_id",
                    array(':employee_id' => $this->id));
                break;
            }
        }
    }
    /*
     * Employee Checks
     */

    public function isPoe()
    {
        if (!empty($this->poe_status) && $this->poe_start_date != '0000-00-00')
        {
            return true;
        }
        return false;
    }

    public function isActivePoe()
    {
        # Poe user isn't part of poe project
        if ($this->project_type != 'POE')
        {
            $this->poe_inactive_reason = 'User is a poe but not in a poe project.';
            return false;
        }

        # Verify not before start
        if (strtotime($this->poe_start_date) > date('U'))
        {
            $this->poe_inactive_reason = 'User attempted sign in before start date.';
            return false;
        }

        # Test for stop date
        if ((!empty($this->poe_stop_date) && $this->poe_stop_date != '0000-00-00')
            && strtotime($this->poe_stop_date . '23:59:59') <= date('U'))
        {
            $this->poe_inactive_reason = 'User attempted sign in after stop date.';
            return false;
        }

        # Verify active
        if ($this->poe_status != 'Active')
        {
            $this->poe_inactive_reason = 'User attempted sign in before activation.' . $this->poe_status;
            return false;
        }
        return true;
    }

    public function inAmnesty()
    {
        if ($this->amnesty == 1)
        {
            if ($this->status == 'amnesty')
            {
                return true;
            }
        }

        return false;
    }

    public function isActivated()
    {
        //connect to audit database
        $db     = new Model($this->dbip, $this->dbname);
        $select = $db->getRow("select email_portal, email_portal_status 
                                      from employees 
                                      where force_void <> '1' 
                                        and id = :employee_id",
                    array(':employee_id'=>$this->id));

        if (!empty($select['email_portal'])
            && (stristr($select['email_portal_status'], 'A')
                || stristr($select['email_portal_status'], 'P')))
        {
            return true;
        }
        return false;

    }

    // isSmsActivated added
    public function isSmsActivated()
    {
        //connect to audit database
        $db     = new Model($this->dbip, $this->dbname);
        $select = $db->getRow("select sms,sms_portal_key,sms_portal_status 
                                      from employees 
                                      where force_void <> '1' 
                                        and id = :employee_id",
                    array(':employee_id'=>$this->id));

        if (isset($select['sms']) && (stristr($select['sms_portal_status'], 'A')))
        {
            return true;
        }

        return false;
    }

    public function isGreen()
    {
        if ($this->isActivated())
        {
            //connect to audit database
            $db     = new Model($this->dbip, $this->dbname);
            $select = $db->getRow("select email_portal_status 
                                         from employees 
                                         where force_void <> '1' 
                                         and id = :employee_id",
                array(':employee_id'=>$this->id));

            if (stristr($select['email_portal_status'], '1'))
            {
                return true;
            }
        }
        return false;
    }

    public function hasEmail()
    {
        return isset($this->email) ? $this->email : false;
    }

    public function hasPortalEmail()
    {
        return isset($this->portal_email) ? $this->portal_email : false;
    }

    public function hasCompanyEmail()
    {
        return isset($this->company_email) ? $this->company_email : false;
    }

    public function hasPassword()
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getVal("select password 
                                      from employees 
                                      where force_void <> '1' 
                                        and id = :employee_id",
            array(':employee_id'=>$this->id));

        return (!empty($select) ? true : false);
    }

    public function getPassword()
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getVal("select password 
                                      from employees 
                                      where force_void <> '1' 
                                        and id = :employee_id",
            array(':employee_id'=>$this->id));

        return $select;
    }

    public function hasReinstatement()
    {
        //connect to database
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getVal("select 1 from dependents where employee_id = :employee_id and drop_status like '%reinstatement%'",
            array(':employee_id' => $this->id));

        return ($select ? true : false);
    }

    /*
     * Employee Setters
     */

    public function setGreenEnabled()
    {
        //connect to audit database
        $db     = new Model($this->dbip, $this->dbname);
        $oldval = $db->getVal("select email_portal_status 
                                      from  employees 
                                      where force_void <> '1'  
                                        and id = :employee_id",
            array(':employee_id'=>$this->id));

        $update = $db->q("update employees set email_portal_status = CONCAT(REPLACE(email_portal_status,'1',''),'1') where id = :employee_id",
            array(':employee_id'=>$this->id));

        if ($update)
        {
            $newval = $db->getVal("select email_portal_status 
                                          from  employees 
                                          where force_void <> '1'  
                                            and id = :employee_id",
                array(':employee_id'=>$this->id));

            logInBoundAuditDB($this->dbip, $this->dbname, $this->id, 0, "email_portal_status: $oldval => $newval");

            return true;
        }

        return false;

    }

    public function setGreenDisabled()
    {
        //connect to database
        $db     = new Model($this->dbip, $this->dbname);
        $oldval = $db->getVal("select email_portal_status 
                                      from  employees 
                                      where force_void <> '1' 
                                        and id = :employee_id",
            array(':employee_id'=>$this->id));

        $update = $db->q("UPDATE employees 
                                 SET email_portal_status = REPLACE(email_portal_status,'1','') 
                                 WHERE force_void <> '1' 
                                   and id = :employee_id",
            array(':employee_id'=>$this->id));

        if ($update)
        {
            $newval = $db->getVal("SELECT email_portal_status 
                                          FROM employees 
                                          WHERE force_void <> '1' 
                                            and id = id = :employee_id",
                array(':employee_id'=>$this->id));

            logInBoundAuditDB($this->dbip, $this->dbname, $this->id, 0, "email_portal_status: $oldval => $newval");

            return true;
        }
        return false;
    }

    public function setValidationKey($key)
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        $update = $db->q("update employees 
                                 set email_portal_key = :validation_key 
                                 where force_void <> '1' 
                                   and id = :employee_id",
                    array(':validation_key' => $key,
                          ':employee_id'    => $this->id));

        return($update->rowCount() ? true : false);
    }

    /*
     *  Flagging password to reset
     */
    public function setPasswordToReset()
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        $update = $db->q("update employees 
                                 set password = :password 
                                 where force_void <> '1' 
                                   and id = :employee_id",
            array(':password' => 'reset',
                ':employee_id'    => $this->id));

        return($update->rowCount() ? true : false);
    }

    public function setEmpAmnesty()
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        //update employees table
        $db->q("update employees 
                       set amnesty_mail_received = '1', amnesty_signed = '1' 
                       where force_void <> '1' 
                         and id = :employee_id",
            array(':employee_id'=>$this->id));
        $db->q("insert into `log` (`date`, employee_id, log_type, msg) 
                values(now(), :employee_id, 0, 'amnesty_mail_received => 1')",
            array(':employee_id'=>$this->id));
        $db->q("insert into `log` (`date`, employee_id, log_type, msg) 
                values(now(), :employee_id, 0, 'amnesty_signed => 1')",
            array(':employee_id'=>$this->id));
        $db->q("insert into `log` (`date`, employee_id, log_type, msg) 
                values(now(), :employee_id, 3, 'Portal amnesty processed')",
            array(':employee_id'=>$this->id));

        //update central db log_inbound table
        logInBound($uid = null, $this->id, $this->projectid, 'A');

        //recalculate employee status
        if ($this->RocheC2V())
        {
            $recalc = DC4_PROCESS_URL . '?restatus_remote='.$this->id;
        }
        else
        {
            $recalc = RECALC_URL . $this->id;
        }
        remote_call($recalc);
    }

    public function setDepAmnesty($id, $date)
    {
        //connect to database
        $db = new Model($this->dbip, $this->dbname);

        //update dependents table
        $update = $db->q("update dependents set term_type = 'amnesty phase', term_date = :date
                            where id = :dependent_id and employee_id = :employee_id ",
                    array(  ':date'         => $date,
                            ':dependent_id' => $id,
                            ':employee_id'  => $this->id));

        if ($update)
        {
            $insert = $db->q("INSERT INTO `log` (`date`, employee_id, log_type, msg) 
                                VALUES (now(), :employee_id, 0, :msg)",
                        array(':employee_id' => $this->id,
                              ':msg'         => "dep{$id}[term_type]: => amnesty phase"));

            if ($insert)
            {
                $db->q("insert into `log` (`date`, employee_id, log_type, msg)
                          values(now(), :employee_id, 0, :msg)" ,
                    array(':employee_id' => $this->id,
                          ':msg'         => "dep{$id}[term_date]: => {$date}"));
            }
        }
    }

    public function setEmpVerification()
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        //update employees table
        $db->q("update employees 
                       set mail_received = '1', signed = '1' 
                       where force_void <> '1'  
                         and id = :employee_id",
            array(':employee_id'=>$this->id));
        $db->q("insert into `log` (`date`, employee_id, log_type, msg) 
                  values(now(), :employee_id, 0, 'mail_received => 1')",
            array(':employee_id'=>$this->id));
        $db->q("insert into `log` (`date`, employee_id, log_type, msg) 
                  values(now(), :employee_id, 0, 'signed => 1')",
            array(':employee_id'=>$this->id));
        $db->q("insert into `log` (`date`, employee_id, log_type, msg) 
                  values(now(), :employee_id, 3, 'Portal verification processed')",
            array(':employee_id'=>$this->id));

        //update central db log_inboud table
        logInBound($uid = null, $this->id, $this->projectid, 'O');

        //recalculate employee status
        $recalc = RECALC_URL . $this->id . '&pendingDocuments=1';
        remote_call($recalc);


    }

    public function setDepVerification($dependent_id, $type, $date)
    {
        //connect to database
        $db = new Model($this->dbip, $this->dbname);

        //update dependents table
        $update = $db->q("update dependents set term_type = :type, term_date = :date
                            where id = :dependent_id and employee_id = :employee_id",
            array(  ':type'         =>  $type,
                    ':date'         =>  $date,
                    ':dependent_id' =>  $dependent_id,
                    ':employee_id'  =>  $this->id));

        if ($update)
        {
            $insert = $db->q("insert into `log` (`date`, employee_id, log_type, msg)
                                values(now(), :employee_id, 0, :msg)",
                        array(':employee_id'  =>  $this->id,
                              ':msg'          =>  "dep{$dependent_id}[term_type]: => {$type}"));

            if ($insert)
            {
                $db->q("insert into `log` (`date`, employee_id, log_type, msg)
                          values(now(), :employee_id, 0, :msg)",
                        array(':employee_id'  =>  $this->id,
                              ':msg'          =>  "dep{$dependent_id}[term_date]: => {$date}"));
            }
        }
    }

    public function setDepSSN($id, $ssn = '')
    {
        //connect to database
        $db = new Model($this->dbip, $this->dbname);

        $check = $db->getRow("select collected_ssn from dependents 
                                where id = :dependent_id and employee_id = :employee_id",
            array(':dependent_id' => $id,
                  ':employee_id'  => $this->id));

        //update dependents table
        if ($check['collected_ssn'] == '')
        {
            $ssn = str_replace('-', '', $ssn);
            $db->q("update dependents set collected_ssn = :ssn 
                    where id = :dependent_id and employee_id = :employee_id",
                array(':ssn'          => $ssn,
                      ':dependent_id' => $id,
                      ':employee_id'  => $this->id));
        }
    }

    public function setEmailKey($key)
    {
        //connect to central database
        $db = new Model();

        $db->q("update employees set email_portal = :email_key where id = :employee_id",
            array(':email_key'   => $key,
                  ':employee_id' => $this->id));
    }

    public function setEmailActive()
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        if (!$this->isActivated())
        {
            $update = $db->q("UPDATE employees 
                                     SET email_portal_status = CONCAT(REPLACE(email_portal_status,'A',''),'A') 
                                     WHERE force_void <> '1' 
                                       and id = :employee_id",
                            array(':employee_id'=>$this->id));
            if ($update)
            {
                return true;
            }
        }
        return false;
    }

    public function canProcessAmnesty()
    {
        //connect to database
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getVal("select 1 from employees where amnesty_mail_received != '1' and id = :employee_id",
                        array(':employee_id'=>$this->id));

        return ($select ? true : false);
    }

    public function verifyPassword($password)
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        $password = $db->getVal("SELECT password 
                                        FROM employees 
                                        WHERE force_void <> '1' 
                                          AND id = :employee_id 
                                          AND `password` = :hash",
            array(':employee_id'     => $this->id,
                  ':hash'        => $db->hashPassword($password)));

        if ($password)
        {
            return true;
        }
        else
        {
            // log this failure
            $db = new Model();
            $db->q("INSERT INTO log_aos_auth_failure (`date`, `system`, `user_id`) 
                      SELECT NOW(), 'M', :employee_id",
                array(':employee_id'=>$this->id));
            return false;
        }
    }

    public function createPassword($password)
    {
        //connect to database
        $db = new Model($this->dbip, $this->dbname);

        $update = $db->q("update employees set `password` = :hashed_password where id = :employee_id",
                        array(':hashed_password' => $db->hashPassword($password),
                              ':employee_id'     => $this->id));

        return ($update ? true : false);
    }

    public function addEmail($email)
    {
        //connect to central database
        $db     = new Model();
        $update = $db->q("update employees 
                                 set email_portal = :email 
                                 where id = :employee_id",
            array(':email'       => $email,
                  ':employee_id' => $this->id));

        if ($update)
        {
            //connect to audit database
            $db     = new Model($this->dbip, $this->dbname);
            $update = $db->q("update employees 
                                       set email_portal = :email 
                                     where force_void <> '1' 
                                       and id = :employee_id",
                array(':email'       => $email,
                      ':employee_id' => $this->id));

            $this->email = $email;

            if ($update)
            {
                return true;
            }
        }
        return false;
    }

    public function setSms($sms)
    {
        //connect to audit database
        $db     = new Model($this->dbip, $this->dbname);
        $update = $db->q("update employees 
                                    set sms = :sms 
                                  where force_void <> '1' 
                                    and id = :employee_id",
            array(':sms'         => $sms,
                  ':employee_id' => $this->id));

        if ($update)
        {
            $this->sms = $sms;

            return true;
        }

        return false;
    }

    public function setSmsKey($sms_key)
    {
        //connect to audit database
        $db     = new Model($this->dbip, $this->dbname);
        $update = $db->q("update employees 
                                   set sms_portal_key = :sms_key 
                                 where force_void <> '1' 
                                   and id = :employee_id",
            array(':sms_key'        => $sms_key,
                  ':employee_id'    => $this->id));

        if ($update)
        {
            $this->sms_key = $sms_key;

            return true;
        }

        return false;
    }

    public function setSmsActive()
    {
        $db     = new Model($this->dbip, $this->dbname);
        $update = $db->q("update employees 
                                    set sms_portal_status = 'A' 
                                 where force_void <> '1' 
                                   and id = :employee_id",
                    array(':employee_id' => $this->id));

        if ($update)
        {
            $this->sms_active = 'A';
            $db->q("INSERT INTO `log` (`date`, employee_id, log_type, msg) 
                      VALUES(now(), :employee_id, 3, 'Employee has enabled SMS')",
                array(':employee_id' => $this->id));

            return true;
        }

        return false;
    }


    public function updateAddress($address1, $address2, $city, $state, $zip, $phone)
    {
        $db = new Model();

        // cancel old changes
        $update = $db->q("UPDATE pending_addr_updates SET deleted=1 
                            WHERE eid=:employee_id AND deleted=0
                            AND date_applied = '0000-00-00 00:00:00'",
            array(':employee_id' => $this->id));

        // insert updated record
        $update = $db->q("insert into pending_addr_updates (`eid`,`street`,`street2`,`city`,`state`,`zip`,`phone`,`date_rec`)
                            values (:employee_id, :address_1, :address_2, :city, :state, :zip, :phone, now())",
            array(':employee_id' => $this->id,
                  ':address_1'   => $address1,
                  ':address_2'   => $address2,
                  ':city'        => $city,
                  ':state'       => $state,
                  ':zip'         => $zip,
                  ':phone'       => $phone));

        return ($update ? true : false);
    }


    /*
     * Hooks to see if documents exists
     */

    public function chk4Document($log_id, $link)
    {
        if (!empty($link) || $this->getPersistentRecord($log_id))
        {
            return true;
        }

        return false;
    }

    public function chk4VerificationLetter($log_id, $link)
    {
        return $this->chk4Document($log_id, $link);
    }

    public function chk4AmnestyLetter($log_id, $link)
    {
        return $this->chk4Document($log_id, $link);
    }

    public function chk4PartialLetter($log_id, $date, $link)
    {
        if (!empty($link) || $this->getPersistentRecord($log_id))
        {
            return true;
        }
        else
        {
            $db     = new Model($this->dbip, $this->dbname);
            $select = $db->getRow("SELECT id, msg, worked, assigned FROM q_partial_mail 
                                    WHERE employee_id = :employee_id AND user_id != '0' 
                                    AND DATE(worked) between cast(subdate(:doc_date, interval 7 day) as date) and :doc_date",
                array(':employee_id' => $this->id,
                      ':doc_date'    => $date));

            if ($select)
            {
                return true;
            }
        }

        return false;
    }

    public function chk4TermConfirmation($log_id, $date, $link)
    {
        if (!empty($link) || $this->getPersistentRecord($log_id))
        {
            return true;
        }
        else
        {
            $db     = new Model($this->dbip, $this->dbname);
            $select = $db->getRow("select termed, validated from q_complete_term 
                                     WHERE employee_id = :employee_id 
                                     AND DATE(worked) between cast(subdate(:doc_date, INTERVAL 7 DAY) AS DATE) AND :doc_date",
                array(':employee_id' => $this->id,
                      ':doc_date'    => $date));
            if ($select)
            {
                return true;
            }
        }

        return false;
    }

    public function chk4FinalNotice($log_id, $link)
    {
        return $this->chk4Document($log_id, $link);
    }

    public function chk4ExtensionNotice($log_id, $link)
    {
        return $this->chk4Document($log_id, $link);
    }

    public function chk4FinalTermNotice($log_id, $link)
    {
        return $this->chk4Document($log_id, $link);
    }

    /*
     * Return document content
     */

    private function getPersistentRecordContent($persistent_id, $url_only = false)
    {
        if ($persistent_id && is_numeric($persistent_id))
        {
            $url = CMS_URL . $this->id . ".pdf?action=mailer&hash=" . hash('sha256', date('U') . 'S@L|' . $this->id)
                . "&persist_id={$persistent_id}&eid={$this->id}";

            if ($url_only == false)
            {
                return remote_call($url);
            }
            else
            {
                return $url;
            }
        }
        return false;
    }

    private function getAvailableTemplates()
    {
        if (empty($this->templates))
        {
            $db        = new Model();
            $templates = $db->getRows("select name from templates where parent_type = 'template' 
                                        and deleted='0000-00-00' and visibility >= 3;");
            if (!empty($templates))
            {
                foreach ($templates as $template)
                {
                    $this->templates[] = $template['name'];
                }
            }
        }
        return $this->templates;
    }

    public function getPersistentRecord($log_id)
    {
        $db        = new Model($this->dbip, $this->dbname);
        $logRecord = $db->getRow("SELECT `msg`, `date` FROM `log` WHERE `employee_id` = :employee_id 
                                     AND `id` = :log_id AND `log_type` = 3",
            array(':employee_id' => $this->id,
                  ':log_id'      => $log_id));

        if ($logRecord)
        {
            $availableTemplates = $this->getAvailableTemplates();
            $log_message        = $logRecord['msg'];
            $log_date           = $logRecord['date'];

            $persist = $db->getRow("SELECT id, template FROM mailer_persisted 
                                      WHERE eid = :employee_id AND project_id = :project_id 
                                      AND log_date = :log_date AND CONCAT(cki_note,' (',mail_date,')') = :log_message",
                          array(':employee_id' => $this->id,
                                ':project_id'  => $this->getProjectID(),
                                ':log_date'    => $log_date,
                                ':log_message' => $log_message ));

            if (is_array($persist)
                && count($persist) >= 1
                && isset($persist['template'])
                && is_array($availableTemplates)
            )
            {
                if (in_array($persist['template'], $availableTemplates))
                {
                    return $persist['id'];
                }
                else
                {
                    logError("Missing template data", "For template " . $persist['template']);
                }
            }
        }
        return false;
    }

    public function getMailerPDF($id, $eid)
    {
        $db        = new Model($this->dbip, $this->dbname);
        $logRecord = $db->getRow("SELECT link FROM log WHERE employee_id = :employee_id 
                                    AND `id` = :log_id AND log_type=3",
            array(':employee_id' => $this->id,
                  ':log_id'      => $id));
        if ($logRecord)
        {
            // given /mailer.php?id=19252&eid=1822446
            // returns 19252
            $link = $this->getMailerLink($logRecord['link']);

            if ($link)
            {
                if (method_exists($_SESSION['user'], 'getMailer'))
                {
                    $mailerData = $_SESSION['user']->getMailer($link, $this->id);
                }
                else
                {
                    $this->employee = new EmployeeModel($eid, true);
                    $mailerData     = $this->employee->getMailer($link, $this->id);
                }

                if ($mailerData)
                {
                    return $mailerData;
                }
            }
        }

        return false;
    }

    public function getVerificationContent($id, $eid, $url_only = false)
    {
        $mailerData = $this->getMailerPDF($id, $eid);

        if (!$mailerData)
        {
            $persistent_id = $this->getPersistentRecord($id);
            $mailerData    = $this->getPersistentRecordContent($persistent_id, $url_only);
        }

        if ($url_only == true)
        {
            // this should be a link
            return $mailerData;
        }

        if (isPDF($mailerData))
        {
            return $mailerData;
        }

        return false;
    }

    public function getAmnestyContent($id, $eid)
    {
        $mailerData = $this->getMailerPDF($id, $eid);
        if (!$mailerData)
        {
            $persistent_id = $this->getPersistentRecord($id);
            $mailerData    = $this->getPersistentRecordContent($persistent_id);
        }

        if (isPDF($mailerData))
        {
            return $mailerData;
        }

        return false;
    }

    public function getPartialContent($id, $eid)
    {
        $mailerData = $this->getMailerPDF($id, $eid);
        if (!$mailerData)
        {
            $persistent_id = $this->getPersistentRecord($id);
            $mailerData    = $this->getPersistentRecordContent($persistent_id);
        }

        if (isPDF($mailerData))
        {
            return $mailerData;
        }
        else
        {
            //connect to audit database
            $db = new Model($this->dbip, $this->dbname);

            // look in q_partial_mail for eid most recent not void record
            //$select = $db->q("select msg, worked, assigned from q_partial_mail where employee_id = '{$this->id}' and user_id != '0' and date(worked) between cast(subdate('{$logRecord['date']}', interval 7 day) as date) and '{$logRecord['date']}' limit 1");
            $select = $db->getRow("SELECT msg FROM q_partial_mail 
                                WHERE employee_id = :employee_id 
                                AND worked='0000-00-00 00:00:00' LIMIT 1;",
                array(':employee_id' => $eid));

            if ($select && isset($select['msg']))
            {
                return $select['msg'];
            }
        }

        return false;
    }
// stopped here
    public function getTermConfirmationContent($id, $eid)
    {
        $mailerData = $this->getMailerPDF($id, $eid);
        if (!$mailerData)
        {
            $persistent_id = $this->getPersistentRecord($id);
            $mailerData    = $this->getPersistentRecordContent($persistent_id);
        }

        if (isPDF($mailerData))
        {
            return $mailerData;
        }
        else
        {
            //connect to audit database
            $db        = new Model($this->dbip, $this->dbname);
            $logRecord = $db->getRow("select date from log 
                                    where employee_id = :employee_id 
                                    and `id` = :log_id and log_type=3",
                            array(':employee_id' => $this->id,
                                  ':log_id'      => $id));

            if ($logRecord)
            {
                $select = $db->getRow("select termed, validated from q_complete_term 
                                   where employee_id = :employee_id and user_id != '0'  
                                   and date(worked) between cast(subdate(:log_date, interval 7 day) as date) 
                                   and :log_date limit 1",
                            array(':employee_id' => $this->id,
                                  ':log_date'    => $logRecord['date']));

                if (!empty($select))
                {
                    return $select;
                }
            }
        }

        return false;
    }

    public function getFinalNoticeContent($id, $eid)
    {
        $mailerData = $this->getMailerPDF($id, $eid);
        if (!$mailerData)
        {
            $persistent_id = $this->getPersistentRecord($id);
            $mailerData    = $this->getPersistentRecordContent($persistent_id);
        }

        if (isPDF($mailerData))
        {
            return $mailerData;
        }

        return false;
    }

    public function getFinalTermNoticeContent($id, $eid)
    {
        $mailerData = $this->getMailerPDF($id, $eid);
        if (!$mailerData)
        {
            $persistent_id = $this->getPersistentRecord($id);
            $mailerData    = $this->getPersistentRecordContent($persistent_id);
        }

        if (isPDF($mailerData))
        {
            return $mailerData;
        }

        return false;
    }

    public function getExtensionNoticeContent($id, $eid)
    {
        $mailerData = $this->getMailerPDF($id, $eid);
        if (!$mailerData)
        {
            $persistent_id = $this->getPersistentRecord($id);
            $mailerData    = $this->getPersistentRecordContent($persistent_id);
        }

        if (isPDF($mailerData))
        {
            return $mailerData;
        }

        return false;
    }

    public function getPostcard()
    {
        //connect to audit database
        $db     = new Model($this->dbip, $this->dbname);
        $select = $db->getVal("select count(*) as total from employees 
                                      where id = :employee_id and project_id = :project_id
                                        and force_void <> '1'
                                        and status = 'complete' and substatus != 'pending postcard'",
            array(':employee_id' => $this->id,
                  ':project_id'  => $this->getProjectID()));

        return ($select == 1 ? true : false);
    }

    public function getUpload($id)
    {
        $upload = array();

        $db     = new Model($this->dbip, $this->dbname);
        $select = $db->getRow("select id, tbl, file_name from scans 
                                where id = :scan_id and eid = :employee_id",
            array(':scan_id' => $id,
                  ':employee_id' => $this->getID()));

        if ($select)
        {
            $upload['file_name'] = $select['file_name'];

            $select = $db->getVal("select file_data from {$select['tbl']} 
                                    where id = :scan_id",
                array(':scan_id' => $id));

            if ($select)
            {
                $upload['file_data'] = $select;

                return $upload;
            }
        }

        return false;
    }

    public function getDocuments()
    {
        $documents = array();

        $db = new Model($this->dbip, $this->dbname);
        $select = $db->getRows(
            "select `id` as 'log_id', `date` as 'log_date', `msg` as 'log_message', `link` from (
            select * from `log`
                where employee_id = :employee_id
                and log_type = '3'
                and msg regexp '[[:<:]]Amnesty|Amnesty Term Confirmation|Verification|Notification|No Response Final|Partial Response Final|Partial|Term Confirmation|Final Notice|Extension Notice|Final Term Notice|Postcard[[:>:]]'
                and msg NOT LIKE '%email%'
                order by id asc
              ) as table1
            group by REPLACE(REPLACE(msg,'sent ',''),'generated ','')
            order by `date` asc",
        array(':employee_id' => $this->getID()));

        if ($select)
        {
            foreach ($select as $document)
            {
                if (preg_match("/\s(sent|generated)\s\(\d{4}\-\d{2}\-\d{2}\)$/i", $document['log_message']))
                {
                    preg_match(
                        "/^(.*)\s(sent|generated)\s(\(\d{4}\-\d{2}\-\d{2}\))$/i", $document['log_message'], $matches
                    );

                    $type = $matches[1];
                    $date = preg_replace("/(\(|\))/", "", $matches[3]);
                    $link = $this->getMailerLink($document['link']);

                    if ($this->isGreen() || $this->isPoe()
                        || date('U') >= strtotime($date)
                    )
                    {
                        array_push(
                            $documents, array(
                            "date_sent"   => $date,
                            "doc_type"    => $type,
                            "log_id"      => $document['log_id'],
                            "log_message" => $document['log_message'],
                            "log_date"    => $document['log_date'],
                            "link"        => $link
                        )
                        );
                    }
                }
            }

            // partial notice hook
            if ($this->status == 'partial'
                && $this->getSubStatus() == 'pending mail'
            )
            {
                // look in q_partial_mail for eid most recent not void record
                $select = $db->getRow("SELECT * FROM q_partial_mail
                                    WHERE employee_id = :employee_id
                                    AND worked='0000-00-00 00:00:00' AND user_id=0 LIMIT 1;",
                    array(':employee_id' => $this->getID()));
                if ($select && isset($select['id']))
                {
                    array_push(
                        $documents, array(
                            "date_sent"   => $select['date'],
                            "doc_type"    => 'Partial',
                            "log_id"      => 0,
                            "log_message" => '',
                            "log_date"    => $select['date'],
                            "link"        => ''
                        )
                    );
                }
            }
        }
        return $documents;
    }

    public function getDocumentLinksForDisplay($linkController = 'documents', $pdfGeneration = true)
    {
        $links = array();
        $dupCheck = array();
        $docs  = $this->getDocuments();

        if ($docs)
        {
            foreach ($docs as $doc)
            {
                $date   = $doc['date_sent'];
                $type   = getDocCode($doc['doc_type']);
                #Retain initial type setting for dup loop
                $typeDupCheck = $type;
                $link   = $doc['link'];
                $log_id = $doc['log_id'];
                $deliveryMethod = 'Document Sent';
                $log_date = $doc['log_date'];
                if(stripos($doc['log_message'],'Generated'))
                {
                    $deliveryMethod = 'Document Posted';    
                }

//              $log_message= $doc['log_message'];

                # Globally disabled or project disabled
                if (PDF_DOWNLOADS
                    && $pdfGeneration
                )
                {
                    if ($type == "Amnesty Letter")
                    {
                        if ($this->chk4AmnestyLetter($log_id, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/amnesty/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Notification Letter"
                        || $type == "Notification Letter 1"
                        || $type == "Notification Letter 2"
                        || $type == "Notification Letter 3")
                    {
                        if ($this->chk4VerificationLetter($log_id, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/notification/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Verification Letter"
                        || $type == "Verification Letter 1"
                        || $type == "Verification Letter 2"
                        || $type == "Verification Letter 3"
                        || $type == "Verification Letter 4"
                    )
                    {
                        if ($this->chk4VerificationLetter($log_id, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/verification/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Partial Notice")
                    {
                        if ($this->chk4PartialLetter($log_id, $date, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/partial/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Final Notice")
                    {
                        if ($this->chk4FinalNotice($log_id, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/finalnotice/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Extension Notice")
                    {
                        if ($this->chk4ExtensionNotice($log_id, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/extensionnotice/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Final Term Notice")
                    {
                        if ($this->chk4FinalTermNotice($log_id, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/finaltermnotice/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Term Confirmation")
                    {
                        if ($this->chk4TermConfirmation($log_id, $date, $link))
                        {
                            $type
                                = "<a href=\"/$linkController/term/{$log_id}/{$this->getID()}\" rel=\"external\" target=\"new\" title=\""
                                . text($type) . "\">" . text($type) . "</a>";
                        }
                    }
                    elseif ($type == "Postcard")
                    {
                        $type = "<a href=\"/$linkController/postcard\" rel=\"external\" target=\"new\" title=\"" . text(
                                $type
                            ) . "\">" . text($type) . "</a>";
                    }
                }

                # Prepping for array check --- prevents POE paper mailers from generating two entries
                $mailerCheckFromLog = array($typeDupCheck,$log_date);

                if(!in_array($mailerCheckFromLog, $dupCheck))
                {
                        $docarray = array(
                            'date'       => date('m/d/Y', strtotime($doc['date_sent'])),
                            'type'       => $deliveryMethod,
                            'type_label' => $deliveryMethod,
                            'detail'     => $type
                        );

                        array_push($links, $docarray);

                        #record entry to compare on next iteration
                        array_push($dupCheck, $mailerCheckFromLog);
                }
            }
        }

        return $links;
    }

    public function getMailerFileName($mid)
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRow("select file_name from mailers where id = :mailer_id",
            array(':mailer_id' => $mid));

        return ($select ? $select['file_name'] : false);
    }

    public function getMailerLink($message)
    {
        $link = false;

        if (stristr($message, 'mailer.php'))
        {
            preg_match("/\?id=[0-9]*/i", $message, $link_match);
            if (isset($link_match[0]))
            {
                $link = substr($link_match[0], 4);
            }
        }

        return $link;
    }

    public function getMailer($mid, $eid = 0)
    {
        //connect to audit database
        $db = new Model($this->dbip, $this->dbname);


        if ($eid != 0)
        {
            if ($this->id != $eid)
            {
                return false;
            }
            else
            {
                $table = $db->getVal("SELECT tbl FROM mailers WHERE id = :mailer_id 
                                        AND eid = :employee_id",
                    array(':mailer_id'   => $mid,
                          ':employee_id' => $eid));
            }
        }
        else
        {
            $table = $db->getVal("SELECT tbl FROM mailers WHERE id = :mailer_id 
                                AND project_id = :project_id",
                    array(':mailer_id'  => $mid,
                          ':project_id' => $this->projectid));
        }

        if (isset($table)
            && !empty($table))
        {
            $select = $db->getVal("SELECT file_data FROM `{$table}` WHERE id = :mailer_id",
                array(':mailer_id'  => $mid));

            return ($select ? $select : false);
        }

        return false;
    }


    /*
     * Employer Specific
     */

    public function getInboundDocs()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("SELECT id, file_name, date, scan_type from scans 
                            where eid = :employee_id and scan_type in ('M', 'F', 'U','A') 
                            and deleted = 0 and batch != '_orig' ORDER BY DATE DESC",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getInboundDocsEmployer()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("SELECT id, file_name, date, scan_type 
                                  FROM scans WHERE eid = :employee_id 
                                  AND scan_type IN ('A') AND deleted = 0 
                                  AND batch != '_orig' ORDER BY date DESC",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getOutboundDocs()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("SELECT id, file_name from mailers where eid = :employee_id",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getCallsRecvd()
    {
        $db = new Model();

        $select = $db->getRows("SELECT date FROM log_inbound WHERE employee_id = :employee_id AND action = 'C'",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getCallsMade()
    {
        $db = new Model();

        // can add later the call failures, 'RF' and 'FF', if needed
        $select = $db->getRows("SELECT `date`,`action` FROM log_outbound 
                                    WHERE `action` IN ('FC', 'RC') AND employee_id = :employee_id
                                    AND `date` <= ( CURDATE() - INTERVAL 3 DAY ) GROUP BY LEFT(`date`,10),`action`;",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getEmailsRecvd()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("SELECT id, date, subject FROM emails_in 
                            WHERE eid = :employee_id AND deleted != '1'",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getEmailsSent()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("select id, date, subject from emails_out where eid = :employee_id and deleted != '1'",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getPortalLogins()
    {
        $db = new Model();

        $select = $db->getRows("select date from log_inbound where employee_id = :employee_id and action in ('P','MP')",
            array(':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getIssueIDs()
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRows("select id, project_id, plan_id from portal_issue_log 
                                  where project_id = :project_id and employee_id = :employee_id",
            array(':project_id' => $this->projectid,
                  ':employee_id' => $this->id));

        return ($select ? $select : false);
    }

    public function getLastCallTaken()
    {
        $db = new Model($this->dbip, $this->dbname);

        $date = $db->getVal("select max(date) from log 
                                where employee_id = :employee_id
                                and log_type = 3 and msg = 'Call Taken'",
            array(':employee_id' => $this->id));

        return $date;
    }

    public function getEmailInfo($id, $eid, $table)
    {
        $db = new Model($this->dbip, $this->dbname);

        $select = $db->getRow("select `from`, `to`, subject, body from {$table} where id = :id and eid = :employee_id",
            array(':id'          => $id,
                  ':employee_id' => $eid  ));

        return ($select ? $select : false);
    }


    /*
     * Static Methods
     */

    public static function isLocked($id, $failures = 5 /*attempts*/, $window = 30 /*minutes*/)
    {    // Checks to see if this EID is currently locked out due to $failures failures in the last $window minutes
        $rv = false;
        if (EmployeeModel::Exists($id))
        {
            $db = new Model();
            // Clean out old entries (to keep the table from growing too big).
            $db->q("delete from log_aos_auth_failure WHERE system='M' AND date < DATE_SUB(NOW(), INTERVAL 120 MINUTE)");
            // Check for failed logins
            $result = $db->getVal("SELECT count(*) as c FROM log_aos_auth_failure 
                                WHERE user_id=:user_id AND system='M' 
                                AND date > DATE_SUB(NOW(), INTERVAL :window MINUTE)",
                array(':user_id' => $id,
                      ':window' => $window));

            if ($result >= $failures)
            {
                $rv = true;
            }
        }

        return $rv;
    }

    public static function isIPLocked($ip, $failures = 100, $window = 5)
    {
        $isLocked = false;

        // ip block, cookie is used for pen testing to disable lockout
        if (!USE_IPBLOCK
            || isset($_COOKIE['speakEasy'])
        )
        {
            return false;
        }

        $db = new Model();

        // Clean out old entries (to keep the table from growing too big).
        $db->q("delete from log_aos_ips WHERE date < DATE_SUB(NOW(), INTERVAL 120 MINUTE)");

        $result = $db->getVal("select count(*) as count from log_aos_ips where ip = :ip and date > date_sub(now(), interval :window minute)",
            array(':ip' => $ip,
                  ':window' => $window));

        if ($result >= $failures)
        {
            $isLocked = true;
            sendMail(EMAIL_ERROR_TO, APP_TITLE . " - IP Blocked Warning",
                "The IP address <strong>{$ip}</strong> was blocked. The address has been blocked for 5 mintues.");
        }

        return $isLocked;
    }

    public static function authenticate($id, $dob)
    {
        //connect to central database
        $db = new Model();

        $select = $db->getRow("select projects.id as id, plan_id, projects.status as status, db, db_ip
                                  from employees
                                  left join projects on employees.project_id = projects.id
                                  where employees.id = :employee_id and dob = :dob",
            array(':employee_id' => $id,
                  ':dob'         => $dob));

        if ($select
            && is_numeric($select['id']))
        {
            // always cache the closed message, for inactive projects this will immediately jump to the closed page
            // for poe clients they will authenticate but then get redirected to close later.

            $auditDb = new Model($select['db_ip'], $select['db']);

             if ($select['plan_id'] != 0)
            {
                $project = $auditDb->getRow("select ep_closed_header, ep_closed_message from plans
                                                    where project_id = :project_id AND id = :plan_id",
                                array(':project_id' => $select['id'],
                                      ':plan_id'    => $select['plan_id']));
            }

            if (!isset($project)
                || empty($project['ep_closed_header'])
            )
            {
                $project = $auditDb->getRow("select ep_closed_header, ep_closed_message 
                                                from projects where id = :project_id",
                                array(':project_id' => $select['id']));
            }

            if (!empty($project['ep_closed_header']))
            {
                $_SESSION['closedHeader'] = $project['ep_closed_header'];
            }

            if (!empty($project['ep_closed_message']))
            {
                $_SESSION['closedMessage'] = $project['ep_closed_message'];
            }

            if ($select['status'] === 'Active')
            {
                // project is active but on the return it will be checked if the employee is poe and active
                return new EmployeeModel($id);
            }
            else
            {
                return true;
            }
        }
        else
        {
            if (EmployeeModel::Exists($id))
            {
                // Log this failure
                $db->q("INSERT INTO log_aos_auth_failure (`date`, `system`, `user_id`) SELECT NOW(), 'M', :employee_id",
                    array(':employee_id' => $id));
            }
            return false;
        }
    }

    public static function retrieveReference($emp_firstname, $emp_lastname, $emp_dob,
        $dep_firstname, $dep_dob, $emp_street, $emp_zip)
    {
        //connect to central database
        $db = new Model();
        $select = $db->getRows('
            SELECT e.id as eid, d.id as did FROM employees e 
            JOIN dependents d ON d.employee_id=e.id
            JOIN projects p on p.id = e.project_id
            WHERE e.dob = :emp_dob
                  AND p.status NOT IN (\'Archived\',\'Inactive\')
                AND LEFT(e.zip, 5) = :emp_zip
                AND e.street LIKE :emp_street
                AND (
                  (e.name LIKE :emp_firstname_L AND e.name LIKE :emp_lastname_R)
                    OR
                  (e.name LIKE :emp_lastname_L AND e.name LIKE :emp_firstname_R)
                    OR
                  (e.name LIKE :emp_firstname_L AND e.name LIKE :emp_lastname_M)
                    OR
                  (e.name LIKE :emp_lastname_L AND e.name LIKE :emp_firstname_M)
                )
                AND (d.name LIKE :dep_firstname_L OR d.name LIKE :dep_firstname_R OR d.name LIKE :dep_firstname_M);',
            array(':emp_dob'         => $emp_dob,
                  ':emp_zip'          => $emp_zip,
                  ':emp_street'       => "$emp_street %",

                  ':emp_firstname_L'    => "$emp_firstname %",
                  ':emp_firstname_R'    => "% $emp_firstname",
                  ':emp_firstname_M'    => "% $emp_firstname %",

                  ':emp_lastname_L'     => "$emp_lastname %",
                  ':emp_lastname_R'     => "% $emp_lastname",
                  ':emp_lastname_M'     => "% $emp_lastname %",

                  ':dep_firstname_L'     => "$dep_firstname %",
                  ':dep_firstname_R'     => "% $dep_firstname",
                  ':dep_firstname_M'     => "% $dep_firstname %"));

        if ($select)
        {
            # dependent check
            if (count($select) > 1)
            {
                return true;
            }
            elseif (isset($select[0]['eid']))
            {
                $eid = $select[0]['eid'];
                $did = $select[0]['did'];

                # true here allows the model to be created for inactive project, we will check status above this method
                $user = new EmployeeModel($eid, true);

                # validate user model was created
                if (is_object($user))
                {
                    # check the dependent dob
                    $dep = $user->getDependent($did);

                    if (is_array($dep)
                        && $dep['dob'] == $dep_dob)
                    {
                        return $user;
                    }
                }
            }
        }

        return false;
    }

    public static function resetPassword($id, $password)
    {
        //connect to central database
        $db = new Model();

        $user = new EmployeeModel($id);

        $select = $db->getRow("select db, db_ip from projects where id = :project_id",
            array(":project_id" => $user->getProjectID()));

        $db = new Model($select['db_ip'], $select['db']);

        $update = $db->q("update employees set password = :hash where id = :user_id",
                    array(':hash' => $db->hashPassword($password),
                          ':user_id' => $user->getID()));

        return ($update ? true : false);
    }

    public static function validateEmail($key)
    {
        $id = base64_decode(substr(urldecode($key), 40));

        if (!is_numeric($id))
        {
            return false;
        }

        //connect to central database
        $db     = new Model();
        $project_id = $db->getVal("select project_id from employees where id = :employee_id",
                        array(':employee_id' => $id));

        if ($project_id)
        {
            $select = $db->getRow("select db, db_ip from projects where id = :project_id",
                array(':project_id' => $project_id));

            if ($select)
            {
                $dbip   = $select['db_ip'];
                $dbname = $select['db'];

                //connect to specific audit database
                $db = new Model($dbip, $dbname);

                $select = $db->getRow("select id, dob 
                                              from employees 
                                              where force_void <> '1'
                                                and email_portal_key = :key",
                            array(':key' => $key));

                if ($select)
                {
                    $id  = $select['id'];
                    $dob = $select['dob'];

                    $update = $db->q("update employees 
                                                set email_portal_key = '' 
                                             where force_void <> '1'
                                               and id = :employee_id",
                        array(':employee_id' => $id));

                    // Save key
                    $db->q("insert into portal_used_keys (`project_id`,`eid`,`key`) values (:project_id,:employee_id,:key)",
                        array(':project_id'  => $project_id,
                              ':employee_id' => $id,
                              ':key'         => $key)
                    );

                    if ($update)
                    {
                        return EmployeeModel::authenticate($id, $dob);
                    }
                }
            }
        }
        return false;

    }

    public static function alreadyValidateEmail($key)
    {
        $key = urldecode($key);

        $id = base64_decode(preg_replace("/^.{0,32}/", "", $key));

        //connect to central database
        $db     = new Model();
        $project_id = $db->getRow("select project_id from employees where id = :employee_id",
            array(':employee_id' => $id));

        if ($project_id)
        {
            $select     = $db->getRow("select db, db_ip from projects where id = :project_id",
                            array(':project_id' => $project_id));

            if ($select)
            {
                $dbip   = $select['db_ip'];
                $dbname = $select['db'];

                //connect to specific audit database
                $db = new Model($dbip, $dbname);

                $select = $db->getVal(
                    "select id from portal_used_keys where project_id = :project_id and eid = :id and `key` = :key",
                        array(':project_id'  => $project_id,
                              ':employee_id' => $id,
                              ':key'         => $key));

                if ($select)
                {
                    return true;
                }
            }
        }
        return false;
    }

    public static function Exists($id, $project_id = null)
    {

        //connect to central database
        $db = new Model();

        //get employee information from central database
        if (isset($project_id))
        {
            $select = $db->getVal("select 1 from employees where id = :eid and project_id = :project_id",
                array(':eid'        => $id,
                      ':project_id' => $project_id));
        }
        else
        {
            $select = $db->getVal("select 1 from employees where id = :eid",
            array(':eid' => $id));
        }

        return $select ? true : false;
    }

    public static function GetEmailByKey($key)
    {
        $id = base64_decode(preg_replace("/^.{0,32}/", "", $key));

        //connect to central database
        $db = new Model();

        //get employee information from central database
        $email_portal = $db->getVal("select email_portal from employees where id = :eid",
            array(':eid' => $id));

        return ($email_portal ? $email_portal : false);
    }

    public static function getEmployeeDOB($eid)
    {
        //connect to central database
        $db = new Model();

        $dob = $db->getVal("select dob from employees where id = :eid",
            array(':eid' => $eid));

        return ($dob ? $dob : false);
    }

    public static function isAnthem($eid)
    {
        //connect to central database
        $db = new Model();

        $grouper = $db->getVal("SELECT grouper FROM projects p, employees e WHERE p.id=e.project_id AND e.id = :eid",
            array(':eid' => $eid));

        return ($grouper == 'Anthem' ? true : false);
    }

    # Roche Specific
    public function RocheC2V()
    {
        return $this->roche;
    }

    public function recordRocheAnswers($dep_id, $coverage, $surcharge)
    {
        $db     = new Model($this->dbip, $this->dbname);

        # get dep and figure rel
        $dep = $this->getDependent($dep_id);

        # get coverage question id
        $coverage_id = $db->getVal("SELECT id FROM project_questions 
                                      WHERE project_id= :project_id AND plan_id= :plan_id 
                                      AND rel=:rel AND name='spouse_coverage';",
            array(':project_id' => $this->projectid,
                  ':plan_id'    => $this->planid,
                  ':rel'        => $dep['rel']));

        if (!$coverage_id)
        {
            $db->q("INSERT INTO project_questions (project_id, plan_id, rel, name, question, enabled, type, size, length) 
                      VALUES(:project_id,:plan_id,:rel,'spouse_coverage','Employment and Coverage','1','textbox','30','50');",
                array(':project_id' => $this->projectid,
                      ':plan_id'    => $this->planid,
                      ':rel'        => $dep['rel']));

            $coverage_id = $db->getRow("SELECT id FROM project_questions 
                                    WHERE project_id= :project_id AND plan_id= :plan_id 
                                      AND rel=:rel AND name='spouse_coverage';",
                array(':project_id' => $this->projectid,
                      ':plan_id'    => $this->planid,
                      ':rel'        => $dep['rel']));

        }

        # get surcharge question id
        $surcharge_id = $db->getRow("SELECT id FROM project_questions 
                                  WHERE project_id=:project_id AND plan_id = :plan_id 
                                  AND rel= :rel AND name='surcharge';",
            array(':project_id' => $this->projectid,
                  ':plan_id'    => $this->planid,
                  ':rel'        => $dep['rel']));

        if (!$surcharge_id)
        {
            $db->q("INSERT INTO project_questions (project_id, plan_id, rel, name, question, enabled, type, size, length) 
                      VALUES(:project_id,:plan_id,:rel,'surcharge','Spouse Surcharge','1','textbox','30','50');",
                array(':project_id' => $this->projectid,
                      ':plan_id'    => $this->planid,
                      ':rel'        => $dep['rel']));

            $surcharge_id = $db->getRow("SELECT id FROM project_questions 
                                      WHERE project_id=:project_id AND plan_id = :plan_id 
                                      AND rel= :rel AND name='surcharge';",
                array(':project_id' => $this->projectid,
                      ':plan_id'    => $this->planid,
                      ':rel'        => $dep['rel']));
        }

        # record answers
        $db->q("REPLACE INTO project_question_answers 
                  VALUES (:project_id,:employee_id,:dependent_id,:coverage_id,NOW(),:coverage)",
            array(':project_id'     => $this->projectid,
                  ':employee_id'    => $this->id,
                  ':dependent_id'   => $dep_id,
                  ':coverage_id'    => $coverage_id['id'],
                  ':coverage'       => $coverage));

        $db->q("REPLACE INTO project_question_answers 
                  VALUES (:project_id,:employee_id,:dependent_id,:surcharge_id,NOW(),:surcharge)",
            array(':project_id'   => $this->projectid,
                  ':employee_id'    => $this->id,
                  ':dependent_id'   => $dep_id,
                  ':surcharge_id'   => $surcharge_id['id'],
                  ':surcharge'      => $surcharge));
    }

    # Anthem Specific
    public function Anthem()
    {
        return $this->anthem;
    }
}