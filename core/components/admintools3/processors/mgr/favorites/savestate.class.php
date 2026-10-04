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
 * Save the state of Favorite Elements
 */
class atFavoritesSaveStateProcessor extends modProcessor {
    public $objectType = 'admintools3';
    public $languageTopics = array('admintools3:default');
//	public $classKey = '';
    //public $permission = '';
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
    public function process() {
        $state = $this->getProperty('state') == 'true' ? true : false;
        $type = $this->getProperty('type');
        $types = ['template', 'tv', 'chunk', 'snippet', 'plugin'];
        if ($type !== 'all' && !in_array($type, $types, true)) {
            return $this->failure('Invalid element type.');
        }
        foreach ($type === 'all' ? $types : [$type] as $elementType) {
            $_SESSION['admintools3']['favoriteElements']['states'][$elementType] = $state;
        }
        //$this->modx->admintools3->saveToCache($_SESSION['admintools3']['favoriteElements']['states'], 'states', 'favorite_elements/' . $this->modx->user->id);
        $this->modx->admintools3->saveToProfile($_SESSION['admintools3']['favoriteElements']['states'],'adminTools3States');

        @session_write_close();
        return $this->success('', $_SESSION['admintools3']['favoriteElements']['states']);
    }
}
return 'atFavoritesSaveStateProcessor';
