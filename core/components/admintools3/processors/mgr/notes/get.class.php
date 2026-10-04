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
 * Get a note
 */
class adminTools3NoteGetProcessor extends modObjectGetProcessor {
	public $objectType = 'admintools3_notes';
	public $classKey = \AdminTools3\Model\Note::class;
//	public $languageTopics = array('admintools3:default');
	//public $permission = 'view_note';


    public function initialize() {
        $result = parent::initialize();
        if ($result !== true) {
            return $result;
        }
        if ($this->object->get('private') && (int) $this->object->get('createdby') !== (int) $this->modx->user->id) {
            return $this->modx->lexicon('access_denied');
        }
        return true;
    }

    /** {@inheritdoc} */
    public function cleanup() {
        $output = $this->object->toArray();
        if (!$userUpdate = $this->object->getOne('UserUpdate')) {
            $output['editedby'] = '';
            $output['editedon'] = '';
        } else {
            $output['editedby'] = $userUpdate->get('username');
        }

        return $this->success('', $output);
    }
}

return 'adminTools3NoteGetProcessor';
