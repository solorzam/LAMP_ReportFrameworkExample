<?php
require ("dcfuncs.php");
include ("header.php");

/*
 This code is only for basic API proof of concept --- moving reporting to AuditOS TOOLS
 * */


verifyAdmin(LVL_ADMIN_REPORTS);

global $tbl;
$tbl="_rpt_".$_SESSION['user']['name'] ."_tmp";
$tbl2="_rpt_".$_SESSION['user']['name']."_tmp2";

ini_set('memory_limit','128M');
set_time_limit(3600);




//create menus
printMenu('Reporting Test');

echo '<br><br>';

/*
if($user->canAccessGlobal())
{
    echo '<a href="/reports.php?mode=Global" id="Global">Global</a> | ';
}
?>
    <a href="/reports.php?mode=Projects"  id="Projects">Projects</a> |
    <a href="/reports.php?mode=Performance"  id="Performance">Performance</a>
    <script>
        $( <?php echo $mode ?> ).addClass("disabled")

        function exitGracefully(eid) {
            alert("Please edit EID=" + eid + " information from Excel CSV spreadsheet.\nDownload CSV file above. \nThank you!");
            return false;
        }
    </script>
    <hr/>
<?php
*/



function isJSON($string){
    return is_string($string) && is_object(json_decode($string)) ? true : false;
}


function curlAPI($url, $data)
{
    $curl = curl_init();

    try
    {
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect: '));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $res = curl_exec($curl);
        curl_close($curl);

        // decode
        try
        {
            //return json_decode($res);
            return $res;
        }
        catch (Exception $e)
        {
            cliLog("JSON_DECODE_ERROR: " . $res);
        }
    }
    catch (Exception $e)
    {
        cliLog("CURL_ERROR: " . curl_error($curl));
        curl_close($curl);
    }

    return false;
}


// Front end user would choose EmployeeReport (class) to run
$results = curlAPI(AUDITOS_REPORT_URL, array(
        'api_key'          => 'bd991a6d-618c-4339-b2ca-101e67614e4e',
        'report_selected'  => 'EmployeeStatusReport',
        'project_id' 	   => '3',
        'user_id'          => $_SESSION['user']['name'],
        'report_grouper'   => 'Anthem',
        'report_statuses'  => 'complete',
        'project' 	       => false,
        'multi' 	       => false,
        'grouper' 	       => true,
        'status'           => true,
        'type'             => false,
        'start_date'       => false,
        'end_date'         => false,
    )
);


$resultRows = json_decode($results, true);

$helper = new \DC\Views\Helpers\DBTable();

echo "<table class=pad style=\"width:93%;margin:inherit;\"\>";
if(isset($resultRows) && $resultRows)
{
    if (is_array($resultRows) and count($resultRows) > 1)
    {
        $helper::headerCells($resultRows[0]);
        foreach ($resultRows as $row) {
            $helper::cells($row);
        }
    }
    else
    {
        foreach ($resultRows as $k=>$v) {
            echo '<tr><td>' . $k . '</td><td>' . $v . '</td></tr>';
        }
    }
}
else
{
    echo '<tr><td>Response to API call is empty</td></tr>';
}
echo "</table>";



include ("footer.php");
