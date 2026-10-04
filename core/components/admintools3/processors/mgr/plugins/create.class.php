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
 * Creates a plugin
 *
 * @param string $name The name of the plugin
 * @param string $plugincode The code of the plugin.
 * @param string $description (optional) A description of the plugin.
 * @param integer $category (optional) The category for the plugin. Defaults to
 * no category.
 * @param boolean $locked (optional) If true, can only be accessed by
 * administrators. Defaults to false.
 * @param boolean $disabled (optional) If true, the plugin does not execute.
 * @param string $events (optional) A JSON array of system events to associate
 * this plugin with.
 *
 * @package admintools3
 */
class adminTools3PluginCreateProcessor extends modElementCreateProcessor {
    public $classKey = 'modPlugin';
    public $languageTopics = array('plugin','category','element');
    public $permission = 'new_plugin';
    public $objectType = 'plugin';
    public $beforeSaveEvent = 'OnBeforePluginFormSave';
    public $afterSaveEvent = 'OnPluginFormSave';

    public function afterSave() {
        $this->saveEvents();
        return parent::afterSave();
    }

    /**
     * Save system events
     * 
     * @return void
     */
    public function saveEvents() {
        $events = $this->getProperty('events', null);
        if (!empty($events)) {
            $events = is_array($events) ? $events : $this->modx->fromJSON($events);
            foreach ($events as $event) {
                $properties = array(
                    'plugin' => $this->object->get('id'),
                    'event' => $event,
                    'enabled' => true,
                );
                /** @var modProcessorResponse $response */
                $response = $this->modx->runProcessor(\MODX\Revolution\Processors\Element\Plugin\Event\Update::class, $properties);
                if ($response->isError()) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, $response->getMessage() . print_r($properties, true));
                }
            }
        }
    }
}

return 'adminTools3PluginCreateProcessor';
