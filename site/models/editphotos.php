<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\common;
use CustomTables\CT;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\Fields;

use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CustomTablesModelEditPhotos extends BaseDatabaseModel
{
	var CT $ct;
	var $imagemethods;
	var $listing_id;
	var $Listing_Title;
	var $galleryname;
	var $GalleryTitle;
	var $imagefolderword;
	var $imagefolder;
	var $imagefolderweb;
	var $imagemainprefix;
	var $maxfilesize;
	var $useridfield;
	var $phototablename;
	var $row;
	var Field $field;

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function load(): bool
	{
		$this->ct = new CT;

		$app = Factory::getApplication();
		$params = $app->getParams();

		$this->maxfilesize = JoomlaBasicMisc::file_upload_max_size();

		$this->imagefolderword = 'esimages';
		$this->imagefolderweb = 'images/esimages';
		$this->imagefolder = JPATH_SITE . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'esimages';

		$this->imagemainprefix = 'g';
		$this->imagemethods = new CustomTablesImageMethods;

		$this->useridfield = $params->get('useridfield');

		$this->ct->getTable($params->get('establename'), $this->useridfield);

		if ($this->ct->Table->tablename === null) {
			Factory::getApplication()->enqueueMessage('Table not selected (62).', 'error');
			return false;
		}

		$this->listing_id = common::inputGetInt("listing_id", 0);
		if (!common::inputGetCmd('galleryname'))
			return false;

		$this->galleryname = common::inputGetCmd('galleryname');

		$this->getObject();

		if (!$this->getGallery())
			return false;

		$this->phototablename = database::getDBPrefix() . 'customtables_gallery_' . $this->ct->Table->tablename . '_' . $this->galleryname;
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function getObject(): bool
	{
		$this->row = $this->ct->Table->loadRecord($this->listing_id);
		if ($this->row === null)
			return false;

		$this->Listing_Title = '';

		//Get first field value as a Gallery Title
		foreach ($this->ct->Table->fields as $mFld) {
			$titleField = $mFld['realfieldname'];
			if (str_contains($mFld['type'], 'multi'))
				$titleField .= $this->ct->Languages->Postfix;

			if ($this->row[$titleField] != '') {
				$this->Listing_Title = $this->row[$titleField];
				break;
			}
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function getGallery(): bool
	{
		$fieldrow = Fields::getFieldRowByName($this->galleryname, $this->ct->Table->tableid);
		if ($fieldrow === null)
			return false;

		$this->field = new Field($this->ct, $fieldrow, $this->row);
		$this->GalleryTitle = $this->field->title;
		$this->imagefolderword = CustomTablesImageMethods::getImageFolder($this->field->params);
		$this->imagefolderweb = $this->imagefolderword;

		$this->imagefolder = JPATH_SITE;
		if ($this->imagefolder[strlen($this->imagefolder) - 1] != '/' and $this->imagefolderword[0] != '/')
			$this->imagefolder .= '/';

		$this->imagefolder .= str_replace('/', DIRECTORY_SEPARATOR, $this->imagefolderword);
		//Create folder if not exists
		if (!file_exists($this->imagefolder)) {
			Factory::getApplication()->enqueueMessage('Path ' . $this->imagefolder . ' not found.', 'error');
			mkdir($this->imagefolder, 0755, true);
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function reorder(): bool
	{
		$images = $this->getPhotoList();

		//Set order

		//Apply Main Photo
		//Get New Ordering
		$MainPhoto = common::inputGetInt('esphotomain');

		foreach ($images as $image) {
			$image->ordering = abs(common::inputGetInt('esphotoorder' . $image->photoid, 0));
			if ($MainPhoto == $image->photoid)
				$image->ordering = -1;
		}

		//Increase all if main
		do {
			$noNegative = true;
			foreach ($images as $image) {
				if ($image->ordering == -1)
					$noNegative = false;
			}

			if (!$noNegative) {
				foreach ($images as $image)
					$image->ordering++;
			}

		} while (!$noNegative);

		asort($images);
		$i = 0;
		foreach ($images as $image) {
			$safeTitle = common::inputPostString('esphototitle' . $image->photoid);
			$safeTitle = str_replace('"', "", $safeTitle);

			$data = [
				'ordering' => $i,
				'title' . $this->ct->Languages->Postfix => $safeTitle
			];
			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('listingid', $this->listing_id);
			$whereClauseUpdate->addCondition('photoid', $image->photoid);
			database::update($this->phototablename, $data, $whereClauseUpdate);

			//$query = 'UPDATE ' . $this->phototablename . ' SET ordering=' . $i . ', title' . $this->ct->Languages->Postfix . '="' . $safeTitle . '" WHERE listingid='
			//	. $this->listing_id . ' AND photoid=' . $image->photoid;
			$i++;
		}
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	function getPhotoList()
	{
		//$query = 'SELECT ordering, photoid,  title' . $this->ct->Languages->Postfix . ' AS title, photo_ext FROM ' . $this->phototablename
		//. ' WHERE listingid=' . $this->listing_id . ' ORDER BY ordering, photoid';

		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', $this->listing_id);

		return database::loadObjectList($this->phototablename, ['ordering', 'photoid', 'title' . $this->ct->Languages->Postfix . ' AS title', 'photo_ext'],
			$whereClause, 'ordering, photoid');
	}

	function delete(): bool
	{
		$photoIDs = common::inputPostString('photoids', '');
		$photo_arr = explode('*', $photoIDs);

		foreach ($photo_arr as $photoId) {
			if ($photoId != '') {
				$this->imagemethods->DeleteExistingGalleryImage($this->imagefolder, $this->imagemainprefix, $this->ct->Table->tableid, $this->galleryname,
					$photoId, $this->field->params[0] ?? '', true);

				database::deleteRecord($this->phototablename, 'photoid', $photoId);
				//$query = 'DELETEFROM ' . $this->phototablename . ' WHERE listingid=' . $this->listing_id . ' AND photoid=' . $photoId;
			}
		}

		$this->ct->Table->saveLog($this->listing_id, 7);
		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function add(): bool
	{
		$file = common::inputFiles('uploadedfile');

		$uploadedfile = "tmp/" . basename($file['name']);
		if (!move_uploaded_file($file['tmp_name'], $uploadedfile))
			return false;

		if (common::inputGetCmd('base64ecnoded', '') == "true") {
			$src = $uploadedfile;
			$dst = "tmp/decoded_" . basename($file['name']);
			common::base64file_decode($src, $dst);
			$uploadedfile = $dst;
		}

		//Check file
		if (!$this->imagemethods->CheckImage($uploadedfile, JoomlaBasicMisc::file_upload_max_size()))//$this->maxfilesize
		{
			Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_ERROR_BROKEN_IMAGE'), 'error');
			unlink($uploadedfile);
			return false;
		}

		//Save to DB
		$photo_ext = $this->imagemethods->FileExtension($uploadedfile);
		$filenameParts = explode('/', $uploadedfile);
		$filename = end($filenameParts);
		$title = str_replace('.' . $photo_ext, '', $filename);

		$photoId = $this->addPhotoRecord($photo_ext, $title);

		$isOk = true;

		//es Thumb
		$newFileName = $this->imagefolder . DIRECTORY_SEPARATOR . $this->imagemainprefix . $this->ct->Table->tableid . '_' . $this->galleryname . '__esthumb_' . $photoId . ".jpg";
		$r = $this->imagemethods->ProportionalResize($uploadedfile, $newFileName, 150, 150, 1, -1, '');

		if ($r != 1)
			$isOk = false;

		$customSizes = $this->imagemethods->getCustomImageOptions($this->field->params[0] ?? '');

		foreach ($customSizes as $imagesize) {
			$prefix = $imagesize[0];
			$width = (int)$imagesize[1];
			$height = (int)$imagesize[2];
			$color = (int)$imagesize[3];

			//save as an extension
			if ($imagesize[4] != '')
				$ext = $imagesize[4];
			else
				$ext = $photo_ext;

			$newFileName = $this->imagefolder . DIRECTORY_SEPARATOR . $this->imagemainprefix . $this->ct->Table->tableid . '_' . $this->galleryname . '_' . $prefix . '_' . $photoId . "." . $ext;
			$r = $this->imagemethods->ProportionalResize($uploadedfile, $newFileName, $width, $height, 1, $color, '');

			if ($r != 1)
				$isOk = false;
		}

		if ($isOk) {
			$originalName = $this->imagemainprefix . $this->ct->Table->tableid . '_' . $this->galleryname . '__original_' . $photoId . "." . $photo_ext;

			if (!copy($uploadedfile, $this->imagefolder . DIRECTORY_SEPARATOR . $originalName)) {
				unlink($uploadedfile);
				return false;
			}
		} else {
			unlink($uploadedfile);
			return false;
		}

		unlink($uploadedfile);
		$this->ct->Table->saveLog($this->listing_id, 6);

		return true;
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	protected function addPhotoRecord(string $photo_ext, string $title): int
	{
		$data = [
			'ordering' => 100,
			'photo_ext' => $photo_ext,
			'listingid' => $this->listing_id,
			'title' => $title
		];

		try {
			database::insert($this->phototablename, $data);
		} catch (Exception $e) {
			echo 'Caught exception: ', $e->getMessage(), "\n";
			die;
		}

		$this->AutoReorderPhotos();
		$whereClause = new MySQLWhereClause();
		$whereClause->addCondition('listingid', $this->listing_id);
		$rows = database::loadObjectList($this->phototablename, ['photoid'], $whereClause, 'photoid', 'DESC', 1);

		if (count($rows) == 1)
			return $rows[0]->photoid;

		return -1;
	}

	/**
	 * @throws Exception
	 * @since 3.2.3
	 */
	function AutoReorderPhotos(): bool
	{
		$images = $this->getPhotoList();
		asort($images);
		$i = 0;
		foreach ($images as $image) {
			$safeTitle = common::inputPostString('esphototitle' . $image->photoid);
			$safeTitle = str_replace('"', "", $safeTitle);


			$whereClauseUpdate = new MySQLWhereClause();
			$whereClauseUpdate->addCondition('listingid', $this->listing_id);
			$whereClauseUpdate->addCondition('photoid', $image->photoid);

			if ($safeTitle != '') {

				$data = [
					'ordering' => $i,
					'title' . $this->ct->Languages->Postfix => $safeTitle
				];
			} else {
				$data = ['ordering' => $i];
			}
			database::update($this->phototablename, $data, $whereClauseUpdate);
			$i++;
		}
		return true;
	}

	function DoAutoResize($uploadedFile, $folder_resized, $image_width, $image_height, $photoid, $fileext): bool
	{
		if (!file_exists($uploadedFile))
			return false;

		$newFileName = $folder_resized . $photoid . '.' . $fileext;

		//hexdec ("#323131")
		$r = $this->imagemethods->ProportionalResize($uploadedFile, $newFileName, $image_width, $image_height, 1, -1, '');
		if ($r != 1)
			return false;

		return true;
	}
}
