<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Vast Development Method 
/-------------------------------------------------------------------------------------------------------/

	@version		1.2.9
	@build			30th November, 2015
	@created		22nd October, 2015
	@package		Sermon Distributor
	@subpackage		preachers.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
  ____  _____  _____  __  __  __      __       ___  _____  __  __  ____  _____  _  _  ____  _  _  ____ 
 (_  _)(  _  )(  _  )(  \/  )(  )    /__\     / __)(  _  )(  \/  )(  _ \(  _  )( \( )( ___)( \( )(_  _)
.-_)(   )(_)(  )(_)(  )    (  )(__  /(__)\   ( (__  )(_)(  )    (  )___/ )(_)(  )  (  )__)  )  (   )(  
\____) (_____)(_____)(_/\/\_)(____)(__)(__)   \___)(_____)(_/\/\_)(__)  (_____)(_)\_)(____)(_)\_) (__) 

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Sermondistributor Model for Preachers
 */
class SermondistributorModelPreachers extends JModelList
{
	/**
	 * Model user data.
	 *
	 * @var        strings
	 */
	protected $user;
	protected $userId;
	protected $guest;
	protected $groups;
	protected $levels;
	protected $app;
	protected $input;
	protected $uikitComp;

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return      string  An SQL query
	 */
	protected function getListQuery()
	{
		// Get the current user for authorisation checks
		$this->user		= JFactory::getUser();
		$this->userId		= $this->user->get('id');
		$this->guest		= $this->user->get('guest');
                $this->groups		= $this->user->get('groups');
                $this->authorisedGroups	= $this->user->getAuthorisedGroups();
		$this->levels		= $this->user->getAuthorisedViewLevels();
		$this->app		= JFactory::getApplication();
		$this->input		= $this->app->input;
		$this->initSet		= true; 
		// [2897] Get a db connection.
		$db = JFactory::getDbo();

		// [2906] Create a new query object.
		$query = $db->getQuery(true);

		// [1788] Get from #__sermondistributor_preacher as a
		$query->select($db->quoteName(
			array('a.id','a.asset_id','a.name','a.alias','a.icon','a.email','a.website','a.description','a.hits','a.ordering'),
			array('id','asset_id','name','alias','icon','email','website','description','hits','ordering')));
		$query->from($db->quoteName('#__sermondistributor_preacher', 'a'));
		$query->where('a.access IN (' . implode(',', $this->levels) . ')');
		$query->where('a.published = 1');
		$query->order('a.ordering ASC');

		// [2919] return the query object
		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$user = JFactory::getUser();
                // check if this user has permission to access items
                if (!$user->authorise('site.preachers.access', 'com_sermondistributor'))
                {
			JError::raiseWarning(500, JText::_('Not authorised!'));
			// redirect away if not a correct (TODO for now we go to default view)
			JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_sermondistributor&view=preachers'));
			return false;
                } 
		// load parent items
		$items = parent::getItems();

		// Get the global params
		$globalParams = JComponentHelper::getParams('com_sermondistributor', true);

		// [2934] Convert the parameter fields into objects.
		foreach ($items as $nr => &$item)
		{
			// [2937] Always create a slug for sef URL's
			$item->slug = (isset($item->alias)) ? $item->id.':'.$item->alias : $item->id;
			// [2005] Make sure the content prepare plugins fire on description.
			$item->description = JHtml::_('content.prepare',$item->description);
			// [2007] Checking if description has uikit components that must be loaded.
			$this->uikitComp = SermondistributorHelper::getUikitComp($item->description,$this->uikitComp);
			// [2038] set idPreacherSermonB to the $item object.
			$item->idPreacherSermonB = $this->getIdPreacherSermonDcaa_B($item->id);
		} 

		// return items
		return $items;
	} 

	/**
	* Method to get an array of Sermon Objects.
	*
	* @return mixed  An array of Sermon Objects on success, false on failure.
	*
	*/
	public function getIdPreacherSermonDcaa_B($id)
	{
		// [2699] Get a db connection.
		$db = JFactory::getDbo();

		// [2701] Create a new query object.
		$query = $db->getQuery(true);

		// [2703] Get from #__sermondistributor_sermon as b
		$query->select($db->quoteName(
			array('b.id'),
			array('id')));
		$query->from($db->quoteName('#__sermondistributor_sermon', 'b'));
		$query->where('b.preacher = ' . $db->quote($id));
		$query->where('b.access IN (' . implode(',', $this->levels) . ')');
		$query->where('b.published = 1');

		// [2757] Reset the query using our newly populated query object.
		$db->setQuery($query);
		$db->execute();

		// [2760] check if there was data returned
		if ($db->getNumRows())
		{
			return $db->loadObjectList();
		}
		return false;
	}


	/**
	* Get the uikit needed components
	*
	* @return mixed  An array of objects on success.
	*
	*/
	public function getUikitComp()
	{
		if (isset($this->uikitComp) && SermondistributorHelper::checkArray($this->uikitComp))
		{
			return $this->uikitComp;
		}
		return false;
	}  
}