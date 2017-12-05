
module.tx_besearch_besearch {
    view {
        # cat=module.tx_besearch_besearch/file; type=string; label=Path to template root (BE)
        templateRootPath = EXT:be_search/Resources/Private/Backend/Templates/
        # cat=module.tx_besearch_besearch/file; type=string; label=Path to template partials (BE)
        partialRootPath = EXT:be_search/Resources/Private/Backend/Partials/
        # cat=module.tx_besearch_besearch/file; type=string; label=Path to template layouts (BE)
        layoutRootPath = EXT:be_search/Resources/Private/Backend/Layouts/
    }
    persistence {
        # cat=module.tx_besearch_besearch//a; type=string; label=Default storage PID
        storagePid =
    }
}
