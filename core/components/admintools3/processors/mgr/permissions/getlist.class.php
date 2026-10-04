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
 * Get permissions
 */
class ResourcePermissionsGetListProcessor extends modObjectGetListProcessor {
    public $objectType = 'admintools3_permissions';
	public $classKey = \AdminTools3\Model\Permission::class;
    //public $languageTopics = array('admintools3:default');
    public $defaultSortField = 'Permissions.weight';
    public $defaultSortDirection = 'ASC';
    public $permission = 'access_permissions';

    /**
     * @return mixed
     */
    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $rid = trim($this->getProperty('resource'));
        $c->setClassAlias('Permissions');
        $c->leftJoin('modUserProfile','User', 'Permissions.principal = User.internalKey AND Permissions.principal_type = "usr"');
        $c->leftJoin('modUserGroup','Group', 'Permissions.principal = Group.id AND Permissions.principal_type = "grp"');
        $c->select('Permissions.*, User.fullname, Group.name as groupname');
        $c->where(array(
            'Permissions.rid' => $rid,
        ));
        $c->sortby('Permissions.weight', 'ASC');
        //$c->sortby('Permissions.priority', 'DESC');

        return $c;
    }
    //TODO Debug
    public function prepareQueryAfterCount(xPDOQuery $c) {
//        $c->prepare();
//        $this->modx->log(modX::LOG_LEVEL_ERROR, $c->toSQL());
        return $c;
    }
    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object) {
        $array = $object->toArray();
        switch ($array['principal_type']) {
            case 'all':
                $array['principal_name'] = $this->modx->lexicon('admintools3_permissions_all');
                break;
            case 'gst':
                $array['principal_name'] = $this->modx->lexicon('admintools3_permissions_guest');
                break;
            case 'grp':
                $array['principal_name'] = $array['groupname'];
                unset($array['groupname']);
                break;
            case 'usr':
                $array['principal_name'] = $array['fullname'];
                unset($array['fullname']);
                break;
        }
        // Edit note
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-pencil-square-o ',
            'title' => $this->modx->lexicon('admintools3_edit'),
            //'multiple' => $this->modx->lexicon('fullcalendar_items_update'),
            'action' => 'updatePermission',
            'button' => true,
            'menu' => true,
        );
        // Remove note
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-trash-o action-red',
            'title' => $this->modx->lexicon('admintools3_remove'),
            //'multiple' => $this->modx->lexicon('admintools3_notes_remove'),
            'action' => 'removePermission',
            'button' => true,
            'menu' => true,
        );

        return $array;
    }
}
return 'ResourcePermissionsGetListProcessor';