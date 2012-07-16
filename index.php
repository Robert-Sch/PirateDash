<?PHP
/*
  dashboard output
  Robert Schiewer
  Piraten Schwerin
  r.schiewer@web.de
*/

define( "PAD_DISPLAY_DAYS", 7);

abstract class IJsonStats
{
  //function GetOutput
  protected $JsonObj;
  public function __construct( $JsonObj = NULL)
  {
    $this->JsonObj = $JsonObj;
  }
  abstract function GetOutputHtml();
  //abstract function GetOutputPlain();
} 

//------------------------------------------------------------------------------
class TMemberStats extends IJsonStats
{
  function GetOutputHtml()
  {
    $Out = '';
    $Out .= TemplateLine( "Piraten " .
      "(" . ExtJVar( $this->JsonObj, "stand") . ")", 'b');
    $Out .= TemplateLine( 
      "Mitglieder: " . ExtJVar( $this->JsonObj, "mitglieder"));
    $Out .= "stimmberechtigt: " . ExtJVar( $this->JsonObj, "stimmberechtigt");
    return $Out;
  }
}; 
//------------------------------------------------------------------------------
class TFinanceStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    $Out .= TemplateLine( "Konto " .
      "(" . ExtJVar( $this->JsonObj, "datum") . ")", 'b');    
    $Out .= TemplateLine( "EUR: " . ExtJVar( $this->JsonObj, "kontostand"));
    return $Out;
  }
};
//------------------------------------------------------------------------------
class THelpDeskStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    //$Out .= TemplateLine( "Helpdesk", 'b');
    $Out .= TemplateLine( "Tickets: " . ExtJVar( $this->JsonObj, "tickets_total"));
    $Out .= TemplateLine( "offen: " . ExtJVar( $this->JsonObj, "tickets_open"));
    $Out .= TemplateLine( "geschlossen: " . ExtJVar( $this->JsonObj, "tickets_closed"));
    $Out .= TemplateLine( "Nachrichten: " . ExtJVar( $this->JsonObj, "articles_total"));
    $Out .= TemplateLine( "EMails: " . ExtJVar( $this->JsonObj, "articles_mail"));
    $Out .= TemplateLine( "intern: " . ExtJVar( $this->JsonObj, "articles_internal"));
    return $Out;
  }
}

//------------------------------------------------------------------------------
//liquid feedback stats for all stages
class TLQFBStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    $ReverseOut = '';
    foreach ( $this->JsonObj as $LQFBEntry)
    {
      $DateStr = '';
      $JSDateStr = ExtJVar( $LQFBEntry, "issue_created");
      $LiquidText = ExtJVar( $LQFBEntry, 'current_draft_content');

      $LiquidText = nl2br( htmlspecialchars ( $LiquidText));
      $LiquidText = preg_replace('/\r/', '\\', $LiquidText);           
      //die();
      //$LiquidText = nl2br( $LiquidText);
      //$LiquidText = nl2br( $LiquidText);
      if ( !empty( $JSDateStr))
        $DateStr = date( "d.m.y", strtotime( $JSDateStr));         
      $ReverseOut =
              '<a target="_blank" '.
              ' onmouseover="return overlib(\''. $LiquidText .'\', WIDTH, 600);" ' . 
              ' onmouseout="return nd();" ' .
              'href="' . ExtJVar( $LQFBEntry, 'url') . '">' . 
              TemplateLine( ExtJVar( $LQFBEntry, 'name') . 
              '</a> (' . $DateStr .')') . 
              $ReverseOut;
    }
    $Out .= $ReverseOut;
    return $Out;
  }
}
//------------------------------------------------------------------------------
class TLQFBActiveStats extends TLQFBStats {};
class TLQFBFrozenStats extends TLQFBStats {};
class TLQFBAcceptedStats extends TLQFBStats {};

//------------------------------------------------------------------------------
//Redmine
class TRedmineStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    foreach ( $this->JsonObj->issues as $RedmineEntry)
    {
      $DateStr = '';
      $JSDateStr  = ExtJVar( $RedmineEntry, "updated_on"); 
      $TimeStamp  = strtotime( $JSDateStr); 
      $DateStr    = date( "d.m.y H:i", $TimeStamp);
      $Headline   = ExtJVar( $RedmineEntry, "subject");
      $ID         = ExtJVar( $RedmineEntry, "id");
      $Link       = "https://redmine.piratenpartei-mv.de/redmine/issues/" . $ID;

      $Out .= TemplateLine( 
        "ID ". $ID . " - " . $DateStr . ": " .
        '<a target="_blank" href="' . $Link . '">' .
        $Headline . '</a>');
    }
    return $Out; 
  }
}
//------------------------------------------------------------------------------
class TRedmineIssueStats extends TRedmineStats {};
class TRedmineTodoStats extends TRedmineStats {};
//------------------------------------------------------------------------------
//Pads last #PAD_DISPLAY_DAYS days
class TPadStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    foreach ( $this->JsonObj as $PadEntry)
    {
      $DateStr = '';
      $JSDateStr  = ExtJVar( $PadEntry, "lastUpdate"); 
      $TimeStamp  = strtotime( $JSDateStr); 
      $DateStr    = date( "d.m.y", $TimeStamp);
      $Headline   = ExtJVar( $PadEntry, "title");
      $Link       = ExtJVar( $PadEntry, "url");
      if ( $TimeStamp > time() - 3600*24* PAD_DISPLAY_DAYS)
      $Out .= TemplateLine( 
        $DateStr . ": " .
        '<a target="_blank" href="' . $Link . '">' .
        $Headline . '</a>');
    }
    return $Out; 
  }
}
//------------------------------------------------------------------------------                                                                                
//calendar stats
class TCalendarStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    //$Dates = array();
    foreach ( $this->JsonObj as $CalendarEntry)
    {
      $DateStr = '';
      $JSDateStr = ExtJVar( $CalendarEntry->start, "dateTime"); 
      if ( !empty( $JSDateStr))
      {
        $TimeStamp = strtotime( $JSDateStr); 
        //$Dates[ $TimeStamp] = ExtJVar( $CalendarEntry, "summary");
        $DateStr = date( "d.m.y H:i", $TimeStamp);
        $Out .= TemplateLine( $DateStr, "u");
        $Out .= TemplateLine( 
          '<a target="_blank" href="' . ExtJVar( $CalendarEntry, 'htmlLink') . '">' .
          ExtJVar( $CalendarEntry, "summary")) . '</a>';
        $Out .= TemplateLine( '');
      }
    }
    return $Out; 
  }
}
//------------------------------------------------------------------------------                                                                                
//Website Articles
class TArticleStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    foreach ( $this->JsonObj as $ArticleEntry)
    {
      //var_dump( $ArticleEntry);
      $DateStr = '';
      $JSDateStr = ExtJVar( $ArticleEntry, "timestamp"); 
      //wrong format yyyy-dd-mm to yyyy-mm-dd
      $_DD = substr( $JSDateStr, 5, 2);
      $_MM = substr( $JSDateStr, 8, 2);
      $JSDateStr = substr_replace( $JSDateStr, $_MM, 5, 2);
      $JSDateStr = substr_replace( $JSDateStr, $_DD, 8, 2);
      $TimeStamp = strtotime( $JSDateStr); 
      $DateStr = date( "d.m.y H:i", $TimeStamp);
      $Out .= TemplateLine( 
          $DateStr . ": " .
          '<a target="_blank" href="' . ExtJVar( $ArticleEntry, 'url') . '">' .
          ExtJVar( $ArticleEntry, "title") . '</a>');
    }
    return $Out; 
  }
}
//------------------------------------------------------------------------------
//Wiki Pages
class TWikiStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    foreach ( $this->JsonObj->query->recentchanges as $WikiEntry)
    {
      $DateStr = '';
      $JSDateStr = ExtJVar( $WikiEntry, "timestamp"); 
      $TimeStamp  = strtotime( $JSDateStr); 
      $DateStr    = date( "d.m.y H:i", $TimeStamp);
      $Headline   = ExtJVar( $WikiEntry, "title");
      $Author     = ExtJVar( $WikiEntry, "user"); 
      $Link       = "http://wiki.piratenpartei.de/" . $Headline;
      
      $Out .= TemplateLine( 
        $DateStr . ": " .
        '<a target="_blank" href="' . $Link . '">' .
        $Headline . '</a>');
    }
    return $Out; 
  }
}
//------------------------------------------------------------------------------                                                                                
//Facebook Articles
class TFacebookStats extends IJsonStats
{
  function GetOutputHtml()
  { 
    $Out = '';
    foreach ( $this->JsonObj as $FacebookEntry)
    {
      $DateStr = '';
      $JSDateStr = ExtJVar( $FacebookEntry, "time"); 
      $TimeStamp  = strtotime( $JSDateStr); 
      $DateStr    = date( "d.m.y H:i", $TimeStamp);
      $Headline   = ExtJVar( $FacebookEntry, "name");
      $Type       = ExtJVar( $FacebookEntry, "type"); 
      $Link       = ExtJVar( $FacebookEntry, 'link');
      if ( empty( $Headline)) $Headline = 'kein Titel';
      if ( !empty( $Link) && strcmp( $Type, 'link') === 0)                                                   
        $Out .= TemplateLine( 
          $DateStr . ": " .
          '<a target="_blank" href="' . $Link . '">' .
          $Headline . '</a>');
    } 
    return $Out; 
  }
}
//------------------------------------------------------------------------------
function TemplateLine( $Input = '', $Format = '')
{
  if ( empty( $Format))
  {
    return $Input . "<br>";
  }
  else
  {
    return "<".$Format.">" . $Input . "</".$Format."><br>";
  }
}
//------------------------------------------------------------------------------
function ExtractJsonVar( &$JsonObj, $VarName)
{
  $Out = '';
  try
  {
    if ( property_exists( $JsonObj, $VarName))
      $Out = $JsonObj->$VarName; 
    return $Out;
  }
  catch ( Exception $Exc)
  {
    return $Out;
  }
}
//------------------------------------------------------------------------------
function ExtJVar( &$JsonObj, $VarName) { return ExtractJsonVar( $JsonObj, $VarName); }
//------------------------------------------------------------------------------
header("Content-Type: text/html; charset=utf-8");


$DashOut = '';
$DataFile = "dashdata.xml";
$DashXML = simplexml_load_file( $DataFile);
$Node = $DashXML->children();


$TemplateFile = "template.html";
$TemplateContent = utf8_encode( file_get_contents( $TemplateFile));

foreach( $DashXML->children() AS $Child)
{

  $Attributes = $Child->attributes(); 
  $TypeObj = $Attributes->Type;
  $JsonStr = html_entity_decode( $Attributes->Value);
  $JsonStr = html_entity_decode( $Attributes->Value);
  $JsonStr = json_decode( $JsonStr);
  $TypeStr = (string) $TypeObj . "Stats";
  //if ( isset( $_GET[ 'module']) && strcmp( $TypeStr, $_GET[ 'module']) !== 0)
  //  continue;
  $ClassName = "T" . $TypeStr;
  if ( class_exists( $ClassName))
  {
  	$TempClass = new $ClassName( $JsonStr);
  	$DashOut = $TempClass->GetOutputHtml();

  } 
  $TemplateContent = str_replace( '{'.StrToUpper($TypeStr).'}', $DashOut, $TemplateContent);

}
$LastXMLChange = filectime( $DataFile);
$TemplateContent = str_replace( '{PAGESTATS}', date( "d.m.y H:i", $LastXMLChange), $TemplateContent);

echo $TemplateContent;  


?>