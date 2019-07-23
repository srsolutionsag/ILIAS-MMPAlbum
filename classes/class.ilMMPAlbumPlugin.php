<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 * Repository Multimedia Portal Album plugin.
 *
 * @author Stefan Born <stefan.born@phzh.ch>
 *
 */
class ilMMPAlbumPlugin extends ilRepositoryObjectPlugin
{

    const PLUGIN_NAME = "MMPAlbum";
    /**
     * @var ilMMPAlbumPlugin
     */
    protected static $instance;


    /**
     * @return \ilMMPAlbumPlugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * (non-PHPdoc)
     *
     * @see ilPlugin::getPluginName()
     */
    public final function getPluginName()
    {
        return self::PLUGIN_NAME;
    }


    /**
     * @return string
     */
    public static function getSecretKey()
    {
        return self::getSetting("secret_key");
    }


    /**
     * @return string
     */
    public static function getBaseUrl()
    {
        $parts = parse_url(self::getAlbumXmlUrlFormat());

        return $parts["scheme"] . "://" . $parts["host"];
    }


    /**
     * @return string
     */
    public static function getAlbumXmlUrlFormat()
    {
        return self::getSetting("url_album_xml_format");
    }


    /**
     * @return string
     */
    public static function getAlbumListUrlFormat()
    {
        return self::getSetting("url_album_list_format");
    }


    /**
     * @param $keyword
     *
     * @return string
     */
    public static function getSetting($keyword)
    {
        global $ilDB;

        $set = $ilDB->queryF("SELECT value FROM rep_robj_xmma_settings WHERE keyword=%s", array("text"), array($keyword));

        while ($rec = $ilDB->fetchAssoc($set)) {
            return $rec["value"];
        }

        return null;
    }


    // add ILIAS 5.1 compatibility
    protected function uninstallCustom()
    {
        return false;
    }
}
