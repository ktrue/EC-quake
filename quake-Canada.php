<?php
// PHP script by Ken True, webmaster@saratoga-weather.org
// 
// Version 1.00 - 12-Mar-2008 -- Initial Release
// Version 1.01 - 26-Apr-2008 -- fixed possible UTC-to-timezone conversion issue on some webservers
// Version 1.02 - 20-Jan-2009 -- added support for lang=fr language selection
// Version 1.03 - 28-Mar-2009 -- fix for minor EC website change
// Version 1.04 - 03-Jul-2009 -- PHP5 support for timezone set
// Version 1.05 - 23-Oct-2009 -- added missing lat/long settings from Settings.php if available
// Version 1.06 - 26-Jan-2011 -- added support for $cacheFileDir global setting
// Version 1.07 - 20-Nov-2011 -- fixes for major EC website changes
// Version 1.08 - 06-Mar-2012 -- fixes for EC website changes
// Version 1.09 - 21-May-2013 -- fixes for EC website changes
// Version 1.10 - 06-Jul-2013 -- fixes for EC website changes (thanks to George at hamiltonweatheronline.net)
// Version 1.11 - 30-Dec-2013 -- fixes for EC website changes
// Version 1.12 - 10-Mar-2016 -- fixes for EC website changes (thanks Ray at tzweather.org)
// Version 2.00 - 06-May-2019 -- use XML for data from http://www.earthquakescanada.nrcan.gc.ca 
//
  $Version = 'quake-Canada.php V2.00 - 06-May-2019';
//
// you may copy/modify/use this script as you see fit,
// no warranty is expressed or implied.
//
// Customized for: Quakes reported by Canada Geological Survey (
//   http://earthquakescanada.nrcan.gc.ca/recent_eq/maps/index_e.php?tpl_region=canada
//
//
// output: creates XHTML 1.0-Strict HTML page (default)
// Options on URL:
//      tablesonly=Y    -- returns only the body code for inclusion
//                         in other webpages.  Omit to return full HTML.
//      magnitude=N.N   -- screens results looking for Richter magnitudes of
//                          N.N or greater.
//      distance=MMM    -- display quakes with epicenters only within 
//                         MMM km of your location
//      lang=[en|fr]    -- language English or French
// example URL:
//  http://your.website/quake-Canada.php?tablesonly=Y&magnitude=2.1&distance=45
//  would return data without HTML header/footer for earthquakes of
//  magnitude 2.1 or larger within a 45 mile radius of your location.
//
// Usage:
//  you can use this webpage standalone (customize the HTML portion below)
//  or you can include it in an existing page:
/*
//            <?php $doIncludeQuake = true;
//                  include("quake-Canada.php");
//            ?> 
*/
//  no parms:    include("quake-Canada.php"); 
//  parms:    include("http://your.website/quake-Canada.php?tableonly=Y&magnitude=2.0&distance=50");
//
//
// settings:  
//  set $ourTZ to your time zone
//    other settings are optional
//
// cacheName is name of file used to store cached USGS webpage
// 
//
  $ourTZ = "America/Toronto";  //NOTE: this *MUST* be set correctly to
// translate UTC times to your LOCAL time for the displays.
//  http://saratoga-weather.org/timezone.txt  has the list of timezone names
//  pick the one that is closest to your location and put in $ourTZ
// also available is the list of country codes (helpful to pick your zone
//  from the timezone.txt table
//  http://saratoga-weather.org/country-codes.txt : list of country codes

 $myLat = '43.2';
 $myLong = '-79.25';

 $highRichter = "3.0"; //change color for quakes >= this magnitude
 $distanceKM = 500;   // earthquakes within 500 km
 
//  pick a format for the time to display ..uncomment one (or make your own)
  $timeFormat = 'D, Y-m-d H:i:s T';  // Fri, 2006-03-31 14:03:22 TZone
//$timeFormat = 'D, Y-M-d H:i:s T';  // Fri, 31-Mar-2006 14:03:22 TZone
//$timeFormat = 'H:i:s T D, d-M-y';  // 14:03:22 TZone Fri, 31-Mar-06
  $cacheFileDir = './';   // default cache file directory
  $cacheName = "quakesCanadaXML.txt";  // used to store the file so we don't have to
  //                          fetch it each time
  $refetchSeconds = 1800;     // refetch every nnnn seconds
  
  $defaultLang = 'en';  // set to 'fr' for french default language
//                      // set to 'en' for english default language

// end of settings


// overrides from Settings.php if available
global $SITE;
if (isset($SITE['latitude'])) 	{$myLat = $SITE['latitude'];}
if (isset($SITE['longitude'])) 	{$myLong = $SITE['longitude'];}
if (isset($SITE['tz'])) {$ourTZ = $SITE['tz']; }
if (isset($SITE['timeFormat'])) {$timeFormat = $SITE['timeFormat'];}
if (isset($SITE['defaultlang'])) 	{$defaultLang = $SITE['defaultlang'];}
if(isset($SITE['cacheFileDir']))     {$cacheFileDir = $SITE['cacheFileDir']; }
// end of overrides from Settings.php

// ------ start of code -------
// Check parameters and force defaults/ranges
if ( ! isset($_REQUEST['tablesonly']) ) {
        $_REQUEST['tablesonly']="";
}
if (isset($doIncludeQuake) and $doIncludeQuake ) {
  $tablesOnly = "Y";
} else {
  $tablesOnly = $_REQUEST['tablesonly']; // any nonblank is ok
}

if ($tablesOnly) {$tablesOnly = "Y";}

if ( ! isset($_REQUEST['distance']) )
        $_REQUEST['distance']="$distanceKM";
$maxDistance = $_REQUEST['distance'];
if (! preg_match("/^\d+$/",$maxDistance) ) {
   $maxDistance = "$distanceKM"; // default for bad data input
}
if ($maxDistance <= "10") {$maxDistance = "10";}
if ($maxDistance >= "8000") {$maxDistance = "8000";}		
// for testing only 
if ( isset($_REQUEST['lat']) ) { $myLat = $_REQUEST['lat']; }
if ( isset($_REQUEST['lon']) ) { $myLong = $_REQUEST['lon']; }

if (isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view' ) {
//--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   readfile($filenameReal);
   exit;
}
if(isset($_REQUEST['lang'])) {
  $Lang = strtolower($_REQUEST['lang']);
} else {
  $Lang = '';
}
if (isset($doLang)) {$Lang = $doLang;};
if (! $Lang) {$Lang = $defaultLang;};

if ($Lang == 'fr') {
  $LMode = 'fr';
  $ECNAME = "Ressources naturelles Canada";
  $ECHEAD = 'le Canada séismes des derniers 30 jours';
  $ECMORE = 'Séismes Canada/Rapports sur les derniers séismes importants';
} else {
  $Lang = 'en';
  $LMode = 'en';
  $ECNAME = "Natural Resources Canada";
  $ECHEAD = 'Canada Earthquakes of the last 30 days';
  $ECMORE = 'Recent News/Recent Significant Earthquake Reports';
}

// support both french and english caches
//$ECURL = preg_replace('|city_.\.html|',"city_$LMode.html",$ECURL);
// Constants
// don't change $fileName or script may break ;-)
//  $fileName = "http://earthquakescanada.nrcan.gc.ca/recent_eq/maps/index_$LMode.php?tpl_region=canada";
//  $fileName = "http://seismescanada.rncan.gc.ca/recent_eq/maps/index_$LMode.php?tpl_region=canada";
//  $fileName = "http://www.earthquakescanada.nrcan.gc.ca/recent_eq/maps/index_$LMode.php?tpl_region=canada";
//  $fileName = "http://www.earthquakescanada.nrcan.gc.ca/recent/maps-cartes/index-$LMode.php?tpl_region=canada";
//$fileName = "http://www.earthquakescanada.nrcan.gc.ca/recent/maps-cartes/index-$LMode.php?maptype=30d&CHIS_SZ=canada";
$fileName = 'http://www.earthquakescanada.nrcan.gc.ca/cache/earthquakes/canada-30.xml'; // XML format 
$sourcePage = 'http://www.earthquakescanada.nrcan.gc.ca/recent/maps-cartes/index-'.$Lang.'.php'; 
// end of constants
$mapLinkRaw = 'http://www.earthquakescanada.nrcan.gc.ca/recent/%s/%s/index-'.$Lang.'.php';

$cacheName = $cacheFileDir . $cacheName;
$cacheName = preg_replace('|.txt$|',"-$Lang.txt",$cacheName);


// omit HTML <HEAD>...</HEAD><BODY> if only tables wanted	
// --------------- customize HTML if you like -----------------------
if (! $tablesOnly) {
	header("Content-type: text/html; charset=iso-8859-1");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php echo $ECNAME.' - ' .$ECHEAD; ?></title>
</head>
<body style="background-color:#FFFFFF;">
<?php
}

// ------------- code starts here -------------------

// Establish timezone offset for time display
# Set timezone in PHP5/PHP4 manner
  if (!function_exists('date_default_timezone_set')) {
	putenv("TZ=" . $ourTZ);
#	$Status .= "<!-- using putenv(\"TZ=$ourTZ\") -->\n";
    } else {
	date_default_timezone_set("$ourTZ");
#	$Status .= "<!-- using date_default_timezone_set(\"$ourTZ\") -->\n";
   }
 print("<!-- $Version -->\n");
 print("<!-- server lcl time is: " . date($timeFormat) . " -->\n");
 print("<!-- server GMT time is: " . gmdate($timeFormat) . " -->\n");
 print("<!-- server timezone for this script is: " . getenv('TZ')." -->\n");
 $timediff = date("Z");
 print "<!-- TZ Delta = $timediff seconds (" . $timediff/3600 . " hours) -->\n";

 if(isset($_REQUEST['cache'])) {$refetchSeconds = 1; }
 
// refresh cached copy of page if needed
// fetch/cache code by Tom at carterlake.org

if (file_exists($cacheName) and filemtime($cacheName) + $refetchSeconds > time()) {
      echo "<!-- using Cached version of $cacheName -->\n";
      $html = implode('', file($cacheName));
    } else {
      echo "<!-- loading $cacheName from $fileName -->\n";
      $html = fetchUrlWithoutHangingQCAN($fileName);
			  $fp = fopen($cacheName, "w");
      if ($fp) {
        $write = fputs($fp, $html);
        fclose($fp);
      } else {
            print "<!-- unable to write cache file $cacheName -->\n";
      }
      echo "<!-- loading finished. -->\n";
	}
	
/*
NEW XML format:

<q:quakeml class="mozwebext">
  <eventParameters publicID="smi:local/15ede0c2-f43c-40dc-9cf8-eb0d8df79306">
    <event publicID="smi:local/20190422.2144002">
      <description>
        <text>205 km W of Port Alice, BC/205 km O de Port Alice, BC</text>
        <type>earthquake name</type>
      </description>
      <origin publicID="smi:local/20190422.2144002">
        <time>
          <value>2019-04-22T21:44:41.000000Z</value>
        </time>
        <latitude>
          <value>50.0303</value>
        </latitude>
        <longitude>
          <value>-130.2778</value>
        </longitude>
        <depth>
          <value>10.0</value>
        </depth>
        <creationInfo>
          <creationTime>2019-04-24T00:00:00.000000Z</creationTime>
        </creationInfo>
      </origin>
      <magnitude publicID="smi:local/20190422.2144002">
        <mag>
          <value>5.3</value>
        </mag>
        <type>Mw</type>
      </magnitude>
    </event>
  </eventParameters>
</q:quakeml>
*/

  list($headers,$content) = explode("\r\n\r\n",$html);
	
	$XML = simplexml_load_string($content);
	
	// print "<!-- XML dump\n".print_r($XML,true)." -->\n";
	
	$QXML = $XML->eventParameters->event;

  $quakesFound = 0;
  $doneHeader = false;
// scan the results and process by 1s 
  foreach ($QXML as $n => $quake) {
	 // print "<!-- quake\n".print_r($quake,true)." -->\n"; 
	 $quaketime = date($timeFormat,strtotime((string)$quake->origin->time->value));
   $latitude = (float)$quake->origin->latitude->value;  // all of Canada is North Latitude = default of '+'
   $longitude = (float)$quake->origin->longitude->value;
	 $depth     = (string)$quake->origin->depth->value; 
   $magnitude = (string)$quake->magnitude->mag->value;
   $location =  (string)$quake->description->text;
	 /*
	 // map url is not reliable so commented out for now
	 list($junk,$smi)      =  explode('/',(string)$quake->magnitude['publicID']);
	 $smi = substr($smi,0,13); // shorten to just the needed part
	 $mapLink = sprintf($mapLinkRaw,substr($smi,0,4),$smi);
	 */
	 
   // print "<!-- time='$quaketime' lat='$latitude' lon='$longitude' mag='$magnitude' loc='$location' -->\n";
   // provide highlighting for quakes >= $highRichter
   if ($magnitude >= $highRichter) {
	 $magnitude = "<span style=\"color: red\">$magnitude</span>";
	 $location = "<span style=\"color: red;\">$location</span>";
   }
   
   $distanceM = round(distance($myLat,$myLong,$latitude,$longitude,"M"));
   $distanceK = round(distance($myLat,$myLong,$latitude,$longitude,"K"));

   
   if ($distanceK <= $maxDistance) { // only print 'close' ones
	  $quakesFound++;    // keep a tally of quakes for summary
	  } else {
	    continue;
   }
 
   if (! $doneHeader) {  // print the header if needed
// --------------- customize HTML if you like -----------------------
	    print "
<table class=\"quake\" cellpadding=\"1\" cellspacing=\"1\" border=\"0\">
<tr><th colspan=\"4\" align=\"center\">$ECHEAD (<= $maxDistance km)</th></tr>\n";
if ($Lang == 'fr') {
	print "<tr><th>Region</th><th>Grandeur</th><th>Distance à<br/>l'épicentre</th><th>Heure locale</th></tr>\n";
} else { 
	print "<tr><th>Region</th><th>Magnitude</th><th>Distance to <br />Epicenter</th><th>Local Time</th></tr>\n";
}
	    $doneHeader = true;
	  } // end doneHeader
// --------------- customize HTML if you like -----------------------
	    print "
<tr>
  <td>$location</td>
  <td align=\"center\"><b>$magnitude</b></td>
  <td align=\"left\" nowrap=\"nowrap\"><b>$distanceK</b> km (<b>$distanceM</b> mi)</td>
  <td align=\"left\" nowrap=\"nowrap\">$quaketime</td>
</tr>\n";
	// print "<!-- $location | $smi | '$mapLink' -->\n";

  } // end foreach loop

// finish up.  Write trailer info
 
	  if ($doneHeader) {
// --------------- customize HTML if you like -----------------------
        if($Lang=='fr') {
		 print "</table><p>Au cours des derniers 30 jours $quakesFound activités séismiques furent enregistrées à l'intérieur de la zone de $maxDistance km.</p>\n";
		} else {
	     print "</table><p>In the last 30 days $quakesFound earthquakes were recorded in the $maxDistance km zone.</p>\n";
		}
	  
	  } else {
// --------------- customize HTML if you like -----------------------
        if ($Lang == 'fr') {
			print "<p>Au cours des derniers 30 jours aucune activité séismique n'a été enregistré à l'intérieur de la zone de $maxDistance km.</p>\n"; 
		} else {
	        print "<p>No Canadian Earthquakes within $maxDistance km recorded for the last 30 days.</p>\n";
		}
	  
	  }	
	  print '<p><a href="'.$sourcePage.'">'.$ECNAME.'</a></p>' . "\n"; 
	  print '<p><a href="http://www.earthquakescanada.nrcan.gc.ca/index-'.$LMode.'.php?CHIS_SZ=canada">';
	  print $ECMORE."</a></p>\n";
	  

// print footer of page if needed    
// --------------- customize HTML if you like -----------------------
if (! $tablesOnly ) {   
?>

</body>
</html>

<?php
}

// ----------------------------functions ----------------------------------- 
 
 function fetchUrlWithoutHangingQCAN($url) {
// get contents from one URL and return as string 
  global $needCookie;
  $useFopen = false;
	$Debug = '';
  $overall_start = time();
  if (! $useFopen) {
   // Set maximum number of seconds (can have floating-point) to wait for feed before displaying page without feed
   $numberOfSeconds=6;   

// Thanks to Curly from ricksturf.com for the cURL fetch functions

  $data = '';
  $domain = parse_url($url,PHP_URL_HOST);
  $theURL = str_replace('nocache','?'.$overall_start,$url);        // add cache-buster to URL if needed
  $Debug .= "<!-- curl fetching '$theURL' -->\n";
  $ch = curl_init();                                           // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $theURL);                         // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                 // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 
    'Mozilla/5.0 (quake-json.php - saratoga-weather.org)');

  curl_setopt($ch,CURLOPT_HTTPHEADER,                          // request LD-JSON format
     array (
         "Accept: text/html,text/plain"
     ));

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $numberOfSeconds);  //  connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, $numberOfSeconds);         //  data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);              // return the data transfer
  curl_setopt($ch, CURLOPT_NOBODY, false);                     // set nobody
  curl_setopt($ch, CURLOPT_HEADER, true);                      // include header information
//  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);              // follow Location: redirect
//  curl_setopt($ch, CURLOPT_MAXREDIRS, 1);                      //   but only one time
  if (isset($needCookie[$domain])) {
    curl_setopt($ch, $needCookie[$domain]);                    // set the cookie for this request
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);             // and ignore prior cookies
    $Debug .=  "<!-- cookie used '" . $needCookie[$domain] . "' for GET to $domain -->\n";
  }

  $data = curl_exec($ch);                                      // execute session

  if(curl_error($ch) <> '') {                                  // IF there is an error
   $Debug .= "<!-- curl Error: ". curl_error($ch) ." -->\n";        //  display error notice
  }
  $cinfo = curl_getinfo($ch);                                  // get info on curl exec.
/*
curl info sample
Array
(
[url] => http://saratoga-weather.net/clientraw.txt
[content_type] => text/plain
[http_code] => 200
[header_size] => 266
[request_size] => 141
[filetime] => -1
[ssl_verify_result] => 0
[redirect_count] => 0
  [total_time] => 0.125
  [namelookup_time] => 0.016
  [connect_time] => 0.063
[pretransfer_time] => 0.063
[size_upload] => 0
[size_download] => 758
[speed_download] => 6064
[speed_upload] => 0
[download_content_length] => 758
[upload_content_length] => -1
  [starttransfer_time] => 0.125
[redirect_time] => 0
[redirect_url] =>
[primary_ip] => 74.208.149.102
[certinfo] => Array
(
)

[primary_port] => 80
[local_ip] => 192.168.1.104
[local_port] => 54156
)
*/
  $Debug .= "<!-- HTTP stats: " .
    " RC=".$cinfo['http_code'] .
    " dest=".$cinfo['primary_ip'] ;
	if(isset($cinfo['primary_port'])) { 
	  $Debug .= " port=".$cinfo['primary_port'] ;
	}
	if(isset($cinfo['local_ip'])) {
	  $Debug .= " (from sce=" . $cinfo['local_ip'] . ")";
	}
	$Debug .= 
	"\n      Times:" .
    " dns=".sprintf("%01.3f",round($cinfo['namelookup_time'],3)).
    " conn=".sprintf("%01.3f",round($cinfo['connect_time'],3)).
    " pxfer=".sprintf("%01.3f",round($cinfo['pretransfer_time'],3));
	if($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
	  $Debug .=
	  " get=". sprintf("%01.3f",round($cinfo['total_time'] - $cinfo['pretransfer_time'],3));
	}
    $Debug .= " total=".sprintf("%01.3f",round($cinfo['total_time'],3)) .
    " secs -->\n";

  //$Debug .= "<!-- curl info\n".print_r($cinfo,true)." -->\n";
  curl_close($ch);                                              // close the cURL session
  //$Debug .= "<!-- raw data\n".$data."\n -->\n"; 
  $i = strpos($data,"\r\n\r\n");
  $headers = substr($data,0,$i);
  $content = substr($data,$i+4);
  if($cinfo['http_code'] <> '200') {
    $Debug .= "<!-- headers returned:\n".$headers."\n -->\n"; 
  }
	print $Debug;
  return $data;                                                 // return headers+contents

 } else {
//   print "<!-- using file_get_contents function -->\n";
   $STRopts = array(
	  'http'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (quake-json.php - saratoga-weather.org)\r\n" .
				"Accept: application/ld+json\r\n"
	  ),
	  'ssl'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
		'verify_peer' => false,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (quake-json.php - saratoga-weather.org)\r\n" .
				"Accept: application/ld+json\r\n"
	  )
	);
	
   $STRcontext = stream_context_create($STRopts);

   $T_start = QCAN_fetch_microtime();
   $xml = file_get_contents($url,false,$STRcontext);
   $T_close = QCAN_fetch_microtime();
   $headerarray = get_headers($url,0);
   $theaders = join("\r\n",$headerarray);
   $xml = $theaders . "\r\n\r\n" . $xml;

   $ms_total = sprintf("%01.3f",round($T_close - $T_start,3)); 
   $Debug .= "<!-- file_get_contents() stats: total=$ms_total secs -->\n";
   $Debug .= "<-- get_headers returns\n".$theaders."\n -->\n";
//   print " file() stats: total=$ms_total secs.\n";
   $overall_end = time();
   $overall_elapsed =   $overall_end - $overall_start;
   $Debug .= "<!-- fetch function elapsed= $overall_elapsed secs. -->\n"; 
//   print "fetch function elapsed= $overall_elapsed secs.\n";
   print $Debug; 
   return($xml);
 }

}    // end ECF_fetch_URL

// ------------------------------------------------------------------

function QCAN_fetch_microtime()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

//------------------------------------------------------------------------------------------
   
// ------------ distance calculation function ---------------------
   
    //**************************************
    //     
    // Name: Calculate Distance and Radius u
    //     sing Latitude and Longitude in PHP
    // Description:This function calculates 
    //     the distance between two locations by us
    //     ing latitude and longitude from ZIP code
    //     , postal code or postcode. The result is
    //     available in miles, kilometers or nautic
    //     al miles based on great circle distance 
    //     calculation. 
    // By: ZipCodeWorld
    //
    //This code is copyrighted and has
	// limited warranties.Please see http://
    //     www.Planet-Source-Code.com/vb/scripts/Sh
    //     owCode.asp?txtCodeId=1848&lngWId=8    //for details.    //**************************************
    //     
/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    /*:: :*/
    /*:: This routine calculates the distance between two points (given the :*/
    /*:: latitude/longitude of those points). It is being used to calculate :*/
    /*:: the distance between two ZIP Codes or Postal Codes using our:*/
    /*:: ZIPCodeWorld(TM) and PostalCodeWorld(TM) products. :*/
    /*:: :*/
    /*:: Definitions::*/
    /*::South latitudes are negative, east longitudes are positive:*/
    /*:: :*/
    /*:: Passed to function::*/
    /*::lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees) :*/
    /*::lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees) :*/
    /*::unit = the unit you desire for results:*/
    /*::where: 'M' is statute miles:*/
    /*:: 'K' is kilometers (default):*/
    /*:: 'N' is nautical miles :*/
    /*:: United States ZIP Code/ Canadian Postal Code databases with latitude & :*/
    /*:: longitude are available at http://www.zipcodeworld.com :*/
    /*:: :*/
    /*:: For enquiries, please contact sales@zipcodeworld.com:*/
    /*:: :*/
    /*:: Official Web site: http://www.zipcodeworld.com :*/
    /*:: :*/
    /*:: Hexa Software Development Center © All Rights Reserved 2004:*/
    /*:: :*/
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    function distance($lat1, $lon1, $lat2, $lon2, $unit) { 
    $theta = $lon1 - $lon2; 
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
    $dist = acos($dist); 
    $dist = rad2deg($dist); 
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);
    if ($unit == "K") {
    return ($miles * 1.609344); 
    } else if ($unit == "N") {
    return ($miles * 0.8684);
    } else {
    return $miles;
    }
  }
  
//To calculate the delta between the local time and UTC:
function tzdelta ( $iTime = 0 )
{
   if ( 0 == $iTime ) { $iTime = time(); }
   $ar = localtime ( $iTime );
   $ar[5] += 1900; $ar[4]++;
   $iTztime = gmmktime ( $ar[2], $ar[1], $ar[0],
       $ar[4], $ar[3], $ar[5], $ar[8] );
   return ( $iTztime - $iTime );
}
  
// --------------end of functions ---------------------------------------
?>
