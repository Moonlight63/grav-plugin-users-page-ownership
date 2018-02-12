<?php
namespace Grav\Plugin;

use Grav\Common\Assets;
use Grav\Common\Filesystem\Folder;
use Grav\Common\GPM\GPM;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Page\Collection;
use Grav\Common\Data\Blueprints;
use Grav\Common\Data\Data;
use Grav\Common\File\CompiledYamlFile;
use Grav\Common\Plugin;
use Grav\Common\Filesystem\RecursiveFolderFilterIterator;
use Grav\Common\User\User;
use Grav\Common\Utils;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Yaml\Yaml;

class UsersPageOwnershipPlugin extends Plugin
{
    protected $enable = false;
    protected $query;
    
    /*public $features = [
        'blueprints' => 9999,
    ];*/

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }
	
	public function onPluginsInitialized(){
        
        $this->grav['locator']->addPath('blueprints', '', __DIR__ . DS . 'blueprints');
        
        if ($this->isAdmin()) {
            $this->initializeAdmin();
        } else {
			$this->initializeClient();
		}
		$this->initializeGlobal();
    }
	
	/**
     * Admin side initialization ---------------------------------------------------------
     */
    public function initializeAdmin()
    {		
		$this->enable([
            'onAdminMenu' => ['onAdminMenu', 0],
			'onAdminRegisterPermissions' => ['onAdminRegisterPermissions', 1000],
            'onAssetsInitialized' => ['initializeAssets', 0],
			'onAdminCreatePageFrontmatter' => ['onAdminCreatePageFrontmatter', 0],
			'onDataTypeExcludeFromDataManagerPluginHook' => ['onDataTypeExcludeFromDataManagerPluginHook', 0],
            'onAdminSave' => ['onAdminSave', 0],
		]);
    }
    
    /**		
     * Add navigation item to the admin plugin		
     */		
    public function onAdminMenu()		
    {		
		$this->onAdminRegisterPermissions();		
    }
    
    /**
    * Initialiaze required assets
    * Group Manager Fix
    * @param \Grav\Common\Assets $assets
    * @return void
    */
    public function initializeAssets() {
        $page = $this->grav['uri']->paths();
        if (count($page) == 3) {
            $page = $page[1];
            if($page === "group-manager" || $page === "user") {
                //dump("YES");
                $this->grav['assets']->addJs('plugin://users-page-ownership/js/grouppageusefixer.js');
            }
        }
    }
	
	/**
     * Set Page Creator
     */
    public function onAdminCreatePageFrontmatter(Event $event)
    {
        $header = $event['header'];
		
        if (!isset($header['creator'])) {
            $header['creator'] = $this->grav['user']['username'];
            $event['header'] = $header;
        }
    }
    
    public function onAdminSave($event)
    {
        $obj = $event['object'];
        
        if ($obj instanceof Page) {
            $obj->header()->creator = $this->grav['user']['username'];
        }
        
        $event['object'] = $obj;
    }
	
	
	/**
     * Add Special plugin permissions
     */	
	public function onAdminRegisterPermissions()
    {
        $admin = $this->grav['admin'];
        $permissions = [
            'users.limitPagesToOwner' => 'boolean',
            'users.showPagesWithNoOwner' => 'boolean',
            'users.canDeletePages' => 'boolean',
            'users.canPostToRoot' => 'boolean'
        ];
        $admin->addPermissions($permissions);
    }
	
	
	
	/**
     * Client side initialization -----------------------------------------------------
     */
	public function initializeClient()
    {
		$this->enable([
			'onPagesInitialized' => ['constructUsersPage', 0],
            'onCollectionProcessed' => ['onCollectionProcessed', 10],
		]);
	}

    private function checkAuthorPage(){
        /** @var Uri $uri */
        $uri = $this->grav['uri'];
        $query = $uri->param('query') ?: $uri->query('query');
        $route = '/authors';

        // performance check for query
        if (empty($query)) {
            return false;
        }

        // performance check for route
        if (!($route && $route == $uri->path())) {
            return false;
        }

		// Explode query into multiple strings. Drop empty values
        $this->query = array_filter(array_filter(explode(',', $query), 'trim'), 'strlen');
        return true;
    }

	public function constructUsersPage(){
        if(!$this->checkAuthorPage()){
            return;
        }
        
        $locator = Grav::instance()['locator'];
        $username = trim($this->grav['uri']->param('query'));
        $file_path = $locator->findResource('account://' . $username . YAML_EXT);
        
        if(!$file_path){
            return;
        }
		
		// create the page
		$page = new Page;
		$page->init(new \SplFileInfo(__DIR__ . '/pages/user.md'));

		// override the template is set in the config
		$template_override = 'userpage';
		if ($template_override) {
			$page->template($template_override);
		}
        $page->route('/authors');

		// fix RuntimeException: Cannot override frozen service "page" issue
		unset($this->grav['page']);

		$this->grav['page'] = $page;
	}
    
    public function onCollectionProcessed(Event $event)
    {
        
        if(!$this->checkAuthorPage()){
            return;
        }
        
        $collection = $event['collection'];
        $username = trim($this->grav['uri']->param('query'));
        
        foreach ($collection as $cpage) {
            
            /*Integrity check*/
            if(!$cpage){
                dump("An error has occured. Please contact site admin. No collection page found.");
                continue;
            }
            
            if(!$cpage->header()){
                dump("An error has occured. Please contact site admin. No page header found.");
                $collection->remove($cpage);
                continue;
            }
            
            if(!property_exists($cpage->header(), 'creator')){
                $collection->remove($cpage);
                continue;
            }
                
            if($cpage->header()->creator != $username){
                $collection->remove($cpage);
                continue;
            }
            
            if($cpage->template() != "item"){
                $collection->remove($cpage);
                continue;
            }
            
        }
        
        $event['collection'] = $collection;
        
    }
	
	
	
	/**
     * Global side initialization ---------------------------------------------------------
     */
	public function initializeGlobal()
    {
		$this->enable([
			'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
			'onTwigSiteVariables'   => ['onTwigSiteVariables', 0],
			/*'onGetPageTemplates' => ['onGetPageTemplates', -1]*/
		]);
		
	}
	
	/**
     * Add plugin templates path and css
     */	
	public function onTwigTemplatePaths()
    {
		if ($this->isAdmin()) {
            array_unshift($this->grav['twig']->twig_paths, __DIR__ . '/templates');
        } else {
			$this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
		}
    }
	public function onTwigSiteVariables()
    {
        $twig = $this->grav['twig'];
		$twig->twig_vars['ownerUtils'] = new TwigUsersOwnership();
        
        if ($this->query) {
            $twig->twig_vars['query'] = implode(', ', $this->query);
        }
        
        /*Group Manager Fix*/
        $page = $this->grav['uri']->paths();
        if (count($page) == 3) {
            $page = $page[1];
            if($page === "group-manager") {
                $twig->twig_vars['group']['pageusefake'] = $twig->twig_vars['group']['pageuse'];
            }
            if($page === "user") {
                $route = 'users/' . $twig->twig_vars['admin']->route;
                $twig->twig_vars['admin']->data($route)['pageusefake'] = $twig->twig_vars['admin']->data($route)['pageuse'];
                if($twig->twig_vars['admin']->data($route)['pageuse'] === "nopagetypes" || !isset($twig->twig_vars['admin']->data($route)['pageuse'])){
                    unset($twig->twig_vars['admin']->data($route)['pageusefake']);
                }
            }
        }
        
    }

    /**
	 * Get Blueprints and templates for rendering pages
     */
	/*public function onGetPageTemplates($event)
	{
	  	$types = $event->types;
	  	$locator = Grav::instance()['locator'];
	  	$types->scanBlueprints($locator->findResource('plugin://' . $this->name . '/blueprints/pages'));
	}*/
    
    
}



class TwigUsersOwnership{
    
    
    public static function userPageTypes($user) {
        $groups = $user->get('groups');
        $pagesArray = [];
        
        if ($groups) {
            foreach ((array)$groups as $group) {
                $pageuse = Grav::instance()['config']->get("groups.{$group}.pageuse");
                if (isset($pageuse)){
                    foreach(explode(",", $pageuse) as $type){
                        if ($type == "nopagetypes") {continue;}
                        if (!in_array($type, $pagesArray)){
                            $pagesArray[] = $type;
                        }
                    }
                }
            }
        }
        if ($user->get('pageuse')){
            foreach(explode(",", $user->get('pageuse')) as $type){
                if ($type == "nopagetypes") {continue;}
                if (!in_array($type, $pagesArray)){
                    $pagesArray[] = $type;
                }
            }
        }
        
        return $pagesArray;
    }
    
    
    public static function pageTypes(){
        
        $grav = Grav::instance();
        $types = Pages::types();
        // First filter by configuration
        $hideTypes = $grav['config']->get('plugins.admin.hide_page_types', []);
        foreach ($hideTypes as $type) {
            unset($types[$type]);
        }
        
        if ( !$grav['user']->authorize('admin.super') ) {
            $showTypes = self::userPageTypes($grav['user']);

            if(!empty($showTypes)){
                foreach($types as $key => $value){
                    if(!in_array($key, $showTypes)){
                        unset($types[$key]);
                    }
                }
            }
        }
        
        // Allow manipulating of the data by event
        $e = new Event(['types' => &$types]);
        Grav::instance()->fireEvent('onAdminPageTypes', $e);
        return $types;
        
    }
    
    
    /**
     * Get available parents raw routes.
     *
     * @return array
     */
    public static function parentsRawRoutes($start_page = null, $show_all = true, $show_fullpath = false, $show_slug = false, $show_modular = false, $limit_levels = false)
    {
        return self::getParents($start_page, $show_all, $show_fullpath, $show_slug, $show_modular, $limit_levels);
    }
    
    /**
     * Get available parents routes
     * @return array
     */
    
    private static function getParents($start_page = null, $show_all = true, $show_fullpath = false, $show_slug = false, $show_modular = false, $limit_levels = false)
    {
        $grav = Grav::instance();
        $pages = $grav['pages'];
        $parents = $pages->getList($start_page, 0, true, $show_all, $show_fullpath, $show_slug, $show_modular, $limit_levels);
        
        foreach ( $parents as $key=>$pageRoute ) {
            $page = $pages->find($key);
            if( !self::showPage($page) ){
                unset($parents[$key]);
            }
        }
        
        return $parents;
    }
    
    public static function showPage($page, $overrideHide = false)
    {
        $grav = Grav::instance();
        $showPage = true;
        $checkChildren = true;

        if ( !empty($page->header()->creator) ) {
            if ( $grav['user']->authorize('users.limitPagesToOwner') && $page->header()->creator != $grav['user']->username ) {
                $showPage = false;
            }
        } else {
            if ( !$grav['user']->authorize('users.showPagesWithNoOwner') ) {
                $showPage = false;
            }
        }

        if ( !empty($page->header()->visibleToGroups) && !empty($grav['user']->groups) ) {
            if ( !empty(array_intersect($page->header()->visibleToGroups, $grav['user']->groups)) ) {
                $showPage = true;
            }
        }

        if ( !empty($page->header()->shareWith) ) {
            if ( in_array($grav['user']->username, $page->header()->shareWith) ) {
                $showPage = true;
            }
        }
        
        if ( $grav['user']->authorize('admin.super') ) {
            $showPage = true;
        }
        
        if ( !empty($page->header()->hideFromParentSelection) ){
            if( $page->header()->hideFromParentSelection['self'] ){
                $showPage = false;
                $checkChildren = false;
            }
        }
        
        $ancestors = [];
        $parent = $page->parent();
        if (!empty($parent)) {
            while (true) {
                if($parent !== null && $parent->parent() !== null){
                    $ancestors[] = $parent;
                    $parent = $parent->parent();
                } else {
                    break;
                }
            }
        }
        
        foreach($ancestors as $ancestor){
            if ( !empty($ancestor->header()->hideFromParentSelection) ){
                if( $ancestor->header()->hideFromParentSelection['children'] ){
                    $showPage = false;
                    $checkChildren = false;
                    break;
                }
            }
        }
        
        
        if(!$showPage and $checkChildren){
            if(sizeof($page->children()) > 0){
                foreach($page->children() as $child){
                    if(self::showPage($child)){
                        $showPage = true;
                        break;
                    }
                }
            }
        }
        return $showPage;
    }
    
	
	/**
     * Load user account.
     *
	 * This function was taken from Grav\Common\User\User and modified to grab the
	 * custom user blueprint that extends from the default user/account blueprint.
	 *
     * Always creates user object. To check if user exists, use $this->exists().
     *
     * @param string $username
     *
     * @return User
     */
    public static function load($username)
    {
        $grav = Grav::instance();
        $locator = $grav['locator'];
        $config = $grav['config'];

        // force lowercase of username
        $username = strtolower($username);
		
        $blueprints = new Blueprints($locator->findResource('plugin://users-page-ownership/blueprints/user'));
        $blueprint = $blueprints->get('account');
        $file_path = $locator->findResource('account://' . $username . YAML_EXT);
        $file = CompiledYamlFile::instance($file_path);
        $content = $file->content();
        if (!isset($content['username'])) {
            $content['username'] = $username;
        }
        if (!isset($content['state'])) {
            $content['state'] = 'enabled';
        }
        $user = new User($content, $blueprint);
        $user->file($file);

        // add user to config
        $config->set("user", $user);
		
        return $user;
	}
	
	public function getUser($username){
		return $this->load($username);
	}
	
	public static function getAll(){
		$account_dir = Grav::instance()['locator']->findResource('account://');
        $files       = array_diff(scandir($account_dir), ['.', '..']);
		$users		 = [];

        foreach ($files as $file) {
            if (strpos($file, '.yaml') !== false) {
                $users[] = User::load(trim(substr($file, 0, -5)));
            }
        }
		
		return $users;
	}

    public static function userNames() {
        $users = self::getAll();

        $userNames = [];
        foreach ($users as $u) {
            $userNames[$u['username']] = $u['username'];
        }

        return $userNames;
    }
	
}