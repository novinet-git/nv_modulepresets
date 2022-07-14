<?php class nvModulePresets
{
    public static function getAddon()
    {
        return rex_addon::get('nv_modulepresets');
    }

    public static function addButtons(rex_extension_point $ep)
    {
        $sFunction = rex_request('function', 'string', null);
        $iSliceId = rex_request('slice_id', 'int', null);

        if (rex_be_controller::getCurrentPage() != "content/edit" or ($iSliceId && $iSliceId == $ep->getParam('slice_id') && ($sFunction == "add" or $sFunction == "edit"))) {
            return;
        }

        $aModules = array();
        $aModules[$ep->getParam("module_id")] = "modul";
        $aModules = nvModulepreview::getAvailableModules($aModules, $ep->getParam('article_id'), $ep->getParam('clang'), $ep->getParam('ctype'));

        if (!isset($aModules[$ep->getParam("module_id")])) {
            return;
        }

        if (!rex::getUser()->hasPerm('nv_modulepresets_edit')) {
            return;
        }

        static::addButton($ep, [
            'hidden_label' => 'Als Vorlage speichern',
            'url' => rex_url::backendController([
                'page' => 'nv_modulepresets/',
                'func' => 'add',
                'article_id' => $ep->getParam('article_id'),
                'module_id' => $ep->getParam('module_id'),
                'slice_id' => $ep->getParam('slice_id'),
                'clang' => $ep->getParam('clang'),
                'ctype' => $ep->getParam('ctype')
            ]),
            'attributes' => [
                'class' => ['btn-default nv-modulepreview'],
                'title' => 'Als Vorlage speichern',
                'data-pjax-no-history' => 'true',
            ],
            'icon' => 'package-addon',
        ]);
    }

    public static function addButton(rex_extension_point $ep, array $btn)
    {
        $items = (array) $ep->getSubject();
        $items[] = $btn;
        $ep->setSubject($items);
    }

    public static function addPresetsToModuleSelect(rex_extension_point $ep)
    {
        #$slice_id = static::getCookie('slice_id', 'int', null);
        #$clang = static::getCookie('clang', 'int', null);
        #$revision = static::getCookie('revision', 'int', 0);
        #$action = static::getCookie('action', 'string', null);

        $sSubject = $ep->getSubject();

        $context = new rex_context([
            'page' => rex_be_controller::getCurrentPage(),
            'article_id' => $ep->getParam("article_id"),
            'clang' => $ep->getParam("clang"),
            'ctype' => $ep->getParam("ctype"),
            'category_id' => $ep->getParam("category"),
            'function' => 'add',
            'action' => 'addfrommodulepreset'
        ]);

        $sHtml = '';

        $aPresets = self::getPresets($ep->getParam("article_id"), $ep->getParam("clang"), $ep->getParam("ctype"));

        if (count($aPresets) > "0") {

            $sHtmlTabs = '<div class="container">';
            $sHtmlTabs .= '<ul class="nav nav-tabs tab-nav" role="tablist" id="nv-modulepreview-tabs">';
            $sHtmlTabs .= '<li class="active">';
            $sHtmlTabs .= '<a href="#nv-modulepreview-tab-modules" aria-controls="nv-modulepreview-tab-modules" role="tab" data-toggle="tab" aria-expanded="true">Module</a>';
            $sHtmlTabs .= '</li>';
            $sHtmlTabs .= '<li>';
            $sHtmlTabs .= '<a href="#nv-modulepreview-tab-presets" aria-controls="nv-modulepreview-tab-presets" role="tab" data-toggle="tab">Vorlagen</a>';
            $sHtmlTabs .= '</li>';
            $sHtmlTabs .= '</ul>';
            $sHtmlTabs .= '</div>';


            $sHtml .= '<!-- tab-content-modulepresets start -->';
            $sHtml .= '<div role="tabpanel" class="tab-pane fade" id="nv-modulepreview-tab-presets">';
            $sHtml .= '<!-- nv-modale-list-presets start --><ul class="module-list">';

            $sShowAsList = "";
            if (rex_config::get('nv_modulepreview', 'show_as_list')) {
                $sShowAsList = "large nv-show-as-list";
            }

            foreach ($aPresets as $iPresetId) {
                $oItem = rex_sql::factory();
                $oItem->setQuery("SELECT * FROM " . rex::getTable("nv_modulepresets") . " WHERE id = :preset_id", ["preset_id" => $iPresetId]);

                $sql = rex_sql::factory();
                $sql->setTable(rex::getTable('module'));
                $sql->setWhere(['id' => $oItem->getValue("module_id")]);
                $sql->select();

                $context->setParam('preset_id', $oItem->getValue("id"));
                $sHtml .= '<li class="card column ' . $sShowAsList . '">';
                $sHtml .= '<a href="' . $context->getUrl(['module_id' => $oItem->getValue("module_id")]) . '" data-href="' . $context->getUrl(['module_id' => $oItem->getValue("module_id")]) . '" class="module" data-name="' . $oItem->getValue("module_id") . '.jpg" data-category="from_modulepreset">';
                $sHtml .= '<div class="header">';

                $sHtml .= '<span>' . $oItem->getValue("title") . ' (Modul: ' . $sql->getValue("name") . ')</span>';
                $sHtml .= '</div>';

                $fileUrl = rex_url::addonAssets('nv_modulepresets', 'images/na.png');
                if ($oItem->getValue('thumbnail')) {
                    //$fileUrl = '/media/nv_modulepreview/' . $oItem->getValue('thumbnail');
                    $fileUrl = rex_url::addonAssets('nv_modulepresets', 'images/thumbnails/' . $oItem->getValue('thumbnail'));
                }
                $thumbnail = '<img src=\'' . $fileUrl . '\' alt=\'Thumbnail ' . $oItem->getValue('thumbnail') . '\'>';

                $sHideImages = "nv-hide-images";
                if (!rex_config::get('nv_modulepreview', 'hide_images')) {
                    $sHtml .= '<div class="image"><div>';
                    $sHtml .= $thumbnail;
                    $sHtml .= '</div></div>';
                    $sHideImages = "";
                }

                if ($oItem->getValue('description')) {
                    $sHtml .= '<div class="' . $sHideImages . '">';
                    $sHtml .= '<div class="description ">' . $oItem->getValue('description') . '</div>';
                    $sHtml .= '</div>';
                }

                $sHtml .= '</a>';
                $sHtml .= '</li>';
            }
        }

        $sHtml .= '</ul><!-- nv-modale-list-presets end -->';
        $sHtml .= '</div>';
        $sHtml .= '<!-- tab-content-modulepresets end -->';



        $sSubject = str_replace("<!-- nv-modal-header-end -->", "<!-- nv-modal-header-end -->" . $sHtmlTabs, $sSubject);
        $sSubject = str_replace("<!-- tab-content-modules end -->", "<!-- tab-content-modules end -->" . $sHtml, $sSubject);



        return $sSubject;
    }

    public static function addSlicesFromPreset()
    {

        $iPresetId = rex_request('preset_id', 'int', null);
        $sAction = rex_request('action', 'string', null);

        if (!$iPresetId or $sAction != "addfrommodulepreset") {
            return;
        }


        $oPreset = rex_sql::factory();
        $oPreset->setQuery("SELECT * FROM " . rex::getTable("nv_modulepresets") . " WHERE id = :id Limit 1", ["id" => $iPresetId]);
        if (!$oPreset->getRows()) {
            return;
        }

        $aProperties = json_decode($oPreset->getValue("properties"), 1);
        $_NEW_REQUEST = [
            'save' => '1',
        ];

        $request = ['value' => 20, 'media' => 10, 'medialist' => 10, 'link' => 10, 'linklist' => 10];
        foreach ($request as $key => $max) {
            $_NEW_REQUEST['REX_INPUT_' . strtoupper($key)] = [];

            for ($i = 1; $i <= $max; ++$i) {
                $_NEW_REQUEST['REX_INPUT_' . strtoupper($key)][$i] = $aProperties[$key . "_" . $i];
            }
            unset($i);
        }
        unset($max, $key, $request);
        $_POST = array_replace($_POST, [
            'module_id' => $oPreset->getValue("module_id"),
        ]);
        $_REQUEST = array_replace($_REQUEST, $_NEW_REQUEST);
    }

    public static function getPresets(int $iArticleId, int $iClangId, int $iCtypeId)
    {

        $module = rex_sql::factory();
        $aModules = array();

        $modules = $module->getArray('select * from ' . rex::getTablePrefix() . 'module order by name');
        foreach ($modules as $aItem) {
            $iId = $aItem["id"];

            $aModules[$iId] = array(
                "id" => $aItem["id"],
                "name" => $aItem["name"],
            );
        }
        $aModules = nvModulepreview::getAvailableModules($aModules, $iArticleId, $iClangId, $iCtypeId);

        $aArr = array();

        $oDb = rex_sql::factory();
        $oDb->setQuery("SELECT * FROM " . rex::getTable("nv_modulepresets") . " WHERE status = '1' ORDER BY prio ASC");
        if ($oDb->getRows()) {
            foreach ($oDb as $oItem) {
                if ($aModules[$oItem->getValue("module_id")]) {
                    $aArr[] = $oItem->getValue("id");
                }
            }
        }

        return $aArr;
    }

    public static function generateCss()
    {
        $oAddon = self::getAddon();
        $compiler = new rex_scss_compiler();
        $compiler->setRootDir($oAddon->getPath('scss/'));
        $compiler->setScssFile([$oAddon->getPath("scss/nv_modulepresets.scss")]);
        $compiler->setCssFile($oAddon->getAssetsPath('css/nv_modulepresets.css'));
        $compiler->compile();
    }

    public static function clearModules($ep)
    {
        $aParams = $ep->getParams();
        $iModuleId = $aParams["id"];
        $oDb = rex_sql::factory();
        $oDb->setQuery("DELETE FROM " . rex::getTable("nv_modulepresets") . " WHERE module_id = :module_id", ["module_id" => $iModuleId]);
    }

    public static function checkMediaInUse($ep)
    {
        // wird nicht mehr verwendet
        return $ep->getSubject();

        $aWarning = $ep->getSubject();
        $sFilename = $ep->getParam("filename");

        $oItems = rex_sql::factory();
        $oItems->setQuery("SELECT * FROM " . rex::getTable("nv_modulepresets") . " WHERE thumbnail = :thumbnail", ["thumbnail" => $sFilename]);
        foreach ($oItems as $oItem) {
            $aWarning[] = "nvModulePresets: " . $oItem->getValue("title") . " | ID: " . $oItem->getValue("id");
        }
        return $aWarning;
    }

    public static function handleThumbnailUploads($ep)
    {
        $oAddon = self::getAddon();
        $oSql = $ep->getParam("sql");
        $oForm = $ep->getParam("form");

        if ($oForm->isEditMode() == "edit") {
            $iId = str_replace("id=", "", $oForm->getWhereCondition());
        } else {
            $iId = $oSql->getLastId();
        }
        $sFormname = $oForm->getName();

        if ($_POST["thumbnail_current"] != "") {
            $oDb = rex_sql::factory();
            $oDb->setQuery("UPDATE " . rex::getTable("nv_modulepresets") . " SET thumbnail = :thumbnail WHERE id = :id", ["id" => $iId, "thumbnail" => $_POST["thumbnail_current"]]);
        }

        if ($_FILES[$sFormname]["tmp_name"]["thumbnail"] != "" or $_POST["thumbnail_delete"] == "1") {
            $oDb = rex_sql::factory();
            $oDb->setQuery("SELECT * FROM " . rex::getTable("nv_modulepresets") . " WHERE id = :id Limit 1", ["id" => $iId]);
            if ($oDb->getValue("thumbnail")) {
                if (file_exists($oAddon->getAssetsPath("images/thumbnails/" . $oDb->getValue("thumbnail")))) {
                    unlink($oAddon->getAssetsPath("images/thumbnails/" . $oDb->getValue("thumbnail")));
                }
                $oDb = rex_sql::factory();
                $oDb->setQuery("UPDATE " . rex::getTable("nv_modulepresets") . " SET thumbnail = '' WHERE id = :id", ["id" => $iId]);
            }
        }
        if ($_FILES[$sFormname]["tmp_name"]["thumbnail"] != "") {
            if (@is_array(getimagesize($_FILES[$sFormname]["tmp_name"]["thumbnail"]))) {
                $sExtension = preg_replace('@.+\.@', '', $_FILES[$sFormname]['name']["thumbnail"]);
                $sThumbnail = $iId . "." . $sExtension;
                move_uploaded_file($_FILES[$sFormname]["tmp_name"]["thumbnail"], $oAddon->getAssetsPath("images/thumbnails/" . $sThumbnail));
                $oDb = rex_sql::factory();
                $oDb->setQuery("UPDATE " . rex::getTable("nv_modulepresets") . " SET thumbnail = :thumbnail WHERE id = :id", ["id" => $iId, "thumbnail" => $sThumbnail]);
            } else {
                $oDb = rex_sql::factory();
                $oDb->setQuery("UPDATE " . rex::getTable("nv_modulepresets") . " SET thumbnail = '' WHERE id = :id", ["id" => $iId]);
            }
        }
    }
}
