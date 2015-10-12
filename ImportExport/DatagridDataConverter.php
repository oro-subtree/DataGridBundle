<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Oro\Bundle\ImportExportBundle\Formatter\DateTimeTypeFormatter;
use Oro\Bundle\ImportExportBundle\Formatter\NumberTypeFormatter;

class DatagridDataConverter implements DataConverterInterface, ContextAwareInterface
{
    /** @var string[] */
    protected static $formatFrontendTypesMap = [
        DateTimeTypeFormatter::TYPE_DATE     => PropertyInterface::TYPE_DATE,
        DateTimeTypeFormatter::TYPE_DATETIME => PropertyInterface::TYPE_DATETIME,
        DateTimeTypeFormatter::TYPE_TIME     => PropertyInterface::TYPE_TIME,
        NumberTypeFormatter::TYPE_DECIMAL    => PropertyInterface::TYPE_DECIMAL,
        NumberTypeFormatter::TYPE_INTEGER    => PropertyInterface::TYPE_INTEGER,
        NumberTypeFormatter::TYPE_PERCENT    => PropertyInterface::TYPE_PERCENT,
        NumberTypeFormatter::TYPE_CURRENCY   => PropertyInterface::TYPE_CURRENCY
    ];

    /**
     * @var ServiceLink
     */
    protected $gridManagerLink;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormatterProvider
     */
    protected $formatterProvider;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var TypeFormatterInterface[]
     */
    protected $formatters = [];

    /**
     * @param ServiceLink         $gridManagerLink
     * @param TranslatorInterface $translator
     * @param FormatterProvider   $formatterProvider
     */
    public function __construct(
        ServiceLink $gridManagerLink,
        TranslatorInterface $translator,
        FormatterProvider $formatterProvider
    ) {
        $this->gridManagerLink   = $gridManagerLink;
        $this->translator        = $translator;
        $this->formatterProvider = $formatterProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        if ($this->context->getValue('columns')) {
            $columns = $this->context->getValue('columns');
        } elseif ($this->context->hasOption('gridName')) {
            $gridName   = $this->context->getOption('gridName');
            $gridConfig = $this->gridManagerLink->getService()->getConfigurationForGrid($gridName);
            $columns    = $gridConfig->offsetGet('columns');
        } else {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export processor must contain "gridName" or "columns" options.'
            );
        }

        $result = [];
        foreach ($columns as $columnName => $column) {
            if (isset($column['renderable']) && false === $column['renderable']) {
                continue;
            }

            $val            = isset($exportedRecord[$columnName]) ? $exportedRecord[$columnName] : null;
            $val            = $this->applyFrontendFormatting($val, $column);
            $label          = $this->translator->trans($column['label']);
            $result[$label] = $val;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        throw new RuntimeException('The convertToImportFormat method is not implemented.');
    }

    /**
     * @param mixed $val
     * @param array $options
     *
     * @return string|null
     */
    protected function applyFrontendFormatting($val, $options)
    {
        if (null !== $val) {
            $frontendType = isset($options['frontend_type']) ? $options['frontend_type'] : null;
            switch ($frontendType) {
                case in_array($frontendType, self::$formatFrontendTypesMap, true):
                    $type      = array_search($frontendType, self::$formatFrontendTypesMap);
                    $formatter = $this->getFormatterForType($type);
                    $val       = $formatter->formatType($val, $type);
                    break;
                case PropertyInterface::TYPE_SELECT:
                    if (isset($options['choices'][$val])) {
                        $val = $this->translator->trans($options['choices'][$val]);
                    }
                    break;
                case PropertyInterface::TYPE_HTML:
                    $val = $this->formatHtmlFrontendType(
                        $val,
                        isset($options['export_type']) ? $options['export_type'] : null
                    );
                    break;
            }
        }

        return $val;
    }

    /**
     * Converts HTML to its string representation
     *
     * @param string $val
     * @param string $exportType
     *
     * @return string
     */
    protected function formatHtmlFrontendType($val, $exportType)
    {
        $result = trim(
            str_replace(
                "\xC2\xA0", // non-breaking space (&nbsp;)
                ' ',
                html_entity_decode(strip_tags($val))
            )
        );
        if ($exportType === 'list') {
            $result = preg_replace('/\s*\n\s*/', ';', $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param string $type
     *
     * @return TypeFormatterInterface
     */
    protected function getFormatterForType($type)
    {
        $formatType = $this->context->getOption(FormatterProvider::FORMAT_TYPE);
        if (isset($this->formatters[$formatType][$type])) {
            return $this->formatters[$formatType][$type];
        }
        $formatter                            = $this->formatterProvider->getFormatterFor($formatType, $type);
        $this->formatters[$formatType][$type] = $formatter;

        return $formatter;
    }
}
