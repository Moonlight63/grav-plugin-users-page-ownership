<?php
namespace Grav\Plugin;

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
use Grav\Common\User\Group;
use Grav\Common\Utils;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Yaml\Yaml;

class AdvancedAdminUsersManagmentPlugin extends Plugin
{
    protected $route = 'users';
    //protected $groute = 'groups';
    protected $enable = false;
    protected $query;
    
    public $features = [
        'blueprints' => 1000,
    ];
    protected $version;

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
		
		$uri = $this->grav['uri'];

        if (strpos($uri->path(), $this->config->get('plugins.admin.route') . '/' . $this->route) === false/* && strpos($uri->path(), $this->config->get('plugins.admin.route') . '/' . $this->groute) === false*/) {
            //return;
        }
        
        // Store this version and prefer newer method
        if (method_exists($this, 'getBlueprint')) {
            $this->version = $this->getBlueprint()->version;
        } else {
            $this->version = $this->grav['plugins']->get('admin')->blueprints()->version;
        }
		
		$this->enable([
			'onAdminMenu' => ['onAdminMenu', 0],
			'onAdminRegisterPermissions' => ['onAdminRegisterPermissions', 1000],
			'onAdminCreatePageFrontmatter' => ['onAdminCreatePageFrontmatter', 0],
			'onDataTypeExcludeFromDataManagerPluginHook' => ['onDataTypeExcludeFromDataManagerPluginHook', 0],
            'onAdminSave' => ['onAdminSave', 0]
		]);
		
    }
	
	/**
     * Add navigation item to the admin plugin
     */
    public function onAdminMenu()
    {
        $this->grav['twig']->plugins_hooked_nav['PLUGIN_USERS.USERS'] = ['route' => $this->route, 'icon' => 'fa-user'];
        //$this->grav['twig']->plugins_hooked_nav['PLUGIN_USERS.GROUPS'] = ['route' => $this->groute, 'icon' => 'fa-user'];
		$this->onAdminRegisterPermissions();
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
     * Exclude users from the Data Manager plugin
     */
    public function onDataTypeExcludeFromDataManagerPluginHook()
    {
        //$this->grav['admin']->dataTypesExcludedFromDataManagerPlugin[] = 'users';
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
			'onPagesInitialized' => ['constuctUsersPage', 0],
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
	
	public function constuctUsersPage(){
        if(!$this->checkAuthorPage()){
            return;
        }
		
		// create the search page
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
        $params = $collection->params();
        
        $username = $this->grav['uri']->param('query');
        
        foreach ($collection as $cpage) {
            $username = trim($username);
            
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
			'onTwigExtensions' => ['onTwigExtensions', 0],
			'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
			'onTwigSiteVariables'   => ['onTwigSiteVariables', 0],
			'onGetPageTemplates' => ['onGetPageTemplates', -1]
		]);
		
	}
	
	/**
     * Add Twig Functions
     */	
	public function onTwigExtensions(){
		
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
		$twig->twig_vars['users'] = new TwigUsers();
        
        if ($this->query) {
            $twig->twig_vars['query'] = implode(', ', $this->query);
            
        }
        
        if($this->isAdmin()){
            $this->grav['assets']->addCss('plugin://advanced-admin-users-managment/css/users.css');
            $this->grav['assets']->addJs('plugin://advanced-admin-users-managment/js/dropzone.js', -1);
        }
            
    }

    /**
	 * Get Blueprints and templates for rendering pages
     */
	public function onGetPageTemplates($event)
	{
	  	$types = $event->types;
	  	$locator = Grav::instance()['locator'];
	  	$types->scanBlueprints($locator->findResource('plugin://' . $this->name . '/blueprints/pages'));
	}
    
    
}



class TwigUsers{
    
    /**
     * Get available parents raw routes.
     *
     * @return array
     */
    public static function parentsRawRoutes()
    {
        $rawRoutes = true;
        return self::getParents($rawRoutes);
    }
    
    /**
     * Get available parents routes
     *
     * @param bool $rawRoutes get the raw route or the normal route
     *
     * @return array
     */
    private static function getParents($rawRoutes)
    {
        $grav = Grav::instance();
        $pages = $grav['pages'];
        $parents = $pages->getList(null, 0, $rawRoutes);
        
        foreach ( $parents as $key=>$pageRoute ) {
            $page = $pages->find($key);
            if( !self::showPage($page) ){
                unset($parents[$key]);
            }
        }
        
        if ($grav['user']->authorize('users.canPostToRoot') || $grav['user']->authorize('admin.super')) {
            $parents = array('/' => 'PLUGIN_ADMIN.DEFAULT_OPTION_ROOT') + $parents;
        }
        
        return $parents;
    }
    
    
    public static function showPage($page)
    {
        $grav = Grav::instance();
        $showPage = true;

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
        
        if ( $grav['user']->authorize('admin.super') ) {
            $showPage = true;
        }
        
        if(!$showPage){
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
		
        $blueprints = new Blueprints($locator->findResource('plugin://advanced-admin-users-managment/blueprints/user'));
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
	
	public function getAll(){
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
    
    public static function getGroup($groupName){
        $obj = Group::load($groupName);
        return($obj);
    }
    
    public static function getGroups(){
        $groups = Grav::instance()['config']->get('groups');
        return ($groups);
    }
	
}