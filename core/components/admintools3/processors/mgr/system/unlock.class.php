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
 * Lock the manager page.
 */
class atUnlockAdminPanelProcessor extends modProcessor {
    public $objectType = 'admintools3';
    public $languageTopics = array('admintools3:default');
    // public $classKey = '';
    // public $permission = '';

    public function initialize() {
        if (is_null($this->modx->admintools3)) {
            $path = $this->modx->getOption('admintools3_core_path', null, $this->modx->getOption('core_path') . 'components/admintools3/') . 'services/';
            $this->modx->getService('admintools3', 'AdminTools3', $path, []);
        }
        return ($this->modx->admintools3 instanceof AdminTools3);
    }

    public function process() {
        if (isset($_POST['admintools3_action'])
            && $_POST['admintools3_action'] == 'unlock'
            && $this->modx->admintools3->unlock(filter_input(INPUT_POST, 'unlock_code', FILTER_SANITIZE_SPECIAL_CHARS))
        ) {
            $_SESSION['admintools3']['locked'] = false;
            return $this->success('');
        }
        return $this->failure('Error');
    }
}
return 'atUnlockAdminPanelProcessor';