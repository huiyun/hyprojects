<?php

/**
 * @package Plugin HY Article for Joomla! 3.1
 * @version hyarticle.php v3110 2013-09-01
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
$pattern = '/{hyarticle (?P<id>\d+)(?P<title>;title)?(?P<introimg>;introimg)?(?P<fullimg>;fullimg)?(?P<nointro>;nointro)?(?P<fulltext>;fulltext)?}/';

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
$class_title = $this->params->get('hyarticle_title', 'hy-title');
$class_imgintro = $this->params->get('hyarticle_imgintro', 'hy-imgintro');
$class_imgfull = $this->params->get('hyarticle_imgfull', 'hy-imgfull');
$class_caption = $this->params->get('hyarticle_caption', 'hy-caption');

/* Initialised parameters */
$max = sizeof($arrS);
$arrD = array_fill(0,$max," ");

/* Joomla objects */
$db1 = JFactory::getDBO();
$user1 = JFactory::getUser();
$uri1 = JFactory::getURI();
$date1 = JFactory::getDate();

/* Clear float HTML. */
$clear = '<br/>';

/* Frontend edit link HTML. Article id is appended during loop later. */
$editlink1 = '<a class="'.$butedit.'" href="'.JURI::current().'?task=article.edit&a_id=';
$editlink2 = '&return='.base64_encode($uri1).'">'.JText::_( 'PLG_CONTENT_HYARTICLE_BUTTON_EDIT' ).' ';
$editlink3 = '</a>';

/* Readmore link HTML. Article id is appended during loop later. */
$read1 = '<a class="'.$butreadmore.'" href="';
$read2 = '"><span>'.JText::_( 'PLG_CONTENT_HYARTICLE_BUTTON_READMORE' ).'</span></a>';

/* Title HTML. Article id is appended during loop later. */
$title1 = '<div class="'.$class_title.'">';
$title2 = '</div>';

/* Images HTML. Article id is appended during loop later. */
$img1intro = '<div class="'.$class_imgintro;
$img1full = '<div class="'.$class_imgfull;
$img2 = '" style="float:';
$img3 = ';"><img src="';
$img4 = '" alt="';
$img5 = '" /><div class="'.$class_caption.'">';
$img6 = '</div></div>';

/* View access levels of users */
$access1 = $this->viewAccess($user1);

/* Filter by publish date */
$nullDate = $db1->quote($db1->getNullDate());
$nowDate = $db1->quote($date1->toSql());

/* Get com_content global parameters */
jimport('joomla.application.component.helper');
$global_floatintro = JComponentHelper::getParams('com_content')->get('float_intro');
$global_floatfull = JComponentHelper::getParams('com_content')->get('float_fulltext');

for ($i=0; $i<$max; $i++) {

	$query1 = $db1->getQuery(true);
	$query1->select('*')->from('#__content')
		->where('id='.(int)$arrS[$i][id][0])->where('state=1')->where('access IN ('.$access1.')')
		->where('(publish_up = ' . $nullDate . ' OR publish_up <= ' . $nowDate . ')')
		->where('(publish_down = ' . $nullDate . ' OR publish_down >= ' . $nowDate . ')');

	$db1->setQuery($query1);
	$r = $db1->loadObject();
	
	if($r) {
		/* Construct edit link */
		$edit = $this->uCanEdit($user1, $r->id) || $this->uCanEditOwn($user1, $r->id, $r->created_by)
		? $clear.$editlink1.$r->id.$editlink2.$r->title.$editlink3 : "";
	
		/* Construct readmore link */
		$read = $r->fulltext && !$arrS[$i][nointro][0] && !$arrS[$i][fulltext][0] 
		? $clear.$read1.JRoute::_(ContentHelperRoute::getArticleRoute($r->id, $r->catid)).$read2 : "";
		
		/* Construct title */
		$title = $arrS[$i][title][0] 
		? $title = $title1.$r->title.$title2 : "";
		
		/* Construct images */
		if ($arrS[$i][introimg][0] || $arrS[$i][fullimg][0]) {
			
			$arrimg = json_decode($r->images,true);
			
			if(!$arrimg[float_intro]) $arrimg[float_intro] = $global_floatintro;
			$imgintro = $arrS[$i][introimg][0]
			? $img1intro.$arrimg[float_intro].$img2.$arrimg[float_intro].$img3.$arrimg[image_intro].$img4.$arrimg[image_intro_alt].$img5.$arrimg[image_intro_caption].$img6 : "";
			
			if(!$arrimg[float_fulltext]) $arrimg[float_fulltext] = $global_floatfull;
			$imgfull = $arrS[$i][fullimg][0]
			? $img1full.$arrimg[float_fulltext].$img2.$arrimg[float_fulltext].$img3.$arrimg[image_fulltext].$img4.$arrimg[image_fulltext_alt].$img5.$arrimg[image_fulltext_caption].$img6 : "";
		}
		else {
			$imgintro = $imgfull = "";
		}
		
		/* Construct intro text */
		$introtext = $arrS[$i][nointro][0] 
		? "" : JHtml::_('content.prepare', $r->introtext);
		
		/* Construct full text */
		$fulltext = $arrS[$i][fulltext][0] 
		? JHtml::_('content.prepare', $r->fulltext) : "";
	
		/* Construct the replaced content */
		$arrD[$i] =  $title.$imgintro.$imgfull.$introtext.$fulltext.$read.$edit;
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
		$replace = $this->findArticle($gather);
		$text = str_replace($find, $replace, $text);
		
		return true;
	}

}

}
