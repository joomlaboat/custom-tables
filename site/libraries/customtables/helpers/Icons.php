<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

class Icons
{
	public static function renderIconSet($type)
	{
		?>
		<div id="ui-<?php echo($type === '' ? 'images' : $type); ?>" style="display: none;">
			<ul>
				<li><?php echo common::translate('COM_CUSTOMTABLES_ADD') ?>
					: <?php echo Icons::iconNew($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_PRINT') ?>
					: <?php echo Icons::iconPrint($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_ORDER_BY') ?>
					: <?php echo Icons::iconOrderBy($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_ASCENDING_ORDER') ?>
					: <?php echo Icons::iconAscendingOrder($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_DESCENDING_ORDER') ?>
					: <?php echo Icons::iconDescendingOrder($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_EDIT') ?>
					: <?php echo Icons::iconEdit($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_PUBLISHED') ?>
					: <?php echo Icons::iconPublished($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_UNPUBLISHED') ?>
					: <?php echo Icons::iconUnpublished($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_REFRESH') ?>
					: <?php echo Icons::iconRefresh($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_DELETE') ?>
					: <?php echo Icons::iconDelete($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_COPY') ?>
					: <?php echo Icons::iconCopy($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_CREATEUSER') ?>
					: <?php echo Icons::iconCreateUser($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_RESET_PASSWORD') ?>
					: <?php echo Icons::iconResetPassword($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_FILE_MANAGER') ?>
					: <?php echo Icons::iconFileManager($type); ?></li>
				<li><?php echo common::translate('COM_CUSTOMTABLES_PHOTO_MANAGER') ?>
					: <?php echo Icons::iconPhotoManager($type); ?></li>
			</ul>
		</div>
		<?php
	}

	public static function iconNew(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_ADD');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/new.png" class="ctToolBarIcon2x" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/new.png" class="ctToolBarIcon2x" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon2x fa fa-plus-circle" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon2x fas fa-plus-circle" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon2x bi bi-file-earmark-plus ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon2x um-faicon-plus-circle" aria-hidden="true" title="' . $title . '"></i>';//checked

		// Default fallback
		else
			return 'New';
	}

	public static function iconPrint(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_PRINT');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/print.png" class="ctToolBarIcon2x" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/print.png" class="ctToolBarIcon2x" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon2x fa fa-print" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon2x fas fa-print" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon2x bi bi-printer ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon2x um-faicon-print" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Print';
	}

	public static function iconOrderBy(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_ORDER_BY');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/order.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/order.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-sort" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-sort" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-filter ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-sort" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Order By';
	}

	public static function iconAscendingOrder(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_ASCENDING_ORDER');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/up.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/up.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-caret-up" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-caret-up" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-caret-up-fill ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-caret-up" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Ascending Order';
	}

	public static function iconDescendingOrder(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_DESCENDING_ORDER');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/down.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/down.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-caret-down" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-caret-down" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-caret-down-fill ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-caret-down" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Descending Order';
	}

	public static function iconEdit(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_EDIT');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/edit.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/edit.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon ctToolBarIcon fa fa-pencil" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon ctToolBarIcon fas fa-pencil-alt" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-pencil-square ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-pencil" aria-hidden="true" title="' . $title . '"></i>';//checked

		// Default fallback
		else
			return 'Edit';
	}

	public static function iconPublished(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_PUBLISHED');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/publish.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/published.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-eye" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-eye" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-eye ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-eye" aria-hidden="true" title="' . $title . '"></i>';//checked

		// Default fallback
		else
			return 'Published';
	}

	public static function iconUnpublished(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_UNPUBLISHED');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/unpublish.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/unpublished.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-eye-slash" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-eye-slash" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-eye-slash ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-eye-slash" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Unpublished';
	}

	public static function iconRefresh(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_REFRESH');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/refresh.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/refresh.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-refresh" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-sync-alt" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-arrow-repeat ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-refresh" aria-hidden="true" title="' . $title . '"></i>';//checked

		// Default fallback
		else
			return 'Refresh';
	}

	public static function iconDelete(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_DELETE');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/delete.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/delete.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-trash" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-trash-alt" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-trash ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-trash" aria-hidden="true" title="' . $title . '"></i>';//checked

		// Default fallback
		else
			return 'Delete';
	}

	public static function iconCopy(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_COPY');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/copy.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/copy.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-copy" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-copy" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-files ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-copy" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Copy';
	}

	public static function iconCreateUser(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_CREATEUSER');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/key-add.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/keyadd.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-user-plus" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-user-plus" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-person-plus ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-user-plus" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Create User';
	}

	public static function iconResetPassword(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_RESET_PASSWORD');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/key.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/key.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-key" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-key" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-key ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-key" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Reset Password';
	}

	public static function iconFileManager(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_FILE_MANAGER');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/filemanager.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/filemanager.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-folder-open" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-folder-open" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-folder ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-folder-open" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'File Manager';
	}

	public static function iconPhotoManager(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_PHOTO_MANAGER');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/photomanager.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/photomanager.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon fa fa-image" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-image" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon bi bi-image ms-1" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon um-faicon-image" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Photo Manager';
	}

	public static function iconGoBack(string $type, string $title = '', ?string $iconFile = null): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_GO_BACK');

		if (!empty($iconFile))
			return '<img src="' . $iconFile . '" alt="' . $title . '" title="' . $title . '" />';

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/icons/arrow_rtl.png" class="ctToolBarIcon2x" alt="' . $title . '" title="' . $title . '" />';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/back.png" class="ctToolBarIcon2x" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon2x fa fa-arrow-left" data-icon="fa fa-arrow-left" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon2x fas fa-arrow-left" data-icon="fas fa-arrow-left" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon2x bi bi-arrow-left" data-icon="bi bi-arrow-left" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon2x um-faicon-arrow-left" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Go Back';
	}

	public static function iconDownloadCSV(string $type, string $title = '', ?string $iconFile = null, int $imageSize = 32): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_DOWNLOAD');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . $iconFile . '" alt="' . $title . '" title="' . $title . '" style="width:' . $imageSize . 'px;height:' . $imageSize . 'px;">';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return 'ICON NEEDED';//'<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/delete.png" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon2x fa fa-file-csv" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon2x fas fa-file-csv" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon2x bi bi-file-earmark-spreadsheet" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon2x um-faicon-file-csv" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Download CSV';
	}

	public static function iconSearch(string $type, string $title = ''): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_SEARCH');

		// Image Icons (default)
		if ($type == '')
			return '';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return '<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/search.png" class="ctToolBarIcon" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon2x fa fa-search" data-icon="fa fa-search" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon fas fa-search" data-icon="fas fa-search" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon2x bi bi-search" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon2x um-faicon-search" aria-hidden="true" title="' . $title . '"></i>'; // Added UM icon support

		// Default fallback
		else
			return 'Search';
	}


	public static function iconDownload(string $type, string $title = '', ?string $iconFile = null, int $imageSize = 32): string
	{
		if (empty($title))
			$title = common::translate('COM_CUSTOMTABLES_DOWNLOAD');

		// Image Icons (default)
		if ($type == '')
			return '<img src="' . $iconFile . '" alt="' . $title . '" title="' . $title . '" style="width:' . $imageSize . 'px;height:' . $imageSize . 'px;">';

		// Not So Pixelly
		elseif ($type == 'not-so-pixelly')
			return 'ICON NEEDED';//'<img src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'images/notsopixelly/48px/delete.png" alt="' . $title . '" title="' . $title . '" />';

		// Font Awesome 4
		elseif ($type == 'font-awesome-4')
			return '<i class="ctToolBarIcon2x fa fa-file" aria-hidden="true" title="' . $title . '"></i>';

		// Font Awesome 5
		elseif ($type == 'font-awesome-5' or $type == 'font-awesome-6')
			return '<i class="ctToolBarIcon2x fas fa-file" aria-hidden="true" title="' . $title . '"></i>';

		// Bootstrap Icons
		elseif ($type == 'bootstrap')
			return '<i class="ctToolBarIcon2x bi bi-file-earmark" aria-hidden="true" title="' . $title . '"></i>';

		elseif ($type == 'ultimate-member')
			return '<i class="ctToolBarIcon2x um-faicon-file" aria-hidden="true" title="' . $title . '"></i>';

		// Default fallback
		else
			return 'Download';
	}

}