<?php

use MODX\Revolution\Processors\Processor;

class AdminTools3NotesExportProcessor extends Processor
{
    public function process()
    {
        $count = $this->modx->getCount(\AdminTools3\Model\Note::class, ['private' => 0])
            + $this->modx->getCount(\AdminTools3\Model\Note::class, [
                'private' => 1,
                'createdby' => $this->modx->user->id,
            ]);

        return $count ? $this->success() : $this->failure($this->modx->lexicon('admintools3_no_notes'));
    }
}

return AdminTools3NotesExportProcessor::class;
