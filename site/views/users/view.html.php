<?php
/*------------------------------------------------------------------------

# TZ Portfolio Extension

# ------------------------------------------------------------------------

# author    DuongTVTemPlaza

# copyright Copyright (C) 2012 templaza.com. All Rights Reserved.

# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL

# Websites: http://www.templaza.com

# Technical Support:  Forum - http://templaza.com/Forum

-------------------------------------------------------------------------*/
 
//no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class TZ_PortfolioViewUsers extends JViewLegacy
{
    protected $item = null;
    function __construct($config = array()){
        $this -> item   = new stdClass();
        parent::__construct($config);
    }
    function display($tpl = null){
        $doc    = JFactory::getDocument();

        $state  = $this -> get('State');
        $params = $state -> params;
        $list   = $this -> get('Users');

        $csscompress    = null;
        if($params -> get('css_compression',0)){
            $csscompress    = '.min';
        }

        $jscompress         = new stdClass();
        $jscompress -> extfile  = null;
        $jscompress -> folder   = null;
        if($params -> get('js_compression',1)){
            $jscompress -> extfile  = '.min';
            $jscompress -> folder   = '/packed';
        }

        if($list){
            $user	= JFactory::getUser();
            $userId	= $user->get('id');
            $guest	= $user->get('guest');
            
            //Get Plugins Model
            $pmodel = JModelLegacy::getInstance('Plugins','TZ_PortfolioModel',array('ignore_request' => true));

            if($params -> get('comment_function_type','default') != 'js'){
                // Compute the article slugs and prepare introtext (runs content plugins).
                if($params -> get('tz_show_count_comment',1) == 1){
                    require_once(JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'HTTPFetcher.php');
                    require_once(JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'readfile.php');
                    $fetch       = new Services_Yadis_PlainHTTPFetcher();
                }
                $threadLink = null;
                $comments   = null;
                if($list){
                    foreach($list as $key => $item){

                        $tzRedirect = $params -> get('tz_portfolio_redirect','p_article'); //Set params for $tzRedirect
                        $itemParams = new JRegistry($item -> attribs); //Get Article's Params

                        //Check redirect to view article
                        if($itemParams -> get('tz_portfolio_redirect')){
                            $tzRedirect = $itemParams -> get('tz_portfolio_redirect');
                        }

                        if($tzRedirect == 'article'){
                            $contentUrl =JRoute::_(TZ_PortfolioHelperRoute::getArticleRoute($item -> slug,$item -> catid), true ,-1);
                        }
                        else{
                            $contentUrl =JRoute::_(TZ_PortfolioHelperRoute::getPortfolioArticleRoute($item -> slug,$item -> catid), true ,-1);
                        }

                        if($params -> get('tz_show_count_comment',1) == 1){
                            if($params -> get('tz_comment_type','disqus') == 'disqus'){
                                $threadLink .= '&thread[]=link:'.$contentUrl;
                            }elseif($params -> get('tz_comment_type','disqus') == 'facebook'){
                                $threadLink .= '&urls[]='.$contentUrl;
                            }
                        }
                    }
                }

                // Get comment counts for all items(articles)
                if($params -> get('tz_show_count_comment',1) == 1){
                    // From Disqus
                    if($params -> get('tz_comment_type','disqus') == 'disqus'){
                        if($threadLink){
                            $url        = 'https://disqus.com/api/3.0/threads/list.json?api_secret='
                                          .$params -> get('disqusApiSecretKey','4sLbLjSq7ZCYtlMkfsG7SS5muVp7DsGgwedJL5gRsfUuXIt6AX5h6Ae6PnNREMiB')
                                          .'&forum='.$params -> get('disqusSubDomain','templazatoturials')
                                          .$threadLink.'&include=open';

                            $content    = $fetch -> get($url);

                            if($content){
                                if($body    = json_decode($content -> body)){
                                    if($responses = $body -> response){
                                        foreach($responses as $response){
                                            $comments[$response ->link]   = $response -> posts;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // From Facebook
                    if($params -> get('tz_comment_type','disqus') == 'facebook'){
                        if($threadLink){
                            $url        = 'http://api.facebook.com/restserver.php?method=links.getStats'
                                          .$threadLink;
                            $content    = $fetch -> get($url);

                            if($content){
                                if($bodies = $content -> body){
                                    if(preg_match_all('/\<link_stat\>(.*?)\<\/link_stat\>/ims',$bodies,$matches)){
                                        if(isset($matches[1]) && !empty($matches[1])){
                                            foreach($matches[1]as $val){
                                                $match  = null;
                                                if(preg_match('/\<url\>(.*?)\<\/url\>.*?\<comment_count\>(.*?)\<\/comment_count\>/msi',$val,$match)){
                                                    if(isset($match[1]) && isset($match[2])){
                                                        $comments[$match[1]]    = $match[2];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // End Get comment counts for all items(articles)
            }else{
                // Add facebook script api
                if($params -> get('tz_show_count_comment',1) == 1){
                    if($params -> get('tz_comment_type','disqus') == 'facebook'){
                        $doc -> addScriptDeclaration('
                            (function(d, s, id) {
                              var js, fjs = d.getElementsByTagName(s)[0];
                              if (d.getElementById(id)) return;
                              js = d.createElement(s); js.id = id;
                              js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
                              fjs.parentNode.insertBefore(js, fjs);
                            }(document, \'script\', \'facebook-jssdk\'));
                       ');
                    }

                    // Add disqus script api
                    if($params -> get('tz_comment_type','disqus') == 'disqus'){
                        $doc -> addScriptDeclaration('
                            /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
                            var disqus_shortname = \'templazatoturials\'; // required: replace example with your forum shortname

                            /* * * DON\'T EDIT BELOW THIS LINE * * */
                            (function () {
                            var s = document.createElement(\'script\'); s.async = true;
                            s.type = \'text/javascript\';
                            s.src = \'http://\' + disqus_shortname + \'.disqus.com/count.js\';
                            (document.getElementsByTagName(\'HEAD\')[0] || document.getElementsByTagName(\'BODY\')[0]).appendChild(s);
                            }());
                       ');
                        $doc -> addCustomTag('
                        <script type="text/javascript">
                            window.addEvent("load",function(){
                                var a=document.getElementsByTagName("A");

                                for(var h=0;h<a.length;h++){
                                    if(a[h].href.indexOf("#disqus_thread")>=0){
                                    var span = document.createElement("span");
                                    span.innerHTML  = a[h].innerHTML;
                                    a[h].parentNode.appendChild(span);
                                    a[h].remove();
                                    }
                                }
                            });
                        </script>
                       ');
                    }
                }
            }

            foreach($list as $row){
                $tzRedirect = $params -> get('tz_portfolio_redirect','p_article'); //Set params for $tzRedirect
                $itemParams = new JRegistry($row -> attribs); //Get Article's Params

                if($params -> get('comment_function_type','default') != 'js'){
                    //Check redirect to view article
                    if($itemParams -> get('tz_portfolio_redirect')){
                        $tzRedirect = $itemParams -> get('tz_portfolio_redirect');
                    }

                    if($tzRedirect == 'article'){
                        $contentUrl =JRoute::_(TZ_PortfolioHelperRoute::getArticleRoute($row -> slug,$row -> catid), true ,-1);
                    }
                    else{
                        $contentUrl =JRoute::_(TZ_PortfolioHelperRoute::getPortfolioArticleRoute($row -> slug,$row -> catid), true ,-1);
                    }

                    if($params -> get('tz_show_count_comment',1) == 1){
                        if($params -> get('tz_comment_type','disqus') == 'disqus' ||
                            $params -> get('tz_comment_type','disqus') == 'facebook'){
                            if($comments){
                                if(array_key_exists($contentUrl,$comments)){
                                    $row -> commentCount   = $comments[$contentUrl];
                                }else{
                                    $row -> commentCount   = 0;
                                }
                            }else{
                                $row -> commentCount   = 0;
                            }

                        }
                    }
                }else{
                    $row -> commentCount   = 0;
                }

                // Compute the asset access permissions.
                // Technically guest could edit an article, but lets not check that to improve performance a little.
                if (!$guest) {
                    $asset	= 'com_tz_portfolio.article.'.$row->id;

                    // Check general edit permission first.
                    if ($user->authorise('core.edit', $asset)) {
                        $itemParams->set('access-edit', true);
                    }
                    // Now check if edit.own is available.
                    elseif (!empty($userId) && $user->authorise('core.edit.own', $asset)) {
                        // Check for a valid user and that they are the owner.
                        if ($userId == $row->created_by) {
                            $itemParams->set('access-edit', true);
                        }
                    }
                }

                $row -> attribs = $itemParams -> toString();

                $row -> text    = null;
                if ($params->get('show_intro', 1)==1) {
                    $row -> text = $row -> introtext;
                }

                JPluginHelper::importPlugin('content');

                $dispatcher	= JDispatcher::getInstance();

                    //
                // Process the content plugins.
                //

                $row->event = new stdClass();
                $results = $dispatcher->trigger('onContentPrepare', array ('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));

                $results = $dispatcher->trigger('onContentAfterTitle', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->afterDisplayTitle = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onContentBeforeDisplay', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->beforeDisplayContent = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onContentAfterDisplay', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->afterDisplayContent = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onContentTZPortfolioVote', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->TZPortfolioVote = trim(implode("\n", $results));

                //Get plugin Params for this article
                $pmodel -> setState('filter.contentid',$row -> id);
                $pluginItems            = $pmodel -> getItems();
                $pluginParams           = $pmodel -> getParams();
                $row -> pluginparams    = clone($pluginParams);

                JPluginHelper::importPlugin('tz_portfolio');
                $results   = $dispatcher -> trigger('onTZPluginPrepare',array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));

                $results = $dispatcher->trigger('onTZPluginAfterTitle', array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));
                $row->event->TZafterDisplayTitle = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onTZPluginBeforeDisplay', array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));
                $row->event->TZbeforeDisplayContent = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onTZPluginAfterDisplay', array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));
                $row->event->TZafterDisplayContent = trim(implode("\n", $results));
            }
        }
        
        $this -> assign('listsUsers',$list);
        $this -> assign('authorParams',$params);
        $this -> assign('params',$params);
        $this -> assign('mediaParams',$params);
        $this -> assign('pagination',$this -> get('Pagination'));
        $author = JModelLegacy::getInstance('User','TZ_PortfolioModel');
        $author = $author -> getUserId(JRequest::getInt('created_by'));
        $this -> assign('listAuthor',$author);
        $params = $this -> get('state') -> params;

        $model  = JModelLegacy::getInstance('Portfolio','TZ_PortfolioModel',array('ignore_request' => true));
        $model -> setState('params',$params);
        $model -> setState('filter.userId',$state -> get('users.id'));
        $this -> assign('char',$state -> get('char'));
        $this -> assign('availLetter',$model -> getAvailableLetter());
        
        if($params -> get('tz_use_image_hover',1) == 1):
            $doc -> addStyleDeclaration('
                .tz_image_hover{
                    opacity: 0;
                    position: absolute;
                    top:0;
                    left: 0;
                    transition: opacity '.$params -> get('tz_image_timeout',0.35).'s ease-in-out;
                   -moz-transition: opacity '.$params -> get('tz_image_timeout',0.35).'s ease-in-out;
                   -webkit-transition: opacity '.$params -> get('tz_image_timeout',0.35).'s ease-in-out;
                }
                .tz_image_hover:hover{
                    opacity: 1;
                    margin: 0;
                }
            ');
        endif;

        if($params -> get('tz_use_lightbox',1) == 1){
            $doc -> addCustomTag('<script type="text/javascript" src="components/com_tz_portfolio/js'.
                $jscompress -> folder.'/jquery.fancybox.pack'.$jscompress -> extfile.'.js"></script>');
            $doc -> addStyleSheet('components/com_tz_portfolio/css/fancybox'.$csscompress.'.css');

            $width      = null;
            $height     = null;
            $autosize   = null;
            if($params -> get('tz_lightbox_width')){
                if(preg_match('/%|px/',$params -> get('tz_lightbox_width'))){
                    $width  = 'width:\''.$params -> get('tz_lightbox_width').'\',';
                }
                else
                    $width  = 'width:'.$params -> get('tz_lightbox_width').',';
            }
            if($params -> get('tz_lightbox_height')){
                if(preg_match('/%|px/',$params -> get('tz_lightbox_height'))){
                    $height  = 'height:\''.$params -> get('tz_lightbox_height').'\',';
                }
                else
                    $height  = 'height:'.$params -> get('tz_lightbox_height').',';
            }
            if($width || $height){
                $autosize   = 'fitToView: false,autoSize: false,';
            }
            $doc -> addCustomTag('<script type="text/javascript">
                jQuery(\'.fancybox\').fancybox({
                    type:\'iframe\',
                    openSpeed:'.$params -> get('tz_lightbox_speed',350).',
                    openEffect: "'.$params -> get('tz_lightbox_transition','elastic').'",
                    '.$width.$height.$autosize.'
		            helpers:  {
                        title : {
                            type : "inside"
                        },
                        overlay : {
                            opacity:'.$params -> get('tz_lightbox_opacity',0.75).',
                        }
                    }
                });
                </script>
            ');
        }

        $doc -> addStyleSheet('components/com_tz_portfolio/css/tzportfolio'.$csscompress.'.css');

        // Add feed links
		if ($params->get('show_feed_link', 1)) {
			$link = '&format=feed&limitstart=';
			$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
			$doc->addHeadLink(JRoute::_($link . '&type=rss'), 'alternate', 'rel', $attribs);
			$attribs = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
			$doc->addHeadLink(JRoute::_($link . '&type=atom'), 'alternate', 'rel', $attribs);
		}
        
        parent::display($tpl);

    }


    protected function FindItemId($_userid=null){
        $app		= JFactory::getApplication();
        $menus		= $app->getMenu('site');
        $active     = $menus->getActive();
        if($_userid){
            $userid    = intval($_userid);
        }

        $component	= JComponentHelper::getComponent('com_tz_portfolio');
        $items		= $menus->getItems('component_id', $component->id);

        if($this -> params -> get('menu_active') && $this -> params -> get('menu_active') != 'auto'){
            return $this -> params -> get('menu_active');
        }

        foreach ($items as $item)
        {
            if (isset($item->query) && isset($item->query['view'])) {
                $view = $item->query['view'];

                if (isset($item -> query['created_by'])) {
                    if ($item->query['created_by'] == $userid) {
                        return $item -> id;
                    }
                }
                else{
                    if($item -> home == 1){
                        $homeId = $item -> id;
                    }
                }
            }
        }

        if(!isset($active -> id)){
            return $homeId;
        }

        return $active -> id;
    }
}