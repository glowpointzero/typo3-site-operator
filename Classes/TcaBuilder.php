<?php
namespace Glowpointzero\SiteOperator;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Glowpointzero\SiteOperator\Exception\TcaBuilderException;

class TcaBuilder implements \Psr\Log\LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;

    protected $extensionKey = '';
    protected $modelName = '';
    protected $tableName = '';
    protected $localLangFilePath = '';

    protected static $ITEM_TYPE_COLUMN_PROPERTY = 'columns';
    protected static $ITEM_TYPE_PALETTE_PROPERTY = 'palettes';

    protected $lastAddedItemProperty;   // One of ITEM_TYPE_x_PROPERTY
    protected $lastAddedItemIdentifier; // A string identifying either a column or a palette.

    protected $tabs = [];
    protected $palettes = [];
    protected $columns = [];
    protected $recordTypes = [];
    protected $ctrl = [];
    protected $defaultSortings = [];

    protected $requiredColumnNames;
    protected $nonRequiredColumnNames;

    /**
     * Creates and returns a new instance.
     *
     * @param $extensionKey
     * @param $modelName
     * @param string $tableNameOverride
     * @param string $localLangFilePathOverride
     * @return TcaBuilder
     */
    public static function create(
        $extensionKey,
        $modelName = '',
        $tableNameOverride = '',
        $localLangFilePathOverride = ''
    )
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        /** @var TcaBuilder $instance */
        $instance = $objectManager->get(
            self::class
        );
        $instance->initialize($extensionKey, $modelName, $tableNameOverride, $localLangFilePathOverride);
        $instance->setTitle($modelName);

        return $instance;
    }

    /**
     * TcaBuilder constructor.
     *
     * @param $extensionKey
     * @param $modelName
     * @param string $tableNameOverride
     * @param string $localLangFilePathOverride
     */
    public function initialize($extensionKey, $modelName, $tableNameOverride = '', $localLangFilePathOverride = '')
    {
        $this->extensionKey = $extensionKey;
        $this->modelName = $modelName;
        $tableName =
            'tx_' . strtolower(str_replace('_', '', $extensionKey))
            . '_domain_model_'
            . strtolower($modelName);

        if ($tableNameOverride) {
            $tableName = $tableNameOverride;
        }
        $this->tableName = $tableName;

        $this->localLangFilePath = sprintf('LLL:EXT:%s/Resources/Private/Language/Model/%s', $extensionKey, $modelName);
        if ($localLangFilePathOverride) {
            $this->localLangFilePath = $localLangFilePathOverride;
        }

        $this->setTitle($modelName);
    }

    /**
     * @param $title
     * @param bool $treatAsLocalLangKey
     * @return TcaBuilder
     */
    public function setTitle($title, $treatAsLocalLangKey = true)
    {
        if ($treatAsLocalLangKey && substr($title, 0, 4) !== 'LLL:') {
            $title = $this->localLangFilePath . ':' . $title;
        }
        $this->ctrl['title'] = $title;

        return $this;
    }

    /**
     * Sets the icon file path for the table records.
     *
     * @param $iconFilePath
     * @param string $relativeSubDirectory
     * @return $this
     */
    public function setIcon($iconFilePath, $relativeSubDirectory = 'Resources/Public')
    {
        $pathParts = [
            'EXT:' . $this->extensionKey
        ];
        if ($relativeSubDirectory) {
            $pathParts[] = $relativeSubDirectory;
        }
        $pathParts[] = $iconFilePath;

        $this->ctrl['iconfile'] = implode('/', $pathParts);
        return $this;
    }


    /**
     * Sets one or multiple column names that act as
     * record labels for the table when in list view.
     *
     * @param array $columnNames
     * @return $this
     */
    public function setRecordLabelColumnNames(array $columnNames)
    {
        $this->ctrl['label'] = array_shift($columnNames);

        if (count($columnNames) > 0) {
            $this->ctrl['label_alt'] = implode(', ', $columnNames);
            $this->ctrl['label_alt_force'] = true;
        }

        return $this;
    }

    /**
     * Sets the column name that should act as the 'creation date'.
     *
     * @param null $columnName
     * @return $this
     */
    public function setCreationDateColumnName($columnName)
    {
        $this->ctrl['crdate'] = $columnName;
        return $this;
    }

    /**
     * Sets the column name that should act as the 'updated date'.
     *
     * @param null $columnName
     * @return $this
     */
    public function setTimestampColumnName($columnName)
    {
        $this->ctrl['tstamp'] = $columnName;
        return $this;
    }

    /**
     * Sets the column name that will contain the 'deleted' flag.
     *
     * @param null $columnName
     * @return $this
     */
    public function setDeletedColumnName($columnName)
    {
        $this->ctrl['delete'] = $columnName;
        return $this;
    }

    /**
     * Sets the column name that will contain the 'disabled' flag.
     *
     * @param $columnName
     * @return $this
     */
    public function setDisabledColumnName($columnName)
    {
        if (!is_array($this->ctrl['enablecolumns'])) {
            $this->ctrl['enablecolumns'] = [];
        }
        $this->ctrl['enablecolumns']['disabled'] = $columnName;
        return $this;
    }

    /**
     * Sets the column name to use as a record 'start time' indicator.
     *
     * @param $columnName
     * @return $this
     */
    public function setStartTimeColumnName($columnName)
    {
        if (!is_array($this->ctrl['enablecolumns'])) {
            $this->ctrl['enablecolumns'] = [];
        }
        $this->ctrl['enablecolumns']['starttime'] = $columnName;
        return $this;
    }

    /**
     * Sets the column name to use as a record 'end time' indicator.
     *
     * @param $columnName
     * @return $this
     */
    public function setEndTimeColumnName($columnName)
    {
        if (!is_array($this->ctrl['enablecolumns'])) {
            $this->ctrl['enablecolumns'] = [];
        }
        $this->ctrl['enablecolumns']['endtime'] = $columnName;
        return $this;
    }

    /**
     * Sets the column name that will contain the user id of the user
     * that created the record.
     *
     * @param null $columnName
     * @return $this
     */
    public function setUserIdColumnName($columnName = null)
    {
        $this->ctrl['cruser_id'] = $columnName;
        return $this;
    }

    /**
     * Sets the one or more column names that are searchable.
     *
     * @param array $columnNames
     * @return $this
     */
    public function setSearchColumnNames($columnNames = [])
    {
        $this->ctrl['searchFields'] = implode(', ', $columnNames);
        return $this;
    }

    /**
     * @param $columnName
     * @return $this
     */
    public function setDescriptionColumnName($columnName)
    {
        $this->ctrl['descriptionColumn'] = $columnName;
        return $this;
    }

    /**
     * Adds a default (backend) sorting for the table. May be
     * called multiple times.
     *
     * @param $columnName
     * @param string $direction
     * @return $this
     */
    public function addDefaultSorting($columnName, $direction = 'ASC')
    {
        $this->defaultSortings[$columnName] = $direction;
        return $this;
    }


    /**
     * Sets a list of column names whose fields are required to
     * be filled in. If most fields are required, consider using
     * $this->setNonRequiredColumnNames(), which may be more
     * efficient in these cases.
     *
     * @see setNonRequiredColumnNames()
     * @param array $columnNames
     * @return $this
     */
    public function setRequiredColumnNames(array $columnNames)
    {
        $this->requiredColumnNames = $columnNames;
        return $this;
    }

    /**
     * Sets a list of column names whose fields are *not* required to
     * be filled in. This is the inverse method of setNonRequiredColumnNames()
     * On generating the TCA, this value will be 'inverted' to feature
     * all required column names in the end.
     *
     * @see setRequiredColumnNames
     * @param array $columnNames
     * @return $this
     */
    public function setNonRequiredColumnNames(array $columnNames)
    {
        $this->nonRequiredColumnNames = $columnNames;
        return $this;
    }

    /**
     * Adds a simple one-liner input field column.
     *
     * @param $columnName
     * @return $this
     */
    public function addSingleLineInputColumn($columnName)
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 200,
                'eval' => 'trim'
            ],
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * Adds a multi line (textarea) input column to the TCA.
     *
     * @param $columnName
     * @return $this
     */
    public function addMultiLineInputColumn($columnName)
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 10,
                'eval' => 'trim'
            ],
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * Adds a one-line input field featuring a link selector to the TCA.
     *
     * @param string $columnName
     * @return $this
     */
    public function addLinkColumn($columnName)
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
            ],
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * Adds a date column to the TCA.
     *
     * @param $columnName
     * @return TcaBuilder
     * @throws TcaBuilderException
     */
    public function addDateColumn($columnName)
    {
        return $this->addDateTimeColumn($columnName)
            ->andOverruleConfigurationWith([
                'config' => [
                    'renderType' => 'inputDateTime',
                    'eval' => 'date'
                ]
            ]);
    }

    /**
     * Adds a time column to the TCA.
     *
     * @param $columnName
     * @return TcaBuilder
     * @throws TcaBuilderException
     */
    public function addTimeColumn($columnName)
    {
        return $this->addDateTimeColumn($columnName)
            ->andOverruleConfigurationWith([
                'config' => [
                    'renderType' => 'inputDateTime',
                    'eval' => 'time'
                ]
            ]);
    }

    /**
     * Adds a date + time column to the TCA.
     *
     * @param $columnName
     * @return $this
     */
    public function addDateTimeColumn($columnName)
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime'
            ],
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * Adds a checkbox column to the current TCA.
     *
     * @param $columnName
     * @param array $valueIds An array of values to be used for all the checkboxes, if multiple checkboxes are present.
     * @return $this
     */
    public function addCheckboxColumn($columnName, $valueIds = [])
    {
        if (count($valueIds) === 0) {
            $valueIds = [0];
        }
        $items = [];
        foreach ($valueIds as $checkboxValue) {
            $label  = $this->getColumnLabel($columnName, '.' . $checkboxValue);
            $items[] = [$label, $checkboxValue];
        }
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'type' => 'check',
                'items' => $items
            ]
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;
        
        return $this;
    }

    /**
     * Basically adds a 'checkbox', but displays it as a toggle UI element.
     *
     * @param $columnName
     * @return $this
     * @throws TcaBuilderException
     */
    public function addToggleColumn($columnName)
    {
        $this->addCheckboxColumn($columnName)
            ->andOverruleConfigurationWith([
                'config' => [
                    'renderType' => 'checkboxToggle'
                ]
            ]);

        return $this;
    }

    /**
     * Adds a single record reference column. Not to be confused
     * with 'addRelationColumn', which may contain multiple references.
     *
     * @todo Make this more obvious to the developer.
     *
     * @param string $columnName
     * @param string $recordTableNames
     * @return $this
     */
    public function addRecordReferenceColumn($columnName, $recordTableNames)
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'allowed' => $recordTableNames,
                'type' => 'group',
                'internal_type' => 'db',
                'maxitems' => 1,
                'minitems' => 0,
                'size' => 1
            ],
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * Adds a record relation column to the TCA.
     *
     * @param $columnName
     * @param $relatedTableName
     * @return $this
     */
    public function addRelationColumn($columnName, $relatedTableName)
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => $relatedTableName,
                'size' => 5,
                'enableMultiSelectFilterTextfield' => true
            ]
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * @return $this
     */
    public function addCategoryColumn($columnName)
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName)
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
            $this->extensionKey,
            $this->tableName,
            $columnName
        );

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * Adds a select field column to the TCA.
     *
     * @param string $columnName
     * @param array $options Single-level array of option values
     * @return $this
     */
    public function addSelectColumn($columnName, $options)
    {
        $items = [];
        foreach ($options as $optionValue) {
            $items[] = [
                $this->getColumnLabel($columnName, '.' . $optionValue),
                $optionValue
            ];
        }
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => $items
            ]
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * @param $columnName
     * @param string $fileExtensions
     * @return $this
     */
    public function addFileColumn($columnName, $fileExtensions = '')
    {
        $columnConfiguration = [
            'label' => $this->getColumnLabel($columnName),
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                $columnName,
                [
                    'overrideChildTca' => [
                        'types' => $GLOBALS['TCA']['tt_content']['columns']['image']['config']['overrideChildTca']['types']
                    ]
                ],
                $fileExtensions
            )
        ];

        $this->{self::$ITEM_TYPE_COLUMN_PROPERTY}[$columnName] = $columnConfiguration;
        $this->lastAddedItemIdentifier = $columnName;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_COLUMN_PROPERTY;

        return $this;
    }

    /**
     * Overrides the last added item (column, tab, palette...) with the
     * given configuration.
     *
     * @param array $overridingConfiguration
     * @return $this
     * @throws TcaBuilderException
     */
    public function andOverruleConfigurationWith(array $overridingConfiguration)
    {
        $this->validateLastAddedItem();

        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
            $this->{$this->lastAddedItemProperty}[$this->lastAddedItemIdentifier],
            $overridingConfiguration
        );

        return $this;
    }

    /**
     * Adds the last added item to the list of required columns.
     *
     * @return $this
     * @throws TcaBuilderException
     */
    public function andMakeItRequired()
    {
        $this->validateLastAddedItem();
        if ($this->lastAddedItemProperty !== self::$ITEM_TYPE_COLUMN_PROPERTY) {
            throw new TcaBuilderException(
                sprintf('Can\'t make items of type "%s" required.', $this->lastAddedItemProperty),
                1545380424
            );
        }
        $this->requiredColumnNames[] = $this->lastAddedItemIdentifier;
        return $this;
    }

    /**
     * Makes sure that an item (column or palette)
     * has been added 'correctly', meaning the last
     * added item is of type 'palette' or 'column'
     * This method may be called before further
     * attempted manipulation of the previously added
     * item.
     *
     * @throws TcaBuilderException
     */
    protected function validateLastAddedItem()
    {
        if (!$this->lastAddedItemProperty) {
            throw new TcaBuilderException(
                'Missing "lastAddedItemType" property. Maybe no columns, tabs, etc. have been added yet?',
                1545380279
            );
        }

        if (!$this->lastAddedItemIdentifier) {
            throw new TcaBuilderException(
                'Missing "lastAddedItemIdentifier" property. This probably means that the most recently added item does not set the identifier correctly.',
                1545385238
            );
        }

        if (!array_key_exists($this->lastAddedItemIdentifier, $this->{$this->lastAddedItemProperty})) {
            throw new TcaBuilderException(
                sprintf('The item having the id "%s" does not exist in the property "%s".', $this->lastAddedItemIdentifier, $this->lastAddedItemProperty),
                1545380424
            );
        }
    }

    /**
     * Adds the last added column to the given palette id.
     *
     * @param string $paletteId
     * @param bool $lineBreakAfter
     * @return $this
     */
    public function toPalette($paletteId, $lineBreakAfter = false)
    {
        $palettes = &$this->{self::$ITEM_TYPE_PALETTE_PROPERTY};

        if (!array_key_exists($paletteId, $palettes)) {
            $palettes[$paletteId] = [];
        }
        if (in_array($this->lastAddedItemIdentifier, $palettes[$paletteId])) {
            return $this;
        }

        // @todo Check the added item type and throw exception, if it is not a column.

        $palettes[$paletteId][] = $this->lastAddedItemIdentifier;
        
        if ($lineBreakAfter) {
            $palettes[$paletteId][] = '--linebreak--';
        }

        $this->lastAddedItemIdentifier = $paletteId;
        $this->lastAddedItemProperty = self::$ITEM_TYPE_PALETTE_PROPERTY;

        return $this;
    }

    /**
     * Adds the last added palette to the given tab id.
     *
     * Note that unlike
     *
     * @param string $tabId
     * @return $this
     */
    public function toTab($tabId)
    {
        // @todo Check the added item type and throw exception, if it is not a palette or a column.

        $itemToAdd = $this->lastAddedItemIdentifier;
        if ($this->lastAddedItemProperty === self::$ITEM_TYPE_PALETTE_PROPERTY) {
            $itemToAdd = '--palette--;;' . $itemToAdd;
        }

        if (!array_key_exists($tabId, $this->tabs)) {
            $this->tabs[$tabId] = [];
        }
        if (in_array($itemToAdd, $this->tabs[$tabId])) {
            return $this;
        }

        $this->tabs[$tabId][] = $itemToAdd;
        return $this;
    }


    /**
     * Adds a 'recordType' to the TCA featuring the whole 'showitem' string.
     *
     * @param string $typeId
     * @param array $shownItems
     * @return $this
     */
    public function setElementsForRecordType($typeId, $shownItems)
    {
        $generatedShowItems = [];
        foreach ($shownItems as $itemId) {

            if (array_key_exists($itemId, $this->tabs)) {
                $generatedShowItems[] = $this->generateShowItem([$itemId]);
                continue;
            }
            if (array_key_exists($itemId, $this->{self::$ITEM_TYPE_PALETTE_PROPERTY})) {
                $generatedShowItems[] = $this->generateShowItem([], [$itemId]);
                continue;
            }
            if (array_key_exists($itemId, $this->{self::$ITEM_TYPE_COLUMN_PROPERTY})) {
                $generatedShowItems[] = $this->generateShowItem([], [], [$itemId]);
                continue;
            }
        }

        $this->recordTypes[$typeId] = [
            'showitem' => implode(',', $generatedShowItems)
        ];

        return $this;
    }


    /**
     * Generates the 'showitem' string given the tab, palette and column ids ("-names")
     * that have been configured previously.
     *
     * @param array $tabIds
     * @param array $paletteIds
     * @param array $columnNames
     * @return string
     */
    public function generateShowItem($tabIds = [], $paletteIds = [], $columnNames = [])
    {
        $tabConfigurations = $this->tabs;
        $columnConfigurations = $this->{self::$ITEM_TYPE_COLUMN_PROPERTY};
        $paletteConfigurations = $this->{self::$ITEM_TYPE_PALETTE_PROPERTY};
        $showItemElements = [];
        $palettesAndFieldsAlreadyUsed = [];

        foreach ($tabIds as $tabId) {
            if (!array_key_exists($tabId, $tabConfigurations)) {
                $this->logger->error(sprintf('[%s]: No configuration for tab "%s" found.', self::class, $tabId));
                continue;
            }
            $showItemElements[] = sprintf('--div--;%s', $this->getPropertyGroupLabel($tabId));
            $showItemElements[] = implode(', ', $tabConfigurations[$tabId]);
            $palettesAndFieldsAlreadyUsed = array_merge($palettesAndFieldsAlreadyUsed, $tabConfigurations[$tabId]);
        }

        foreach ($paletteIds as $paletteId) {
            if (!array_key_exists($paletteId, $paletteConfigurations)) {
                $this->logger->error(sprintf('[%s]: No configuration for palette "%s" found.', self::class, $paletteId));
                continue;
            }
            $paletteString = sprintf('--palette--;;%s', $paletteId);
            if (in_array($paletteString, $palettesAndFieldsAlreadyUsed)) {
                // Add fields of this palette to the 'already used' list.
                $palettesAndFieldsAlreadyUsed = array_merge($palettesAndFieldsAlreadyUsed, $paletteConfigurations[$paletteId]);
                continue;
            }
            $showItemElements[] = $paletteString;

            $palettesAndFieldsAlreadyUsed[] = $paletteString;
            $palettesAndFieldsAlreadyUsed = array_merge($palettesAndFieldsAlreadyUsed, $paletteConfigurations[$paletteId]);
        }

        foreach ($columnNames as $columnName) {
            if (!array_key_exists($columnName, $columnConfigurations)) {
                $this->logger->error(sprintf('[%s]: No configuration for column "%s" found.', self::class, $columnName));
                continue;
            }
            if (in_array($columnName, $palettesAndFieldsAlreadyUsed)) {
                continue;
            }
            $showItemElements[] = $columnName;
        }

        return implode(', ', $showItemElements);
    }

    /**
     * Creates the supposed label path based on the column name and the
     * previously initialized 'localLangFilePath'.
     *
     * @param $columnName
     * @param string $labelIdSuffix
     * @return string
     */
    protected function getColumnLabel($columnName, $labelIdSuffix = '')
    {
        return sprintf(
            '%s:%s%s',
            $this->localLangFilePath,
            GeneralUtility::underscoredToLowerCamelCase($columnName),
            $labelIdSuffix
        );
    }

    /**
     * Creates the supposed label path based on a palette or tab id.
     * This is *not* called 'getTabLabel' or 'getPaletteLabel', as
     * this terminology is specific to the TYPO3 (!) backend (!)
     * and there must be a broader, more meaningful and general
     * terminology.
     *
     * @param $labelId
     * @return string
     */
    protected function getPropertyGroupLabel($labelId)
    {
        return sprintf(
            '%s:propertyGroup.%s',
            $this->localLangFilePath,
            $labelId
        );
    }

    /**
     * Exports the current configuration into a valid 'TCA' array.
     *
     * @return array
     */
    public function toArray()
    {
        $tca = [
            'ctrl' => $this->ctrl,
            'columns' => $this->columns,
            'interface' => [
                'showRecordFieldList' => implode(', ', array_keys($this->columns))
            ],
            'types' => [],
            'palettes' => [],
        ];

        // Add 'required' to the 'eval' section of all fields as
        // defined by $this->requiredColumnNames and/or $this->nonRequiredColumnNames
        $requiredColumns = is_array($this->requiredColumnNames) ? $this->requiredColumnNames : [];
        if (is_array($this->nonRequiredColumnNames)) {
            $allColumns = array_keys($this->{self::$ITEM_TYPE_COLUMN_PROPERTY});
            $requiredColumns = array_diff($allColumns, $this->nonRequiredColumnNames);
        }
        foreach ($requiredColumns as $requiredColumn) {
            if (!isset($tca['columns'][$requiredColumn])) {
                $this->logger->error(sprintf('[%s]: Required column "%s" not found.', self::class, $requiredColumn));
                continue;
            }
            if (in_array($tca['columns'][$requiredColumn]['config']['type'], ['select', 'group'])) {
                if (!isset($tca['columns'][$requiredColumn]['config']['minitems'])) {
                    $tca['columns'][$requiredColumn]['config']['minitems'] = 1;
                }
                if ($tca['columns'][$requiredColumn]['config']['minitems'] < 1) {
                    $tca['columns'][$requiredColumn]['config']['minitems'] = 1;
                }
                continue;
            }

            if (!isset($tca['columns'][$requiredColumn]['config']['eval'])) {
                $tca['columns'][$requiredColumn]['config']['eval'] = '';
            }
            $evalValues = GeneralUtility::trimExplode(',', $tca['columns'][$requiredColumn]['config']['eval']);
            $evalValues[] = 'required';
            $tca['columns'][$requiredColumn]['config']['eval'] = implode(',', array_unique($evalValues));
        }

        if ($this->defaultSortings) {
            $tca['ctrl']['default_sortby'] = '';
            foreach ($this->defaultSortings as $column => $direction) {
                $tca['ctrl']['default_sortby'] .= $column . ' ' . $direction . ', ';
            }
        }

        foreach ($this->palettes as $paletteId => $columns) {
            $tca['palettes'][$paletteId] = [
                'label' => $this->getPropertyGroupLabel($paletteId),
                'showitem' => implode(', ', $columns)
            ];
        }

        foreach ($this->recordTypes as $recordTypeId => $recordTypeConfiguration)
        {
            $tca['types'][$recordTypeId] = $recordTypeConfiguration;
        }

        // Add a default record type featuring all columns / palettes, etc. if none
        // has been added explicitly.
        if (count($tca['types']) === 0) {
            $tca['types']['0']['showitem'] = $this->generateShowItem(
                array_keys($this->tabs),
                array_keys($this->{self::$ITEM_TYPE_PALETTE_PROPERTY}),
                array_keys($this->{self::$ITEM_TYPE_COLUMN_PROPERTY})
            );
        }
        return $tca;
    }
}
