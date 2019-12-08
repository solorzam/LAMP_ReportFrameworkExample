<?php

//include("project.model.php");
include(LIB."jp2/jpgraph.php");
include(LIB."jp2/jpgraph_pie.php");
include(LIB."jp2/jpgraph_pie3d.php");
include(LIB."jp2/jpgraph_line.php");
include(LIB."jp2/jpgraph_plotline.php");

class GraphModel
{	
    private $projects;
    private $db;


    private function db()
    {
        if(empty($this->db))
        {
            $this->db = new Model();
        }
        return $this->db;
    }
    
    public function __construct($projects)
    {
        $this->projects = $projects;
    }



    public function employeeStatus($filename)
    {
        # data containers
        $responseData   = 0;
        $noResponseData = 0;
        
        # graph components
        $data   = array();
        $labels = array();
        $colors = array();
        
        # iterate over each project to get
        # needed data
        foreach($this->projects as $project)
        {
            # split on project_id:plan_id
            $ids = preg_split("/\:/", $project);
            
            $project_id  = $ids[0];
            $plan_id     = isset($ids[1]) ? $ids[1] : null;
            
            $project = new ProjectModel($project_id, $plan_id);
            
            if( $project->inAmnesty() )
            {
                # response data
                $responseData += $project->amnestyResponse() + $project->amnestyClientUpdate();
                
                # no response data
                $noResponseData += $project->noAmnestyResponse();
            }
            else
            {
                # response data
                $responseData += $project->completeResponseReceived() + $project->partialResponseReceived();
                
                # no response data
                $noResponseData += $project->noResponseReceived();
            }
        }

        # build graph components
        array_push($data, $responseData);
        array_push($data, $noResponseData);
        array_push($labels, "Response %0.1f%%");
        array_push($colors, "#37424a");
        array_push($labels, "No Response %0.1f%%");
        array_push($colors, "#fb4f14");
        
        if((($responseData === 0 && $noResponseData ===0) || count($data) === 0))
        {
            return false;
        }
        else
        {
            $plot = new PiePlot($data);
            $plot->ShowBorder(false, false);
            $plot->SetLegends($labels);
            $plot->SetSliceColors($colors);
            $plot->SetCenter(0.5, 0.45);
            $plot->SetGuideLines(false);
            $plot->value->SetFormat('%0.1f');
            $plot->value->SetColor('#666');
            
            $graph = new PieGraph(350, 250);
            $graph->SetAntialiasing();
            $graph->legend->SetPos(0.13, 0.92, 'left', 'bottom');
            $graph->legend->SetColumns(2);
            $graph->legend->SetFrameWeight(0);
            $graph->legend->SetFillColor('white');
            $graph->legend->SetShadow('white', 1);
            $graph->legend->SetLineWeight(2);
            $graph->legend->SetColor('#333', 'white');
            $graph->SetFrame(false);
            $graph->Add($plot);
            
            if(file_exists(GRAPHS.$filename))
            {
                unlink(GRAPHS.$filename);
            }
            
            $graph->Stroke(GRAPHS.$filename);

            return $filename;
        }
    }
	
    public function dependentStatus($filename)
    {
        # data containers
        $verifiedData       = 0;
        $unverifiedData     = 0;
        $terminatedData     = 0;
        $notTerminatedData  = 0;
        $noAmnestyResponse  = 0;
        $clientUpdateData   = 0;
        
        # graph components
        $data   = array();
        $labels = array();
        $colors = array();
        
        # iterate over each project to get
        # needed data
        foreach($this->projects as $project)
        {
            # split on project_id:plan_id
            $ids = preg_split("/\:/", $project);
            
            $project_id  = $ids[0];
            $plan_id     = isset($ids[1]) ? $ids[1] : null;
            
            $project = new ProjectModel($project_id, $plan_id);
            
            if( $project->inAmnesty() )
            {
                # gather data
                $notTerminatedData  += $project->amnestyDepsNotTerm();
                $terminatedData     += $project->amnestyDepsTerm();
                $noAmnestyResponse  += $project->amnestyDepsNoResponse();
                $clientUpdateData   += $project->amnestyDepsClientUpdate();
            }
            else
            {
                # gather data
                $verifiedData       += $project->verifiedDependents();
                $unverifiedData     += $project->unverifiedDependents();
                $terminatedData     += $project->termDependents();
                $clientUpdateData   += $project->cliendUpdate();
            }
        }
        
        if( $verifiedData !== 0 )
        {
            array_push($labels, "Verified %0.1f%%");
            array_push($colors, "#37424a");
            array_push($data,   $verifiedData);
        }
        
        if( $unverifiedData !== 0 )
        {
            array_push($labels, "Unverified %0.1f%%");
            array_push($colors, "#aeaa6c");
            array_push($data,   $unverifiedData);
        }
        
        if( $terminatedData !== 0 )
        {
            array_push($labels, "Terminated %0.1f%%");
            array_push($colors, "#fb4f14");
            array_push($data,   $terminatedData);
        }
        
        if( $notTerminatedData !== 0 )
        {
            array_push($labels, "Not Terminated %0.1f%%");
            array_push($colors, "#69be28");
            array_push($data,   $notTerminatedData);
        }
        
        if( $noAmnestyResponse !== 0 )
        {
            array_push($labels, "No Amnesty Response %0.1f%%");
            array_push($colors, "#8fcae7");
            array_push($data,   $noAmnestyResponse);
        }
        
        if( $clientUpdateData !== 0 )
        {
            array_push($labels, "Client Update %0.1f%%");
            array_push($colors, "#fecb00");
            array_push($data,   $clientUpdateData);
        }

        if(count($data) === 0)
		{
			return false;
		}
		else
		{
			$plot = new PiePlot($data);
			$plot->ShowBorder(false, false);
			$plot->SetLegends($labels);
			$plot->SetSliceColors($colors);
			$plot->SetCenter(0.50, 0.45);
			$plot->value->SetFormat('%0.1f');
			$plot->SetGuideLines(false);
			$plot->value->SetColor('#666');
			
			$graph = new PieGraph(400, 250);
			$graph->SetAntialiasing();
			$graph->legend->SetPos(0.1, 0.98, 'left', 'bottom');
			$graph->legend->SetColumns(2);
			$graph->legend->SetFrameWeight(0.1);
			$graph->legend->SetShadow('white', 1);
			$graph->legend->SetFillColor('white');
			$graph->legend->SetLineWeight(2);
			$graph->legend->SetColor('#333', 'white');
			$graph->SetFrame(false);
			$graph->Add($plot);
			
			if(file_exists(GRAPHS.$filename))
			{
				unlink(GRAPHS.$filename);
			}
			
			$graph->Stroke(GRAPHS.$filename);

			return $filename;
		}
    }
	
    public function csActivity($filename)
    {
        if(count($this->projects) > 1)
        {
            return false;
        }
        else
        {
            # split on project_id:plan_id
            $ids = preg_split("/\:/", $this->projects[0]);

            $project_id  = $ids[0];
            $plan_id     = isset($ids[1]) ? $ids[1] : null;

            $project = new ProjectModel($project_id, $plan_id);

            $db = new Model($project->getIP(), $project->getDB());

            $callsTmp = $mailTmp = $callsDataX = $callsDataY = $mailDataX = $mailDataY = array();

            // Poe doesn't have a traditional start/stop date
            if ($project->isPoe())
            {
                switch($_SESSION['pop']['population_filter'])
                {
                    case 'Finished between':
                    case 'Started between':
                        $start = strtotime($_SESSION['pop']['start_date']);
                        $stop = strtotime($_SESSION['pop']['end_date']);
                        break;

                    case 'Active':
                    case 'Inactive':
                    case "Everyone":
                    Default:
                        $start = time() - (180 * 86400);
                        $stop = time();
                        break;
                }

                $start =  (int) floor($start / 86400);
                $stop = (int) floor($stop / 86400);

//                echo "FORMAT: ".date('Y-m-d', ($start * 86400) + 43200) . "  -- " .date('Y-m-d',($stop * 86400) + 43200)."<br>";

            }
            else
            {
                $select = $db->getRow("
                    SELECT 
                        LEFT(g_audit_start_date,10) as startdate, 
                        IF(LEFT(now(),10) < g_audit_stop_date, LEFT(now(), 10), g_audit_stop_date) as stopdate
                    FROM projects
                    WHERE id = :project_id",
                    array(':project_id' => $project->getProjectID()));
                $start =  (int) floor(strtotime($select['startdate']) / 86400);
                $stop = (int) floor(strtotime($select['stopdate']) / 86400);
            }

            if(empty($start) && isset($stop))
            {
                return false;
            }
            else if(isset($start) && empty($stop))
            {
                return false;
            }
            else if($start >= $stop)
            {
                return false;
            }
            else
            {
                $cdb = $this->db();

                if ($project->getPlanID() != 0)
                {
                    $callVolume = $cdb->getRows("
                        SELECT LEFT(li.date,10) as day, count(li.id) as c
                        FROM log_inbound li
                        LEFT JOIN employees e on e.id=li.employee_id
                        WHERE action='C'
                          AND li.project_id = :project_id
                          AND e.plan_id = :plan_id
                        GROUP BY day",
                        array(':project_id' => $project->getProjectID(),
                              ':plan_id'    => $project->getPlanID()));

                    $mailVolume = $cdb->getRows("
                            SELECT LEFT(li.date,10) as day, count(li.id) as c
                              FROM log_inbound li
                              LEFT JOIN employees e on e.id=li.employee_id
                                WHERE action in('M','F','U','MU')
                                  AND li.project_id = :project_id
                                  AND e.plan_id = :plan_id
                            GROUP BY day",
                        array(':project_id' => $project->getProjectID(),
                              ':plan_id'    => $project->getPlanID()));
                }
                else
                {
                    $callVolume = $cdb->getRows("
                            SELECT LEFT(li.date,10) as day, count(li.id) as c
                            FROM log_inbound li
                            WHERE action='C'
                                AND li.project_id = :project_id
                            GROUP BY day",
                        array(':project_id' => $project->getProjectID()));

                    $mailVolume = $cdb->getRows("
                        SELECT LEFT(li.date,10) as day, count(li.id) as c
                        FROM log_inbound li
                          WHERE action in('M','F','U','MU')
                            AND li.project_id = :project_id
                            GROUP BY day",
                        array(':project_id' => $project->getProjectID()));
                }

                // process call volume
                foreach($callVolume as $row)
                {
                    $row['day'] =  floor(strtotime($row['day']) / 86400);
                    $callsTmp[$row['day']] = $row['c'];
                }
                // process mail volume
                foreach($mailVolume as $row)
                {
                    $row['day'] =  floor(strtotime($row['day']) / 86400);
                    $mailTmp[$row['day']] = $row['c'];
                }
//                $mailTmp[6]=1;

                if(count($callsTmp) <= 0 || count($mailTmp) <= 0)
                {
                    return false;
                }
                else
                {
                    for($date = $start; $date <= $stop; $date++)
                    {
                        if(array_key_exists($date, $callsTmp))
                        {
                            array_push($callsDataX, $date);
                            array_push($callsDataY, ($callsTmp[$date] > 0 ? $callsTmp[$date] : 0));
                        }
                        else
                        {
                            array_push($callsDataX, $date);
                            array_push($callsDataY, 0);
                        }

                        if(array_key_exists($date, $mailTmp))
                        {
                            array_push($mailDataX, $date);
                            array_push($mailDataY, ($mailTmp[$date] > 0 ? $mailTmp[$date] : 0));
                        }
                    }

                    if(count($callsDataX) <= 0 || count($callsDataY) <= 0 || count($mailDataX) <= 0 || count($mailDataY) <= 0)
                    {
                        return false;
                    }
                    else
                    {
                        //create graph
                        $graph = new Graph(800, 350, "auto");
                        $graph->SetScale("intlin", 0, 0, $start, $stop);
                        $graph->ygrid->Show(true, true);
                        $graph->xgrid->Show(true, true);
                        $graph->xaxis->scale->ticks->Set(14, 1);
                        $graph->xaxis->SetLabelFormatCallback(array($this, 'dateCallBack'));
                        $graph->legend->Pos(0.5, 0.999, "center", "bottom");
                        $graph->legend->SetFrameWeight(0.1);
                        $graph->legend->SetShadow('white', 1);
                        $graph->legend->SetFillColor('white');
                        $graph->legend->SetLineWeight(2);
                        $graph->legend->SetColor('#333', 'white');
                        $graph->legend->SetColumns(4);
                        $graph->img->SetAntialiasing();
                        $graph->img->SetMargin(30, 10, 30, 60);

                        //create call plot
                        $cp = new LinePlot($callsDataY, $callsDataX);
                        $cp->SetWeight(3);
                        $cp->SetColor('#69be28');
                        $cp->SetLegend("Calls Received");
                        $graph->Add($cp);

                        //create mail plot
                        $mp = new LinePlot($mailDataY, $mailDataX);
                        $mp->SetWeight(3);
                        $mp->SetColor('#2a6ebb');
                        $mp->SetLegend("Documents Received");
                        $graph->Add($mp);

                        //get verification letter mailing dates
                        $v1 = $db->getVal("select LEFT(v_mail_1_date,10) from projects where id = :project_id",
                           array(':project_id' => $project->getProjectID()) );
                        $v1 = floor(strtotime($v1) / 86400);

                        if($v1 <= $stop)
                        {
                            $pl = new PlotLine(VERTICAL, $v1);
                            $pl->SetWeight(2);
                            $pl->SetColor('#aeaa6c');
                            $pl->SetLegend('Verification Letter');

                            $graph->Add($pl);
                        }

                        //get verification letter mailing dates
                        $v2 = $db->getVal("select LEFT(v_mail_2_date,10) from projects where id = :project_id",
                           array(':project_id' => $project->getProjectID()) );
                        $v2 = floor(strtotime($v2) / 86400);

                        if($v2 <= $stop)
                        {
                            $p2 = new PlotLine(VERTICAL, $v2);
                            $p2->SetWeight(2);
                            $p2->SetColor('#aeaa6c');

                            $graph->Add($p2);
                        }

                        //get verification letter mailing dates
                        $v3 = $db->getVal("select LEFT(v_mail_3_date,10) from projects where id = :project_id",
                           array(':project_id' => $project->getProjectID()) );
                        $v3 = floor(strtotime($v3) / 86400);

                        if($v3 <= $stop)
                        {
                            $p3 = new PlotLine(VERTICAL, $v3);
                            $p3->SetWeight(2);
                            $p3->SetColor('#aeaa6c');

                            $graph->Add($p3);
                        }

                        //get verification letter mailing dates
                        $v4 = $db->getVal("select LEFT(v_mail_4_date,10) from projects where id = :project_id",
                           array(':project_id' => $project->getProjectID()) );
                        $v4 = floor(strtotime($v4) / 86400);

                        if($v4 <= $stop)
                        {
                            $p4 = new PlotLine(VERTICAL, $v4);
                            $p4->SetWeight(2);
                            $p4->SetColor('#aeaa6c');

                            $graph->Add($p4);
                        }

                        //get verification due date
                        $dd = $db->getVal("select LEFT(v_due_date,10) from projects where id = :project_id",
                           array(':project_id' => $project->getProjectID()) );
                        $dd = floor(strtotime($dd) / 86400);

                        $dd = new PlotLine(VERTICAL, $dd);
                        $dd->SetWeight(2);
                        $dd->SetColor('#f00');
                        $dd->SetLineStyle('dotted');
                        $dd->SetLegend('Verification Due');
                        $graph->Add($dd);

                        $graph->SetFrame(false);

                        if(file_exists(GRAPHS.$filename))
                        {
                            unlink(GRAPHS.$filename);
                        }

                        $graph->Stroke(GRAPHS.$filename);

                        return $filename;
                    }
                }
            }
        }
    }

    public function dateCallBack($date)
    {
        return date("m/d", ($date * 86400) + 43200);
    }
}