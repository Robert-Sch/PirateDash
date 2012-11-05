<?PHP

/*
  local caching of opendata content for dashboard
  Robert Schiewer
  Piraten Schwerin
  r.schiewer@web.de

*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
 
if ( isset( $_SERVER[ 'REQUEST_METHOD'])) die( "web access denied!");

$TargetFile =  dirname(__FILE__) . '/'. 'dashdata.xml';

$Sources = array(
  "Member" => "http://opendata.piratenpartei-mv.de/mv/mitglieder",
  "Finance" => "http://opendata.piratenpartei-mv.de/mv/kontostand",
  "HelpDesk" => "http://opendata.piratenpartei-mv.de/helpdesk",
  /*"LQFBActive" => "http://opendata.piratenpartei-mv.de/lqfb/mv/status/voting",
  "LQFBFrozen" => "http://opendata.piratenpartei-mv.de/lqfb/mv/status/frozen",    
  "LQFBAccepted" => "http://opendata.piratenpartei-mv.de/lqfb/mv/status/accepted",*/
  "RedmineIssue" => "http://opendata.piratenpartei-mv.de/redmine/vorstand",
  "RedmineTodo" => "http://opendata.piratenpartei-mv.de/redmine/arbeitsamt",
  "Pad" => "http://opendata.piratenpartei-mv.de/pads",
  "Calendar" => "http://opendata.piratenpartei-mv.de/calendar",
  "Article" => "http://opendata.piratenpartei-mv.de/articles",
  "Wiki" => "http://opendata.piratenpartei-mv.de/wiki",
  "Facebook" => "http://opendata.piratenpartei-mv.de/facebook/piratenmv/stream",
  "TwitterMV" => "http://opendata.piratenpartei-mv.de/twitter/piraten_mv/stream",
  "TwitterMVReply" => "http://opendata.piratenpartei-mv.de/twitter/piraten_mv/replies",
  "TwitterMVIT" => "http://opendata.piratenpartei-mv.de/twitter/piraten_mv_it/stream",
  "TwitterMVITReply" => "http://opendata.piratenpartei-mv.de/twitter/piraten_mv_reply/replies",
  "TwitterMVLaVo" => "http://opendata.piratenpartei-mv.de/twitter/piraten_mv_lavo/stream",
  "TwitterMVLaVoReply" => "http://opendata.piratenpartei-mv.de/twitter/piraten_mv_lavo/replies"
);

$LQFBBase = "https://lqpp.de/mv/";
$LQFBBUBase = "https://lqfb.piratenpartei.de/lf/";
$LQFBSources = array(
  "LQFBActive" => $LQFBBase . "index/index.html?tab=open&filter=frozen",
  "LQFBFrozen" => $LQFBBase . "index/index.html?tab=open&filter=half_frozen",    
  "LQFBAccepted" => $LQFBBase . "index/index.html?tab=open&filter=accepted",
  "LQFBBUActive" => $LQFBBUBase . "unit/show/1.html?tab=open&filter=frozen",
  "LQFBBUFrozen" => $LQFBBUBase . "unit/show/1.html?tab=open&filter=half_frozen",    
  "LQFBBUAccepted" => $LQFBBUBase . "unit/show/1.html?tab=open&filter=accepted"  
);
//$LQFBSources = array( "LQFBActive" => "./dump.html");
//------------------------------------------------------------------------------
$ImportData = array();
//Page parsing while inactive lqfb api
foreach ( $LQFBSources as $SrcName => $SrcLocation)
{
	$Content = file_get_contents( $SrcLocation);
  $Doc = new DOMDocument();
	$Doc->loadHTML( $Content);	
	$LinkTags = $Doc->getElementsByTagName('a');
	$ImportLqfb = array();
	foreach ( $LinkTags as $Link)
	{
		$LinkValue = $Link->getAttribute('href'); //full link or relative link on same server
		$LinkText = $Link->nodeValue;
		if ( strpos( $LinkValue, '../initiative/' ) !== FALSE)
		{
			if ( strpos( $SrcLocation, $LQFBBase) === 0)
			{
				$LQFBBaseStr = $LQFBBase;
			}
			else
			{
				$LQFBBaseStr = $LQFBBUBase;
			}
			//$FullPath = $LQFBBaseStr . substr( $LinkValue, 0, strrpos( $LinkValue, '/') +1);
			$FullPath = substr( $SrcLocation, 0, strrpos( $SrcLocation, '/'));
			while ( strpos( $LinkValue, "../") !== FALSE)
			{
				$FullPath = substr( $FullPath, 0, strrpos( $FullPath, '/'));
				$LinkValue = substr( $LinkValue, strpos( $LinkValue, '../') + 3);
			} 
			$FullPath = $FullPath . "/" . $LinkValue;
			$ContentIni = file_get_contents( $FullPath);
			sleep( 2);
			$IniDoc = new DOMDocument();
			$IniDoc->loadHTML( $ContentIni);
			
			$LiquidEntry = array();
			$LiquidEntry[ 'name'] = $LinkText;
			$LiquidEntry[ 'url'] = $FullPath;
			$DivTags = $IniDoc->getElementsByTagName('div');
			foreach ($DivTags as $DivTag)
			{
				$Classname = $DivTag->getAttribute("class");
				if ( strcmp( $Classname, 'draft_content wiki') === 0)
				{
	  			$LiquidEntry[ 'current_draft_content'] = $DivTag->nodeValue;//preg_replace('/\n/', '/\r\n', $DivTag->nodeValue);				
				}
				if ( strcmp( $Classname, 'content issue_policy_info') === 0)
				{
					$TimeRemaining = substr( $DivTag->lastChild->nodeValue, strpos( $DivTag->lastChild->nodeValue, 'noch'));
					$LiquidEntry[ 'str_remaining'] = $TimeRemaining;
				}				
			}
			//author
			$SpanTags = $IniDoc->getElementsByTagName('span');
			foreach ( $SpanTags as $SpanTag)			
			{
				$Classname = $SpanTag->getAttribute( "class");

				if ( strcmp( $Classname, 'initiator_names') === 0)
				{
					$LocalLinks = $SpanTag->getElementsByTagName( 'a');
					if ( $LocalLinks->length >= 2)
					{
						$LiquidEntry[ 'author'] = $LocalLinks->item(1)->nodeValue;
					}					
				}

			}
			array_push( $ImportLqfb, $LiquidEntry);
		}
	}
	$ImportData[ $SrcName] = json_encode( $ImportLqfb);
}

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

/*
<row 
	Type="Member" 
	Value="{&#10;    &amp;quot;mitglieder&amp;quot;: 484,&#10;    &amp;quot;stimmberechtigt&amp;quot;: 269,&#10;    &amp;quot;einwohner&amp;quot;: 1600000,&#10;    &amp;quot;mitglieder_je_einwohner&amp;quot;: 295,&#10;    &amp;quot;flaeche&amp;quot;: 23180,&#10;    &amp;quot;mitglieder_je_flaeche&amp;quot;: 21,&#10;    &amp;quot;stand&amp;quot;: &amp;quot;04.08.2012&amp;quot;&#10;}"/>
*/
?>