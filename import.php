<?PHP

/*
  local caching of opendata content for dashboard
  Robert Schiewer
  Piraten Schwerin
  r.schiewer@web.de

*/

if ( isset( $_SERVER[ 'REQUEST_METHOD'])) die( "web access denied!");

$TargetFile =  dirname(__FILE__) . '/'. 'dashdata.xml';

$Sources = array(
  "Member" => "http://opendata.piratenpartei-mv.de/mv/mitglieder",
  "Finance" => "http://opendata.piratenpartei-mv.de/mv/kontostand",
  "HelpDesk" => "http://opendata.piratenpartei-mv.de/helpdesk",
  "LQFBActive" => "http://opendata.piratenpartei-mv.de/lqfb/mv/status/voting",
  "LQFBFrozen" => "http://opendata.piratenpartei-mv.de/lqfb/mv/status/frozen",    
  "LQFBAccepted" => "http://opendata.piratenpartei-mv.de/lqfb/mv/status/accepted",
  "RedmineIssue" => "http://opendata.piratenpartei-mv.de/redmine/vorstand",
  "RedmineTodo" => "http://opendata.piratenpartei-mv.de/redmine/arbeitsamt",
  "Pad" => "http://opendata.piratenpartei-mv.de/pads",
  "Calendar" => "http://opendata.piratenpartei-mv.de/calendar",
  "Article" => "http://opendata.piratenpartei-mv.de/articles",
  "Wiki" => "http://opendata.piratenpartei-mv.de/wiki",
  "Facebook" => "http://opendata.piratenpartei-mv.de/facebook/piratenmv/stream"   
);

//------------------------------------------------------------------------------
$ImportData = array();
foreach ( $Sources as $SrcName => $SrcLocation)
{
  $Content = file_get_contents( $SrcLocation);
  $Content = utf8_decode( $Content);
  $Content = str_replace ( array ( '&', '"', "'", '<', '>' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;' ), $Content);
  $Content = utf8_encode( $Content);
  $ImportData[ $SrcName] = $Content;
}
$XmlDoc = new SimpleXMLElement( "<data></data>");
foreach ( $ImportData as $Key => $Content)
{
  $Headline = '[' . $Key . ']' . "\n";
  $ContentAbs = '|' . $Content . '|' . "\n";
  $NewRow = $XmlDoc->addChild( "row");
  $NewRow->addAttribute("Type", htmlentities( $Key));  
  $NewRow->addAttribute("Value", $Content);
}
$XmlDoc->asXML( $TargetFile);
?>