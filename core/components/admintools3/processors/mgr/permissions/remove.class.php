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
 * Remove resource permissions
 */
class resourcePermissionsRemoveProcessor extends modObjectProcessor {
    public $objectType = 'admintools3_permissions';
    public $classKey = \AdminTools3\Model\Permission::class;
    public $permission = 'access_permissions';


	/**
	 * @return array|string
	 */
	public function process() {
		$ids = $this->modx->fromJSON($this->getProperty('ids'));
		if (empty($ids)) {
			return $this->failure($this->modx->lexicon('admintools3_permissions_err_ns'));
		}

		foreach ($ids as $id) {
			/** @var Permission $object */
			if (!$object = $this->modx->getObject($this->classKey, $id)) {
				return $this->failure($this->modx->lexicon('admintools3_permissions_err_nf'));
			}

			$object->remove();
		}

		return $this->success();
	}

}

return 'resourcePermissionsRemoveProcessor';