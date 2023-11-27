<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\DataTypes\Tree;

jimport('joomla.application.component.model');

class CustomTablesModelListEdit extends JModel
{
	var string $imagefolder = "images/esoptimages";

	function __construct()
	{
		parent::__construct();
		$array = common::inputGet('cid', array(), 'array');
		$this->setId((int)$array[0]);
	}

	function setId($tree_id)
	{
		// Set id and wipe data

		$this->_id = $tree_id;
		$this->_data = null;
	}

	function getData()
	{
		$row = $this->getTable();
		$row->load($this->_id);
		return $row;
	}

	function store()
	{
		$optionname = strtolower(trim(preg_replace("/[^a-zA-Z\d]/", "", common::inputGet('optionname', '', 'STRING'))));
		$title = ucwords(strtolower(trim(common::inputGet('title', '', 'STRING'))));

		common::inputSet('optionname', $optionname);
		common::inputSet('title', $title);

		//save image if needed
		$fieldname = 'imagefile';
		$value = 0;
		$imagemethods = new CustomTablesImageMethods;
		$tree_id = common::inputGet('id', 0, 'INT');
		$imagefolder = JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'esoptimages';

		$imageparams = '';


		if ($tree_id == 0)
			$file = common::inputGet($fieldname, '', 'files', 'array');

		$filename = $file['name'];
		if ($filename != '') {
			$imageparams = common::inputGet('imageparams', '', 'string');

			if (strlen($imageparams) == 0)
				$imageparams = Tree::getHeritageInfo(common::inputGet('parentid', 0, 'INT'), 'imageparams');

			$value = $imagemethods->UploadSingleImage('', $fieldname, $imagefolder, $imageparams, '-options');
		} else {

			$ExistingImage = Tree::isRecordExist($tree_id, 'id', 'image', '#__customtables_options');
			$file = common::inputGet($fieldname, '', 'files', 'array');

			$filename = $file['name'];
			if ($filename == '') {
				if (common::inputGetCmd('image_delete') == 'true') {
					if ($ExistingImage !== null)
						$imagemethods->DeleteExistingSingleImage($ExistingImage, $imagefolder, $imageparams, '-options', $fieldname, 'id');
				}
			} else {
				$imageparams = common::inputGetString('imageparams');
				if (strlen($imageparams) == 0)
					$imageparams = Tree::getHeritageInfo(common::inputGet('parentid', 0, 'INT'), 'imageparams');

				$value = $imagemethods->UploadSingleImage($ExistingImage, $fieldname, $imagefolder, $imageparams, '-options' . 'id');
			}
		}
		if ($value != 0)
			common::inputSet('image', $value);

		$row = $this->getTable();
		// consume the post data with allow_html
		$data = common::inputGet('jform', array(), 'ARRAY');

		if (!$row->bind($data))
			return false;

		// Make sure the  record is valid
		if (!$row->check())
			return false;

		// Store
		if (!$row->store())
			return false;

		$tree_id = $row->id;
		//set FamilyTree
		$row = $this->getTable();
		// Make sure the  record is valid
		$row->load($tree_id);

		// Store
		$row->familytree = '-' . Tree::getFamilyTree($tree_id, 0) . '-';
		$familyTreeStr = Tree::getFamilyTreeString($tree_id, 0);
		if ($familyTreeStr != '')
			$row->familytreestr = ',' . $familyTreeStr . '.' . $row->optionname . '.';
		else
			$row->familytreestr = ',' . $row->optionname . '.';

		if (!$row->store())
			return false;

		return true;
	}

	function delete()
	{
		$cids = common::inputPost('cid', array(), 'array');
		$row = $this->getTable();

		if (count($cids)) {
			foreach ($cids as $cid) {
				if (!$row->delete($cid)) {
					return false;
				}
			}
		}
		return true;
	}
}
