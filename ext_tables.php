<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'SvenJuergens.BeSearch',
                'system', // Make module a submodule of 'tools'
                'besearch', // Submodule key
                '', // Position
                [
                    'BeSearch' => 'index'
                ],
                [
                    'access' => 'admin',
                    'icon'   => 'EXT:be_search/Resources/Public/Icons/user_mod_besearch.svg',
                    'labels' => 'LLL:EXT:be_search/Resources/Private/Language/locallang_besearch.xlf',
                ]
            );

        }
    }
);
