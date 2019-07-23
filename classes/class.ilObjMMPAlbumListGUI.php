<?php

include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
 * ListGUI implementation for Example object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author Stefan Born <stefan.born@phzh.ch>
 */
class ilObjMMPAlbumListGUI extends ilObjectPluginListGUI
{

    public function initType()
    {
        $this->setType("xmma");
    }


    /**
     * @return string name of gui class handling the commands
     */
    public function getGuiClass()
    {
        return "ilObjMMPAlbumGUI";
    }


    /**
     * Get commands
     */
    public function initCommands()
    {
        global $DIC;

        return array(
            array(
                "permission" => "read",
                "cmd"        => "view",
                "default"    => true,
            ),
            array(
                "permission" => "write",
                "cmd"        => "edit",
                "txt"        => $DIC->language()->txt("edit"),
                "default"    => false,
            ),
        );
    }


    /**
     * (non-PHPdoc)
     *
     * @see ilObjectListGUI::getAdditionalInformation()
     */
    public function getAdditionalInformation()
    {
        // TODO: thumbnails!
        return null;//"<strong>My Info</strong>";
    }


    /**
     * Get item properties
     *
     * @return    array        array of property arrays:
     *                        "alert" (boolean) => display as an alert property
     *                        (usually in red)
     *                        "property" (string) => property name
     *                        "value" (string) => property value
     */
    public function getProperties()
    {
        global $DIC;

        $props = array();

        $this->plugin->includeClass("class.ilObjMMPAlbumAccess.php");
        if (!ilObjMMPAlbumAccess::checkOnline($this->obj_id)) {
            $props[] = array(
                "alert"    => true,
                "property" => $DIC->language()->txt("status"),
                "value"    => $DIC->language()->txt("offline"),
            );
        }

        // if the user can edit the album, display the update type
        if ($DIC->access()->checkAccess("write", "", $this->ref_id)) {
            $this->plugin->includeClass("class.ilObjMMPAlbum.php");

            $updateMode = ilObjMMPAlbumAccess::getUpdateMode($this->obj_id);
            if ($updateMode == ilObjMMPAlbum::UPDATE_MODE_AUTO) {
                $updateModeText = "update_mode_auto";
            } else {
                $updateModeText = "update_mode_manual";
            }

            $props[] = array(
                "alert"    => false,
                "property" => $this->plugin->txt("update_mode"),
                "value"    => $this->plugin->txt($updateModeText),
            );
        }

        return $props;
    }
}
