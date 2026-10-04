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
 * Update resource permissions
 */
class resourcePermissionsUpdateProcessor extends modObjectUpdateProcessor {
    public $objectType = 'admintools3_permissions';
    public $classKey = \AdminTools3\Model\Permission::class;
    public $permission = 'access_permissions';

	/**
	 * @return bool
	 */
	public function beforeSet() {
        $id = (int)$this->getProperty('id');
        $rid = $this->getProperty('rid');
        list($type, $principal) = explode('-', $this->getProperty('principal'));
        if ($this->modx->getCount($this->classKey, array('rid' => $rid, 'principal_type' => $type, 'principal' => $principal, 'id:!=' => $id))) {
            $this->modx->error->addField('principal', $this->modx->lexicon('admintools3_permissions_err_ae'));
            return parent::beforeSet();
        }
        switch ($type) {
            case 'all':
                $weight = 0;
                break;
            case 'gst':
                $weight = 1;
                break;
            case 'grp':
                $weight = 10;
                break;
            case 'usr':
                $weight = 100;
                break;
            default:
                $weight = 0;
                break;
        }
        if ($type != 'grp') $this->setProperty('priority', 0);
        $this->setProperty('weight', $weight);
        $this->setProperty('principal_type', $type);
        $this->setProperty('principal', $principal);

		return parent::beforeSet();
	}
}

return 'resourcePermissionsUpdateProcessor';
