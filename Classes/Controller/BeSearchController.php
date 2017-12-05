<?php
namespace SvenJuergens\BeSearch\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Lang\LanguageService;

class BeSearchController extends ActionController
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\IconFactory
     */
    protected $iconFactory;

    protected $sword;

    protected $excludeTables = 'sys_log,sys_history,tx_extensionmanager_domain_model_repository';

    /**
     * @var LanguageService
     */
    protected $languageService;


    public function initializeIndexAction()
    {
        $this->languageService = $GLOBALS['LANG'];
        $this->languageService->includeLLFile('EXT:lang/Resources/Private/Language/locallang_t3lib_fullsearch.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $arguments = $this->request->getArguments();
        if (\is_array($arguments) && isset($arguments['sword']) && !empty($arguments['sword'])) {
            $this->sword = htmlspecialchars($arguments['sword']);
        }
    }

    public function indexAction()
    {
        $items = null;
        if ($this->sword !== null) {
            $items = $this->search();
        }
        $this->view->assignMultiple([
            'items' => $items,
            'excludeTables' => $this->excludeTables
        ]);
    }

    /**
     * Search
     *
     * @return string
     */
    public function search()
    {
        $swords = $this->sword;
        $out = '';
        if ($swords) {
            foreach ($GLOBALS['TCA'] as $table => $value) {
                if (GeneralUtility::inList($this->excludeTables, $table)) {
                    continue;
                }

                // Get fields list
                $conf = $GLOBALS['TCA'][$table];
                // Avoid querying tables with no columns
                if (empty($conf['columns'])) {
                    continue;
                }
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
                $tableColumns = $connection->getSchemaManager()->listTableColumns($table);
                $fieldsInDatabase = [];
                foreach ($tableColumns as $column) {
                    $fieldsInDatabase[] = $column->getName();
                }
                $fields = array_intersect(array_keys($conf['columns']), $fieldsInDatabase);

                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $queryBuilder->count('*')->from($table);
                $likes = [];
                $excapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($swords) . '%';
                foreach ($fields as $field) {
                    $likes[] = $queryBuilder->expr()->like(
                        $field,
                        $queryBuilder->createNamedParameter($excapedLikeString, \PDO::PARAM_STR)
                    );
                }
                $count = $queryBuilder->orWhere(...$likes)->execute()->fetchColumn(0);

                if ($count > 0) {
                    $queryBuilder = $connection->createQueryBuilder();
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $queryBuilder->select('uid', $conf['ctrl']['label'])
                        ->from($table)
                        ->setMaxResults(200);
                    $likes = [];
                    foreach ($fields as $field) {
                        $likes[] = $queryBuilder->expr()->like(
                            $field,
                            $queryBuilder->createNamedParameter($excapedLikeString, \PDO::PARAM_STR)
                        );
                    }
                    $statement = $queryBuilder->orWhere(...$likes)->execute();
                    $lastRow = null;
                    $rowArr = [];
                    while ($row = $statement->fetch()) {
                        $rowArr[] = $this->resultRowDisplay($row, $conf, $table);
                        $lastRow = $row;
                    }
                    $markup = [];
                    $markup[] = '<div class="panel panel-default">';
                    $markup[] = '  <div class="panel-heading">';
                    $markup[] = htmlspecialchars($this->languageService->sL($conf['ctrl']['title'])) . ' (' . $count . ')';
                    $markup[] = '  </div>';
                    $markup[] = '  <table class="table table-striped table-hover">';
                    $markup[] = $this->resultRowTitles($lastRow, $conf, $table);
                    $markup[] = implode(LF, $rowArr);
                    $markup[] = '  </table>';
                    $markup[] = '</div>';

                    $out .= implode(LF, $markup);
                }
            }
        }
        return $out;
    }

    /**
     * Result row display
     *
     * @param array $row
     * @param array $conf
     * @param string $table
     * @return string
     */
    public function resultRowDisplay($row, $conf, $table): string
    {
        $out = '<tr>';
        foreach ($row as $fieldName => $fieldValue) {
            if ($fieldName !== 'pid' && $fieldName !== 'deleted') {
                $out .= '<td>' . htmlspecialchars($fieldValue) . '</td>';
            }
        }
        $out .= '<td>';
        if (!$row['deleted']) {
            $out .= '<div class="btn-group" role="group">';
            $url = BackendUtility::getModuleUrl('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    . GeneralUtility::implodeArrayForUrl('SET', (array)GeneralUtility::_POST('SET'))
            ]);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url) . '">'
                . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
            $out .= '</div><div class="btn-group" role="group">';
            $out .= '<a class="btn btn-default" href="#" onClick="top.launchView(\'' . $table . '\',' . $row['uid']
                . ');return false;">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render()
                . '</a>';
            $out .= '</div>';
        } else {
            $out .= '<div class="btn-group" role="group">';
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db', [
                    'cmd' => [
                        $table => [
                            $row['uid'] => [
                                'undelete' => 1
                            ]
                        ]
                    ],
                    'redirect' => GeneralUtility::linkThisScript()
                ])) . '" title="' . htmlspecialchars($this->languageService->getLL('undelete_only')) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore', Icon::SIZE_SMALL)->render() . '</a>';
            $formEngineParameters = [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::linkThisScript()
            ];
            $redirectUrl = BackendUtility::getModuleUrl('record_edit', $formEngineParameters);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db', [
                    'cmd' => [
                        $table => [
                            $row['uid'] => [
                                'undelete' => 1
                            ]
                        ]
                    ],
                    'redirect' => $redirectUrl
                ])) . '" title="' . htmlspecialchars($this->languageService->getLL('undelete_and_edit')) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore-edit', Icon::SIZE_SMALL)->render() . '</a>';
            $out .= '</div>';
        }
        $out .= '</td></tr>';
        return $out;
    }

    /**
     * Render table header
     *
     * @param array $row Table columns
     * @param array $conf Table TCA
     * @param string $table Table name
     * @return string HTML of table header
     */
    public function resultRowTitles($row, $conf, $table)
    {
        $tableHeader = [];
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        foreach ($row as $fieldName => $fieldValue) {
            if ($fieldName !== 'pid'&& $fieldName !== 'deleted') {
                $title = htmlspecialchars($this->languageService->sL($fieldName));
                $tableHeader[] = '<th>' . $title . '</th>';
            }
        }
        // Add empty icon column
        $tableHeader[] = '<th></th>';
        // Close header row
        $tableHeader[] = '</tr></thead>';
        return implode(LF, $tableHeader);
    }
}
