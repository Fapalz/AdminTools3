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
 * Unbind plugin from event
 */
class adminTools3EventPluginRemoveProcessor extends modObjectProcessor {
    public $objectType = 'admintools3_plugin';
    public $classKey = 'modPluginEvent';
//    public $languageTopics = array('admintools3:default');
    public $permission = 'edit_plugin';


	/**
	 * @return array|string
	 */
	public function process() {
		$id = (int) $this->getProperty('id');
		$event = $this->getProperty('event');
		if (empty($id)) {
			return $this->failure($this->modx->lexicon('admintools3_error'));
		}

        if (!$object = $this->modx->getObject($this->classKey, array('pluginid' => $id, 'event' => $event))) {
            return $this->failure($this->modx->lexicon('admintools3_err_object_nf'));
        }

        if ($object->remove()) {
            unset($this->modx->eventMap[$event][$id]);
        }

		return $this->success();
	}
}

return 'adminTools3EventPluginRemoveProcessor';