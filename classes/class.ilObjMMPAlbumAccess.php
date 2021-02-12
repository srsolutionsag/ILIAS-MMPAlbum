<?php

include_once("./Services/Repository/classes/class.ilObjectPluginAccess.php");

/**
 * Access/Condition checking for Example object
 *
 * Please do not create instances of large application classes (like
 * ilObjExample) Write small methods within this class to determin the status.
 *
 * @author  Stefan Born <stefan.born@phzh.ch>
 * @version $Id$
 */
class ilObjMMPAlbumAccess extends ilObjectPluginAccess
{

    /**
     * Checks wether a user may invoke a command or not
     * (this method is called by ilAccessHandler::checkAccess)
     *
     * Please do not check any preconditions handled by
     * ilConditionHandler here. Also don't do usual RBAC checks.
     *
     * @param string $a_cmd           command (not permission!)
     * @param string $a_permission    permission
     * @param int    $a_ref_id        reference id
     * @param int    $a_obj_id        object id
     * @param int    $a_user_id       user id (if not provided, current user is
     *                                taken)
     *
     * @return    boolean        true, if everything is ok
     */
    public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
    {
        global $DIC;
        /**
         * @var $ilAccess \ilAccessHandler
         */

        if ($a_user_id == "") {
            $a_user_id = $DIC->user()->getId();
        }

        switch ($a_permission) {
            case "read":
                if (!ilObjMMPAlbumAccess::checkOnline($a_obj_id)
                    && !$DIC->access()->checkAccessOfUser($a_user_id, "write", "", $a_ref_id)
                ) {
                    return false;
                }
                break;
        }

        return true;
    }


    /**
     * Check online status of example object
     */
    static function checkOnline($a_id)
    {
        global $DIC;

        $set = $DIC->database()->query(
            "SELECT is_online FROM rep_robj_xmma_data WHERE id = "
            . $DIC->database()->quote($a_id, "integer")
        );
        $rec = $DIC->database()->fetchAssoc($set);

        return (boolean) $rec["is_online"];
    }


    /**
     * Check online status of example object
     *
     * @param $a_id
     *
     * @return int
     */
    static function getUpdateMode($a_id)
    {
        global $DIC;

        $set = $DIC->database()->query(
            "SELECT update_mode FROM rep_robj_xmma_data WHERE id = "
            . $DIC->database()->quote($a_id, "integer")
        );
        $rec = $DIC->database()->fetchAssoc($set);

        return (int) $rec["update_mode"];
    }
}
