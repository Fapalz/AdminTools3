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
 * Remove element from the last edited list
 * @deprecated Not used
 */
class adminTools3NotesRemoveProcessor extends modProcessor {
    public $objectType = 'admintools3_notes';
    public $classKey = \AdminTools3\Model\Note::class;
//    public $languageTopics = array('admintools3:default');
//    public $permission = 'remove_notes';

    /**
     * @return array|string
     */
    public function process() {
        /*if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }*/

        $ids = $this->modx->fromJSON($this->getProperty('ids'));
        if (empty($ids)) {
            return $this->failure($this->modx->lexicon('admintools3_notes_err_ns'));
        }

        foreach ($ids as $id) {
            /** @var Note $object */
            if (!$object = $this->modx->getObject($this->classKey, $id)) {
                return $this->failure($this->modx->lexicon('admintools3_notes_err_nf'));
            }
			if ($object->get('private') && (int) $object->get('createdby') !== (int) $this->modx->user->id) {
				return $this->failure($this->modx->lexicon('access_denied'));
			}

            $object->remove();
        }

        return $this->success();
    }
}
return 'adminTools3NotesRemoveProcessor';
