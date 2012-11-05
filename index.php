<?PHP
/*
  dashboard output
  Robert Schiewer
  Piraten Schwerin
  r.schiewer@web.de
*/

define( "PAD_DISPLAY_DAYS", 10);
define( "TWITTER_DISPLAY_DAYS", 10);

global $TweetCache;
$TweetCache = array();
class TTweet
{ 
  public $Author, $Content;
  public function __construct( $Author, $Content)
  {
    $this->Author = $Author;
    $this->Content = $Content;
  }
}

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
  protected $NextLevelStr = 'next period';
  protected $JBeginVar = 'issue_half_frozen';
  protected $JFinishVar = 'issue_verification_time';
  
  function GetOutputHtml()
  { 
    $Out = '';
    $ReverseOut = '';
    //var_dump( $this->JsonObj);
    if ( !is_array( $this->JsonObj))
      return;
    foreach ( $this->JsonObj as $LQFBEntry)
    {
      $DateStr    = '';
      $Author = ExtJVar( $LQFBEntry, "author");
      $JSDateStr  = ExtJVar( $LQFBEntry, "issue_created");
      $IHFDateStr = ExtJVar( $LQFBEntry, $this->JBeginVar);
      $PhaseLengthStr = ExtJVar( $LQFBEntry, $this->JFinishVar); 
      $PhaseLength = intval( substr($PhaseLengthStr, 0, strlen( $PhaseLengthStr)-4));                                                          
      if ( !empty( $IHFDateStr) && !empty( $PhaseLength))
      {
        $FinishTime = strtotime( $IHFDateStr) + $PhaseLength * 24 * 3600;
        $RemainingH = round( ($FinishTime - time()) / 3600, 0);
        $DateStr = date( "d.m.y H:i", $FinishTime) . ", ";
      }

      $LiquidText = addslashes( ExtJVar( $LQFBEntry, 'current_draft_content'));
      //var_dump( $LiquidText);
      $LiquidText = nl2br( htmlspecialchars ( $LiquidText));
      $LiquidText = preg_replace('/\n/', '\\\n', $LiquidText);           
      if ( !empty( $JSDateStr))
        $DateStr = date( "d.m.y", strtotime( $JSDateStr)) . ", ";
      
      $FinishStr = ExtJVar( $LQFBEntry, "str_remaining");
      if ( !empty( $FinishStr))
      {
      	$FinishStr = $FinishStr . ' bis '. $this->NextLevelStr;
      }
      if ( !empty( $FinishTime))
      {
      	$FinishStr = PrettyPeriod( $FinishTime) . ' bis '. $this->NextLevelStr;
      }
      $Out .=
              '<a target="_blank" '.
              ' onmouseover="return overlib(\''. $LiquidText .'\', WIDTH, 600);" ' . 
              ' onmouseout="return nd();" ' .
              'href="' . ExtJVar( $LQFBEntry, 'url') . '">' . 
              TemplateLine( ExtJVar( $LQFBEntry, 'name') . 
              '</a> (' . $DateStr . 
              (!empty( $Author)? $Author . "," : "") .
              $FinishStr .
              ')') . 
              $ReverseOut;
    }
    //$Out .= $ReverseOut;
    return $Out;
  }
}
//------------------------------------------------------------------------------
class TLQFBActiveStats extends TLQFBStats
{
  protected $NextLevelStr = 'geschlossen';
  protected $JBeginVar = 'issue_fully_frozen';
  protected $JFinishVar = 'issue_voting_time';
};
class TLQFBFrozenStats extends TLQFBStats
{ 
  protected $NextLevelStr = 'Abstimmung';
  protected $JBeginVar = 'issue_half_frozen';
  protected $JFinishVar = 'issue_verification_time';
};
class TLQFBAcceptedStats extends TLQFBStats
{ 
  protected $NextLevelStr = 'eingefroren';
  protected $JBeginVar = 'issue_created';
  protected $JFinishVar = 'issue_discussion_time';
};
//------------------------------------------------------------------------------
class TLQFBBUActiveStats extends TLQFBStats
{
  protected $NextLevelStr = 'geschlossen';
  protected $JBeginVar = 'issue_fully_frozen';
  protected $JFinishVar = 'issue_voting_time';
};
class TLQFBBUFrozenStats extends TLQFBStats
{ 
  protected $NextLevelStr = 'Abstimmung';
  protected $JBeginVar = 'issue_half_frozen';
  protected $JFinishVar = 'issue_verification_time';
};
class TLQFBBUAcceptedStats extends TLQFBStats
{ 
  protected $NextLevelStr = 'eingefroren';
  protected $JBeginVar = 'issue_created';
  protected $JFinishVar = 'issue_discussion_time';
};
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
      $Author     = ExtJVar( $RedmineEntry->author, "name");
      $RMText     = ExtJVar( $RedmineEntry, "description");
      $RMText     = nl2br( htmlspecialchars ( $RMText));
      $RMText     = preg_replace('/\r/', '\\', $RMText);
      $Link       = "https://redmine.piratenpartei-mv.de/redmine/issues/" . $ID;

      $Out .= TemplateLine( 
        'ID '. $ID . ' - ' . $DateStr . ': <a' .
        ' onmouseover="return overlib(\''. $RMText .'\', WIDTH, 600);" ' . 
        ' onmouseout="return nd();" ' .
        ' target="_blank" href="' . $Link . '">' .
        $Headline . '</a> (' . $Author . ')');
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
        $Headline . '</a> (' . $Author . ')');
    }
    return $Out; 
  }
}
//------------------------------------------------------------------------------                                                                                
//Facebook Articles -> Mailinglist
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
      $FBText     = ExtJVar( $FacebookEntry, "description");
      $FBText     = nl2br( htmlspecialchars ( $FBText));
      $FBText     = preg_replace('/\r/', '\\', $FBText);      
      $FBText     = preg_replace('/\n/', '\\\n', $FBText);
      if ( empty( $Headline)) $Headline = 'kein Titel';
      if ( !empty( $Link) && strcmp( $Type, 'link') === 0)                                                   
        $Out .= TemplateLine( 
          $DateStr . ': <a' .
          ' onmouseover="return overlib(\''. $FBText .'\', WIDTH, 600);" ' . 
          ' onmouseout="return nd();" ' .
          ' target="_blank" href="' . $Link . '">' .
          $Headline . '</a>');
    } 
    return $Out; 
  }
}
//------------------------------------------------------------------------------
class TTwitterStats extends IJsonStats
{
  protected $UserFilter; 
  
  function GetOutputHtml()
  {
    //only Collecting
    global $TweetCache;
    foreach ( $this->JsonObj as $Tweet)
    {
      $JSDateStr = ExtJVar( $Tweet, "time");
      $TimeStamp = strtotime( $JSDateStr);
      $TwUser = ExtJVar( $Tweet, "user");
      if ( !empty( $this->UserFilter) && strcmp( $TwUser, $this->UserFilter) !== 0)
        continue;      
      $TwText    = ExtJVar( $Tweet, "text") . " "; 
      $TweetCache[ $TimeStamp] = new TTweet( $TwUser, $TwText); 
    } 
  }
  
  function GetCollectedOutputHtml()
  {
    $Out = '';
    $ColorClass = '';
    global $TweetCache;
    krsort( $TweetCache, SORT_NUMERIC); 
    foreach ( $TweetCache as $TimeStamp => $Tweet)
    {
      $DateStr    = date( "d.m.y H:i", $TimeStamp);      
      if ( $TimeStamp > time() - 3600*24* TWITTER_DISPLAY_DAYS)
      {
        $ColorClass = strcmp( $ColorClass, "row1") !== 0 ? "row1" : "row2";
        $TwText = preg_replace('/(http|https):\/\/[^ ]*(?= )/si', '<a href="$0">$0</a>', $Tweet->Content);        
        $Out .= TemplateLine( 
                  TemplateLine( $DateStr . " " . $Tweet->Author . ':', FALSE, 'td', 'nowrap') . 
                  TemplateLine( $TwText, FALSE, 'td'), FALSE, 'tr', 'valign=top class=' .  $ColorClass);
      }
    }
    $Out = TemplateLine( $Out, FALSE, 'table');
    //var_dump( $Out);
    return $Out;    
  }
}
//------------------------------------------------------------------------------
class TTwitterMVStats extends TTwitterStats { protected $UserFilter = 'piraten_mv'; };
class TTwitterMVReplyStats extends TTwitterStats { protected $UserFilter = 'piraten_mv'; };
class TTwitterMVITStats extends TTwitterStats { protected $UserFilter = 'piraten_mv_it'; };
class TTwitterMVITReplyStats extends TTwitterStats { protected $UserFilter = 'piraten_mv_it'; };
class TTwitterMVLaVoStats extends TTwitterStats { protected $UserFilter = 'piraten_mv_lavo'; };
class TTwitterMVLaVoReplyStats extends TTwitterStats { protected $UserFilter = 'piraten_mv_lavo'; };

//------------------------------------------------------------------------------
function TemplateLine( $Input = '', $LineBreak = TRUE , $Format = '', $ExtraFlags = '')
{
  if ( empty( $Format))
  {
    return $Input . "<br>";
  }
  else
  {
    return "<".$Format." ". $ExtraFlags . ">" . $Input . "</".$Format.">" . ($LineBreak? "<br>" : "");
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
function PrettyPeriod( $FutureTime, $DayStr = 'd', $HourStr = 'h')
{
   $Result = '';
   $RemainingH = round( ($FutureTime - time()) / 3600, 0);
      
   if ( $RemainingH > 24)
   {
     $RemainingD = floor( $RemainingH / 24);
     $RemainingH = $RemainingH - $RemainingD * 24;
     $Result .= $RemainingD . $DayStr;
   }
   $Result .= ' ' . $RemainingH . $HourStr;
   return $Result;
}
//------------------------------------------------------------------------------
function ExtJVar( &$JsonObj, $VarName) { return ExtractJsonVar( $JsonObj, $VarName); }
//------------------------------------------------------------------------------
header("Content-Type: text/html; charset=utf-8");


$DashOut = '';
$DataFile = "dashdata.xml";
$DashXML = simplexml_load_file( $DataFile);


$TemplateFile = "template.html";
$TemplateContent = utf8_encode( file_get_contents( $TemplateFile));

foreach( $DashXML->children() AS $Child)
{

  $Attributes = $Child->attributes(); 
  $TypeObj = $Attributes->Type;
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

$TwitterStats = new TTwitterStats( '');
$TwitterOut = $TwitterStats->GetCollectedOutputHtml();
$LastXMLChange = filectime( $DataFile);
$TemplateContent = str_replace( '{PAGESTATS}', date( "d.m.y H:i", $LastXMLChange), $TemplateContent);
$TemplateContent = str_replace( '{TWITTERSTATS}', $TwitterOut, $TemplateContent);
echo $TemplateContent;  


?>