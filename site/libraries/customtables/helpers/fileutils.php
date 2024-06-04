<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use InvalidArgumentException;
use RuntimeException;

/**
 * Utility class for handling file and folder operations.
 * @since 3.2.9
 */
class FileUtils
{
    /**
     * Default folder path for Joomla.
     * @since 3.2.9
     */
    private const DEFAULT_FOLDER_JOOMLA = '/images';
    private const DEFAULT_FOLDER_WORDPRESS = '/wp-content/uploads';

    /**
     * Get or create a directory path within the Joomla environment.
     *
     * @param string $folder The desired folder path (relative or absolute)
     * @return string The web directory path
     * @throws InvalidArgumentException If the provided folder path is invalid
     * @since 3.2.9
     */
    public static function getOrCreateDirectoryPath(string $folder): string
    {
        $folder = self::normalizeFolderPath($folder);
        $fullPath = str_replace('//', '/', CUSTOMTABLES_ABSPATH . $folder);//In WP CUSTOMTABLES_ABSPATH ends with /
        $fullPath = str_replace('/', DIRECTORY_SEPARATOR, $fullPath);

        self::createDirectory($fullPath);
        return $folder;
    }

    /**
     * Normalize the folder path according to Joomla conventions.
     *
     * @param string $folder The folder path to normalize
     * @return string The normalized folder path
     * @throws InvalidArgumentException If the provided folder path is invalid
     * @since 3.2.9
     */
    private static function normalizeFolderPath(string $folder): string
    {

        if (defined('_JEXEC'))
            $defaultFolder = self::DEFAULT_FOLDER_JOOMLA;
        else
            $defaultFolder = self::DEFAULT_FOLDER_WORDPRESS;

        if (empty($folder))
            return $defaultFolder;

        $folder = self::ensureLeadingSlash($folder);
        $folder = self::ensureImagesPrefix($folder, $defaultFolder);

        return rtrim($folder, '/');
    }

    /**
     * Ensure the folder path starts with a leading slash.
     *
     * @param string $folder The folder path to process
     * @return string The folder path with a leading slash
     * @since 3.2.9
     */
    private static function ensureLeadingSlash(string $folder): string
    {
        return '/' . ltrim($folder, '/');
    }

    /**
     * Ensure the folder path has the "/images" prefix according to Joomla conventions.
     *
     * @param string $folder The folder path to process
     * @return string The folder path with the "/images" prefix
     * @since 3.2.9
     */
    private static function ensureImagesPrefix(string $folder, string $defaultFolder): string
    {
        if (!str_starts_with($folder, $defaultFolder))
            $folder = $defaultFolder . $folder;

        return $folder;
    }

    /**
     * Create the directory if it doesn't exist.
     *
     * @param string $path The full path to the directory
     * @throws RuntimeException If the directory cannot be created
     * @since 3.2.9
     */
    private static function createDirectory(string $path): void
    {
        if (!file_exists($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" could not be created', $path));
        }
    }
}