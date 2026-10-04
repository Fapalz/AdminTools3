<?php

use MODX\Revolution\modCategory;
use MODX\Revolution\modChunk;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\Processors\Processor;

class AdminTools3FavoriteCategoriesProcessor extends Processor
{
    public function checkPermissions()
    {
        return $this->modx->hasPermission('element_tree');
    }

    public function process()
    {
        $corePath = $this->modx->getOption(
            'admintools3_core_path',
            null,
            $this->modx->getOption('core_path') . 'components/admintools3/'
        );
        $service = $this->modx->getService('admintools3', 'AdminTools3', rtrim($corePath, '/\\') . '/services/');
        if (!$service) {
            return $this->failure('AdminTools3 service is unavailable.');
        }

        $favorites = $service->getFromProfile('adminTools3Elements');
        if (!is_array($favorites)) {
            $favorites = [];
        }

        $types = [
            'template' => ['templates', modTemplate::class],
            'tv' => ['tvs', modTemplateVar::class],
            'chunk' => ['chunks', modChunk::class],
            'snippet' => ['snippets', modSnippet::class],
            'plugin' => ['plugins', modPlugin::class],
        ];
        $result = [];
        $categoryCache = [];

        foreach ($types as $type => [$key, $class]) {
            $visibleCategories = [];
            foreach (($favorites[$key] ?? []) as $elementId) {
                $element = $this->modx->getObject($class, (int) $elementId);
                if (!$element || !$element->checkPolicy('list')) {
                    continue;
                }

                $categoryId = (int) $element->get('category');
                $visited = [];
                while ($categoryId > 0 && !isset($visited[$categoryId])) {
                    $visited[$categoryId] = true;
                    if (!array_key_exists($categoryId, $categoryCache)) {
                        $categoryCache[$categoryId] = $this->modx->getObject(modCategory::class, $categoryId);
                    }
                    $category = $categoryCache[$categoryId];
                    if (!$category || !$category->checkPolicy('list')) {
                        break;
                    }
                    $visibleCategories[$categoryId] = $categoryId;
                    $categoryId = (int) $category->get('parent');
                }
            }
            $result[$type] = array_values($visibleCategories);
        }

        return $this->success('', $result);
    }
}

return AdminTools3FavoriteCategoriesProcessor::class;
