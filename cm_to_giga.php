<?php

// edit Name of Group in Contact Manager
$contact_manager_group = isset($_GET['cgroup']) ? $_GET['cgroup'] : "SomeName"; // <-- Edit "SomeName" to make your own default



function quoted ($S) { return "\"$S\""; } 

function Ausgabe($S)  
  {    echo "$S\n";  }

class entry
{   
    public $Items = ['home1'=>'', 'name'=>'', 'office2'=>'', 'office1'=>'', 'mobile2'=>'', 'mobile1'=>'', 'surname'=>'', 'home2'=>''];   
    public $MyTypeTranslation = ['home'=>'home','work'=>'office','cell'=>'mobile'];   
    public $DisplayName = 'xxx kein gültiger Eintrag! xxx';
    public $TelNums = [];    
    public $numbers = 0;     
    public function __construct($DisplayName)
           {
               if ($DisplayName != '') $this->DisplayName = $DisplayName;
               foreach ($this->MyTypeTranslation as $key => $NewKey)  { $this->TelNums[$NewKey]=[]; }
               foreach($this->Items as $key => $V)  { $this->Items[$key] = '';}
               $this->Items['surname'] = $DisplayName;
               $this->numbers = 0;
           }
    public function Add($type,$number)
           {   
               if (!array_key_exists($type,$this->MyTypeTranslation)) return;
               $my_type = $this->MyTypeTranslation[$type]; 
               $this->TelNums[$my_type][] = $number;         
               $this->numbers++;
           }
    public function BuildXMLZeile()
           {
              if ($this->numbers < 1) return;  
               
              foreach ($this->TelNums as $key=>$Nums)
                {  
                   for ($i=0; $i<2; $i++)  
                     { 
                        if ($i>count($Nums)-1) $N=''; 
                        else $N = $Nums[$i]; 
                        $Index = $i+1;
                        $type = $key.$Index; 
                        $this->Items[$type] = $N;
                     }   
                }
              $E = "<entry";          
              foreach ($this->Items as $key => $Wert) $E .= " $key=".quoted($Wert);
              Ausgabe($E."/>");
          }  # end function Build..
                 
} 




header("Content-Type: text/xml");

// Load FreePBX bootstrap environment
require_once('/etc/freepbx.conf');

// Initialize a database connection
global $db;

// This pulls every number in contact maanger that is part of the group specified by $contact_manager_group
$sql = "SELECT cen.number, cge.displayname, cen.type, cen.E164, 0 AS 'sortorder' FROM contactmanager_group_entries AS cge LEFT JOIN contactmanager_entry_numbers AS cen ON cen.entryid = cge.id WHERE cge.groupid = (SELECT cg.id FROM contactmanager_groups AS cg WHERE cg.name = '$contact_manager_group') ORDER BY cge.displayname, cen.number;";

// Execute the SQL statement
$res = $db->prepare($sql);
$res->execute();
// Check that something is returned
if (DB::IsError($res)) {
    // Potentially clean this up so that it outputs pretty if not valid                
    error_log( "There was an error attempting to query contactmanager<br>($sql)<br>\n" . $res->getMessage() . "\n<br>\n");
} else {
    $contacts = $res->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contacts as $i => $contact){
        $contact['displayname'] = htmlspecialchars($contact['displayname']);
        // put the changes back into $contacts
        $contacts[$i] = $contact;
    }


Ausgabe ("<?xml version=\"1.0\" encoding=\"utf-8\"?>");
Ausgabe("<!DOCTYPE LocalDirectory>");
Ausgabe("<list>");

$Entry = new entry('');
    foreach ($contacts as $contact) 
    {
        $DispName = $contact['displayname'];
        $TelNum   = $contact['number'];
        $Type     = $contact['type'];
        if ($DispName == '') continue;
        if ($DispName != $Entry->DisplayName) 
           {  
              $Entry->BuildXMLZeile();
              $Entry = new entry($DispName);
           }   
        if ($TelNum == '') continue;    
        $Entry->Add($Type,$TelNum);
     }
$Entry->BuildXMLZeile();

Ausgabe ("</list>");
    
} 

?>
