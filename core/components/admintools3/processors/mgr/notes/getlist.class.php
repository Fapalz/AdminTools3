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
 * Get notes
 */
class NotesGetListProcessor extends modObjectGetListProcessor {
    public $objectType = 'admintools3_notes';
	public $classKey = \AdminTools3\Model\Note::class;
//    public $languageTopics = array('admintools3:default');
    public $defaultSortField = 'Note.id';
    public $defaultSortDirection = 'DESC';
    //public $permission = 'view_notes';

    /**
     * @return mixed
     */
    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $c->leftJoin('modUser','UserCreate');
        $c->select('Note.*, UserCreate.username');
        $where = '(Note.private = 0 OR (Note.private = 1 AND Note.createdby = '. (int) $this->modx->user->get('id') .'))';
        $query = trim((string) $this->getProperty('searchQuery'));
        $wheresearch = trim($this->getProperty('wheresearch'));
        if ($query) {
            $like = $this->modx->quote('%' . $query . '%');
            $tag = $this->modx->quote($query);
            switch ($wheresearch) {
            	case 1:
                case '':
                    $where .= ' AND (Note.title LIKE ' . $like . ' OR Note.text LIKE ' . $like . ' OR FIND_IN_SET(' . $tag . ', Note.tags))';
            		break;
                case 2:
                    $where .= ' AND Note.title LIKE ' . $like;
                    break;
                case 3:
                    $where .= ' AND Note.text LIKE ' . $like;
                    break;
                case 4:
                    $where .= ' AND FIND_IN_SET(' . $tag . ', Note.tags)';
                    break;
            }
        }
        $c->where($where);
        $private = $this->getProperty('private');
        if ($private) {
            $c->andCondition(array('Note.createdby'=>$this->modx->user->id));
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
        if (!empty($array['url']) && preg_match('~^(https?://|/)~i', $array['url'])) {
            $url = htmlspecialchars($array['url'], ENT_QUOTES, 'UTF-8');
            $array['url'] = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>';
        } elseif (!empty($array['url'])) {
            $array['url'] = htmlspecialchars($array['url'], ENT_QUOTES, 'UTF-8');
        }
        // Edit note
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-pencil-square-o ',
            'title' => $this->modx->lexicon('admintools3_note_edit'),
            //'multiple' => $this->modx->lexicon('fullcalendar_items_update'),
            'action' => 'updateNote',
            'button' => true,
            'menu' => true,
        );
        // Remove note
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-trash-o action-red',
            'title' => $this->modx->lexicon('admintools3_note_remove'),
            'multiple' => $this->modx->lexicon('admintools3_notes_remove'),
            'action' => 'removeNote',
            'button' => true,
            'menu' => true,
        );

        return $array;
    }
}
return 'NotesGetListProcessor';
