<?php
if (!rex::isBackend() or !rex::getUser()) {
    return;
}

if ($this->getConfig('run_update')) {
    nvModulePresets::generateCss();
    $this->removeConfig('run_update');
}

if (file_exists($this->getAssetsPath("css/novinet.css"))) {
    rex_view::addCssFile($this->getAssetsUrl("css/novinet.css"));
}

if (file_exists($this->getAssetsPath("css/nv_modulepresets.css"))) {
    rex_view::addCssFile($this->getAssetsUrl("css/nv_modulepresets.css"));
}

nvModulePresets::addSlicesFromPreset();

rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', ['nvModulePresets', 'addButtons']);

rex_extension::register('NV_MODULEPREVIEW_MODULESELECT', ['nvModulePresets', 'addPresetsToModuleSelect']);

rex_extension::register('MODULE_DELETED', array('nvModulePresets', 'clearModules'), rex_extension::LATE);

rex_extension::register('NV_MODULEPREVIEW_SHOWSEARCH', function ($ep) {
    $iArticleId = $ep->getParam("article_id");
    $iClangId = $ep->getParam("clang");
    $iCtypeId = $ep->getParam("ctype");
    if (count(nvModulePresets::getPresets($iArticleId, $iClangId, $iCtypeId)) > 0) {
        return true;
    }
}, rex_extension::LATE);
