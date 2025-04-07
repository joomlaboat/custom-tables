<?php
// administrator/components/com_customtables/views/editphp/view.html.php

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;

$doc = Factory::getDocument();

// Add CodeMirror CSS
//$doc->addStyleSheet(JURI::root() . 'media/editors/codemirror/lib/codemirror.css');

// Add CodeMirror JS
//$doc->addScript(JURI::root() . 'media/editors/codemirror/lib/codemirror.js');

// Add PHP Mode for syntax highlighting
//$doc->addScript(JURI::root() . 'media/editors/codemirror/mode/php/php.js');

class CustomTablesViewEditphp extends BaseHtmlView
{
	protected $fileContent;
	protected $encodedPath;

	public function display($tpl = null)
	{
		$input = Factory::getApplication()->input;
		$tableName = $input->getCmd('tablename', '');
		$encodedPath = $input->get('file', '', 'BASE64');
		$baseFolder = JPATH_SITE . '/components/com_customtables/customphp/';

		if (empty($encodedPath)) {
			$this->fileContent = '
<?php
namespace CustomTables;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class *filename* extends CustomPHP
{
    private DatabaseInterface $db;

    public function __construct(CT &$ct, string $action)
    {
        parent::__construct($ct, $action);
        $this->db = Factory::getContainer()->get(\'db\');
    }
   
    public function process(?array $row, ?array $row_old): void
    {
        // Available actions: create, refresh, update, delete
    
        switch($this->action) {
            case \'refresh\':
            
            /*
            Example:
                if (isset($row[\'id\'])) {
                    $query = $this->db->getQuery(true)
                        ->update($this->db->quoteName(\'#__customtables_table_' . $tableName . '\'))
                        ->set($this->db->quoteName(\'ct_status\') . \' = \' . $this->db->quote(\'Updated\'))
				->where($this->db->quoteName(\'id\') . \' = \' . (int)$row[\'id\']);
                    
                    $this->db->setQuery($query);
                    $this->db->execute();
                }
            */
		    break;

            case \'create\':
                if (isset($row[\'id\'])) {
                
                /*
                
                	Example:
                
					$query = $this->db->getQuery(true)
						->update($this->db->quoteName(\'#__customtables_table_' . $tableName . '\'))
						->set($this->db->quoteName(\'ct_status\') . \' = \' . $this->db->quote(\'This is a new record\'))
						->where($this->db->quoteName(\'id\') . \' = \' . (int)$row[\'id\']);

					$this->db->setQuery($query);
					$this->db->execute();
					
					*/
					
				}
            break;
        }
    }
}

			';
			$this->encodedPath = ''; // No path yet
		} else {
			$relPath = base64_decode($encodedPath);
			$fullPath = realpath($baseFolder . $relPath);

			if (strpos($fullPath, realpath($baseFolder)) !== 0 || !File::exists($fullPath)) {
				throw new Exception('Invalid file path.');
			}

			$this->fileContent = file_get_contents($fullPath);
			$this->encodedPath = $encodedPath;
		}

		parent::display($tpl);
	}
}
