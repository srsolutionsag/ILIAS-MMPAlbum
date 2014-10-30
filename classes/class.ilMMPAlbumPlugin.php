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
	/**
	 * (non-PHPdoc)
	 * @see ilPlugin::getPluginName()
	 */
	final function getPluginName()
	{
		return "MMPAlbum";
	}
	
	static function getSecretKey()
	{
		return self::getSetting("secret_key");
	}
	
	static function getBaseUrl()
	{
		$parts = parse_url(self::getAlbumXmlUrlFormat());
		return $parts["scheme"] . "://" . $parts["host"];
	}
	
	static function getAlbumXmlUrlFormat()
	{
		return self::getSetting("url_album_xml_format");
	}
	
	static function getAlbumListUrlFormat()
	{
		return self::getSetting("url_album_list_format");
	}
	
	static function getSetting($keyword)
	{
		global $ilDB;
		
		$set = $ilDB->queryF(
			"SELECT value FROM rep_robj_xmma_settings WHERE keyword=%s", 
			array("text"),
			array($keyword));
		
		while ($rec = $ilDB->fetchAssoc($set))
		{
			return $rec["value"];
		}
		
		return null;
	}
}

?>
