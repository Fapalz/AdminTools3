<?php

use MODX\Revolution\Processors\Processor;

class AdminTools3NotesUploadProcessor extends Processor
{
    public function process()
    {
        $content = (string) $this->getProperty('content', '');
        if ($content === '' || strlen($content) > 5 * 1024 * 1024) {
            return $this->failure($this->modx->lexicon('admintools3_err_empty_file'));
        }

        $notes = json_decode($content, true);
        if (!is_array($notes)) {
            // Read legacy AdminTools exports without instantiating PHP objects.
            $notes = @unserialize($content, ['allowed_classes' => false]);
        }
        if (!is_array($notes) || count($notes) > 10000) {
            return $this->failure($this->modx->lexicon('admintools3_err_empty_file'));
        }

        $fields = ['title', 'text', 'url', 'tags', 'private'];
        foreach ($notes as $note) {
            if (!is_array($note) || !isset($note['title'], $note['text'])) {
                return $this->failure($this->modx->lexicon('admintools3_err_empty_file'));
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $note) && !is_scalar($note[$field]) && $note[$field] !== null) {
                    return $this->failure($this->modx->lexicon('admintools3_err_empty_file'));
                }
            }
        }
        foreach ($notes as $note) {
            $record = $this->modx->newObject(\AdminTools3\Model\Note::class);
            foreach ($fields as $field) {
                if (array_key_exists($field, $note)) {
                    $record->set($field, $field === 'private' ? (int) (bool) $note[$field] : (string) $note[$field]);
                }
            }
            $record->set('createdby', $this->modx->user->id);
            $record->set('createdon', time());
            if (!$record->save()) {
                return $this->failure($this->modx->lexicon('admintools3_notes_err_save'));
            }
        }

        return $this->success();
    }
}

return AdminTools3NotesUploadProcessor::class;
