<?php
use MODX\Revolution\modX as modX;
use MODX\Revolution\modSystemEvent as modSystemEvent;
use MODX\Revolution\Processors\Processor as modProcessor;
use MODX\Revolution\Processors\ModelProcessor as modObjectProcessor;
use MODX\Revolution\Processors\Model\CreateProcessor as modObjectCreateProcessor;
use MODX\Revolution\Processors\Model\GetProcessor as modObjectGetProcessor;
use MODX\Revolution\Processors\Model\GetListProcessor as modObjectGetListProcessor;
use MODX\Revolution\Processors\Model\UpdateProcessor as modObjectUpdateProcessor;
use MODX\Revolution\Processors\Model\RemoveProcessor as modObjectRemoveProcessor;
use MODX\Revolution\Processors\Element\Create as modElementCreateProcessor;
use xPDO\Om\xPDOQuery as xPDOQuery;
use xPDO\Om\xPDOObject as xPDOObject;


/**
 * Create a note
 */
class adminTools3NoteCreateProcessor extends modObjectCreateProcessor {
	public $objectType = 'admintools3_notes';
	public $classKey = \AdminTools3\Model\Note::class;
	//public $languageTopics = array('admintools3');
	//public $permission = 'create_notes';


	/**
	 * @return bool
	 */
	public function beforeSet() {
		$title = trim($this->getProperty('title'));
		if (empty($title)) {
			$this->modx->error->addField('title', $this->modx->lexicon('admintools3_note_err_title'));
		}
        $text = trim($this->getProperty('text'));
        $text = preg_replace('/(<br>$)/', '', $text);
        $this->setProperty('text',$text);
//        $this->setProperty('text',htmlspecialchars($text));
        $url = trim($this->getProperty('url'));
        if ($url == 'http://') $this->setProperty('url', '');
        $this->setProperty('createdon', time());
        $this->setProperty('createdby', $this->modx->user->get('id'));

		return parent::beforeSet();
	}
}

return 'adminTools3NoteCreateProcessor';