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
 * Get list of edited elements
 */
class EditedElementGetListProcessor extends modObjectGetListProcessor {
    public $objectType = 'modManagerLog';
	public $classKey = 'modManagerLog';
    public $languageTopics = array('modmanagerlog:default', 'admintools3:default');
    public $defaultSortField = 'modManagerLog.occurred';
    public $defaultSortDirection = 'DESC';
    //public $permission = 'view';

    /**
     * @return mixed
     */
    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $c->select($this->modx->getSelectColumns('modManagerLog','modManagerLog','',array('action'),true));
//        $c->select($this->modx->getSelectColumns('modManagerLog','modManagerLog'));
        $c->select(array('User.username','Template.templatename','Chunk.name as chunkname','Snippet.name as snippetname','Plugin.name as pluginname','TV.name as tvname'));
        $c->innerJoin('modUser','User');
        $classes = [
            'Template' => ['modTemplate', \MODX\Revolution\modTemplate::class],
            'Chunk' => ['modChunk', \MODX\Revolution\modChunk::class],
            'Snippet' => ['modSnippet', \MODX\Revolution\modSnippet::class],
            'Plugin' => ['modPlugin', \MODX\Revolution\modPlugin::class],
            'TV' => ['modTemplateVar', \MODX\Revolution\modTemplateVar::class],
        ];
        foreach ($classes as $alias => $names) {
            $c->leftJoin($names[1], $alias,
                '`modManagerLog`.item = `' . $alias . '`.`id` AND `modManagerLog`.`classKey` IN ('
                . implode(',', array_map([$this->modx, 'quote'], $names)) . ')'
            );
        }
        $query = trim($this->getProperty('query'));
        if ($query) {
            $search = $this->modx->quote('%' . $query . '%');
            $c->where(
                '(Template.templatename LIKE ' . $search . ' OR Chunk.name LIKE ' . $search . ' OR Snippet.name LIKE ' . $search . ' OR Plugin.name LIKE ' . $search . ' OR TV.name LIKE ' . $search . ')'
            );
        } else {
            $c->where(
                '(modManagerLog.action LIKE "template_%" OR modManagerLog.action LIKE "chunk_%" OR modManagerLog.action LIKE "snippet_%" OR modManagerLog.action LIKE "plugin_%" OR modManagerLog.action LIKE "tv_%")'
            );
        }
        $user = intval($this->getProperty('user'));
        if ($user) {
            $c->andCondition(array('modManagerLog.user'=>$user));
        }
        $dateStart = trim($this->getProperty('datestart'));
        if ($dateStart) {
            $dateStart = date('Y-m-d',strtotime($dateStart));
            $c->andCondition(array('modManagerLog.occurred:>='=>$dateStart));
        }
        $dateEnd = trim($this->getProperty('dateend'));
        if ($dateEnd) {
            $dateEnd = date('Y-m-d 23:59:59',strtotime($dateEnd));
            $c->andCondition(array('modManagerLog.occurred:<='=>$dateEnd));
        }

        return $c;
    }

    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object) {
        $array = $object->toArray();
        $array['key'] = $array['classKey'].'-'.$array['item'];
        $classKey = basename(str_replace('\\', '/', $array['classKey']));
        $types = [
            'modTemplate' => ['templatename', 'template'],
            'modChunk' => ['chunkname', 'chunk'],
            'modSnippet' => ['snippetname', 'snippet'],
            'modPlugin' => ['pluginname', 'plugin'],
            'modTemplateVar' => ['tvname', 'tv'],
        ];
        if (isset($types[$classKey])) {
            [$nameField, $type] = $types[$classKey];
            $array['type'] = $this->modx->lexicon('admintools3_type_' . $type);
            $array['name'] = trim((string) ($array[$nameField] ?? ''));
        } else {
            $array['type'] = $classKey;
            $array['name'] = '';
        }
        $array['missing'] = $array['name'] === '';
        if ($array['missing']) {
            $array['name'] = '(' . $this->modx->lexicon('deleted') . ')';
        }

        unset($array['templatename'],$array['chunkname'],$array['snippetname'],$array['pluginname'],$array['tvname'],$array['id']);
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-pencil-square-o ',
            'title' => $this->modx->lexicon('admintools3_open'),
            //'multiple' => $this->modx->lexicon('fullcalendar_items_update'),
            'action' => 'openElement',
            'button' => true,
            'menu' => true,
        );

        return $array;
    }
}
return 'EditedElementGetListProcessor';
