<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use CustomTables\CTUser;
use \Joomla\CMS\Factory;
use \JoomlaBasicMisc;
use \Joomla\CMS\Uri\Uri;

class RecordToolbar
{
	var $ct;
	var $Table;
	
	var $isEditable;
	var $isPublishable;
	var $isDeletable;
	
	var $jinput;
	var $Itemid;
	
	var $id;
	var $rid;
	
	var $row;
	
	var $iconPath;
	
	function __construct(&$ct, $isEditable, $isPublishable, $isDeletable , $Itemid)
	{
		$this->ct = $ct;
		$this->Table = $ct->Table;
		
		$this->isEditable = $isEditable;
		$this->isPublishable = $isPublishable;
		$this->isDeletable = $isDeletable;
		
		$this->jinput = Factory::getApplication()->input;
		
		$this->Itemid = $Itemid;
		
		$this->iconPath = Uri::root(true).'/components/com_customtables/libraries/customtables/html/images/';
	}
	
	public function render($row,$mode)
	{
		$this->id=$row['listing_id'];
		$this->rid=$this->Table->tableid.'x'.$this->id;
		$this->row=$row;
		
		if($this->isEditable)
		{
			switch($mode)
			{
				case 'edit':
					return $this->renderEditIcon();
					
				case 'refresh':
					$rid='esRefreshIcon'.$this->rid;
					$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_REFRESH' );
					$img='<img src="'.$this->iconPath.'refresh.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
					return '<div id="'.$rid.'" class="toolbarIcons"><a href="javascript:esRefreshObject('.$this->id.', \''.$rid.'\');">'.$img.'</a></div>';
					
				case 'gallery':
					if(is_array($this->Table->imagegalleries) and count($this->Table->imagegalleries)>0)
						return $this->renderImageGalleryIcon();
					else
						return '';
						
				case 'filebox':
					if(is_array($this->Table->fileboxes) and count($this->Table->fileboxes)>0)
						return $this->renderFileBoxIcon();
					else
						return '';
						
				case 'copy':
					return $this->renderCopyIcon();
					
				case 'resetpassword':
					return $this->renderResetPasswordIcon();
			}
		}

		if($this->isDeletable and $mode == 'delete')
			return $this->renderDeleteIcon();
		elseif($mode == 'publish')
			return $this->renderPublishIcon();
		elseif($mode == 'checkbox')
			return '<input type="checkbox" name="esCheckbox'.$this->Table->tableid.'" id="esCheckbox'.$this->rid.'" value="'.$this->id.'" />';
	
		return '';
	}

	protected function renderPublishIcon()
	{
		if($this->isPublishable)
		{
			$rid = 'esPublishIcon'.$this->rid;
			
			if($this->row['listing_published'])
			{
				$link='javascript:esPublishObject('.$this->id.', \''.$rid.'\',0);';
                $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_UNPUBLISH' );
				$img='<img src="'.$this->iconPath.'publish.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
			}
			else
			{
				$link='javascript:esPublishObject('.$this->id.', \''.$rid.'\',1);';
                $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISH' );
				$img='<img src="'.$this->iconPath.'unpublish.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
			}
			return '<div id="'.$rid.'" class="toolbarIcons"><a href="'.$link.'">'.$img.'</a></div>';
		}
		else
		{
			if(!$this->row['listing_published'])
				return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PUBLISHED');
		}
		return '';
	}

    protected function renderEditIcon()
	{
		$editlink=$this->ct->Env->WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'
						.'&amp;returnto='.$this->ct->Env->encoded_current_url
						.'&amp;listing_id='.$this->id;

		if($this->jinput->get('tmpl','','CMD')!='')
			$editlink.='&tmpl='.$this->jinput->get('tmpl','','CMD');

		if($this->Itemid>0)
			$editlink.='&amp;Itemid='.$this->Itemid;

        $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT' );
		$img='<img src="'.$this->iconPath.'edit.png" border="0" alt="'.$alt.'" title="'.$alt.'">';

		$link=$editlink;

		return '<div id="esEditIcon'.$this->rid.'" class="toolbarIcons"><a href="'.$link.'">'.$img.'</a></div>';
	}

	protected function renderImageGalleryIcon()
	{
		$imagegalleries = [];
		foreach($thid->Table->imagegalleries as $gallery)
		{
			$imagemanagerlink='index.php?option=com_customtables&amp;view=editphotos'
				.'&amp;establename='.$this->Table->tablename
				.'&amp;galleryname='.$gallery[0]
				.'&amp;listing_id='.$this->id
				.'&amp;returnto='.$this->ct->Env->encoded_current_url;

			if($this->jinput->get('tmpl','','CMD')!='')
				$imagemanagerlink.='&tmpl='.$this->jinput->get('tmpl','','CMD');

			if($this->Itemid>0)
				$imagemanagerlink.='&amp;Itemid='.$this->Itemid;

            $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PHOTO_MANAGER' ).' ('.$gallery[1].')';
			$img='<img src="'.$this->iconPath.'photomanager.png" border="0" alt="'.$alt.'" title="'.$alt.'">';

			$imagegalleries[] = '<div id="esImageGalleryIcon'.$this->rid.'" class="toolbarIcons"><a href="'.$this->ct->Env->WebsiteRoot.$imagemanagerlink.'">'.$img.'</a></div>';

		}
		return implode('',$imagegalleries);
	}

	protected function renderFileBoxIcon()
	{
		$fileboxes = [];
		
		foreach($this->Table->fileboxes as $filebox)
		{
			$filemanagerlink='index.php?option=com_customtables&amp;view=editfiles'
				.'&amp;establename='.$this->Table->tablename
				.'&amp;fileboxname='.$filebox[0]
				.'&amp;listing_id='.$this->id
				.'&amp;returnto='.$this->ct->Env->encoded_current_url;

			if($this->jinput->get('tmpl','','CMD')!='')
				$filemanagerlink.='&tmpl='.$this->jinput->get('tmpl','','CMD');

			if($this->Itemid>0)
				$filemanagerlink.='&amp;Itemid='.$this->Itemid;

            $alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_MANAGER').' ('.$filebox[1].')';
			$img='<img src="'.$this->iconPath.'filemanager.png" border="0" '
							.'alt="'.$alt.'" '
							.'title="'.$alt.'">';

			$fileboxes[] = '<div id="esFileBoxIcon'.$this->rid.'" class="toolbarIcons"><a href="'.$this->ct->Env->WebsiteRoot.$filemanagerlink.'">'.$img.'</a></div>';
		}
		
		return implode('',$fileboxes);
	}

	protected function renderCopyIcon()
	{
		$Label = 'Would you like to copy ('.$this->firstFieldValueLabel().')?';
		
		$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_COPY' );
		$img='<img src="'.$this->iconPath.'copy.png" border="0" alt="'.$alt.'" title="'.$alt.'">';

		return '<div id="ctCopyIcon'.$this->rid.'" class="toolbarIcons"><a href=\'javascript:ctCopyObject("'.$Label.'", '.$this->id.', "ctCopyIcon'.$this->rid.'")\'>'.$img.'</a></div>';
	}
	
	protected function renderResetPasswordIcon()
	{
		$realuserid=$this->row[$this->Table->useridrealfieldname];
		
		if($realuserid==0)
		{
			$rid='ctCreateUserIcon'.$this->rid;
			$alt='Create User Account';
			$img='<img src="'.$this->iconPath.'key-add.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
			$resetLabel=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_USERWILLBECREATED' ).' '.$this->firstFieldValueLabel() ;
			$action='ctCreateUser("'.$resetLabel.'", '.$this->id.', "'.$rid.'")';
		}
		else
		{
			$userrow=CTUser::GetUserRow($realuserid);
			if($userrow!=null)
			{
				$user_full_name=ucwords(strtolower($userrow['name']));
		
				$rid='ctResetPasswordIcon'.$this->rid;
				$alt='Username: '.$userrow['username'];
				$img='<img src="'.$this->iconPath.'key.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
				$resetLabel='Would you like to reset '.$user_full_name.' ('.$userrow['username'].') password?';		
				$action='ctResetPassword("'.$resetLabel.'", '.$this->id.', "'.$rid.'")';
			}
			else
				return 'User account deleted, open and save the record.';
		}
		
		return '<div id="'.$rid.'" class="toolbarIcons"><a href=\'javascript:'.$action.' \'>'.$img.'</a></div>';
	}
	
	protected function renderDeleteIcon()
	{
		$deleteLabel = $this->firstFieldValueLabel();

		$alt=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DELETE' );
		$img='<img src="'.$this->iconPath.'delete.png" border="0" alt="'.$alt.'" title="'.$alt.'">';
		$msg='Do you want to delete ('.$deleteLabel.')?';

		return '<div id="esDeleteIcon'.$this->rid.'" class="toolbarIcons"><a href=\'javascript:esDeleteObject("'.$msg.'", '.$this->id.', "esDeleteIcon'.$this->rid.'")\'>'.$img.'</a></div>';
	}
	
	protected function firstFieldValueLabel()
	{
		$min_ordering = 99999999;
		
		$fieldtitlevalue='';
		
		foreach($this->Table->fields as $mFld)
		{
			$ordering=(int)$mFld['ordering'];
			if($mFld['type']!='dummy' and $ordering < $min_ordering)
			{
				$min_ordering = $ordering;
				$fieldtitlevalue=$this->getFieldCleanValue4RDI($mFld);
			}
		}

		return substr($fieldtitlevalue,-100);
	}
	
	protected function getFieldCleanValue4RDI(&$mFld)
	{
		$titlefield=$mFld['realfieldname'];
		if(strpos($mFld['type'],'multi')!==false)
			$titlefield.=$this->ct->Languages->Postfix;

		$fieldtitlevalue=$this->row[$titlefield];
		$deleteLabel=strip_tags($fieldtitlevalue);

		$deleteLabel=trim(preg_replace("/[^a-zA-Z0-9 ,.]/", "", $deleteLabel));
		$deleteLabel = preg_replace('/\s{3,}/',' ', $deleteLabel);

		return $deleteLabel;
	}
}