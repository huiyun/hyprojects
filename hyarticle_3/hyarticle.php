<?php

/**
 * @package Plugin HY Article for Joomla! 3.1
 * @version hyarticle.php v3001 2013-09-01
 * @author Huiyun Lu
 * @copyright (C) 2013 - HY Projects
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
define('DS',DIRECTORY_SEPARATOR);
include_once(JPATH_ROOT.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');

class plgContentHyarticle extends JPlugin {

/**
         * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
         * If you want to support 3.0 series you must override the constructor
         *
         * @var    boolean
         * @since  3.1
         */
        protected $autoloadLanguage = true;
 
        /**
         * Plugin method with the same name as the event will be called automatically.
         */


/**
	 * Plugin that embeds an article inside another article.
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$article An object with a "text" property or the string to be cloaked.
	 * @param   mixed    &$params  Additional parameters. See {@see PlgContentEmailcloak()}.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  boolean	True on success.
	 */

public function onContentPrepare($context, &$article, &$params, $page = 0) {

	if ($context == 'com_finder.indexer')
		{
			return true;
		}
	
	if(is_object($article)) return $this->embedArticle($article->text);
	else return $this->embedArticle($article);

}
// ////////////////////////////////////////////////////////////////////////////////
// Gather all the required data from the content
// Return $matches, an array containing the data
protected function gatherData($subject) {

$matches=array();
$pattern = '/{hyarticle (?P<id>\d+)( title=(?P<title>[yn]))?}/';

for ($i=0,$offset=0;;$i++) {
	if (!preg_match ($pattern, $subject, $matches[$i], PREG_OFFSET_CAPTURE, $offset)) break;
	else $offset=$matches[$i][0][1]+1;	
}

return $matches;
}
// ////////////////////////////////////////////////////////////////////////////////
// Fill array with the data gathered
// Return $arrD, the filled array
// $arrS: Source Array, $iden1: 2nd dimension index, $iden2: 3rd dimension index
protected function fillArray1($arrS, $iden1, $iden2) {

$max = sizeof($arrS);
for ($i=0; $i<$max; $i++) {
	$arrD[$i] = $arrS[$i][$iden1][$iden2];																																					
}

return $arrD;
}
// ////////////////////////////////////////////////////////////////////////////////
// Check if user can edit all articles
// Return true if yes, otherwise false
// Object $user is an instance of JUser, $pk is the primay key or id
protected function uCanEdit($user, $pk) {

if ($user->authorise( "core.edit", "com_content.article.".$pk)) return true;
else return false;
}
// ////////////////////////////////////////////////////////////////////////////////
// Check if user can edit own article
// Return true if yes, otherwise false
// Object $user is an instance of JUser, $pk is the primay key or id, $author is authorid
protected function uCanEditOwn($user, $pk, $author) {

$userid = $user->get('id');
if ( $user->authorise( "core.edit.own", "com_content.article.".$pk) && $userid==$author ) return true;
else return false;
}
// ////////////////////////////////////////////////////////////////////////////////
// Get the access levels of a user / guest and implode it into a string to pass into query
// Return $useracc a string of all the access levels for a user separated by commas
// Object $user is an instance of JUser
protected function viewAccess($user) {

/* if user is a guest, they can view access public:1 and guest:5*/
if ($user->get('Guest')) $useracc="1,5";

else {
	$userid = $user->get('id');
	$useracc = implode(",", $user->getAuthorisedViewLevels($userid));
}

return $useracc;
}
// ////////////////////////////////////////////////////////////////////////////////
// Convert Id to content article
// Return $arrD which is filled with the content of the embedded article
// Array $arrS contains the article ids extracted from {hyarticle id}
protected function findArticle($arrS) {

/* Get plugin params */
$butreadmore = $this->params->get('hyarticle_readmore', 'hy-article');
$butedit = $this->params->get('hyarticle_edit', 'hy-edit');

/* Initialised parameters */
$max = sizeof($arrS);
$arrD = array_fill(0,$max," ");
$idstr = "";

/* Joomla objects */
$db1 = JFactory::getDBO();
$user1 = JFactory::getUser();
$uri1 = JFactory::getURI();
$date1 = JFactory::getDate();

/* Frontend edit link HTML. Article id is appended during loop later. */
$editlink1 = '<div><a class="'.$butedit.'" href="'.JURI::root().'index.php?task=article.edit&a_id=';
$editlink2 = '&return='.base64_encode($uri1).'">'.JText::_( 'PLG_CONTENT_HYARTICLE_BUTTON_EDIT' ).' ';
$editlink3 = '</a></div>';

/* Readmore link HTML. Article id is appended during loop later. */
$read1 = '<div><a class="'.$butreadmore.'" href="';
$read2 = '">'.JText::_( 'PLG_CONTENT_HYARTICLE_BUTTON_READMORE' ).'</a></div>';

/* View access levels of users */
$access1 = $this->viewAccess($user1);

/* Convert the array of article ids into integers and join them into a string separated by comma */
for ($i=0; $i<$max; $i++) {
	$idstr = $idstr.(int)$arrS[$i];
	if ($i!=$max-1) $idstr = $idstr.",";
}

/* Filter by publish date */
$nullDate = $db1->quote($db1->getNullDate());
$nowDate = $db1->quote($date1->toSql());

$query1 = $db1->getQuery(true);
$query1->select('*')->from('#__content')
	->where('id IN ('.$idstr.')')->where('state=1')->where('access IN ('.$access1.')')
	->where('(publish_up = ' . $nullDate . ' OR publish_up <= ' . $nowDate . ')')
	->where('(publish_down = ' . $nullDate . ' OR publish_down >= ' . $nowDate . ')');

$db1->setQuery($query1);
$result1 = $db1->loadObjectList();

foreach ($result1 as $r) {
	/* Construct edit link */
	$edit = $this->uCanEdit($user1, $r->id) || $this->uCanEditOwn($user1, $r->id, $r->created_by)
	? $editlink1.$r->id.$editlink2.$r->title.$editlink3 : "";
	/* Construct readmore link */
	$read = $r->fulltext
	? $read1.JRoute::_(ContentHelperRoute::getArticleRoute($r->id, $r->catid)).$read2 : "";
	/* Construct the replaced content */
	$content =  JHtml::_('content.prepare', $r->introtext).$read.$edit;
	
	for ($i=0; $i<$max; $i++) {
		if ((int)$arrS[$i]==$r->id) $arrD[$i] = $content;				
	}	
}

return $arrD;
}
// ////////////////////////////////////////////////////////////////////////////////
// Combine all functions
protected function embedArticle(&$text) {

	if (strpos($text,'{hyarticle')===false) {
		return true;
	}

	else {
		$gather = $this->gatherData($text);
		$find = $this->fillArray1($gather,0,0);
		$replace1 = $this->fillArray1($gather,'id',0);
		$replace = $this->findArticle($replace1);
		$text = str_replace($find, $replace, $text);
		
		return true;
	}

}

}
