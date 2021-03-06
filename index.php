<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * xmcontent module
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @author          Mage Gregory (AKA Mage)
 */
include __DIR__ . '/header.php';
$xoopsOption['template_main'] = 'xmcontent_index.tpl';
include_once XOOPS_ROOT_PATH . '/header.php';

$keywords = '';

$xoopsTpl->assign('index_header', $helper->getConfig('index_header', ""));
$xoopsTpl->assign('index_footer', $helper->getConfig('index_footer', ""));
$xoopsTpl->assign('index_columncontent', $helper->getConfig('index_columncontent', ""));
$xoopsTpl->assign('index_content', $helper->getConfig('index_content', 0));

if (0 == $helper->getConfig('index_content', 0)) {
	// Get start pager
	$start = XoopsRequest::getInt('start', 0);
	// Criteria
	$criteria = new CriteriaCompo();
	$criteria->setSort('content_weight ASC, content_title');
	$criteria->setOrder('ASC');
	$criteria->setStart($start);
	$criteria->setLimit($nb_limit);
	$criteria->add(new Criteria('content_status', 1));
	$criteria->add(new Criteria('content_maindisplay', 1));
	$content_arr         = $contentHandler->getall($criteria);
	$content_count_total = $contentHandler->getCount($criteria);
	$content_count       = count($content_arr);
	$xoopsTpl->assign('content_count', $content_count);
	$count     = 1;
	$count_row = 1;
	if ($content_count > 0) {
		foreach (array_keys($content_arr) as $i) {
			$content_id       = $content_arr[$i]->getVar('content_id');
			$content['id']    = $content_id;
			$content['title'] = $content_arr[$i]->getVar('content_title');
			$content['logo'] = $url_logo . $content_arr[$i]->getVar('content_logo');
			$text             = $content_arr[$i]->getVar('content_text');		
			//short description
			if (true == $helper->getConfig('options_template', 0) && '' != $content_arr[$i]->getVar('content_template')){
				if (false == strpos($text, '[break_dsc]')){
					$content['text'] = $text;
				}else{
					$content['text'] = substr($text,0,strpos($text,'[break_dsc]'));
				}
			} else {
				if (false == strpos($text, '[break_dsc]')){
					$content['text'] = '';
				}else{
					$content['text'] = substr($text,0,strpos($text,'[break_dsc]'));
				}
			}		
			$content['count'] = $count;
			if ($count_row == $count) {
				$content['row'] = true;
				$count_row      = $count_row + $xoopsModuleConfig['index_columncontent'];
			} else {
				$content['row'] = false;
			}
			if ($count == $content_count) {
				$content['end'] = true;
			} else {
				$content['end'] = false;
			}
			$xoopsTpl->append_by_ref('content', $content);
			$count++;
			$keywords .= $content['title'] . ',';
			unset($content);
		}
		// Display Page Navigation
		if ($content_count_total > $nb_limit) {
			$nav = new XoopsPageNav($content_count_total, $nb_limit, $start, 'start');
			$xoopsTpl->assign('nav_menu', $nav->renderNav(4));
		}
	}
	//SEO
	//description
	$xoTheme->addMeta('meta', 'description', strip_tags($xoopsModule->name()));
	//keywords
	$keywords = substr($keywords, 0, -1);
	$xoTheme->addMeta('meta', 'keywords', $keywords);
} else {
	
	$content_id = $helper->getConfig('index_content', 0);
	if (0 == $content_id) {
		redirect_header(XOOPS_URL, 2, _AM_XMCONTENT_VIEWCONTENT_NOCONTENT);
		exit();
	}
	$content = $contentHandler->get($content_id);
	if (0 == count($content)) {
		redirect_header(XOOPS_URL, 2, _AM_XMCONTENT_VIEWCONTENT_NOCONTENT);
		exit();
	}
	if (0 == $content->getVar('content_status')) {
		redirect_header(XOOPS_URL, 2, _AM_XMCONTENT_VIEWCONTENT_NACTIVE);
		exit();
	}
	// permission to view
	$gpermHandler = xoops_getHandler('groupperm');
	if (is_object($xoopsUser)) {
		$groups = $xoopsUser->getGroups();
	} else {
		$groups = XOOPS_GROUP_ANONYMOUS;
	}
	$perm_view = $gpermHandler->checkRight('xmcontent_contentview', $content_id, $groups, $xoopsModule->getVar('mid'), false);
	if (!$perm_view) {
		redirect_header(XOOPS_URL, 2, _NOPERM);
		exit();
	}
	// css
	if (true == $helper->getConfig('options_css', 0) && '' != $content->getVar('content_css')){
		$xoTheme->addStylesheet( XOOPS_URL . '/uploads/xmcontent/css/' . $content->getVar('content_css'), null );
	}
	// template
	if (true == $helper->getConfig('options_template', 0) && '' != $content->getVar('content_template')){
		$xoopsTpl->assign('content_template', XOOPS_ROOT_PATH . "/uploads/xmcontent/templates/" . $content->getVar('content_template'));
	}
	$xoopsTpl->assign('content_title', $content->getVar('content_title'));
	$xoopsTpl->assign('content_text' , str_replace('[break_dsc]', '', $content->getVar('content_text', 'show')));
	$xoopsTpl->assign('content_docomment', $content->getVar('content_docomment'));
	$xoopsTpl->assign('content_dopdf', $content->getVar('content_dopdf'));
	$xoopsTpl->assign('content_doprint', $content->getVar('content_doprint'));
	$xoopsTpl->assign('content_dosocial', $content->getVar('content_dosocial'));
	$xoopsTpl->assign('content_domail', $content->getVar('content_domail'));
	$xoopsTpl->assign('content_dotitle', $content->getVar('content_dotitle'));

	//xmdoc
	if (xoops_isActiveModule('xmdoc') && $helper->getConfig('options_xmdoc', 0) == 1) {
		xoops_load('utility', 'xmdoc');
		XmdocUtility::renderDocuments($xoopsTpl, $xoTheme, 'xmcontent', $content_id);
	} else {
		$xoopsTpl->assign('xmdoc_viewdocs', false);
	}
	//SEO
	// pagetitle
	$xoopsTpl->assign('xoops_pagetitle', \Xmf\Metagen::generateSeoTitle($content->getVar('content_title') . '-' . $xoopsModule->name()));
	//description
	if ('' == $content->getVar('content_mdescription')) {
		$xoTheme->addMeta('meta', 'description', \Xmf\Metagen::generateDescription($content->getVar('content_text'), 30));
	} else {
		$xoTheme->addMeta('meta', 'description', $content->getVar('content_mdescription'));
	}
	//keywords
	if ('' == $content->getVar('content_mkeyword')) {
		$keywords = \Xmf\Metagen::generateKeywords($content->getVar('content_text'), 10);    
		$xoTheme->addMeta('meta', 'keywords', implode(', ', $keywords));
	} else {
		$xoTheme->addMeta('meta', 'keywords', $content->getVar('content_mkeyword'));
	}
}

include XOOPS_ROOT_PATH . '/footer.php';
