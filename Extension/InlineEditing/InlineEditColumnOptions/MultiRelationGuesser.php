<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;

/**
 * Class MultiRelationGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class MultiRelationGuesser implements GuesserInterface
{
    const MULTI_RELATION = 'multi-relation';

    const DEFAULT_EDITOR_VIEW = 'orodatagrid/js/app/views/editor/multi-relation-editor-view';
    const DEFAULT_API_ACCESSOR_CLASS = 'oroui/js/tools/search-api-accessor';

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $result = [];
 
        if (array_key_exists(Configuration::FRONTEND_TYPE_NAME, $column) 
            && $column[Configuration::FRONTEND_TYPE_NAME] === self::MULTI_RELATION) {
           $isConfiguredInlineEdit = array_key_exists(Configuration::BASE_CONFIG_KEY, $column);
            $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);
            $result = $this->guessApiAccessorClass($column, $isConfiguredInlineEdit, $result);
        }

        return $result;
    }

    /**
     * @param $column
     * @param $isConfiguredInlineEdit
     * @param $result
     *
     * @return array
     */
    protected function guessEditorView($column, $isConfiguredInlineEdit, $result)
    {
        $editorKey = 'editor';
        $viewKey = 'view';
        $isConfigured = $isConfiguredInlineEdit
            && array_key_exists($editorKey, $column[Configuration::BASE_CONFIG_KEY]);
        $isConfigured = $isConfigured
            && array_key_exists($viewKey, $column[Configuration::BASE_CONFIG_KEY][$editorKey]);
        if (!$isConfigured) {
            $result[Configuration::BASE_CONFIG_KEY][$editorKey][$viewKey] = RelationGuesser::DEFAULT_EDITOR_VIEW;
        }

        return $result;
    }

    /**
     * @param $column
     * @param $isConfiguredInlineEdit
     * @param $result
     *
     * @return array
     */
    protected function guessApiAccessorClass($column, $isConfiguredInlineEdit, $result)
    {
        $apiAccessorKey = 'autocomplete_api_accessor';
        $classKey = 'class';
        $isConfigured = $isConfiguredInlineEdit
            && array_key_exists($apiAccessorKey, $column[Configuration::BASE_CONFIG_KEY]);
        $isConfigured = $isConfigured
            && array_key_exists($classKey, $column[Configuration::BASE_CONFIG_KEY][$apiAccessorKey]);
        if (!$isConfigured) {
            $result[Configuration::BASE_CONFIG_KEY][$apiAccessorKey][$classKey]
                = RelationGuesser::DEFAULT_API_ACCESSOR_CLASS;
        }

        return $result;
    }
}
