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
 * Verify the existence of element
 */
class lastEditedElementsVerifyProcessor extends modProcessor {
    public $objectType = 'admintools3';
//	public $classKey = '';
    public $languageTopics = array('admintools3:default');
    public $permission = 'remove_led_elements';

    /**
     * @return mixed
     */
    public function process() {
        $id = (int) $this->getProperty('id');
        $classKey = $this->getProperty('classKey');
//        $classKey = 'mod'.ucfirst($type);
        if (!$this->modx->getCount($classKey, $id)) {
            return $this->failure($this->modx->lexicon('admintools3_element_nf'));
        }
        return $this->success();
    }
}
return 'lastEditedElementsVerifyProcessor';