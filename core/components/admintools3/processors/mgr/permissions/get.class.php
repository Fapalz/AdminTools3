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
 * Get an Item
 */
class resourcePermissionsGetProcessor extends modObjectGetProcessor {
    public $objectType = 'admintools3_permissions';
    public $classKey = \AdminTools3\Model\Permission::class;
    public $permission = 'access_permissions';


    /**
     * Return the response
     * @return array
     */
    public function cleanup() {
        $output = $this->object->toArray();
        switch ($output['principal_type']) {
            case 'all':
                $output['principal'] = 'all-0';
                break;
            case 'gst':
                $output['principal'] = 'gst-0';
                break;
            case 'grp':
                $output['principal'] = 'grp-' . $output['principal'];
                break;
            case 'usr':
                $output['principal'] = 'usr-' . $output['principal'];
                break;
        }
        return $this->success('', $output);
    }
}

return 'resourcePermissionsGetProcessor';