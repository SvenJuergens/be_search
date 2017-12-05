
# Module configuration
module.tx_besearch_tools_besearchbesearch {
    persistence {
        storagePid = {$module.tx_besearch_besearch.persistence.storagePid}
    }
    view {
        templateRootPaths.0 = EXT:be_search/Resources/Private/Templates/
        templateRootPaths.1 = {$module.tx_besearch_besearch.view.templateRootPath}
        partialRootPaths.0 = EXT:be_search/Resources/Private/Partials/
        partialRootPaths.1 = {$module.tx_besearch_besearch.view.partialRootPath}
        layoutRootPaths.0 = EXT:be_search/Resources/Private/Layouts/
        layoutRootPaths.1 = {$module.tx_besearch_besearch.view.layoutRootPath}
    }
}
