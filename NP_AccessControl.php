<?php
/* Version History:
* v1.41b-lm1 2015-01-12: by Leo (http://nucleus.slightlysome.net/leo)
* - Tested and updated to run on PHP 5.4
*/

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_AccessControl extends NucleusPlugin {

	function getName() { return 'Access Control'; }
	function getAuthor()  { return 'Andy, Leo'; }
	function getURL() { return 'http://nucleus.slightlysome.net/plugins/accesscontrol'; }
	function getVersion() { return '1.41b-lm1'; }
	
	function getDescription() { 
		return _ACCSSCNTRL_DESCRIPTION;
	}

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function init() {
		// include language file for this plugin
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory().$language.'.php'))
			include_once($this->getDirectory().$language.'.php');
		else
			include_once($this->getDirectory().'english.php');
	}


	function getEventList() { return array('InitSkinParse', 'PreSkinParse','PostSkinParse','PreItem', 'PreComment'); }	

	function install() {
		$this->createBlogOption('login_needed',_ACCSSCNTRL_OPTION_PROTECT,'select','nothing',
		_ACCSSCNTRL_OPTION_NOPROTECT . '|nothing|' .
		_ACCSSCNTRL_OPTION_LOGINONLY . '|needlogin|' .
		_ACCSSCNTRL_OPTION_MEMBERONLY . '|memberonly' );
		$this->createBlogOption('sub_skin',_ACCSSCNTRL_OPTION_LOGINSKIN,'text','loginform');
		$this->createBlogOption('allow_rss', _ACCSSCNTRL_OPTION_ALLOWRSS, 'yesno', 'no');
		$this->createBlogOption('rss_skins', _ACCSSCNTRL_OPTION_RSSSKINS, 'text', 'api/rsd,feeds/atom,feeds/rss20,xmlrss,xml/rsd');
		$this->createBlogOption('rss_username', _ACCSSCNTRL_OPTION_RSSUSER, 'text', '');
		$this->createBlogOption('rss_password', _ACCSSCNTRL_OPTION_RSSPASS, 'password', '');
		$this->createBlogOption('login_iteration',_ACCSSCNTRL_OPTION_ITERATION,'text','5');
		
		$this->createBlogOption('skin_restriction', _ACCSSCNTRL_OPTION_SKINREST,'yesno','no');
		$this->createBlogOption('allowed_skins', _ACCSSCNTRL_OPTION_ALLWDSKN,'text','feeds/atom,feeds/rss20,xmlrss,api/rsd');
		$this->createItemOption('restrict_this_item', _ACCSSCNTRL_OPTION_THISITEM, 'select', 'nothing',
		_ACCSSCNTRL_OPTION_NOPROTECT . '|nothing|' .
		_ACCSSCNTRL_OPTION_LOGINONLY . '|needlogin|' .
		_ACCSSCNTRL_OPTION_MEMBERONLY . '|memberonly');
	}
		
	function testlogin($data) {
		global $CONF, $manager, $member, $blogid, $itemid;
		if (($data['type'] == 'item') && ($this->getItemOption($itemid, 'restrict_this_item') == 'yes')) {
			if (!($member->isLoggedIn()) || (!$member->isTeamMember($blogid)))
				return FALSE;
		}
		switch ($this->getBlogOption($blogid, 'login_needed')) {
			case 'nothing' :
				return TRUE;
			case 'needlogin' :
				if ($member->isLoggedIn()) return TRUE;
				break;
			case 'memberonly' :
				if ($member->isLoggedIn() && $member->isTeamMember($blogid)) return TRUE;
				break;
		}
		if ($this->getBlogOption($blogid, 'allow_rss') == 'yes') {
			$skinname = $data['skin']->getName();
			$skinlist = explode(',', $this->getBlogOption($blogid, 'rss_skins'));
			if (in_array($skinname, $skinlist)) {
				$user = $this->getBlogOption($blogid, 'rss_username');
				$pass = $this->getBlogOption($blogid, 'rss_password');
				$iteration = $this->getBlogOption($blogid, 'login_iteration');
				while ($iteration-- && (($_SERVER['PHP_AUTH_USER'] != $user) || ($_SERVER['PHP_AUTH_PW'] != $pass))) {
					header('WWW-Authenticate: Basic realm="Authentication"');
					header('HTTP/1.0 401 Unauthorized');
					echo _ACCSSCNTRL_CONTENTS_PROHIBIT;
					exit;
				}
				if (!$iteration) return FALSE;
				return TRUE;
			}
		}
		
		$skinname = $this->getBlogOption($blogid, 'sub_skin');
		if ($data['skin']->id == SKIN::getIdFromName($skinname)) return TRUE;
		return FALSE;
	}

	function event_InitSkinParse(&$data) {
		global $blogid;
		if (!$this->testlogin($data)) {
			if (intrequestvar('iteration') < $this->getBlogOption($blogid, 'login_iteration')) {
				$skinName = trim($this->getBlogOption($blogid, 'sub_skin'));
				if (SKIN::exists($skinName)) {
					$skin =& SKIN::createFromName($skinName);
					// copied from NP_SkinSwitcher.php
					$data['skin']->SKIN($skin->getID()); 
				}
			} else {
				$this->doError = TRUE;
				$this->errorMessage = _ACCSSCNTRL_INVALID_USERPASS;
				return;
			}
		}
		if (!$this->testblog($data)) {
			$this->doError = TRUE;
			$this->errorMessage = _ACCSSCNTRL_CONTENTS_PROHIBIT;
		}
	}

	function event_PreSkinParse(&$data) {
		if ($this->doError && ($data['type'] != 'error')) {
			ob_start(array(&$this, 'ob_DoNothing'));
		}
	}
	
	function event_PostSkinParse(&$data) {
		if ($this->doError && ($data['type'] != 'error')) {
			ob_end_clean();
			$GLOBALS['errormessage'] = $this->errorMessage;
			$data['skin']->parse('error');
		}
	}

	function testblog($data) {
		global $blogid;
		$blog = new BLOG($blogid);
		if ($this->getBlogOption($blog->blogid, 'skin_restriction') == "no")
			return TRUE;
		$defaultskin = $blog->getDefaultSkin();
		$skinname = SKIN::getNameFromID($defaultskin);
		$currentSkinName = $data['skin']->getName();
		if ($currentSkinName == $skinname) return TRUE;
		$allowedskins = $this->getBlogOption($blog->blogid, 'allowed_skins');
		return ( ! (strpos(','.$allowedskins.',' , ','.$currentSkinName.',') === FALSE));
	}


	function ob_DoNothing($data) {
		return '';
	}
	
	function testitemcomment($bid, $iid) {
		global $blogid, $skinid, $manager, $member;
		if (($this->getBlogOption($bid, 'login_needed') == "memberonly") && (!$member->isLoggedIn() || !$member->isTeamMember($bid)))
			return FALSE;
		if (($this->getBlogOption($bid, 'login_needed') == "needlogin") && (!$member->isLoggedIn()))
			return FALSE;
		switch ($this->getItemOption($iid, 'restrict_this_item')) {
		case 'nothing' :
			break;
		case 'needlogin' :
			if (!$member->isLoggedIn()) return FALSE;
			break;
		case 'memberonly' :
			if (!$member->isLoggedIn() || !$member->isTeamMember($bid)) return FALSE;
			break;
		}
		if ($this->getBlogOption($bid, 'skin_restriction') == "no")
			return TRUE;
		$blog = $manager->getBlog($bid);
		$defaultskin = $blog->getDefaultSkin();
		if ($skinid == $defaultskin) return TRUE;
		$skinname = SKIN::getNameFromID($skinid);
		$allowedskins = $this->getBlogOption($blogid, 'allowed_skins');
		return (! strpos(','.$allowedskins.',' , ','.$skinname.',') === TRUE);
	}

	function event_PreItem(&$data) {
		$iid = $data['item']->itemid;
		$bid = getBlogIDFromItemID($iid);
		if (!$this->testitemcomment($bid, $iid)) {
			$data['item']->title = _ACCSSCNTRL_ITEM_PROHIBIT;
			$data['item']->body = '';
			$data['item']->more = '';
		}
	}

	function event_PreComment(&$data) {
		global $itemid;
		$bid = $data['comment']['blogid'];
//		$iid = $data['comment']['itemid'];
		if (!$this->testitemcomment($bid, $itemid)) {
			$data['comment'] = Array();
		}
	}

	function sanitizeRequestUri()
	{
		$request_uri = serverVar('SCRIPT_NAME');
		foreach ($_GET as $name => $val) {

			// when magic quotes off, need to use stripslashes
			if (!get_magic_quotes_gpc()) {
				$val = addslashes($val);
			}

			list($val,$tmp) = explode('\\', $val);
			$request_uri .= sprintf("?%s=%s", $name, $val);
		}

		$_SERVER['REQUEST_URI'] = $request_uri;
	}


	function doSkinVar($skinType, $param = '') {
		$this->sanitizeRequestUri();
//	sucoshi+yamabuki
		$search=array('?page=0','?action=logout');
		$zerouri=str_replace($search,'',$_SERVER['REQUEST_URI']);
		$url = 'http://' . $_SERVER["HTTP_HOST"] . $zerouri;
//		$url = 'http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$iteration = intrequestvar('iteration') + 1;
		$formdata =<<<LOGINFORM
	<form method="post" action="$url">
	  <div class="loginform">
		<input type="hidden" name="action" value="login" />
		<input type="hidden" name="iteration" value="$iteration" />
		<label for="nucleus_lf_name" accesskey="l"><%text(_LOGINFORM_NAME)%></label>: <input id="nucleus_lf_name" name="login" size="10" value="" class="formfield" />
		<br />
		<label for="nucleus_lf_pwd"><%text(_LOGINFORM_PWD)%></label>: <input id="nucleus_lf_pwd" name="password" size="10" type="password" value="" class="formfield" />
		<br />
		<input type="submit" value="<%text(_LOGIN)%>" class="formbutton" />
		<br />
		<input type="checkbox" value="1" name="shared" id="nucleus_lf_shared" /><label for="nucleus_lf_shared"><%text(_LOGINFORM_SHARED)%></label>
	  </div>
	</form>
LOGINFORM;

		$actions = new ACTIONS('index');
		$parser = new PARSER(array('text'), $actions);
		$actions->setParser($parser);
		$parser->parse($formdata);
	}

	function doTemplateCommentsVar(&$item, &$comment, $param1) {
		doTemplateVar($item, $param1);
	}

	function doTemplateVar(&$item, $param1) {
		$iid = $item->itemid;
		$bid = getBlogIDFromItemID($iid);
		switch ($param1) {
		case 'checkin' :
			if (!$this->testitemcomment($bid, $iid)) {
				ob_start(array(&$this, 'ob_DoNothing'));
			}
			break;
		case 'checkout' :
			if (!$this->testitemcomment($bid, $iid)) {
				ob_end_clean();
			}
			break;
		}
	}

}
?>