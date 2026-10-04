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
 * Bind plugin from event
 */
class adminTools3EventPluginBindProcessor extends modObjectProcessor {
    public $objectType = 'admintools3_plugin';
    public $classKey = 'modPluginEvent';
//    public $languageTopics = array('admintools3:default');
    public $permission = 'edit_plugin';


	/**
	 * @return array|string
	 */
	public function process() {
		$id = (int) $this->getProperty('pluginid');
		$event = $this->getProperty('event');
		$priority = $this->getProperty('priority');
		if (empty($id)) {
			return $this->failure($this->modx->lexicon('admintools3_error'));
		}
        /** @var modPluginEvent $object */
        if ($this->modx->getCount($this->classKey, array('pluginid' => $id, 'event' => $event)) == 0) {
            $object = $this->modx->newObject($this->classKey);
            $object->fromArray(array('pluginid' => $id, 'event' => $event, 'priority' => $priority), '', true);

            if ($object->save()) {
                $this->modx->eventMap[$event][$id] = $id;
            }
        }

		return $this->success();
	}
}

return 'adminTools3EventPluginBindProcessor';