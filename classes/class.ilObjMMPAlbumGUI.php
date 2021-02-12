<?php

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
 * User Interface class for Multimedia Portal Album repository object.
 *
 * User interface classes process GET and POST parameter and call
 * application classes to fulfill certain tasks.
 *
 * @author            Stefan Born <stefan.born@phzh.ch>
 *
 * $Id$
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @ilCtrl_isCalledBy ilObjMMPAlbumGUI: ilRepositoryGUI, ilAdministrationGUI
 * @ilCtrl_isCalledBy ilObjMMPAlbumGUI: ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjMMPAlbumGUI: ilPermissionGUI, ilInfoScreenGUI
 * @ilCtrl_Calls      ilObjMMPAlbumGUI: ilCommonActionDispatcherGUI
 * @ilCtrl_Calls      ilObjMMPAlbumGUI: ilObjectCopyGUI
 *
 */
class ilObjMMPAlbumGUI extends ilObjectPluginGUI
{

    const THUMB_SIZE = 160;
    const DISPLAY_SIZE = 1024;
    const ALBUM_BY_LIST = 1;
    const ALBUM_ID_MANUAL = 2;
    /**
     * @var array
     */
    private $albumIds = array();
    /**
     * @var \ilObjMMPAlbum
     */
    public $object;


    /**
     * Initialisation
     */
    protected function afterConstructor()
    {
        // anything needed after object has been constructed
        // - example: append my_id GET parameter to each request
        //   $ilCtrl->saveParameter($this, array("my_id"));

        if ($_SERVER['SERVER_NAME'] != "localhost") {
            setcookie("ILIAS_MMP_Cookie", time(), false, "/", ".phzh.ch", false, true);
        } else {
            setcookie("ILIAS_MMP_Cookie", "localCookieTest", false, "/", false);
        }
    }


    /**
     * Get type.
     */
    final function getType()
    {
        return "xmma";
    }


    public function executeCommand()
    {
        global $DIC;

        // always display description if not creating
        if (!$this->getCreationMode()) {
            $props = array();
            $canEdit = $DIC->access()->checkAccess("write", "", $this->object->getRefId());

            // description
            $DIC->ui()->mainTemplate()->setDescription($this->object->getLongDescription());

            // offline status
            if ($canEdit && !ilObjMMPAlbumAccess::checkOnline($this->obj_id)) {
                $props[] = array(
                    "property" => $DIC->language()->txt("status"),
                    "value"    => $DIC->language()->txt("offline"),
                );
            }

            // if the user can edit the album, display the update type
            if ($canEdit) {
                $updateMode = ilObjMMPAlbumAccess::getUpdateMode($this->obj_id);
                if ($updateMode == ilObjMMPAlbum::UPDATE_MODE_AUTO) {
                    $updateModeText = $this->txt("update_mode_auto");
                } else {
                    $updateModeText = $this->txt("update_mode_manual");
                    $lastUpdate = new ilDateTime($this->object->getLastUpdateDate(), IL_CAL_DATETIME);
                    $lastUpdateText = sprintf($this->txt("update_mode_last_update"), ilDatePresentation::formatDate($lastUpdate));

                    $updateNowText = sprintf("<a href=\"%s\">%s</a>", $DIC->ctrl()->getLinkTarget($this, "updateAlbum"), $this->txt("update_now"));

                    $updateModeText .= " ($lastUpdateText | $updateNowText)";
                }

                $props[] = array(
                    "property" => "<span class=\"light\">"
                        . $this->txt("update_mode"),
                    "value"    => "$updateModeText</span>",
                );
            }

            if (count($props) > 0) {
                $DIC->ui()->mainTemplate()->setAlertProperties($props);
            }

            $this->addHeaderAction();
        }

        parent::executeCommand();
    }


    /**
     * @param null $a_sub_type
     * @param null $a_sub_id
     *
     * @return \ilObjectListGUI
     */
    protected function initHeaderAction($a_sub_type = null, $a_sub_id = null)
    {
        $lg = parent::initHeaderAction($a_sub_type, $a_sub_id);

        return $lg;
    }


    /**
     * @param string $cmd
     * Handles all commmands of this class, centralizes permission checks
     *
     * @throws ilCtrlException
     * @throws ilObjectException
     */
    public function performCommand($cmd)
    {
        // missed class handling
        $next_class = $this->ctrl->getNextClass($this);

        switch ($next_class) {
            case "ilcommonactiondispatchergui":
                include_once("Services/Object/classes/class.ilCommonActionDispatcherGUI.php");
                $gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
                $this->ctrl->forwardCommand($gui);
                break;

            default:
                switch ($cmd) {
                    // list all commands that need write permission here
                    case "edit":
                    case "update":
                    case "updateAlbum":
                        $this->checkPermission("write");
                        $this->$cmd();
                        break;

                    // list all commands that need read permission here
                    case "view":
                        $this->checkPermission("read");
                        $this->$cmd();
                        break;
                }
                break;
        }
    }


    /**
     * After object has been created -> jump to this command
     *
     * @return string
     */
    public function getAfterCreationCmd()
    {
        return "view";
    }


    /**
     * Get standard command
     *
     * @return string
     */
    public function getStandardCmd()
    {
        return "view";
    }


    /**
     * Set tabs
     */
    public function setTabs()
    {
        global $DIC;

        // tab for the "show content" command
        if ($DIC->access()->checkAccess("read", "", $this->object->getRefId())) {
            $DIC->tabs()->addTab("content", $DIC->language()->txt("content"), $this->ctrl->getLinkTarget($this, "view"));
        }

        // standard info screen tab
        $this->addInfoTab();

        // tab displaying the properties
        if ($DIC->access()->checkAccess("write", "", $this->object->getRefId())) {
            $DIC->tabs()->addTab("properties", $DIC->language()->txt("options"), $this->ctrl->getLinkTarget($this, "edit"));
        }

        // standard permission tab
        $this->addPermissionTab();
    }


    /**
     * @param string $a_new_type
     *
     * @return \ilPropertyFormGUI
     */
    public function initCreateForm($a_new_type)
    {
        // adds commands: 'save', 'cancel'
        $this->form = parent::initCreateForm($a_new_type);
        $this->initForm();

        return $this->form;
    }


    private function initForm()
    {
        global $DIC;

        $this->plugin->includeClass("class.ilObjMMPAlbum.php");

        // remove default fields that album id is on top!
        $this->form->removeItemByPostVar("title");
        $this->form->removeItemByPostVar("desc");

        // get user login
        $userLogin = $DIC->user()->getLogin();
        if (strpos($userLogin, "@") === false) {
            // students do not have a '.' in their name
            if (strpos($userLogin, ".") === false) {
                $userLogin .= "@stud.phzh.ch";
            } else {
                $userLogin .= "@phzh.ch";
            }
        }

        // get albums that are readable by the current user
        $select = null;
        $album_id = ($this->object ? $this->object->getAlbumId() : null);
        $albums = ilObjMMPAlbum::getAlbumList($userLogin, $album_id);
        if ($albums !== false) {
            $select = new ilSelectInputGUI($this->txt("album"), "album_id_select");
            $albumOptions = array();
            $albumOptions["0"] = " - Album auswählen -";
            foreach ($albums->AlbumList->Album as $album) {
                // TODO: change for scrambled IDs
                $value = "" . $album->Id; // urlencode($album->ScrambleId)
                //$value = urlencode($album->ScrambleId);

                if ($album->Cat == "myAlbums") {
                    $optionText = $album->Title . " (Bilder: " . $album->Count
                        . ")";
                } else {
                    $optionText = $album->Title . " (Bilder: " . $album->Count
                        . " | " . $album->Owner . ")";
                }

                $albumOptions[$value] = $optionText;
                $this->albumIds[] = $value;
            }
            $select->setOptions($albumOptions);
        }

        // album id textbox
        $ti = new ilTextInputGUI($this->txt("album_id"), "album_id");
        $ti->setMaxLength(255);
        $ti->setSize(50);
        $ti->setInfo($this->txt("album_id_info"));

        // album list defined?
        if ($select) {
            $rb = new ilRadioGroupInputGUI($this->txt("album_id"), "album_id_option");
            $rb->setRequired(true);
            $rb->setValue(self::ALBUM_BY_LIST);

            // select
            $rbo = new ilRadioOption("Album aus Liste auswählen", self::ALBUM_BY_LIST, null);
            $rbo->addSubItem($select);
            $rb->addOption($rbo);

            // enter manually
            $rbo = new ilRadioOption("Album-ID manuell eingeben", self::ALBUM_ID_MANUAL, null);
            $rbo->addSubItem($ti);
            $rb->addOption($rbo);

            $this->form->addItem($rb);
        } else {
            $ti->setTitle($this->txt("album_id"));
            $ti->setRequired(true);
            $this->form->addItem($ti);
        }

        // title
        $ti = new ilTextInputGUI($this->lng->txt("title"), "title");
        $ti->setInfo($this->txt("get_album_info_when_empty"));
        $ti->setMaxLength(128);
        $ti->setSize(50);
        $ti->setRequired(false);
        $this->form->addItem($ti);

        // description
        $ta = new ilTextAreaInputGUI($this->lng->txt("description"), "desc");
        $ta->setInfo($this->txt("get_album_info_when_empty"));
        $ta->setCols(60);
        $ta->setRows(5);
        $ta->setRequired(false);
        $this->form->addItem($ta);

        // online
        $cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
        $cb->setChecked(true);
        $this->form->addItem($cb);

        // update option
        $rb = new ilRadioGroupInputGUI($this->txt("update_mode"), "update_mode");
        $rb->addOption(new ilRadioOption($this->txt("update_mode_auto"), ilObjMMPAlbum::UPDATE_MODE_AUTO, $this->txt("update_mode_auto_info")));
        $rb->addOption(new ilRadioOption($this->txt("update_mode_manual"), ilObjMMPAlbum::UPDATE_MODE_MANUAL, $this->txt("update_mode_manual_info")));
        $this->form->addItem($rb);
    }


    /**
     * Init  form.
     *
     * @param int $a_mode Edit Mode
     */
    public function initPropertiesForm()
    {
        // adds commands: 'update'
        $this->form = parent::initEditForm();
        $this->initForm();
    }


    /**
     * Get values for edit properties form
     */
    public function getPropertiesValues()
    {
        $values["title"] = $this->object->getTitle();
        $values["desc"] = $this->object->getLongDescription();
        $values["online"] = $this->object->getOnline();

        $albumId = $this->object->getAlbumId();
        $values["album_id"] = $albumId;
        $values["album_id_select"] = urlencode($albumId);

        // set option for album selection
        if (in_array(urlencode($albumId), $this->albumIds)) {
            $values["album_id_option"] = self::ALBUM_BY_LIST;
        } else {
            $values["album_id_option"] = self::ALBUM_ID_MANUAL;
        }

        $values["update_mode"] = $this->object->getUpdateMode();

        // set values
        $this->form->setValuesByArray($values);
    }


    /**
     * (non-PHPdoc)
     *
     * @see ilObject2GUI::edit()
     */
    public function edit()
    {
        global $DIC;

        $DIC->tabs()->activateTab("properties");
        $this->initPropertiesForm();
        $this->getPropertiesValues();
        $DIC->ui()->mainTemplate()->setContent($this->form->getHTML());
    }


    /**
     * (non-PHPdoc)
     *
     * @see ilObject2GUI::save()
     */
    public function save()
    {
        $new_type = $_REQUEST["new_type"];
        $this->ctrl->setParameter($this, "new_type", $new_type);

        $form = $this->initCreateForm($new_type);
        $album = null;
        $this->saveOrUpdateAlbum($album, $form);
    }


    /**
     * (non-PHPdoc)
     *
     * @see ilObject2GUI::update()
     */
    public function update()
    {
        global $DIC;

        $DIC->tabs()->activateTab("properties");

        $this->initPropertiesForm();
        $this->saveOrUpdateAlbum($this->object, $this->form);
    }


    /**
     * Saves or updates an album.
     *
     * @param ilObjMMPAlbum     $album The album to save or update.
     * @param ilPropertyFormGUI $form  The submitted form to get the data from.
     */
    private function saveOrUpdateAlbum(&$album, &$form)
    {
        global $DIC;

        if ($form->checkInput()) {
            $hasError = false;

            $albumIdOption = $form->getInput("album_id_option");
            $albumIdSelected = urldecode($form->getInput("album_id_select"));
            $albumId = $form->getInput("album_id");

            // from list selected?
            if ($albumIdOption == self::ALBUM_BY_LIST) {
                // is an entry selected?
                if ($albumIdSelected != "0") {
                    $albumId = $albumIdSelected;
                } else {
                    $idInput = $form->getItemByPostVar("album_id_select");
                    $idInput->setAlert($this->txt("selection_required"));
                    ilUtil::sendFailure($this->lng->txt("form_input_not_valid"), false);
                    $hasError = true;
                }
            } else {
                if (trim($albumId) == "") {
                    $idInput = $form->getItemByPostVar("album_id");
                    $idInput->setAlert($this->lng->txt("msg_input_is_required"));
                    ilUtil::sendFailure($this->lng->txt("form_input_not_valid"), false);
                    $hasError = true;
                }
            }

            $updateMode = $form->getInput("update_mode");
            $title = $form->getInput("title");
            $desc = $form->getInput("desc");
            $isOnline = $form->getInput("online");

            // download XML if no error yet
            if (!$hasError) {
                $albumXml = ilObjMMPAlbum::downloadAlbumContent($albumId);
                if ($albumXml === false) {
                    $idInput = $form->getItemByPostVar(
                        $albumIdOption
                        == self::ALBUM_BY_LIST ? "album_id_select" : "album_id"
                    );
                    $idInput->setAlert($this->txt("album_not_found"));
                    $hasError = true;
                }
            }

            if (!$hasError) {
                $createAlbum = !is_object($album);

                if ($createAlbum) {
                    $this->ctrl->setParameter($this, "new_type", "");
                    $album = new ilObjMMPAlbum();
                    $this->object = $album;
                }

                // description not defined? try to load from XML
                $albumObj = @simplexml_load_string($albumXml);
                if ($albumObj !== false) {
                    if (trim($title) == "") {
                        $title = $albumObj->Title;
                    }

                    if (trim($desc) == "") {
                        $desc = $albumObj->Description;
                    }
                }

                // don't save XML if auto updating
                if ($updateMode == ilObjMMPAlbum::UPDATE_MODE_AUTO) {
                    $albumXml = null;
                }

                // set album properties
                $album->setTitle($title);
                $album->setDescription($desc);
                $album->setOnline($isOnline);
                $album->setAlbumId($albumId);
                $album->setUpdateMode($updateMode);
                $album->setAlbumXml($albumXml);

                // create or update
                if ($createAlbum) {
                    $album->create();
                    $this->putObjectInTree($album, $_GET["ref_id"]);
                    $this->afterSave($album);

                    return;
                } else {
                    $album->update();
                    ilUtil::sendSuccess($DIC->language()->txt("msg_obj_modified"), true);
                    $this->ctrl->redirect($this, "edit");

                    return;
                }
            }
        }

        $form->setValuesByPost();
        $DIC->ui()->mainTemplate()->setContent($form->getHtml());
    }


    private function updateAlbum()
    {
        $albumId = $this->object->getAlbumId();
        $albumXml = ilObjMMPAlbum::downloadAlbumContent($albumId);
        if ($albumXml !== false) {
            $this->object->setAlbumXml($albumXml);
            if ($this->object->update()) {
                ilUtil::sendSuccess($this->txt("update_successful"), true);
            } else {
                ilUtil::sendFailure($this->txt("update_failed"), true);
            }
        } else {
            ilUtil::sendFailure($this->txt("album_not_found"), false);
        }

        // redirect to previous page
        $this->ctrl->redirect($this, $this->getStandardCmd());
    }


    /**
     * Show album
     */
    public function view()
    {
        global $DIC;

        // permanent link
        $DIC->ui()->mainTemplate()->setPermanentLink($this->getType(), $this->object->getRefId());

        $DIC->tabs()->activateTab("content");

        $album = $this->object->getAlbum();
        if ($album !== false) {
            // load prerequisites
            $this->loadJavaScript();
            $this->loadCss();

            // get the lightbox template
            $lightboxTpl = $this->plugin->getTemplate("tpl.lightbox.html");
            $lightboxTpl->setVariable("LOADING_TEXT", $this->txt("image_loading"));

            // get the image template
            $mediaTpl = $this->plugin->getTemplate("tpl.media_list.html");

            // base url as MMP only delivers partial URLs
            $baseUrl = ilMMPAlbumPlugin::getBaseUrl();

            $mediumId = 0;
            foreach ($album->Media->Medium as $medium) {
                // everything else than picture and video is not supported!
                if ($medium->MediaType != "Picture"
                    && $medium->MediaType != "Video"
                ) {
                    continue;
                }

                $thumbImg = null;
                $displayImg = null;

                // get the desired thumb and display size
                $thumbCount = 0;
                foreach ($medium->Thumbs->Thumb as $thumb) {
                    if ($thumb->Size == self::THUMB_SIZE) {
                        $thumbImg = $thumb;
                        $thumbImg->Url = $baseUrl . $thumbImg->Url;
                    } else {
                        if ($thumb->Size == self::DISPLAY_SIZE) {
                            $displayImg = $thumb;
                        }
                    }
                    $thumbCount++;
                }

                // thumbnail not found?
                if ($thumbImg == null) {
                    if ($thumbCount > 0) {
                        $thumbImg = $medium->Thumbs->Thumb[0];

                        // adjust size
                        $thumbImg->Width = self::THUMB_SIZE;
                        $thumbImg->Height = self::THUMB_SIZE;
                    } else {
                        $thumbImg = new stdClass();
                        $thumbImg->Width = self::THUMB_SIZE;
                        $thumbImg->Height = self::THUMB_SIZE;
                        $thumbImg->Size = self::THUMB_SIZE;
                        $thumbImg->Url = "./Customizing/global/plugins/Services/Repository/RepositoryObject/MMPAlbum/templates/images/"
                            . strtolower($medium->MediaType)
                            . ".png";
                    }
                }

                // display image not found? take original
                if ($displayImg == null) {
                    $displayImg = $medium;
                }

                // get original download link
                $downloadLink = $baseUrl
                    . $medium->Url; //$this->plugin->getDirectory() . "/download.php?img=" . rawurlencode($baseUrl . $medium->Url) . "&name=" . rawurlencode($medium->Title);

                $mediaTpl->setCurrentBlock("medium");
                if ($medium->MediaType == "Picture") {
                    $imageTpl = $this->plugin->getTemplate("tpl.image_thumb.html");

                    $imageTpl->setVariable("DATA_ID", $mediumId);

                    $imageTpl->setVariable(
                        "IMG_LINK", $baseUrl
                        . $displayImg->Url
                    );
                    $imageTpl->setVariable("IMG_ORIG_LINK", $downloadLink);
                    $imageTpl->setVariable("IMG_TITLE", $medium->Title);
                    $imageTpl->setVariable("IMG_DESC", $medium->Description);

                    $imageTpl->setVariable("THUMB_SIZE", $thumbImg->Size);
                    $imageTpl->setVariable("THUMB_LINK", $thumbImg->Url);
                    $imageTpl->setVariable("THUMB_WIDTH", $thumbImg->Width);
                    $imageTpl->setVariable("THUMB_HEIGHT", $thumbImg->Height);

                    $imageTpl->setVariable(
                        "THUMB_OFFSET_TOP", ($thumbImg->Size
                            - $thumbImg->Height)
                        / 2
                    );

                    $imageTpl->setVariable("DOWNLOAD_TEXT", $this->txt("download"));
                    $imageTpl->setVariable("IMG_DOWNLOAD_LINK", $downloadLink);

                    // add to list template
                    $mediaTpl->setVariable("MEDIUM", $imageTpl->get());
                } else {
                    $videoTpl = $this->plugin->getTemplate("tpl.video_thumb.html");

                    $videoTpl->setVariable("DATA_ID", $mediumId);

                    $videoTpl->setVariable("VIDEO_LINK", "#");
                    $videoTpl->setVariable("VIDEO_IFRAME", rawurlencode($medium->EmbedString));
                    $videoTpl->setVariable("VIDEO_TITLE", $medium->Title);
                    $videoTpl->setVariable("VIDEO_DESC", $medium->Description);

                    $videoTpl->setVariable("THUMB_SIZE", $thumbImg->Size);
                    $videoTpl->setVariable("THUMB_LINK", $thumbImg->Url);

                    // add to list template
                    $mediaTpl->setVariable("MEDIUM", $videoTpl->get());
                }
                $mediaTpl->parseCurrentBlock();

                $mediumId++;
            }

            // get the album template
            $albumTpl = $this->plugin->getTemplate("tpl.album.html");
            $albumTpl->setVariable("LIGHTBOX", $lightboxTpl->get());
            $albumTpl->setVariable("IMAGES", $mediaTpl->get());

            // write content
            $DIC->ui()->mainTemplate()->setContent($albumTpl->get());
        } else {
            $DIC->ui()->mainTemplate()->setContent($this->txt("load_album_error"));
        }
    }


    private function loadJavaScript()
    {
        global $DIC, $https;

        if (version_compare(ILIAS_VERSION_NUMERIC, '4.2.0') >= 0) {
            include_once 'Services/jQuery/classes/class.iljQueryUtil.php';
            iljQueryUtil::initjQuery();
        } else {
            if ($https->isDetected()) {
                $DIC->ui()->mainTemplate()->addJavaScript('https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js');
            } else {
                $DIC->ui()->mainTemplate()->addJavaScript('http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js');
            }
        }

        // load our java scripts
        $DIC->ui()->mainTemplate()->addJavaScript(
            $this->plugin->getDirectory()
            . "/js/jquery.isotope.min.js"
        );
        $DIC->ui()->mainTemplate()->addJavaScript($this->plugin->getDirectory() . "/js/xmma.js");
    }


    private function loadCss()
    {
        global $DIC;
        $DIC->ui()->mainTemplate()->addCss($this->plugin->getStyleSheetLocation("xmma.css"));
        $DIC->ui()->mainTemplate()->addCss($this->plugin->getStyleSheetLocation("isotope.css"));
    }
}
