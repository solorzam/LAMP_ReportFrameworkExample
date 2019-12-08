<?php

class ProjectModel // extends Model
{
    # Project Details
    private $ip, $db, $projectid, $projectname, $planid, $type, $planname, $status, $email, $pmid, $grouper;
    private $vendorName, $companyName, $cost_per_dep, $audit_cost;
    private $street1, $street2, $city, $state, $zip, $country, $link, $fax, $phone, $hours, $clientLogo, $partnerLogo;

    # Project Options
    private $collectSSN, $green, $translation, $softLanding, $portalVerification;
    private $hideAddrUpdate, $hideROI, $onlineCDC, $pdfGeneration, $employerPreview, $sms;
    private $ep_tab_welcome, $ep_tab_welcome_box, $ep_tab_eligibility, $ep_tab_upload;
    private $ep_tab_account, $ep_tab_account_docs, $ep_tab_account_deps;
    private $ep_tab_contact, $ep_tab_resources, $ep_tab_faq;

    # Project Reports
    private $clientDrop, $termDetails, $finalExtract, $reportCDC, $reportIssueLog, $reportLateSubmission;

    # Project Dates
    private $startDate, $stopDate;
    private $a_due_date, $a_mail_date;
    private $v_final_due_date, $v_mail_1_date, $v_mail_2_date, $v_mail_3_date, $v_mail_4_date, $v_due_date;

    # for pop selector / filled from session
    private  $start_date, $end_date;

    # Misc
    private $uploads = true;
    private $empCache, $depCache = false;
    private $fixedRoi = false;

    # selfService
    private $selfServiceEntry, $selfServiceTools = false;
    
    #popFilter
    private $pop_filter = '', $pop_filter_params = array();

    #plan parameters for queries
    private $plan_query = '', $plan_query_params = array();




    public function __construct($projectid, $planid)
    {

        #
        # Central Project details
        #

        # pull from cache if possible
        $dbCache = new CacheModel();
        $select = $dbCache->getRow("select id, name, grouper, db, db_ip, project_type, 
                                        g_email, pm_id, status 
                                        from projects where id = :project_id",
                        array(':project_id' => $projectid));

        # if there is no project id we shouldn't provide a project object
        if (!$select) { return FALSE; }

        $this->ip          = $select["db_ip"];
        $this->db          = $select["db"];
        $this->projectid   = $select["id"];
        $this->projectname = $select["name"];
        $this->type        = $select["project_type"];
        $this->status      = $select["status"];
        $this->email       = $select["g_email"];
        $this->pmid        = $select["pm_id"];
        $this->grouper     = $select["grouper"];
        
        # Connect to Audit
        $dbCache = new CacheModel($this->ip, $this->db);

        #
        # Audit Project details
        #
        $select = $dbCache->getRow("select * from project_details 
                                      where project_id = :project_id",
                array(':project_id' => $projectid));

        if (!$select) { return FALSE; }

        # Project Options
        $this->collectSSN           = $select['project_ssn_collection'] == 0 ? FALSE : TRUE;
        $this->green                = $select['project_green'] == 0 ? FALSE : TRUE;
        $this->translation          = $select['ep_translation'] == 0 ? FALSE : TRUE;
        $this->softLanding          = $select['ep_soft_landing'] == 0 ? FALSE : TRUE;
        $this->portalVerification   = $select['ep_verification'] == 0 ? FALSE : TRUE;
        $this->pdfGeneration        = !($select['ep_disable_pdfs'] == 0 ? FALSE : TRUE);
        $this->sms                  = $select['ep_enable_sms'] == 0 ? FALSE : TRUE;
        $this->hideAddrUpdate       = $select['ep_hide_addr_update'] == 0 ? FALSE : TRUE;
        $this->hideROI              = $select['ep_hide_roi'] == 0 ? FALSE : TRUE;
//        $this->onlineCDC            = $select['ep_cdc_toggle'] == 0 ? FALSE : TRUE;
        $this->employerPreview      = $select['ep_employer_emp_preview'] == 0 ? FALSE : TRUE;

        $this->ep_tab_welcome       = $select['ep_tab_welcome'] == 0 ? FALSE : TRUE;
        $this->ep_tab_welcome_box   = $select['ep_tab_welcome_box'] == 0 ? FALSE : TRUE;
        $this->ep_tab_eligibility   = $select['ep_tab_eligibility'] == 0 ? FALSE : TRUE;
        $this->ep_tab_upload        = $select['ep_tab_upload'] == 0 ? FALSE : TRUE;
        $this->ep_tab_account       = $select['ep_tab_account'] == 0 ? FALSE : TRUE;
        $this->ep_tab_account_deps  = $select['ep_tab_account_deps'] == 0 ? FALSE : TRUE;
        $this->ep_tab_account_docs  = $select['ep_tab_account_docs'] == 0 ? FALSE : TRUE;
        $this->ep_tab_contact       = $select['ep_tab_contact'] == 0 ? FALSE : TRUE;
        $this->ep_tab_resources     = $select['ep_tab_resources'] == 0 ? FALSE : TRUE;
        $this->ep_tab_faq           = $select['ep_tab_faq'] == 0 ? FALSE : TRUE;

        # Project Reports
        $this->clientDrop            = $select['ep_report_client_drop'] == 0 ? FALSE : TRUE;
        $this->termDetails           = $select['ep_report_term_details'] == 0 ? FALSE : TRUE;
        $this->finalExtract          = $select['ep_report_final_extract'] == 0 ? FALSE : TRUE;
        $this->reportCDC             = $select['ep_report_cdc'] == 0 ? FALSE : TRUE;
        $this->reportIssueLog        = $select['ep_report_issue_log'] == 0 ? FALSE : TRUE;
        $this->reportLateSubmission  = $select['ep_report_late_submission'] == 0 ? FALSE : TRUE;


        # self service option
        $this->selfServiceEntry     = $select['ep_self_service_entry'] == 0 ? FALSE : TRUE;
        $this->selfServiceTools     = $select['ep_self_service_tools'] == 0 ? FALSE : TRUE;

        #fixed ROI
        $this->fixedRoi    = $select['fixed_roi'];

        #
        # Audit Project overrides details
        #
        $select = $dbCache->getRow("select pc_ids, g_fax, g_phone, g_phone_hours,
            g_audit_start_date, g_audit_stop_date, g_link,
            v_due_date, v_mail_1_date, v_mail_2_date, v_mail_3_date, v_mail_4_date, v_final_due_date,
            a_mail_date, a_due_date, cost_per_dep, audit_cost,
            g_return_street_1, g_return_street_2, g_return_city, g_return_state,
            g_return_zip, g_return_country, g_vendor_name, g_company_name, ep_client_logo, ep_partner_logo
             from projects where id = :project_id",
                array(':project_id' => $projectid));

        # if there is no project id we shouldn't provide a project object
        if (!$select) { return FALSE; }

        $this->pcids                = explode(';',$select["pc_ids"]);
        $this->startDate            = $select['g_audit_start_date'];
        $this->stopDate             = $select['g_audit_stop_date'];
        $this->v_due_date           = $select['v_due_date'];
        $this->v_mail_1_date        = $select['v_mail_1_date'];
        $this->v_mail_2_date        = $select['v_mail_2_date'];
        $this->v_mail_3_date        = $select['v_mail_3_date'];
        $this->v_mail_4_date        = $select['v_mail_4_date'];
        $this->v_final_due_date     = $select['v_final_due_date'];
        $this->a_mail_date          = $select['a_mail_date'];
        $this->a_due_date           = $select['a_due_date'];
        $this->cost_per_dep         = $select['cost_per_dep'];
        $this->audit_cost           = $select['audit_cost'];

        #
        # Plan level details
        #
        if ($planid != 0) {
            $planSelect = $dbCache->getRow("select id, name, g_link, g_fax, g_phone, g_phone_hours, g_return_street_1, g_return_street_2,
              g_return_city, g_return_state, g_return_zip, g_return_country, g_vendor_name, g_company_name,
              ep_client_logo, ep_partner_logo from plans where id = :plan_id and project_id = :project_id",
                array(':plan_id'    => $planid,
                      ':project_id' => $projectid  ));


            # set plan name
            if ($planSelect) {
                $this->planid   = $planSelect["id"];
                $this->planname = $planSelect["name"];
            }
        } else {
            $planSelect = array();
            $this->planid = 0;
        }

        # clean up all variables in plan override
        $clean = function($array) {
            return array_filter($array, 'trim');
        };
        $overrides = array_merge($select, $clean($planSelect));

        # overrides
        $this->link          = $overrides['g_link'];
        $this->fax           = $overrides['g_fax'];
        $this->phone         = $overrides['g_phone'];
        $this->hours         = $overrides['g_phone_hours'];
        $this->vendorName    = $overrides['g_vendor_name'] != '' ? $overrides['g_vendor_name'] : 'HMS Employer Solutions';
        $this->companyName   = $overrides['g_company_name'];
        $this->street1       = $overrides['g_return_street_1'];
        $this->street2       = $overrides['g_return_street_2'];
        $this->city          = $overrides['g_return_city'];
        $this->state         = $overrides['g_return_state'];
        $this->zip           = $overrides['g_return_zip'];
        $this->country       = $overrides['g_return_country'];
        $this->clientLogo    = $overrides['ep_client_logo'] != '' ? $overrides['ep_client_logo'] : FALSE;
        $this->partnerLogo   = $overrides['ep_partner_logo'] != '' ? $overrides['ep_partner_logo'] : FALSE;


        if (isset($_SESSION['pop'])) {
            $this->setPopFilter();
        }
    }

    public function setPopFilter()
    {
        # pull the start/end dates or make default (should never happen here)
        $this->start_date     = $_SESSION['pop']['start_date'];
        $this->end_date       = $_SESSION['pop']['end_date'];

        if ($this->isPoe())
        {
            switch($_SESSION['pop']['population_filter'])
            {
                 case "Active":
                    $this->pop_filter        = " and e.poe_status in ('Active','Runout')";
                    break;

                case "Inactive":
                    $this->pop_filter        = " and e.poe_status = 'Inactive'";
                    break;

                case 'Finished between':
                    $this->pop_filter        = " and e.poe_status = 'Inactive' and e.poe_start_date != '0000-00-00' and e.poe_stop_date >= :start_date and e.poe_stop_date <= :stop_date";
                    $this->pop_filter_params = array(':start_date' => $this->start_date, ':stop_date' => $this->end_date );
                    break;

                case 'Started between':
                    $this->pop_filter        = " and e.poe_start_date != '0000-00-00' and e.poe_start_date >= :start_date and e.poe_start_date <= :stop_date";
                    $this->pop_filter_params = array(':start_date' => $this->start_date, ':stop_date' => $this->end_date );
                    break;

                case 'Everyone':
                default:
                    $this->pop_filter        = " and e.poe_start_date != '0000-00-00'";
                    break;
            }
        }
        else
        {
             switch($_SESSION['pop']['population_filter'])
            {
                 case "Active":
                    $this->pop_filter        = " and p.status in ('Active','Runout')";
                    break;

                case "Inactive":
                    $this->pop_filter        = " and p.status = 'Inactive'";
                    break;

                case 'Finished between':
                    $this->pop_filter        = " and p.status = 'Inactive' and p.g_audit_stop_date >= :start_date and p.g_audit_stop_date <= :stop_date";
                    $this->pop_filter_params = array(':start_date' => $this->start_date, ':stop_date' => $this->end_date );
                    break;

                case 'Started between':
                    $this->pop_filter        = " and p.g_audit_start_date >= :start_date and p.g_audit_start_date <= :stop_date";
                    $this->pop_filter_params = array(':start_date' => $this->start_date, ':stop_date' => $this->end_date );
                    break;

                case 'Everyone':
                default:
                    $this->pop_filter        = " ";
                    break;
            }
        }
    }

    public static function Exists($id)
    {
        //connect to central database
		$db = new Model();

        $select = $db->getVal("select 1 from projects where id = :id and status = 'active'",
                    array(':id' => $id));

        return ($select ? true : false);
    }

    public function isPoe()
    {
        return (strtoupper(trim($this->type)) == 'POE' ? true : false);
    }

    public function isGreen()
    {
        return ($this->green == 1 ? true : false);

    }

    public function isActive()
    {
        return (strtolower($this->status) == 'active' ? true : false);
    }

    public function useSMS()
    {
        return $this->sms;
    }

    public function getProjectID()
	{
		return $this->projectid;
	}
	
	public function getProjectName()
	{
		return $this->projectname;
	}
	
	public function getPlanID()
	{
		return $this->planid;
	}
	
	public function getPlanName()
	{
		return htmlentities($this->planname);
	}
	
	public function getStatus()
	{
		return $this->status;
	}
	
	public function getEmail()
	{
		return $this->email;
	}

    public function getLink()
    {
        return $this->link;
    }
	
	public function getIP()
	{
		return $this->ip;
	}
	
	public function getDB()
	{
		return $this->db;
	}
	
	public function getPMEmail()
	{
		return $this->pmemail;
	}

    public function getPCEmails()
    {
        return $this->pcemails;
    }

	public function getFax()
	{
		return htmlentities($this->fax);
	}
	
	public function getPhone()
	{
		return htmlentities($this->phone);
	}
	
	public function getHours()
	{
		return htmlentities($this->hours);
	}
	
	public function collectSSN()
	{
		return $this->collectSSN;
	}
	
	public function getAddress()
	{
		$address = '';
		
		$address .= $this->street1 . '<br />';
		
		if( isset($this->street2) && $this->street2 != '' )
		{
			$address .= $this->street2 . '<br />';
		}
		
		$address .= htmlentities($this->city) . ', ' . htmlentities($this->state) . ' ' . $this->zip . '<br />';
		$address .= $this->country . '<br />';
		
		return $address;
	}
    
    public function useSoftLanding()
    {
        return $this->softLanding;
    }

    public function useTabWelcome()
    {
        return $this->ep_tab_welcome;
    }

    public function useTabWelcomeBox()
    {
        return $this->ep_tab_welcome_box;
    }

    public function useTabEligibility()
    {
        return $this->ep_tab_eligibility;
    }

    public function useTabUpload()
    {
        return $this->ep_tab_upload;
    }

    public function useTabAccount()
    {
        return $this->ep_tab_account;
    }

    public function useTabAccountDocs()
    {
        return $this->ep_tab_account_docs;
    }

    public function useTabAccountDeps()
    {
        return $this->ep_tab_account_deps;
    }

    public function useTabContact()
    {
        return $this->ep_tab_contact;
    }

    public function useTabResources()
    {
        return $this->ep_tab_resources;
    }

    public function useTabFAQ()
    {
        return $this->ep_tab_faq;
    }


    public function usePDFgeneration()
    {
        return $this->pdfGeneration;
    }

    public function useClientDrop()
    {
        return $this->clientDrop;
    }

    public function usetermDetails()
    {
        return $this->termDetails;
    }

    public function useFinalExtract()
    {
        return $this->finalExtract;
    }

    public function useCDCReport()
    {
        return $this->reportCDC;
    }

    public function useIssueLogReport()
    {
        return $this->reportIssueLog;
    }

    public function useLateSubmissionReport()
    {
        return $this->reportLateSubmission;
    }

    public function useTranslation()
    {
        return $this->translation;
    }

    public function useAddrUpdate()
    {
        return !$this->hideAddrUpdate;
    }

    public function useROI()
    {
        return !$this->hideROI;
    }


    /*
     * NEW Optimized DB Calls
     */

    # $type = amnesty or verification
    public function getEmployeeStatus($type)
    {
        switch($type)
        {
            case 'amnesty':
                $results = $this->amnestyEmployeeStatus();
                break;

            case 'verification':
                $results = $this->verificationEmployeeStatus();
                break;

            default:
                $results = FALSE;
                break;
        }

        return $results;
    }

    public function onlineVerification()
    {
        return $this->portalVerification;
    }

    public function getGeneralName()
    {
        if (isset($this->companyName) && !empty($this->companyName))
        {
            return $this->companyName;
        }
        else if (isset($this->projectname) && !empty($this->projectname))
        {
            return $this->projectname;
        }
        else
        {
            return FALSE;
        }
    }

	public function hasPlan($projectid, $planid)
	{
		if($planid != 0)
		{
			//connect to database
			$db = new Model($this->ip, $this->db);
			
			$select = $db->getVal("select id from plans where id = :plan_id and project_id = :project_id",
                array(':plan_id'    => $planid,
                      ':project_id' => $projectid  ));
			
			return ($select ? true : false);
		}
		else
		{
			return TRUE;
		}
	}

    public function getPMid()
    {
        return $this->pmid;
    }

    public function getPCids()
    {
        return $this->pcids;
    }

	public function getCompanyName()
	{
        return htmlentities($this->companyName);
	}
	
	public function getEmployeeName($eid)
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
		
		$select = $db->getVal("select name from employees where id = :employee_id",
            array(':employee_id' => $eid));

		return $select;
	}

    public function getVendorName()
    {
        return htmlentities($this->vendorName);
    }

    public function getClientLogo()
    {
        return $this->clientLogo;
    }

    public function getPartnerLogo()
    {
        return $this->partnerLogo;
    }

    /*
     * Private Query Helper
     */

    private function addPlanParameter($type = '', $alias = '')
    {
        $db = new Model();
        $output = array('query'  => '',
                        'params' => array());
        $alias = (!empty($alias) ? $alias."." : '' );

        if($this->planid != 0)
        {
            if($type == 'in')
            {
                $output['query'] = " and {$alias}plan_id in (" . $db->buildInString($this->planid) . ")";
            }
            else
            {
                $output['query'] = " and {$alias}plan_id = :plan_id ";
                $output['params'] = array(':plan_id' => $this->planid);
            }
        }
        return $output;
    }

    /**
     * moved this from the main query to only hit the max admin when needed
     * saves about 3 queries a page
     */
    private function getPM()
    {
        $this->pmname  = '';
        $this->pmemail = '';

        //get project manager details
        $db = new Model();
        $select = $db->getRow("select full_name, email from users where dc_id = :pm_id" ,
            array(':pm_id' => $this->pmid));

        if($select)
        {
            $this->pmname 	= $select["full_name"];
            $this->pmemail 	= $select["email"];
        }
    }

    private function getPCs()
    {
        $this->pcemails = array();

        if (!empty($this->pcids))
        {
            $db = new Model();
            $select = $db->getRows("select email from users where dc_id in (".$db->buildInString($this->pcids).")");
            if($select)
            {
                foreach($select as $row)
                {
                    $this->pcemails[] = $row["email"];
                }
            }
        }
    }

    /*
     * Some caching functions
     */
    public function __get($name) {
            if (array_key_exists($name, $this)) {
                return $this->$name;
            }

            switch($name) {
                case 'pmname':
                    $this->getPM();
                    return $this->pmname;
                    break;
                case 'pmemail':
                    $this->getPM();
                    return $this->pmemail;
                    break;
                case 'pcemails':
                    $this->getPCs();
                    return $this->pcemails;
                    break;
            }
        }
	
	public function requirePassword()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);

		$select = $db->getVal("select ep_password from project_details where project_id = :project_id",
            array(':project_id' => $this->projectid));

		return ($select == '1' ? true : false);
	}
	
	public function inAmnesty()
	{
        $dbCache = new CacheModel($this->ip,$this->db);

        $select = $dbCache->getVal("select max(amnesty) as flag from employees e, projects p
                                      WHERE e.project_id=p.id AND project_id = :project_id "
                                    . $this->pop_filter . " limit 1",
            array_merge(array(':project_id' => $this->projectid), $this->pop_filter_params ));

		return ($select == 1 ? true : false);
	}
	
	public function getProjectNameByID($pid)
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
		
		$name = $db->getVal("select name from projects where id = :pid",
            array(':pid' => $pid));

        return $name ? $name : null;
	}
	
	public function getPlanNameByID($pid)
	{
		//connect to database
		$db = new Model($this->ip, $this->db);

        $name = $db->getVal("select name from plans where id = :pid",
            array(':pid' => $pid));

        return $name ? $name : null;
	}

	//cache methods for dep/emp stats	
    private function employeeCache($key)
    {
        if(!(is_array($this->empCache)))
        {
            $addPlans = $this->addPlanParameter();

            $query = "SELECT client_status, count(e.id) as c
                        FROM employees e, projects p
                        WHERE e.project_id=p.id AND e.force_void <> '1' AND project_id = :project_id 
                        {$this->pop_filter} {$addPlans['query']} group by client_status";

            $params = array_merge(array(':project_id' => $this->projectid),
                                    $this->pop_filter_params,
                                    $addPlans['params']);

            // create the cache array
            $db = new Model($this->ip, $this->db);
            $select = $db->getRows($query,$params);

            $total = 0;

            foreach($select as $status)
            {
                    $this->empCache[$status['client_status']] = $status['c'];
                    $total += $status['c'];
            }

            $this->empCache['Total']=$total;
        }

        return (isset($this->empCache[$key]) ? $this->empCache[$key] : 0);
    }

    private function dependentCache($key)
    {
        if(!(is_array($this->depCache)))
        {
            $addPlans = $this->addPlanParameter('','e');

            $query = "SELECT d.client_status as client_status, count(d.id) as c
                        FROM dependents d, employees e, projects p
                        WHERE e.project_id=p.id AND  e.force_void <> '1' AND  d.project_id = :project_id AND e.id = d.employee_id
                        {$this->pop_filter} {$addPlans['query']} group by client_status";

            $params = array_merge(array(':project_id' => $this->projectid),
                                    $this->pop_filter_params,
                                    $addPlans['params']);

            // create the cache array
            $db = new Model($this->ip, $this->db);
            $select = $db->getRows($query,$params);

            $total = 0;

            foreach($select as $status)
            {
                $this->depCache[$status['client_status']] = $status['c'];
                $total += $status['c'];
            }

            $this->depCache['Total'] = $total;
        }

        return (isset($this->depCache[$key]) ? $this->depCache[$key] : 0);
    }

	//employee stats methods
	public function totalEmployees()
	{
        return $this->employeeCache('Total');
	}
	
	public function completeResponseReceived()
	{
		return $this->completeResponse() + $this->completeResponseWithTerm() + $this->completeResponseClientUpdate();
	}
	
	public function completeResponse()
	{
        return $this->employeeCache('Complete');
	}
	
	public function completeResponseWithTerm()
	{
        return $this->employeeCache('Complete With Termination');
    }
	
	public function completeResponseClientUpdate()
	{
        return $this->employeeCache('Client Update');
	}
	
	public function partialResponseReceived()
	{
		return $this->partialResponse() + $this->partialResponseInsuffDocTerm();
	}
	
	public function partialResponse()
	{
        return $this->employeeCache('Partial Response')
            + $this->employeeCache('Partial Response - Pending Review')
            + $this->employeeCache('Response Received - Pending Review');
	}
	
	public function partialResponseMissingSignature()
	{
        return $this->employeeCache('Partial Missing Signature');
	}
	
	public function partialResponseInsuffDocTerm()
	{
        return $this->employeeCache('Insufficient Doc Termination');
	}
	
	public function noResponseReceived()
	{
		return $this->noResponse() + $this->noResponseTerm();
	}
	
	public function noResponse()
	{
        return $this->employeeCache('No Response');
	}
	
	public function noResponseTerm()
	{
        return $this->employeeCache('No Response Termination');
	}

	//dependent stats methods
	public function totalDependents()
	{
        return $this->dependentCache('Total');
	}
	
	public function verifiedDependents()
	{
        return $this->dependentCache('Verified');
	}
	
	public function unverifiedDependents()
	{
        return $this->unverifiedDependentsNoDocs()+$this->unverifiedDependentsPartialDocs();
	}
	
	public function unverifiedDependentsNoDocs()
	{
        return $this->dependentCache('No Documents');
	}
	
	public function unverifiedDependentsPartialDocs()
	{                
        return $this->dependentCache('Partial Documents') + $this->dependentCache('Pending Review');
	}
	
	public function termDependents()
	{
        return $this->termDependentsAmnesty()+$this->termDependentTerminated()+$this->termDependentsVoluntary()+
            $this->termDependentsInvoluntary()+$this->termDependentsInsuffDocsTerm()+$this->termDependentsNoResponseTerm();
	}
	
	public function termDependentsAmnesty()
	{
        return $this->dependentCache('Amnesty Termination');
	}
	
	public function termDependentsVoluntary()
	{
        return $this->dependentCache('Voluntary Termination');
	}
	
	public function termDependentsInvoluntary()
	{
        return $this->dependentCache('Involuntary Termination');
	}
	
	public function termDependentsInsuffDocsTerm()
	{
        return $this->dependentCache('Insufficient Doc Termination');
	}
	
	public function termDependentsNoResponseTerm()
	{
        return $this->dependentCache('No Response Termination');
	}
	
	public function cliendUpdate()
	{
        return $this->dependentCache('Client Update');
	}
	
	public function termDependentTerminated()
	{
		return $this->dependentCache('Terminated');
	}
	
	public function termByRelationship()
	{
        $planAdd = $this->addPlanParameter('','d');

        $query ="select r.notes as relationship, count(distinct d.id) as c
                    from dependents d, rules r, employees e, projects p
                    where d.project_id= :project_id and d.status='term' and d.term_type NOT IN ('client update','duplicate')
                    and d.rel = r.rel and d.plan_id = r.plan_id
                    and d.project_id=p.id and  e.force_void <> '1' and d.employee_id=e.id {$planAdd['query']}
                    {$this->pop_filter} group by relationship order by c desc";

        $params = array_merge(array(':project_id' => $this->projectid),
                              $planAdd['params'],
                              $this->pop_filter_params);

		$db = new Model($this->ip, $this->db);
        return $db->getRows($query,$params);
	}
	
	public function termVoluntaryTypes()
	{
        $addPlans = $this->addPlanParameter('','e');

		$query = "select term_type, count(*) as c
                    from dependents d, employees e, projects p
                    where d.client_status = 'Voluntary Termination' and e.force_void <> '1'  
                    and d.project_id = :project_id
                    and d.employee_id=e.id and d.project_id=p.id                    
                    {$this->pop_filter} {$addPlans['query']}
                    group by term_type order by c desc";

        $params = array_merge(array(':project_id' => $this->getProjectID()),
            $this->pop_filter_params,
            $addPlans['params']);

        $db = new Model($this->ip, $this->db);
		return $db->getRows($query,$params);
	}

    public function termAmnesty()
    {
        return $this->dependentCache('Amnesty Termination');
    }

    public function termVoluntary()
    {
        return $this->dependentCache('Voluntary Termination');
    }
	
	public function termInvoluntary()
	{
        return $this->dependentCache('Involuntary Termination');
	}
	
	public function termInsufficientDocs()
	{
        return $this->dependentCache('Insufficient Doc Termination');
	}
	
	public function termNoResponse()
	{
        return $this->dependentCache('No Response Termination');
	}

	public function amnestyResponse()
	{
        return $this->employeeCache('Amnesty Response');
	}

    private function amnestyDepsByStatus($having)
    {
        $planAdd = $this->addPlanParameter('in','e');

        $query = "select count(z.id) from (select e.id,
            group_concat(distinct d.client_status order by d.client_status) as ds
            from employees e, dependents d
            where e.id=d.employee_id and  e.force_void <> '1' and e.project_id= :project_id {$planAdd['query']} 
        and d.client_status!='Client Update' and e.client_status='Amnesty Response'
                group by e.id having ds = :havingCondition ) as z";

        $params = array_merge(array(':project_id' => $this->projectid), $planAdd['params'],array(':havingCondition' => $having));

        $db = new Model($this->ip, $this->db);
        return $db->getVal($query, $params);

    }
	
	public function amnestyNoDepsTerm()
	{
        return $this->amnestyDepsByStatus('Not Terminated');
	}
	
	public function amnestySomeDepsTerm()
	{
        return $this->amnestyDepsByStatus('Not Terminated,Terminated');
	}

	public function amnestyAllDepsTerm()
	{
        return $this->amnestyDepsByStatus('Terminated');
	}
	
	public function noAmnestyResponse()
	{
        return $this->employeeCache('No Amnesty Response');
	}
	
	public function amnestyClientUpdate()
	{
        return $this->employeeCache('Client Update');
	}
	
	public function amnestyDepResponse()
	{
        return $this->employeeCache('Not Terminated') + $this->employeeCache('Terminated');
	}
	
	public function amnestyDepsNotTerm()
	{
		return $this->dependentCache('Not Terminated');
	}
	
	public function amnestyDepsTerm()
	{
		return $this->dependentCache('Terminated');
	}
	
	public function amnestyDepsNoResponse()
	{
		return $this->dependentCache('No Amnesty Response');
	}
	
	public function amnestyDepsClientUpdate()
	{
		return $this->dependentCache('Client Update');
	}
	
	//report methods
	public function getReports()
	{
        $planAdd = $this->addPlanParameter('in');

		$db = new Model($this->ip, $this->db);
		$select = $db->getRows("select * from portal_status_reports where project_id = :project_id {$planAdd['query']}",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params']));
		
		return $select;
	}
	
	public function getReport($id, $projectid)
	{
        $planAdd = $this->addPlanParameter();

		$db = new Model($this->ip, $this->db);
		$select = $db->getRow("select * from portal_status_reports where id = :id and project_id = :project_id {$planAdd['query']}",
            array_merge(array(':id' => $id, ':project_id' => $projectid), $planAdd['params']));
		
		return $select;
	}
	
	public function auditStart()
	{
        return $this->startDate;
	}
	
	public function auditStop()
	{
        return $this->stopDate;
	}

	public function getGrouper()
    {
        return $this->grouper;
    }

    public function getGraceDate()
    {
        return $this->v_final_due_date;
    }

    public function finalMailDate()
    {
        return $this->v_mail_4_date;
    }

    public function finalDueDate()
    {
        return $this->v_final_due_date;
    }
	
	public function amnestyMailDate()
	{
        return $this->a_mail_date;
	}
	
	public function amnestyDueDate()
	{
        return $this->a_due_date;
	}

    public function verificationDueDate()
    {
        return $this->v_due_date;
    }

    public function getMailDate1()
    {
        return $this->v_mail_1_date;
    }

    public function getMailDate2()
    {
        return $this->v_mail_2_date;
    }

    public function getMailDate3()
    {
        return $this->v_mail_3_date;
    }

    public function getMailDate4()
    {
        return $this->v_mail_4_date;
    }

    public function amnestyVolume()
	{
		$db = new Model($this->ip, $this->db);
        if ($this->planid != 0)
        {
            $select = $db->getVal("select a_mail_volume from plans where id = :plan_id and project_id = :project_id",
                array(':plan_id' => $this->planid, ':project_id' => $this->projectid));
        }
        else
        {
            $select = $db->getVal("select a_mail_volume from projects where id = :project_id",
                array(':project_id' => $this->projectid));
        }
		
		return $select;
	}
	
	public function verificationMailDates()
	{
        return array('v_mail_1_date' => $this->v_mail_1_date,
                     'v_mail_2_date' => $this->v_mail_2_date,
                     'v_mail_3_date' => $this->v_mail_3_date,
                     'v_mail_4_date' => $this->v_mail_4_date);
	}
	
	public function verificationVolumes()
	{
        $db = new Model($this->ip, $this->db);

        if ($this->planid != 0)
        {
            $select =  $db->getRow("select v_mail_1_volume, v_mail_2_volume, v_mail_3_volume
                                from plans where id = :plan_id and project_id = :project_id",
                        array(':plan_id' => $this->planid, ':project_id' => $this->projectid));
        }
        else
        {
            $select = $db->getRow("select v_mail_1_volume, v_mail_2_volume, v_mail_3_volume
                                      from projects where id = :project_id",
                            array(':project_id' => $this->projectid));
        }
		return $select;
	}

    public function finalNoticeVolumes()
    {
        $db = new Model($this->ip, $this->db);

        if ($this->planid != 0)
        {
            $select = $db->getRow("SELECT LEFT(l.msg, LENGTH(l.msg)-18) AS mailer_type, COUNT(*) AS COUNT 
                FROM `log` l, employees e 
                WHERE e.project_id = :project_id
                  AND e.plan_id = :plan_id
                  AND e.id=l.employee_id 
                  AND l.log_type=3 and  e.force_void <> '1'
                  AND l.msg LIKE 'Final Notice%sent (____-__-__)' 
                GROUP BY mailer_type;",
                array(':plan_id' => $this->planid, ':project_id' => $this->projectid));
        }
        else
        {
            $select = $db->getRow("SELECT LEFT(l.msg, LENGTH(l.msg)-18) AS mailer_type, COUNT(*) AS COUNT 
                FROM `log` l, employees e 
                WHERE e.project_id=:project_id
                  AND e.id=l.employee_id 
                  AND l.log_type=3 and  e.force_void <> '1'
                  AND l.msg LIKE 'Final Notice%sent (____-__-__)' 
                GROUP BY mailer_type;",
                array(':project_id' => $this->projectid));
        }
        return $select['COUNT'];
    }
	
    public function getIssuesList($planid=NULL)
        {
            $db = new Model($this->ip, $this->db);
            $projectQuery = "psl.project_id = :project_id";
            $projectQueryParams = array(':project_id' => $this->projectid);
            if(isset($planid))
            {
                $projectQuery.=  " and psl.plan_id = :plan_id";
                $projectQueryParams[':plan_id'] = $planid;
            }

            if ($this->isPoe())
            {
                 $select = $db->getRows("
                     ( -- Catch poe project specific items if poe-filter is set
                         (
                            select psl.id, updated_at, psl.employee_id, e.name as employee_name, psl.project_id, p.name as project_name,
                            psl.plan_id, plan.name as plan_name, summary, category, psl.status, next_action
                            from portal_issue_log psl
                            left join employees e on e.id=employee_id
                            left join projects p on p.id=psl.project_id
                            left join plans plan on plan.id=psl.plan_id
                            where e.force_void <> '1' and {$projectQuery} {$this->pop_filter}
                         )
                        )
                        UNION
                        ( -- Catch global project issues
                         (
                            select id, updated_at, employee_id,
                            '' as employee_name, project_id,
                            (select name from projects where id = project_id) as project_name, plan_id,
                            (select name from plans where id = psl.plan_id) as plan_name, summary, category, status, next_action
                            from portal_issue_log psl where {$projectQuery} and employee_id = '' order by updated_at desc
                         )
                        ) order by updated_at desc;
                     ",
                     array_merge($projectQueryParams, $this->pop_filter_params));
            }
            else
            {
                $select = $db->getRows("
                    select
                        psl.id,
                        psl.updated_at,
                        psl.employee_id,
                        e.name as employee_name,
                        psl.project_id,
                        p.name as project_name,
                        psl.plan_id,
                        plan.name as plan_name,
                        psl.summary,
                        psl.category,
                        psl.status,
                        psl.next_action
                    from portal_issue_log psl
                        left join employees e on e.id=employee_id
                        left join projects p on p.id=psl.project_id
                        left join plans plan on plan.id=psl.plan_id
                        where e.force_void <> '1' and 
                        {$projectQuery}
                        {$this->pop_filter}
                    order by updated_at desc
                ",
                    array_merge($projectQueryParams, $this->pop_filter_params));
            }
                    
            # issues to return
            $issues = array();

            foreach($select as $issue)
            {
                array_push($issues, $issue);
            }

            return $issues;
        }
	
	public function openIssuesAmount($plan_id=NULL)
	{
        $count = 0;

		//connect to database
		$db = new Model($this->ip, $this->db);

        $projectQuery = " psl.project_id = :project_id ";
        $projectQueryParams = array(':project_id' => $this->getProjectID());

        if( isset( $plan_id ) )
        {
            $projectQuery .=  " and psl.plan_id = :plan_id ";
            $projectQueryParams[':plan_id'] = $plan_id;
        }


        if ($this->isPoe())
        {

            $select = $db->getVal("select count(*) AS rowCount
                                    from portal_issue_log psl
                                    left join employees e on e.id=employee_id
                                    left join projects p on p.id=psl.project_id
                                    where {$projectQuery} {$this->pop_filter} and psl.status = 'Open' and e.force_void <> '1'",
                array_merge($projectQueryParams,$this->pop_filter_params));

            $count += (!empty($select) ? $select : 0 );

            $select = $db->getVal("select count(*) AS rowCount 
                                    from portal_issue_log psl where {$projectQuery} 
                                    and  status = 'Open' and employee_id = '' 
                                    order by updated_at desc",
                 $projectQueryParams);

            $count += (!empty($select) ? $select : 0 );
        }
        else
        {
            $select = $db->getVal("select count(*) AS rowCount 
                                from portal_issue_log psl
                                where {$projectQuery} and
                                status = 'Open'",
                            $projectQueryParams);

            $count += (!empty($select) ? $select : 0 );
        }

        return $count;
	}

    private function projectIssueAmountByStatus($status)
    {
        $db = new Model($this->ip, $this->db);
        return $db->getVal("select count(*) from portal_issue_log where project_id = :project_id and status = :status",
                    array(':project_id' => $this->projectid, ':status' => $status));
    }

    public function pendingIssuesAmount()
	{
		return $this->projectIssueAmountByStatus('Pending');
	}

	public function resolvedIssuesAmount()
	{
        return $this->projectIssueAmountByStatus('Resolved');
	}

	public function getIssueSummary($iid)
	{
		$db = new Model($this->ip, $this->db);
		$select = $db->getVal("select summary from portal_issue_log where id = :iid",
            array(':iid' => $iid));

		return $select;
	}
	
	public function getIssueStatus($iid)
	{
		$db = new Model($this->ip, $this->db);
		$select = $db->getVal("select status from portal_issue_log where id = :iid",
            array(':iid' => $iid));

		return $select;
	}
	
	public function getIssueCategory($iid)
	{
		$db = new Model($this->ip, $this->db);
		$select = $db->getVal("select category from portal_issue_log where id = :iid",
            array(':iid' => $iid));

		return $select;
	}
	
	public function getIssueEID($iid)
	{
		$db = new Model($this->ip, $this->db);
		$select = $db->getVal("select employee_id from portal_issue_log where id = :iid",
            array(':iid' => $iid));

		return $select;
	}

	public function searchByName($name)
	{
		$db = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter('','e');
		
		//results array gets returned
		//employees array gets set as an element of the results array
		$results 	= array();
		$employees 	= array();

		$name = preg_replace("/\"/", "",  $name);

        $params = array_merge(array(':search_name' => "%{$name}%",
            ':project_id' => $this->projectid),
            $planAdd['params'],
            $this->pop_filter_params);

		//get total results first
		$query = "select count(*) from employees e, projects p, plans pl
                     WHERE e.project_id=p.id
                        AND e.plan_id=pl.id
                        AND e.force_void <> '1'
                        AND (e.name like :search_name)
                        AND e.project_id = :project_id ". $planAdd['query'] . $this->pop_filter;

        $count = $db->getVal($query,$params);

		//if results from count query is more than zero then continue if not then no reason to do another ping to the db, just return zero
		if($count > 0)
		{
			//reset query string to get row data not just count(*)
			$select = $db->getRows("select e.id, e.name, e.project_id, e.plan_id, p.name as project_name, 
                                      pl.name as plan_name, e.client_status, e.client_id
                                        from employees e, projects p, plans pl
                                         WHERE e.project_id=p.id
                                            AND e.plan_id=pl.id
                                            AND e.force_void <> '1'
                                            AND (e.name like :search_name)
                                            AND e.project_id = :project_id "
                                            . $planAdd['query'] . $this->pop_filter . " limit 100",
                                    $params);

			foreach($select as $employee)
			{
				array_push($employees, $employee);
			}
			
			$results['count'] = $count;
			$results['results'] = $employees;
		}
		else
		{
			//since nothing was found return a zero count
			$results['count'] = 0;
		}
		
		return $results;
	}
	
	public function searchByID($id)
	{
		//connect to audit database
		$db = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter('','e');
		
		//results array gets returned
		//employees array gets set as an element of the results array
		$results 	= array();
		$employees 	= array();

        $params = array_merge(array(':id'         => "%{$id}%",
                                    ':project_id' => $this->projectid),
                              $planAdd['params'],
                              $this->pop_filter_params);

        //get total results first
		$count = $db->getVal("select count(*) from employees e, projects p, plans pl
                            WHERE (e.id like :id OR client_id like :id)
                                AND e.project_id=p.id
                                AND e.plan_id=pl.id
                                AND e.force_void <> '1'
                                AND e.project_id = :project_id ". $planAdd['query'] . $this->pop_filter,
                    $params);

		//if results from count query is more than zero then continue
		//if not then no reason to do another ping to the db, just return zero
		if($count > 0)
		{
			//reset query string to get row data not just count(*)
			$select = $db->getRows("select e.id, e.name, e.project_id, e.plan_id, p.name as project_name, 
                                       pl.name as plan_name, client_status, client_id
			                          from employees e, projects p, plans pl
                                      where (e.id like :id or client_id like :id)
                                        AND e.project_id=p.id
                                        AND e.plan_id=pl.id
                                        AND e.force_void <> '1'
                                        AND e.project_id = :project_id ". $planAdd['query'] . $this->pop_filter." limit 100",
                $params);

            if ($select)
            {
                foreach($select as $employee)
                {
                    array_push($employees, $employee);
                }
            }
			$results['count'] = $count;
			$results['results'] = $employees;
		}
		else
		{
			//since nothing was found return a zero count
			$results['count'] = 0;
		}
		
		return $results;
	}

	public function getWelcomeText()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
		
		//select welcome text from both projects and plans table
		$select = $db->getRow("select pl.ep_welcome_text as plan, pr.ep_welcome_text as project
                            from plans as pl, projects as pr 
                              where pl.id =  :plan_id and pr.id = :project_id",
            array(':plan_id' => $this->planid,':project_id' => $this->projectid));

		if(!empty($select['plan']))
		{
			return $select['plan'];
		}
        return $select['project'];
	}

    public function getMobileWelcomeText()
    {
        //connect to database
        $db = new Model($this->ip, $this->db);

        //select welcome text from both projects and plans table
        $select = $db->getRow("select
            pl.ep_mobile_text as mobile_plan,
            pr.ep_mobile_text as mobile_project,
            pl.ep_welcome_text as plan,
            pr.ep_welcome_text as project
            from plans as pl, projects as pr where pl.id = :plan_id and pr.id = :project_id",
            array(':plan_id' => $this->planid,':project_id' => $this->projectid));

        if(!empty($select['mobile_plan']))
        {
            return html_entity_decode($select['mobile_plan'], ENT_QUOTES, 'UTF-8');
        }
        elseif(!empty($select['mobile_project']))
        {
            return html_entity_decode($select['mobile_project'], ENT_QUOTES, 'UTF-8');
        }
        elseif(!empty($select['plan']))
        {
            return html_entity_decode($select['plan'], ENT_QUOTES, 'UTF-8');
        }
        elseif(!empty($select['project']))
        {
            return html_entity_decode($select['project'], ENT_QUOTES, 'UTF-8');
        }
        else
        {
            return '';
        }
    }

	public function getEligibilityText()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
		
		//select welcome text from both projects and plans table
		$select = $db->getRow("select pl.ep_verification_text as plan, pr.ep_verification_text as project 
                                  from plans as pl, projects as pr 
                                  where pl.id = :plan_id and pr.id = :project_id",
                        array(':plan_id' => $this->planid,':project_id' => $this->projectid));
                
		if(!empty($select['plan']))
		{
			return html_entity_decode($select['plan'], ENT_QUOTES, 'UTF-8');
		}
        return html_entity_decode($select['project'], ENT_QUOTES, 'UTF-8');
	}
	
	public function getRequirementsText()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
		
		//select welcome text from both projects and plans table
		$select = $db->getRow("select pl.ep_doc_text as plan, pr.ep_doc_text as project from plans as pl, projects as pr where pl.id = :plan_id and pr.id = :project_id",
            array(':plan_id' => $this->planid,':project_id' => $this->projectid));
		
		if(!empty($select['plan']))
		{
			return html_entity_decode($select['plan'], ENT_QUOTES, 'UTF-8');
		}
		else
		{
			return html_entity_decode($select['project'], ENT_QUOTES, 'UTF-8');
		}
	}
	
	public function getCostPerDependent()
	{
		return $this->cost_per_dep;
	}
	
	public function getCostSavings()
	{        
		//connect to database
		$db = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter('','d');

        $query = "SELECT count(d.id) as total
            FROM dependents d, employees e, projects p
            WHERE d.project_id = :project_id "
               . $planAdd['query'] .
               "AND p.id=d.project_id
                AND e.id=d.employee_id
                AND d.status = 'term'
                AND e.force_void <> '1'
                AND d.term_type NOT IN ('duplicate', 'client update') " . $this->pop_filter;

        $params = array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params);

        $select = $db->getVal($query,$params);

		return $select * $this->getCostPerDependent();
	}

    public function getAuditCost()
    {
        return $this->audit_cost != 0.00 ? $this->audit_cost : 0;
    }

	public function getROI()
	{
        $roi = $this->fixedRoi;
        if(empty($roi))
        {
            $roi = $this->audit_cost != 0.00 ? ($this->getCostSavings() - $this->audit_cost) / $this->audit_cost * 100 : NULL;
        }
		return $roi;
	}
	
	public function getEmployeesWithDependents()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter();

		$select = $db->getVal("select count(e.id) as total 
                                      from employees e, projects p 
                                      WHERE e.project_id = p.id
                                        AND e.force_void <> '1' 
                                        AND project_id = :project_id {$planAdd['query']} {$this->pop_filter}",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));

        return $select;
	}
	
	public function getTotalDependents()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter('','d');

        $select = $db->getVal("select count(d.id) as total 
                                      from dependents d, employees e, projects p 
                                      WHERE e.project_id=p.id 
                                        AND d.project_id = :project_id
                                        AND e.force_void <> '1'
                                        AND e.id=d.employee_id {$planAdd['query']} {$this->pop_filter}",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
		
		return $select;
	}
	
	public function getDependents()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter('','d');

        $dependents = $db->getRows("select r.notes, count(distinct d.id) as c 
                                           from dependents d, rules r, employees e, projects p 
                                           where r.plan_id=d.plan_id 
                                           and e.id=d.employee_id 
                                           and r.rel=d.rel 
                                           and d.project_id=p.id 
                                           and e.force_void <> '1'
                                           and d.project_id = :project_id {$planAdd['query']} {$this->pop_filter} group by notes order by c desc",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));

		return $dependents;
	}

	public function getFAQs()
	{
		//connect to database
		$db = new Model($this->ip, $this->db);

        $faqs = $db->getRows("select * 
                                     from project_faqs 
                                     where project_id = :project_id 
                                       and display in ('AuditOS','VerifyOS','Both') 
                                       and deleted = 'N' 
                                       order by `order` asc",
                    array(':project_id' => $this->projectid));

        if (!empty($faqs))
        {
            if (count($faqs) > 0)
            {
                foreach($faqs as $row=>$faq)
                {
                    if (empty($faq['q']) || empty($faq['a']))
                    {
                        unset($faqs[$row]);
                    }
                }
            }
        }

		return $faqs;
	}
	
	public function getRules($rel)
	{
		//connect to database
		$db = new Model($this->ip, $this->db);
		
		$select = $db->getVal("select docs_open_text from rules where plan_id = :plan_id and rel = :rel",
            array(':plan_id' => $this->getPlanID(), ':rel' => $rel));
		
		if($select)
		{
			return $select;
		}

        return NULL;
	}

    public function canUpload()
    {
        return $this->uploads;
    }

    public function reportByStatus()
    {
        $planAdd = $this->addPlanParameter('','e');

        return $this->runReport("SELECT e.id as reference_number, plans.name as plan, e.client_id,
            e.client_value_1,e.client_value_2,e.client_value_3,e.client_value_4,e.client_value_5,
            e.client_value_6,e.client_value_7,e.client_value_8,e.client_value_9,e.client_value_10,
            e.email, e.email_rcvd, e.email_portal, e.sms, e.sms_portal_status, e.name as emp_name, e.status as emp_status,
            e.substatus as emp_client_substatus, e.client_status as emp_client_status,
            if (p.project_type='POE',e.poe_start_date,p.g_audit_start_date) as start_date,
            if (p.project_type='POE',e.poe_stop_date,p.g_audit_stop_date) as stop_date,
            if (INSTR(group_concat(l.msg), 'IVR status'),'Y','N') as IVR,
            if (INSTR(group_concat(l.msg), 'portal access'),'Y','N') as Portal
            ".POE_DEBUG_FIELDS."
             FROM plans, projects p,
                (employees e left join log l on e.id=l.employee_id and l.log_type=2 and l.user_id=0 )
            WHERE e.plan_id=plans.id
                AND e.project_id = p.id
                AND e.project_id = :project_id
                AND e.force_void <> '1'
                {$planAdd['query']} {$this->pop_filter}
                GROUP BY e.id
                ORDER BY e.id",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
    }

    public function reportIssueLog()
    {
        $planAdd = $this->addPlanParameter('','psl');

        if ($this->isPoe())
        {
            $stmt = "
                     ( -- Catch poe project specific items if poe-filter is set
                         (
                            select psl.status, updated_at, p.name as project_name, 
                                   plan.name as plan_name, 
                                   e.id as EID, e.name as employee_name, e.client_id as client_id,
                                   category, summary, 
                                   replace(replace(replace(replace(psl.log,'<b>',''),'</b>',''),'<strong>',''),'</strong>','') as log 
                            from portal_issue_log psl
                            left join employees e on e.id=employee_id
                            left join projects p on p.id=psl.project_id
                            left join plans plan on plan.id=psl.plan_id
                            where e.force_void <> '1'
                            and psl.project_id = :project_id 
                            {$planAdd['query']} {$this->pop_filter}
                         )
                        )
                        UNION
                        ( -- Catch global project issues
                         (
                            select status, updated_at, (select name from projects where id = project_id) as project_name,
                                   (select name from plans where id = psl.plan_id) as plan_name, 
                                   '' as EID, '' as employee_name, '' as client_id,
                                   category, summary, 
                                   replace(replace(replace(replace(psl.log,'<b>',''),'</b>',''),'<strong>',''),'</strong>','') as log                
                            from portal_issue_log psl 
                            where
                            psl.project_id = :project_id
                            and employee_id = ''  
                            {$planAdd['query']}  order by updated_at desc
                         )
                        ) order by updated_at desc;
                     ";
        }
        else
        {
            $stmt = "
                    select
                       psl.status, psl.updated_at, p.name as project_name, 
                       plan.name as plan_name, 
                       e.id as EID, e.name as employee_name, e.client_id as client_id,
                       psl.category, psl.summary,
                       replace(replace(replace(replace(psl.log,'<b>',''),'</b>',''),'<strong>',''),'</strong>','') as log          
                    from portal_issue_log psl
                        left join employees e on e.id=employee_id
                        left join projects p on p.id=psl.project_id
                        left join plans plan on plan.id=psl.plan_id
                        where e.force_void <> '1'
                        and psl.project_id = :project_id
                        {$planAdd['query']}
                        {$this->pop_filter}
                    order by updated_at desc
                ";
        }

        return $this->runReport($stmt,
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
    }

    public function reportLateSubmission()
    {
        $stmt = "SELECT 
                        e.id AS reference_number, e.client_id AS employee_client_id, e.name AS employee_name, e.client_status,
                        s.file_name AS file_name, IF (s.scan_type = 'M','Mail', IF (s.scan_type = 'F','Fax',
                        IF (s.scan_type = 'A','Admin Upload', IF (s.scan_type = 'U','Upload','')))) AS late_submission_method,
                        s.date AS date_of_submission, 
                        IF (
                            p.project_type = 'POE',
                            e.poe_start_date,
                            p.g_audit_start_date
                            ) AS start_date,
                        IF (
                            p.project_type = 'POE',
                            e.poe_stop_date,
                            p.g_audit_stop_date
                            ) AS stop_date
                FROM
                      employees e,
                      projects p,
                      scans s
                WHERE e.project_id = p.id
                      AND e.id = s.eid
                      AND e.project_id = :project_id
                      AND (s.date >= DATE_ADD(g_audit_stop_date, INTERVAL 1 DAY)
                                    OR s.date >= DATE_ADD(e.poe_stop_date, INTERVAL 1 DAY)
                      )
               ";

        return $this->runReport($stmt, array(':project_id' => $this->projectid));
    }

    public function reportTermDetails()
    {
        $planAdd = $this->addPlanParameter('','e');
        return $this->runReport("SELECT e.id as reference_number, e.client_id,
            e.client_value_1,e.client_value_2,e.client_value_3,e.client_value_4,e.client_value_5,
            e.client_value_6,e.client_value_7,e.client_value_8,e.client_value_9,e.client_value_10,
            e.name as emp_name,d.id as DID, d.name as dep_name, d.rel as dep_rel_code, d.dob as dep_dob, d.term_type, d.term_date, plans.name as plan,
            if (p.project_type='POE',e.poe_start_date,p.g_audit_start_date) as start_date,
            if (p.project_type='POE',e.poe_stop_date,p.g_audit_stop_date) as stop_date
            ".POE_DEBUG_FIELDS."
            FROM dependents d, employees e, plans, projects p
            WHERE e.id=d.employee_id
                AND e.project_id = p.id
                AND d.plan_id=plans.id
                AND e.project_id = :project_id
                AND e.force_void <> '1'
                {$planAdd['query']} {$this->pop_filter}
                AND d.status='term'
                AND d.term_type!='client update'
                ORDER BY e.id, d.id",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
    }

    public function reportCompleted()
    {
        $planAdd = $this->addPlanParameter('','e');
        return $this->runReport("SELECT e.id as reference_number, plans.name as plan, e.client_id,
            e.client_value_1,e.client_value_2,e.client_value_3,e.client_value_4,e.client_value_5,
            e.client_value_6,e.client_value_7,e.client_value_8,e.client_value_9,e.client_value_10,
            e.email, e.email_rcvd, e.email_portal, e.name as emp_name, e.status as emp_status, e.client_status as emp_client_status,
            if (p.project_type='POE',e.poe_start_date,p.g_audit_start_date) as start_date,
            if (p.project_type='POE',e.poe_stop_date,p.g_audit_stop_date) as stop_date
            ".POE_DEBUG_FIELDS."
            FROM employees e, plans, projects p
            WHERE e.plan_id=plans.id
                AND e.project_id = p.id
                AND e.force_void <> '1'
                AND e.project_id = :project_id
                {$planAdd['query']} {$this->pop_filter}
                AND e.status='complete'
                ORDER BY e.id",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
    }

    public function reportBounced()
    {
        $planAdd = $this->addPlanParameter('','e');
        return $this->runReport("SELECT e.id AS reference_number, e.name, e.email, e.email_rcvd, e.email_portal, l.date
            ".POE_DEBUG_FIELDS."
            FROM log l, employees e, projects p
            WHERE l.employee_id=e.id
                AND e.project_id = p.id
                AND e.force_void <> '1'
                AND e.project_id = :project_id
                {$planAdd['query']} {$this->pop_filter}
                AND l.log_type=3
                AND l.msg='Email bounce received'
                ORDER BY e.id",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
    }

    public function reportAddressUpdates()
    {
        $planAdd = $this->addPlanParameter('','e');
        return $this->runReport("
                    SELECT e.id as EID, e.id as reference_number, plans.name as plan, e.client_id,
                        e.client_value_1,e.client_value_2,e.client_value_3,e.client_value_4,e.client_value_5,
                        e.client_value_6,e.client_value_7,e.client_value_8,e.client_value_9,e.client_value_10,
                        e.email, e.email_rcvd, e.email_portal, e.name as emp_name,
                        e.status as emp_status, e.client_status as emp_client_status,
                        e.street, e.street2, e.city, e.state, e.zip,
                        if (e.address_status='Invalid', 'Y','') as invalid,
                        if (INSTR(group_concat(l.msg), 'Address updated'), 'Y','') as updated,
                        if (INSTR(group_concat(l.msg), 'Address updated (NCOA)'), 'Y','') as NCOA,
                        if (INSTR(group_concat(l.msg), 'IVR status'),'Y','') as IVR,
                        if (INSTR(group_concat(l.msg), 'portal access'),'Y','') as Portal
                    FROM plans, projects p,
                        (employees e left join log l on e.id=l.employee_id and l.log_type=2 )
                    WHERE e.plan_id=plans.id
                        AND e.project_id = p.id
                        AND e.project_id = :project_id
                        AND e.force_void <> '1'
                        {$planAdd['query']} {$this->pop_filter}
                        AND (l.msg like 'address updated%' or e.address_status='invalid')
                    GROUP BY e.id
                    ORDER BY e.id",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
    }

    public function reportInactivity()
    {
        $planAdd = $this->addPlanParameter('','e');
        return $this->runReport("SELECT e.id as reference_number, plans.name as plan, e.client_id,
            e.client_value_1,e.client_value_2,e.client_value_3,e.client_value_4,e.client_value_5,
            e.client_value_6,e.client_value_7,e.client_value_8,e.client_value_9,e.client_value_10,
            e.email, e.email_rcvd, e.email_portal, e.sms, e.sms_portal_status, e.name as emp_name, e.status as emp_status, e.client_status as emp_client_status,
            if (p.project_type='POE',e.poe_start_date,p.g_audit_start_date) as start_date,
            if (p.project_type='POE',e.poe_stop_date,p.g_audit_stop_date) as stop_date,
            if (INSTR(group_concat(l.msg), 'IVR status'),'Y','N') as IVR,
            if (INSTR(group_concat(l.msg), 'portal access'),'Y','N') as Portal
            ".POE_DEBUG_FIELDS."
            FROM plans, projects p,
                (employees e left join log l on e.id=l.employee_id and l.log_type=2 and l.user_id=0 )
            WHERE e.plan_id=plans.id
                AND e.status='open'
                AND e.project_id = p.id
                AND e.force_void <> '1'
                AND e.project_id = :project_id
                {$planAdd['query']} {$this->pop_filter}
            GROUP BY e.id
            ORDER BY e.id;",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
    }

    public function reportFinalExtract()
    {
        $tbl = "_rpt_".$_SESSION["user"]->getID()."_otmp";

        // Get docs from central
        $central_db = new Model();
        $document_results = $central_db->getRows("SELECT id, name as document FROM documents ORDER BY id");

        if ($document_results)
        {
            // Parse document list and build logic for checking dependent
            // documents
            $documents           = array();
            $dependent_doc_check = array();
            foreach($document_results as $row)
            {
                $documents[$row['id']] = $row['document'];
                $dependent_doc_check[] = "IF(d.d".$row['id'].",'".$row['document']." ','')"; // space required after docname
            }
            $dependent_document_sql = 'CONCAT('.implode(',',$dependent_doc_check).') AS `docs_received`, ';

        } else {
            return FALSE;
        }

        $db = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter('','e');
        $db->q("drop table if exists `{$tbl}`");

        $db->createTableFromSelect($tbl,"SELECT e.id AS reference_number, e.client_id as employee_client_id,

                e.client_value_1 as employee_client_value_1, e.client_value_2 as employee_client_value_2,
                e.client_value_3 as employee_client_value_3, e.client_value_4 as employee_client_value_4,
                e.client_value_5 as employee_client_value_5, e.client_value_6 as employee_client_value_6,
                e.client_value_7 as employee_client_value_7, e.client_value_8 as employee_client_value_8,
                e.client_value_9 as employee_client_value_9, e.client_value_10 as employee_client_value_10,

                e.name as employee_name,
                e.street as employee_street_1, e.street2 as employee_street_2, e.city as employee_city,
                e.state as employee_state, e.zip as employee_zip, e.phone as employee_phone,
                e.email as employee_email, e.email_rcvd as employee_received_email,
                e.email_portal as employee_email_portal, e.status AS employee_status,
                e.client_status AS employee_client_status, if(e.signed=1,'YES','NO') as employee_signed,
                d.id as DID, d.client_id as dependent_client_id,

                d.client_value_1 as dependent_client_value_1, d.client_value_2 as dependent_client_value_2,
                d.client_value_3 as dependent_client_value_3, d.client_value_4 as dependent_client_value_4,
                d.client_value_5 as dependent_client_value_5, d.client_value_6 as dependent_client_value_6,
                d.client_value_7 as dependent_client_value_7, d.client_value_8 as dependent_client_value_8,
                d.client_value_9 as dependent_client_value_9, d.client_value_10 as dependent_client_value_10,

                d.name as dependent_name, d.rel as dependent_rel_code, d.dob as dependent_dob,
                d.products as product, d.valid_override as
                `admin_override`, d.status AS dep_status, d.client_status AS dep_client_status, d.ssn AS dep_ssn_orig,
                d.collected_ssn AS dep_ssn_collected, r.req as docs_required,
                $dependent_document_sql
                IF(d.status='term',d.term_type,'') AS `term_type`,
                IF(d.status='term',d.term_date,'') AS `term_date`,
                if (p.project_type='POE',e.poe_start_date,p.g_audit_start_date) as start_date,
                if (p.project_type='POE',e.poe_stop_date,p.g_audit_stop_date) as stop_date, plans.name as plan
                ".POE_DEBUG_FIELDS."
                FROM employees e, dependents d, plans, rules r, projects p
                WHERE plans.id=e.plan_id
                    AND e.project_id = p.id
                    AND e.project_id = :project_id
                    {$planAdd['query']} {$this->pop_filter}
                    AND e.id=d.employee_id
                    AND e.force_void <> '1'
                    AND d.rel=r.rel
                    AND d.plan_id=r.plan_id
                    ORDER BY e.id",
            array_merge(array(':project_id' => $this->projectid),
                        $planAdd['params'] ,
                        $this->pop_filter_params));

        $db->q("alter table {$tbl} add column docs_outstanding varchar(255) not null after docs_received, add index(docs_required), add index(docs_received)");
        $result = $db->getRows("select distinct docs_required, docs_received from {$tbl}");

        // Bail out on failure
        if (!$result) { return FALSE; }

        foreach($result as $row)
        {
            $req = str_replace(array('(',')','|','&'),'',$row['docs_required']);
            $reqArray = array();
            foreach(explode('d',$req) as $docId)
            {
                if (isset($documents[$docId]))
                {
                    $reqArray[] = $documents[$docId];
                }
            }
            $docsArray  = explode(' ',$row['docs_received']);
            $reqArray   = array_diff($reqArray, $docsArray);
            $db->q("update {$tbl} set docs_outstanding = '" . implode(',',$reqArray) . "' where docs_required = :docs_required and docs_received = :docs_received",
                array(':docs_required'   => $row['docs_required'],
                      ':docs_received'   => $row['docs_received']));
        }

        krsort($documents); // required so we replace d100 before d1
        foreach ($documents as $id => $name)
        {
            $db->q("update {$tbl} set docs_required=replace(docs_required,:did, :name)",
                array(':did'  => "d{$id}",
                      ':name' => $name ));
        }

        $data = $db->getRows("SELECT * from {$tbl}");
        $db->q("drop table if exists `{$tbl}`");

        return $data;
    }

    public function reportClientDrop()
    {
        // client drop only for poe
        if (!$this->isPoe())
        {
            return FALSE;
        }

        $tbl    = "_rpt_".$_SESSION["user"]->getID()."_otmp";
        $db     = new Model($this->ip, $this->db);
        $planAdd = $this->addPlanParameter('','e');

        $db->q("drop table if exists `{$tbl}`");
        $db->createTableFromSelect($tbl, "SELECT e.id AS reference_number, e.client_id as employee_client_id,

                e.client_value_1 as employee_client_value_1, e.client_value_2 as employee_client_value_2,
                e.client_value_3 as employee_client_value_3, e.client_value_4 as employee_client_value_4,
                e.client_value_5 as employee_client_value_5, e.client_value_6 as employee_client_value_6,
                e.client_value_7 as employee_client_value_7, e.client_value_8 as employee_client_value_8,
                e.client_value_9 as employee_client_value_9, e.client_value_10 as employee_client_value_10,

                e.name as employee_name, d.id as DID, d.client_id as dependent_client_id,

                d.client_value_1 as dependent_client_value_1, d.client_value_2 as dependent_client_value_2,
                d.client_value_3 as dependent_client_value_3, d.client_value_4 as dependent_client_value_4,
                d.client_value_5 as dependent_client_value_5, d.client_value_6 as dependent_client_value_6,
                d.client_value_7 as dependent_client_value_7, d.client_value_8 as dependent_client_value_8,
                d.client_value_9 as dependent_client_value_9, d.client_value_10 as dependent_client_value_10,

                d.name as dependent_name, d.rel as dependent_rel_code, d.dob as dependent_dob,
                d.products as product, d.term_type, d.term_date, plans.name as plan
            FROM dependents d, employees e, plans, projects p
            WHERE e.id=d.employee_id
                AND e.project_id = p.id
                AND e.force_void <> '1'
                AND e.project_id = :project_id
                {$planAdd['query']} {$this->pop_filter}
                AND d.plan_id=plans.id
                AND d.status='term'
                AND d.term_type NOT IN ('client update','duplicate')
                AND d.drop_status!='Dropped'
                AND ((e.status='complete'
                    AND e.substatus in ('pending term',''))
                    OR (d.term_type in ('amnesty phase')))
            ORDER BY e.id, d.id", array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));

        $data = $db->getRows("SELECT reference_number, employee_client_id,
            employee_client_value_1, employee_client_value_2, employee_client_value_3, employee_client_value_4,
            employee_client_value_5, employee_client_value_6, employee_client_value_7, employee_client_value_8,
            employee_client_value_9, employee_client_value_10, employee_name, dependent_client_id,
            dependent_client_value_1, dependent_client_value_2, dependent_client_value_3, dependent_client_value_4,
            dependent_client_value_5, dependent_client_value_6, dependent_client_value_7, dependent_client_value_8,
            dependent_client_value_9, dependent_client_value_10, dependent_name, dependent_rel_code, dependent_dob,
            product, term_type, term_date, plan from {$tbl}");

        return $data;
    }
//fix
    public function reportCDC($isFiltered = false)
    {
        $tbl = "_rpt_".$_SESSION["user"]->getID()."_cdc_tmp1";
        $tmp1 = "_rpt_".$_SESSION["user"]->getID()."_cdc_tmp2";

        $db = new Model($this->ip, $this->db);

        $result = $db->getVal("SELECT count(*) as count
                   FROM project_questions where project_id= :project_id and type!='label' and enabled='1'",
            array(':project_id' => $this->projectid));

        if ($result <= 0)
        {
            die("No customer data collection questions configured for this project.");
        }

        $planAdd = $this->addPlanParameter('','e');
        # create base table with employee information
        $db->q("drop table if exists {$tbl}, {$tmp1}");

        $db->q("CREATE TABLE {$tmp1} ( `EID` varchar(255) NOT NULL,
             `plan_name` varchar(100) NOT NULL DEFAULT '', `emp_client_id` varchar(255) NOT NULL,
             `emp_name` varchar(255) NOT NULL DEFAULT '', `emp_dob` varchar(100) not null,
             `emp_status` varchar(255) NOT NULL, `DID` int(11) NOT NULL DEFAULT '0',
             `dep_client_id` varchar(255) NOT NULL, `dep_name` varchar(255) DEFAULT NULL,
             `dep_dob` varchar(100) not null, `dep_status` varchar(255) NOT NULL,
             `rel` varchar(255) NOT NULL DEFAULT '', `dep_rel` varchar(255) NOT NULL DEFAULT '',
             `plan_id` int(255) NOT NULL DEFAULT '0',
              `poe_start_date` date NOT NULL, `poe_stop_date` date NOT NULL)");

        $db->q("INSERT INTO {$tmp1} SELECT e.id as EID, plans.name as plan_name, e.client_id as emp_client_id, e.name as emp_name,
                    e.dob as emp_dob, e.client_status as emp_status, d.id as DID, d.client_id as dep_client_id, d.name as dep_name,
                    d.dob as dep_dob, d.client_status as dep_status, d.rel, r.notes as dep_rel, d.plan_id, e.poe_start_date, e.poe_stop_date 
                    from employees e, dependents d, rules r, plans, projects p
                    WHERE e.plan_id=plans.id
                      AND e.project_id = p.id
                      AND e.id=d.employee_id
                      AND e.force_void <> '1'
                      AND d.plan_id=r.plan_id
                      AND d.rel=r.rel
                      AND e.project_id = :project_id
                      {$planAdd['query']} {$this->pop_filter}",
            array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));

        # if employee based questions and insert them first
        $hasEmpQuestions = $db->getVal("select count(*) as count from project_questions where project_id = :project_id and rel='EE'",
            array(':project_id' => $this->projectid));

        if($hasEmpQuestions >= 1)
        {
            $db->q("INSERT INTO {$tmp1}(EID, plan_name, emp_client_id, emp_name, emp_dob, emp_status, rel, plan_id,poe_start_date,poe_stop_date)
                       SELECT e.id, plans.name, client_id, e.name, dob, client_status, 'EE', plan_id, e.poe_start_date, e.poe_stop_date
                       FROM employees e, plans, projects p
                       WHERE p.id=e.plan_id
                          AND e.force_void <> '1'
                          AND e.project_id = :project_id  {$this->pop_filter}",
                array_merge(array(':project_id' => $this->projectid), $planAdd['params'] , $this->pop_filter_params));
        }
        # create new table from the results, index it and remove old tmp table
        $db->createTableFromSelect($tbl, "select * from {$tmp1} order by EID, DID");
        $db->q("alter table {$tbl} add index(DID), add index(EID), add index(rel), add index(plan_id)");
        $db->q("drop table if exists {$tmp1}");

        # create table with project questions

        $db->q("CREATE TABLE `{$tmp1}` ( `id` mediumint(8) unsigned NOT NULL DEFAULT '0',
            `name` varchar(30) NOT NULL, `question` tinytext,
            `plan_id` mediumint(8) unsigned NOT NULL, `rel` char(4) NOT NULL,
            `display_order` tinyint(3) unsigned DEFAULT '0',
            PRIMARY KEY (`id`), KEY `name` (`name`), KEY `question` (`question`(100)) )");

        $db->q("INSERT INTO {$tmp1} SELECT id, name, question, plan_id, rel, display_order
                   from project_questions where project_id = :project_id and type!='label' and enabled='1'
                   order by plan_id, rel, display_order",
            array(':project_id' => $this->projectid));

        # add column for each question
        $fields = array();
        $res = $db->getRows("select distinct concat('q_',id) as c from {$tmp1} order by plan_id, rel, display_order");

        foreach($res as $c)
        {
            $fields[] = " add column `".$c['c']."` varchar(100) not null";
        }

        #Fix for INNODB row length issue
        $db->q("ALTER TABLE {$tbl} ENGINE=MYISAM");
        $db->q("alter table {$tbl} ".implode(',', $fields));

        # add column descriptions
        $k = '';
        $v = '';
        $res = $db->getRows("select distinct concat('q_',id) as c, question from {$tmp1}");

        foreach($res as $row)
        {
            $k.= ", `{$row['c']}`";
            $v.= ", '".addslashes($row['question'])."'";
        }

        $db->q("insert into {$tbl} (EID, plan_name,  emp_client_id, emp_name, emp_dob, emp_status,
                    DID, dep_client_id, dep_name, dep_dob, dep_rel, dep_status{$k})
                    select 'EID', 'Plan', 'emp_client_id', 'emp_name', 'emp_dob', 'emp_status', 'DID',
                    'dep_client_id', 'dep_name', 'dep_dob', 'dep_rel', 'dep_status'{$v}");



        /*
         * Table is setup at this point
         */

        # add dependent answer defaults
        $res = $db->getRows("select q.rel, q.plan_id, group_concat('q_',q.id) as question_ids
                           from project_questions q
                           where q.project_id= :project_id and type!='label' and enabled='1'
                           group by plan_id, rel
                           order by plan_id, rel, q.id",
            array(':project_id' => $this->projectid));

        foreach($res as $row)
        {
            $ids = implode("='No Response', ",  explode(',', $row['question_ids']))."='No Response'";
            $db->q("update $tbl set $ids 
                    where rel = :rel and plan_id = :plan_id",
                array(':rel' => $row['rel'], ':plan_id' => $row['plan_id']));
        }

        # add employee answers if needed
        if($hasEmpQuestions)
        {
            $res = $db->getRows("SELECT e.id as id, group_concat(' q_',t.id,'=[|', a.answer,'[|') as answer
                                FROM employees e, project_question_answers a, {$tmp1} t
                                WHERE e.force_void <> '1' 
                                  AND e.project_id = :project_id 
                                  AND e.id=a.employee_id
                                  AND a.dependent_id=0 
                                  AND a.question_id=t.id 
                                  AND e.status='complete'" .
                                ($this->isPoe() ? ' AND e.`poe_stop_date` >= CURRENT_DATE() - INTERVAL 2 MONTH' : '') .
                                " group by a.employee_id",
                        array(':project_id' => $this->projectid));

            if (!empty($res))
            {
                foreach($res as $row)
                {
                    $db->q("update {$tbl} set ".str_replace('[|','\'',addslashes($row['answer'])) .
                           " where DID='' and EID= :employee_id",
                        array(':employee_id' => $row['id']));
                }
            }
        }

        # add dependent answers
        $res = $db->getRows("select d.id as id, group_concat(' q_',t.id,'=[|', a.answer,'[|') as answer
                                   from dependents d, employees e, project_question_answers a, {$tmp1} t
                                  where d.project_id = :project_id 
                                    and e.id = d.employee_id
                                    and e.force_void <> '1'
                                    and d.id=a.dependent_id and a.question_id=t.id
                                    and e.status = 'complete'" .
                            ($this->isPoe() ? ' AND e.`poe_stop_date` >= CURRENT_DATE() - INTERVAL 2 MONTH' : '') .
                            " group by a.dependent_id ",
                    array(':project_id' => $this->projectid));

        if (!empty($res))
        {
            foreach ($res as $row)
            {
                $db->q("update {$tbl} set " . str_replace('[|', '\'', addslashes($row['answer']))
                       ." where DID = :dependent_id",
                        array(':dependent_id' => $row['id']));
            }
        }

        // DMND0072624 - CDC questions not setup for rel (for this relationship in project)
        if ($isFiltered)
        {
            $rel = $db->getVals("SELECT DISTINCT rel FROM `project_questions` WHERE project_id = :project_id",
                                array(':project_id' => $this->projectid));

            $db->q("delete from {$tbl} where rel not in ('', '" .
                (is_array($rel) ? implode("','", array_filter($rel)) : $rel) . "')");
        }

        # Clean up
        $db->q("alter table {$tbl} drop column plan_id, drop column rel");
        $db->q("drop table if exists {$tmp1}");

        # return report
        $data = $db->getRows("SELECT * from `{$tbl}` order by if(EID='EID',0,EID), DID");
        return $data;
    }

    public function clientDrop($eid_to_confirm)
    {
        // client drop only for poe
        if (!$this->isPoe())
        {
            return FALSE;
        }

        if ($eid_to_confirm)
        {
            $response   = array();

            $tbl    = "_rpt_".$_SESSION["user"]->getID()."_otmp";
            $db     = new Model($this->ip, $this->db);

            $tableFound = FALSE;
            $tableCheck = $db->getVals("show tables like '{$tbl}'");

            if(is_array($tableCheck))
            {
                foreach($tableCheck as $table) # show tables like changes array key, have to loop
                {
                    if($table == $tbl)
                    {
                        $tableFound = TRUE;
                    }
                }
            }

            if($tableFound == FALSE)
            {
                return "Report not generated, please generate report before attempting drop.";
            }

            $results = $db->getRows("select * from `{$tbl}`");

            if (!$results)
                return FALSE;
            }

            if (isset($results['reference_number']))
            {
                // stupid query return
                $results = array($results);
            };

            $last_row = count($results)-1;
            if ($results[$last_row]['reference_number'] == $eid_to_confirm)
            {
                $response['total_rows'] = count($results);
                $date = date('Y-m-d H:i:s');

                // do drop
                $x=0;
                foreach($results as $result)
                {
                    $db->q("INSERT INTO `log` (`date`, employee_id, log_type, link, msg) 
                        VALUES (NOW(), :reference_number,3,'', :did)",
                        array(':reference_number' => $result['reference_number'],
                            ':did' => 'Client notified of term: ' . strtoupper($result['dependent_name'])) . " ($date)");

                    $db->q("INSERT INTO `log` (`date`, employee_id, log_type, link, msg) 
                        VALUES (NOW(), :reference_number,0,'', :did)",
                         array(':reference_number' => $result['reference_number'],
                          ':did' => 'dep' . $result['DID'] . '[drop_status]:  => Dropped'));

                    $db->q("update dependents set drop_status='Dropped' where id = :id",
                        array(':id' =>  $result['DID']));
                }

                $db->q("drop table if exists `{$tbl}`");
            }
            else
            {
                $response = TRUE;
            }

            return $response;
    }

    private function runReport($sql,$params = array())
    {
        $db         = new Model($this->ip, $this->db);
        $data       = $db->getRows($sql,$params);
        return $data;
    }

    /*
     * Roche Specific
     */

    public function reportRocheFinalExtract()
    {
        $db     = new Model($this->ip, $this->db);

        //        # Determine the raw table to use
                // was going to do this but don't need to after all
        //        $src_table = $db->getVal("SELECT src_table FROM dependents WHERE project_id = {$this->projectid} LIMIT 1;");
        //
        //        # Validate table
        //        if (!$db->getVal("SELECT table_name FROM information_schema.TABLES WHERE table_name ='raw_roche' LIMIT 1;"))
        //        {
        //            return FALSE;
        //        }

        if ($this->isPoe())
        {
            $result = $db->getRows("SELECT * FROM (
              SELECT e.client_id AS EmployeeID,
                    e.client_value_10 as `Employee Last Name`,
                    e.client_value_9 as `Employee Middle Initial`,
                    e.client_value_8 as `Employee First Name`,

                    d.client_id as `Depend. Id`,

                    d.client_value_10 as `Dependent Last Name`,
                    d.client_value_9 as `Dependent Middle Initial`,
                    d.client_value_8 as `Dependent First Name`,

                    DATE_FORMAT(d.dob, '%m/%d/%Y') as `Dep DOB`,
                    IF(d.rel='1M','1',
                      IF(d.rel='3M','13',
                        IF(d.rel='2D','2',
                          TRIM(LEADING '0' FROM d.rel)
                        )
                      )
                    ) as `Rel. Code`,
                    d.client_value_4 as `Rel. Desc.`,

                    '1' as `Dep Verification Code`,
                    'Doc' as `Dep Ver Desc`,
                    IF(d.`client_status`='Verified',DATE_FORMAT(poe_stop_date,'%m/%d/%Y'),'') as `Dep Ver Date`,
                    IF(pa1.answer IS NULL, '',
                      if (LOCATE('Employed and Enrolled',pa1.answer), 'Y',
                        if (LOCATE('Employed and NOT Enrolled',pa1.answer), 'Y',
                          if (LOCATE('Employed and Pay Full Premium',pa1.answer), 'N',
                            if (LOCATE('Employed and NOT Offered',pa1.answer), 'N',
                              if (LOCATE('Self Employed',pa1.answer), 'N',
                                if (LOCATE('Not Employed/Retired',pa1.answer), 'N',
                                  if (LOCATE('Employed by Roche or Genentech',pa1.answer), 'N','')
                                )
                              )
                            )
                          )
                        )
                      )
                    ) as `Surcharge`,
                    IF(pa1.answer IS NULL, '',
                      if (LOCATE('Employed and Enrolled',pa1.answer), 'employed_enrolled',
                        if (LOCATE('Employed and NOT Enrolled',pa1.answer), 'employed_offered_not enrolled',
                          if (LOCATE('Employed and Pay Full Premium',pa1.answer), 'employed_full_premium',
                            if (LOCATE('Employed and NOT Offered',pa1.answer), 'employed_not_offered',
                              if (LOCATE('Self Employed',pa1.answer), 'self_employed',
                                if (LOCATE('Not Employed/Retired',pa1.answer), 'not_employed_retired',
                                  if (LOCATE('Employed by Roche or Genentech',pa1.answer), 'also_an_employee','')
                                )
                              )
                            )
                          )
                        )
                      )
                    ) as `spouse_coverage`

                    FROM employees e
                    JOIN projects p ON p.id=e.project_id
                    JOIN dependents d ON e.id=d.employee_id

                    JOIN project_question_answers pa1 ON pa1.employee_id=e.id AND pa1.dependent_id=d.id
                    JOIN project_questions pq1 ON pq1.project_id=e.project_id AND pq1.id=pa1.question_id AND pq1.name = 'spouse_employed'


                    WHERE e.force_void <> '1' and e.project_id = :project_id {$this->pop_filter}

              UNION ALL

              SELECT e.client_id AS EmployeeID,
                    e.client_value_10 as `Employee Last Name`,
                    e.client_value_9 as `Employee Middle Initial`,
                    e.client_value_8 as `Employee First Name`,

                    d.client_id as `Depend. Id`,

                    d.client_value_10 as `Dependent Last Name`,
                    d.client_value_9 as `Dependent Middle Initial`,
                    d.client_value_8 as `Dependent First Name`,

                    DATE_FORMAT(d.dob, '%m/%d/%Y') as `Dep DOB`,
                    IF(d.rel='1M','1',
                      IF(d.rel='3M','13',
                        IF(d.rel='2D','2',
                          TRIM(LEADING '0' FROM d.rel)
                        )
                      )
                    ) as `Rel. Code`,
                    d.client_value_4 as `Rel. Desc.`,

                    '1' as `Dep Verification Code`,
                    'Doc' as `Dep Ver Desc`,
                    IF(d.`client_status`='Verified',DATE_FORMAT(poe_stop_date,'%m/%d/%Y'),'') as `Dep Ver Date`,
                    '' as `Surcharge`,
                    '' as `spouse_coverage`

                    FROM employees e
                    JOIN projects p ON p.id=e.project_id
                    JOIN dependents d ON e.id=d.employee_id

                    WHERE e.force_void <> '1' and e.project_id = :project_id {$this->pop_filter}
            ) combined
            GROUP BY `Depend. Id`
            ORDER BY EmployeeID;",

                array_merge(array(':project_id' => $this->projectid), $this->pop_filter_params));
        }
        # click to verify
        elseif ($this->projectid == '2526' # PROD 2017
            || $this->projectid == '2102' # PROD
            || $this->projectid == '2095'
            || $this->projectid == '2101') # QA
        {
            $result = $db->getRows("SELECT * FROM (
              SELECT r.employee_client_id AS EmployeeID,
                r.employee_client_value_10 as `Employee Last Name`,
                r.employee_client_value_9 as `Employee Middle Initial`,
                r.employee_client_value_8 as `Employee First Name`,

                r.dependent_client_id as `Depend. Id`,

                r.dependent_client_value_10 as `Dependent Last Name`,
                r.dependent_client_value_9 as `Dependent Middle Initial`,
                r.dependent_client_value_8 as `Dependent First Name`,

                DATE_FORMAT(d.dob, '%m/%d/%Y') as `Dep DOB`,
                IF(d.rel='1M','1',
                      IF(d.rel='3M','13',
                        IF(d.rel='2D','2',
                          TRIM(LEADING '0' FROM d.rel)
                        )
                      )
                    ) as `Rel. Code`,
                r.dependent_client_value_4 as `Rel. Desc.`,

                '0' as `Dep Verification Code`,
                'No Doc' as `Dep Ver Desc`,
                IF(d.`client_status`='Not Terminated',DATE_FORMAT(p.g_audit_stop_date,'%m/%d/%Y'),'') as `Dep Ver Date`,
                IF(pa1.answer IS NULL, '', pa1.answer) as `Surcharge`,
                IF(pa2.answer IS NULL, '', pa2.answer) as `spouse_coverage`

                FROM employees e
                JOIN projects p ON p.id=e.project_id
                JOIN dependents d ON e.id=d.employee_id
                JOIN raw_roche_click_to_verify r ON  r.id=d.src_id

                JOIN project_question_answers pa1 ON pa1.employee_id=e.id AND pa1.dependent_id=d.id
                JOIN project_questions pq1 ON pq1.project_id=e.project_id AND pq1.id=pa1.question_id AND pq1.name = 'surcharge'

                JOIN project_question_answers pa2 ON pa2.employee_id=e.id AND pa2.dependent_id=d.id
                JOIN project_questions pq2 ON pq2.project_id=e.project_id AND pq2.id=pa2.question_id AND pq2.name = 'spouse_coverage'

                WHERE e.force_void <> '1' and e.project_id = :project_id

              UNION ALL

              SELECT r.employee_client_id AS EmployeeID,
                r.employee_client_value_10 as `Employee Last Name`,
                r.employee_client_value_9 as `Employee Middle Initial`,
                r.employee_client_value_8 as `Employee First Name`,

                r.dependent_client_id as `Depend. Id`,

                r.dependent_client_value_10 as `Dependent Last Name`,
                r.dependent_client_value_9 as `Dependent Middle Initial`,
                r.dependent_client_value_8 as `Dependent First Name`,

                DATE_FORMAT(d.dob, '%m/%d/%Y') as `Dep DOB`,
                IF(d.rel='1M','1',
                      IF(d.rel='3M','13',
                        IF(d.rel='2D','2',
                          TRIM(LEADING '0' FROM d.rel)
                        )
                      )
                    ) as `Rel. Code`,
                r.dependent_client_value_4 as `Rel. Desc.`,

                '0' as `Dep Verification Code`,
                'No Doc' as `Dep Ver Desc`,
                IF(d.`client_status`='Not Terminated',DATE_FORMAT(p.g_audit_stop_date,'%m/%d/%Y'),'') as `Dep Ver Date`,
                '' as `Surcharge`,
                '' as `spouse_coverage`

                FROM employees e
                JOIN projects p ON p.id=e.project_id
                JOIN dependents d ON e.id=d.employee_id
                JOIN raw_roche_click_to_verify r ON  r.id=d.src_id

                WHERE e.force_void <> '1' and e.project_id =  :project_id
            ) combined
            GROUP BY `Depend. Id`
            ORDER BY EmployeeID;",
                array(':project_id' => $this->projectid));
        }
        else // typical audit
        {
            $result = $db->getRows("SELECT * FROM (
              SELECT r.employee_client_id AS EmployeeID,
                    r.employee_client_value_10 as `Employee Last Name`,
                    r.employee_client_value_9 as `Employee Middle Initial`,
                    r.employee_client_value_8 as `Employee First Name`,

                    r.dependent_client_id as `Depend. Id`,

                    r.dependent_client_value_10 as `Dependent Last Name`,
                    r.dependent_client_value_9 as `Dependent Middle Initial`,
                    r.dependent_client_value_8 as `Dependent First Name`,

                    DATE_FORMAT(d.dob, '%m/%d/%Y') as `Dep DOB`,
                    IF(d.rel='1M','1',
                      IF(d.rel='3M','13',
                        IF(d.rel='2D','2',
                          TRIM(LEADING '0' FROM d.rel)
                        )
                      )
                    ) as `Rel. Code`,
                    r.dependent_client_value_4 as `Rel. Desc.`,

                    '1' as `Dep Verification Code`,
                    'Doc' as `Dep Ver Desc`,
                    IF(d.`client_status`='Verified',DATE_FORMAT(p.g_audit_stop_date,'%m/%d/%Y'),'') as `Dep Ver Date`,
                    IF(pa1.answer IS NULL, '',
                      if (LOCATE('Employed and Enrolled',pa1.answer), 'Y',
                        if (LOCATE('Employed and NOT Enrolled',pa1.answer), 'Y',
                          if (LOCATE('Employed and Pay Full Premium',pa1.answer), 'N',
                            if (LOCATE('Employed and NOT Offered',pa1.answer), 'N',
                              if (LOCATE('Self Employed',pa1.answer), 'N',
                                if (LOCATE('Not Employed/Retired',pa1.answer), 'N',
                                  if (LOCATE('Employed by Roche or Genentech',pa1.answer), 'N','')
                                )
                              )
                            )
                          )
                        )
                      )
                    ) as `Surcharge`,
                    IF(pa1.answer IS NULL, '',
                      if (LOCATE('Employed and Enrolled',pa1.answer), 'employed_enrolled',
                        if (LOCATE('Employed and NOT Enrolled',pa1.answer), 'employed_offered_not enrolled',
                          if (LOCATE('Employed and Pay Full Premium',pa1.answer), 'employed_full_premium',
                            if (LOCATE('Employed and NOT Offered',pa1.answer), 'employed_not_offered',
                              if (LOCATE('Self Employed',pa1.answer), 'self_employed',
                                if (LOCATE('Not Employed/Retired',pa1.answer), 'not_employed_retired',
                                  if (LOCATE('Employed by Roche or Genentech',pa1.answer), 'also_an_employee','')
                                )
                              )
                            )
                          )
                        )
                      )
                    ) as `spouse_coverage`

                    FROM employees e
                    JOIN projects p ON p.id=e.project_id
                    JOIN dependents d ON e.id=d.employee_id
                    JOIN raw_roche r ON r.id=d.src_id

                    JOIN project_question_answers pa1 ON pa1.employee_id=e.id AND pa1.dependent_id=d.id
                    JOIN project_questions pq1 ON pq1.project_id=e.project_id AND pq1.id=pa1.question_id AND pq1.name = 'spouse_employed'

                    WHERE e.force_void <> '1' and e.project_id = :project_id

              UNION ALL

              SELECT r.employee_client_id AS EmployeeID,
                    r.employee_client_value_10 as `Employee Last Name`,
                    r.employee_client_value_9 as `Employee Middle Initial`,
                    r.employee_client_value_8 as `Employee First Name`,

                    r.dependent_client_id as `Depend. Id`,

                    r.dependent_client_value_10 as `Dependent Last Name`,
                    r.dependent_client_value_9 as `Dependent Middle Initial`,
                    r.dependent_client_value_8 as `Dependent First Name`,

                    DATE_FORMAT(d.dob, '%m/%d/%Y') as `Dep DOB`,
                    IF(d.rel='1M','1',
                      IF(d.rel='3M','13',
                        IF(d.rel='2D','2',
                          TRIM(LEADING '0' FROM d.rel)
                        )
                      )
                    ) as `Rel. Code`,
                    r.dependent_client_value_4 as `Rel. Desc.`,

                    '1' as `Dep Verification Code`,
                    'Doc' as `Dep Ver Desc`,
                    IF(d.`client_status`='Verified',DATE_FORMAT(p.g_audit_stop_date,'%m/%d/%Y'),'') as `Dep Ver Date`,
                    '' as `Surcharge`,
                    '' as `spouse_coverage`

                    FROM employees e
                    JOIN projects p ON p.id=e.project_id
                    JOIN dependents d ON e.id=d.employee_id
                    JOIN raw_roche r ON r.id=d.src_id

                    WHERE e.force_void <> '1' and e.project_id = :project_id
            ) combined
            GROUP BY `Depend. Id`
            ORDER BY EmployeeID;",
                array(':project_id' => $this->projectid));
        }

        # Bail out on failure
        if (!$result) { return FALSE; }

        return $result;
    }


    public function hasSelfServiceEntry() {
        if($this->getStatus() == 'Active') {
            return $this->selfServiceEntry;
        }
        return false;
    }

    public function hasSelfServiceTools() {
        if($this->getStatus() == 'Active') {
            return $this->selfServiceTools;
        }
        return false;
    }


}
