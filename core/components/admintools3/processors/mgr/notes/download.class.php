<?php

use MODX\Revolution\Processors\Processor;

class AdminTools3NotesDownloadProcessor extends Processor
{
    public function process()
    {
        $query = $this->modx->newQuery(\AdminTools3\Model\Note::class);
        $query->where('(Note.private = 0 OR Note.createdby = ' . (int) $this->modx->user->id . ')');
        $rows = [];
        foreach ($this->modx->getIterator(\AdminTools3\Model\Note::class, $query) as $note) {
            $rows[] = $note->toArray('', false, true);
        }

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="admintools3-notes.json"');
        header('Cache-Control: no-store');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

return AdminTools3NotesDownloadProcessor::class;
