<?php

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");

/**
 * Application class for Multimedia Portal Album plugin.
 *
 * @author  Stefan Born <stefan.born@phzh.ch>
 * @version $Id$
 *
 */
class ilObjMMPAlbum extends ilObjectPlugin
{

    const UPDATE_MODE_AUTO = 0;
    const UPDATE_MODE_MANUAL = 1;
    /**
     * @var bool
     */
    private $online = false;
    /**
     * @var int
     */
    private $album_id = null;
    /**
     * @var int
     */
    private $update_mode = self::UPDATE_MODE_AUTO;
    /**
     * @var string
     */
    private $album_xml = null;


    /**
     * ilObjMMPAlbum constructor.
     *
     * @param int $a_ref_id
     */
    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
    }


    public final function initType()
    {
        $this->setType("xmma");
    }


    /**
     * Create object
     */
    public function doCreate()
    {
        global $ilDB;

        $ilDB->insert(
            "rep_robj_xmma_data", array(
                "id"          => array("integer", $this->getId()),
                "is_online"   => array("integer", $this->getOnline()),
                "album_url"   => array("text", $this->getAlbumId()),
                "update_mode" => array("integer", $this->getUpdateMode()),
                "album_xml"   => array("clob", $this->getAlbumXml()),
            )
        );
    }


    /**
     * Read data from db
     */
    public function doRead()
    {
        global $ilDB;

        $set = $ilDB->queryF("SELECT * FROM rep_robj_xmma_data WHERE id=%s", array("integer"), array($this->getId()));

        while ($rec = $ilDB->fetchAssoc($set)) {
            $this->setOnline($rec["is_online"]);
            $this->setAlbumId($rec["album_url"]);
            $this->setUpdateMode($rec["update_mode"]);
            $this->setAlbumXml($rec["album_xml"]);
        }
    }


    /**
     * Update data
     */
    public function doUpdate()
    {
        global $ilDB;

        $ilDB->update(
            "rep_robj_xmma_data", array(
            "is_online"   => array("integer", $this->getOnline()),
            "album_url"   => array("text", $this->getAlbumId()),
            "update_mode" => array("integer", $this->getUpdateMode()),
            "album_xml"   => array("clob", $this->getAlbumXml()),
        ), array("id" => array("integer", $this->getId()))
        );
    }


    /**
     * Delete data from db
     */
    public function doDelete()
    {
        global $ilDB;

        $ilDB->manipulateF("DELETE FROM rep_robj_xmma_data WHERE id=%s", array("integer"), array($this->getId()));
    }


    /**
     * @param ilObjMMPAlbum $new_obj
     * @param int           $a_target_id
     * @param int           $a_copy_id
     */
    public function doCloneObject($new_obj, $a_target_id, $a_copy_id = null)
    {
        /**
         * @var $new_obj ilObjMMPAlbum
         */
        assert(is_a($new_obj, 'ilObjMMPAlbum'));

        $new_obj->setOnline($this->getOnline());
        $new_obj->setAlbumId($this->getAlbumId());
        $new_obj->setUpdateMode($this->getUpdateMode());
        $new_obj->setAlbumXml($this->getAlbumXml());
        $new_obj->update();
    }


    /**
     * Set if online
     *
     * @param boolean $is_online
     */
    public function setOnline($is_online)
    {
        $this->online = $is_online;
    }


    /**
     * Get if online
     *
     * @return boolean Online
     */
    public function getOnline()
    {
        return $this->online;
    }


    /**
     * Set album ID
     *
     * @param string $album_id URL
     */
    public function setAlbumId($album_id)
    {
        $this->album_id = $album_id;
    }


    /**
     * Get album ID
     *
     * @return string Album URL
     */
    public function getAlbumId()
    {
        return $this->album_id;
    }


    /**
     * Sets the update mode
     *
     * @param integer $a_val Update mode
     */
    public function setUpdateMode($a_val)
    {
        $this->update_mode = $a_val;
    }


    /**
     * Get the update mode
     *
     * @return string Update mode
     */
    public function getUpdateMode()
    {
        return $this->update_mode;
    }


    /**
     * Sets the album XML definition
     *
     * @param string $a_val Album XML definition
     */
    public function setAlbumXml($a_val)
    {
        $this->album_xml = $a_val;
    }


    /**
     * Gets the album XML definition
     *
     * @return string Album XML definition
     */
    public function getAlbumXml()
    {
        return $this->album_xml;
    }


    /**
     * Gets the album represented by the XML definition.
     */
    public function getAlbum()
    {
        $xml = $this->getAlbumDefinition();
        if ($xml !== false) {
            $useInternalErrors = libxml_use_internal_errors(true);

            $xmlObj = simplexml_load_string($xml);
            if ($xmlObj !== false) {
                libxml_use_internal_errors($useInternalErrors);

                return $xmlObj;
            }

            // trace error
            $errorText = '';
            foreach (libxml_get_errors() as $error) {
                $errorText .= "<br/> - Line " . $error->line . ": "
                    . $error->message;
            }

            ilUtil::sendFailure(sprintf($this->txt("xml_album_error"), $this->getAlbumId(), $errorText), false);

            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        return false;
    }


    /**
     * @param      $userEmail
     * @param null $album_id
     *
     * @return bool|\SimpleXMLElement
     */
    public static function getAlbumList($userEmail, $album_id = null)
    {
        $xml = self::downloadAlbumListXml($userEmail);
        if ($xml !== false) {
            $useInternalErrors = libxml_use_internal_errors(true);

            $xmlObj = simplexml_load_string($xml);
            if ($xmlObj !== false) {
                libxml_use_internal_errors($useInternalErrors);

                return $xmlObj;
            }

            // trace error
            $errorText = '';
            foreach (libxml_get_errors() as $error) {
                $errorText .= "<br/> - Line " . $error->line . ": "
                    . $error->message;
            }

            ilUtil::sendFailure(sprintf(ilMMPAlbumPlugin::getInstance()->txt("xml_album_error"), $album_id, $errorText), false);

            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        return false;
    }


    /**
     * Downloads the album content from the Multimedia-Portal.
     *
     * @param string $album_id The album id.
     *
     * @return bool
     */
    static function downloadAlbumContent($album_id)
    {
        $url = self::buildAlbumXmlUrl($album_id);

        // load url content
        $xml = @file_get_contents($url);
        if ($xml !== false && trim($xml) != "") {
            return $xml;
        } else {
            if (isset($http_response_header)) {
                $errorText = $http_response_header[0];
            } else {
                $lastError = error_get_last();
                $errorText = $lastError["message"];
            }

            // needed for getting the texts
            $album = new ilObjMMPAlbum();
            if (strpos($errorText, "404") > 0) {
                ilUtil::sendFailure($album->txt("album_not_found"), false);
            } else {
                ilUtil::sendFailure(sprintf($album->txt("request_album_error"), $album_id, $errorText), false);
            }

            return false;
        }
    }


    /**
     * Builds an URL to download an album definition.
     *
     * @param string $album_id The album id.
     *
     * @return string
     */
    static function buildAlbumXmlUrl($album_id)
    {
        $urlFormat = ilMMPAlbumPlugin::getAlbumXmlUrlFormat();

        // replace placeholders with real values
        $url = str_replace("[ID]", $album_id, $urlFormat);
        $url = str_replace("[SID]", ilMMPAlbumPlugin::getSecretKey(), $url);

        return $url;
    }


    /**
     * Gets the album definition depending on the update mode.
     */
    private function getAlbumDefinition()
    {
        if ($this->getUpdateMode() == self::UPDATE_MODE_AUTO) {
            return self::downloadAlbumContent($this->getAlbumId());
        } else {
            $xml = $this->getAlbumXml();
            if ($xml != null) {
                return $xml;
            } else {
                return false;
            }
        }
    }


    /**
     * @param $userEmail
     *
     * @return bool|string
     */
    static function downloadAlbumListXml($userEmail)
    {
        $urlFormat = ilMMPAlbumPlugin::getAlbumListUrlFormat();
        $url = str_replace("[LOGIN]", $userEmail, $urlFormat);
        $url = str_replace("[SID]", ilMMPAlbumPlugin::getSecretKey(), $url);

        $xml = @file_get_contents($url);
        if ($xml !== false && trim($xml) != "") {
            return $xml;
        } else {
            return false;
        }
    }
}

