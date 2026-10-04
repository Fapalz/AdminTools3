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
 * Removean element from the favorite list
 */
class atFavoritesRemoveElementProcessor extends modProcessor {
    public $objectType = 'admintools3';
//	public $classKey = '';
    public $languageTopics = array('admintools3:default');
    //public $permission = 'view';
    /**
     * @return boolean
     */
    public function initialize() {
        if (is_null($this->modx->admintools3)) {
            $path = $this->modx->getOption('admintools3_core_path', null, $this->modx->getOption('core_path') . 'components/admintools3/') . 'services/';
            $this->modx->getService('admintools3', 'AdminTools3', $path, []);
        }
        return ($this->modx->admintools3 instanceof AdminTools3);
    }
    /**
     * @return mixed
     */
    public function process() {
        $id = (int) $this->getProperty('id');
        $type = $this->getProperty('type') . 's';
        $_SESSION['admintools3']['favoriteElements']['elements'][$type] = array_values(array_diff($_SESSION['admintools3']['favoriteElements']['elements'][$type],array($id)));
//        $this->modx->admintools3->saveToCache($_SESSION['admintools3']['favoriteElements']['elements'], 'elements', 'favorite_elements/' . $this->modx->user->id);
        $this->modx->admintools3->saveToProfile($_SESSION['admintools3']['favoriteElements']['elements'],'adminTools3Elements');
        @session_write_close();

        return $this->success('', $_SESSION['admintools3']['favoriteElements']['elements']);
    }
}
return 'atFavoritesRemoveElementProcessor';