<?php

include_once('./Services/Component/classes/class.ilPluginConfigGUI.php');

/**
 * Configuration GUI for the Multimedia Portal Album plugin.
 *
 * @author  Stefan Born <stefan.born@phzh.ch>
 * @version $Id$
 *
 */
class ilMMPAlbumConfigGUI extends ilPluginConfigGUI
{

    /**
     * Handles all commmands, default is 'configure'
     *
     * @access public
     */
    public function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
            case 'save':
                $this->$cmd();
                break;
        }
    }


    /**
     * Configure screen
     *
     * @access public
     */
    public function configure()
    {
        global $DIC;

        $form = $this->initConfigurationForm();

        // set all plugin settings values
        $set = $DIC->database()->query("SELECT * FROM rep_robj_xmma_settings");
        while ($rec = $DIC->database()->fetchAssoc($set)) {
            $input = $form->getItemByPostVar($rec["keyword"]);
            if ($input) {
                $input->setValue($rec["value"]);
            }
        }

        $DIC->ui()->mainTemplate()->setContent($form->getHTML());
    }


    /**
     * Init configuration form.
     *
     * @return object form object
     * @access public
     */
    public function initConfigurationForm()
    {
        global $DIC;

        $pl = $this->getPluginObject();

        include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setTableWidth("100%");
        $form->setTitle($pl->txt("plugin_configuration"));
        $form->setFormAction($DIC->ctrl()->getFormAction($this));

        // secret key (text)
        $secretKey = new ilTextInputGUI($pl->txt("secret_key"), "secret_key");
        $secretKey->setInfo($pl->txt("secret_key_info"));
        $secretKey->setRequired(true);
        $secretKey->setMaxLength(255);
        $secretKey->setSize(80);
        $form->addItem($secretKey);

        // url album xml format (text)
        $urlXmlFormat = new ilTextInputGUI($pl->txt("url_album_xml_format"), "url_album_xml_format");
        $urlXmlFormat->setInfo($pl->txt("url_album_xml_info"));
        $urlXmlFormat->setRequired(true);
        $urlXmlFormat->setMaxLength(255);
        $urlXmlFormat->setSize(80);
        $form->addItem($urlXmlFormat);

        // url album list format (text)
        $urlListFormat = new ilTextInputGUI($pl->txt("url_album_list_format"), "url_album_list_format");
        $urlListFormat->setInfo($pl->txt("url_album_list_info"));
        $urlListFormat->setRequired(true);
        $urlListFormat->setMaxLength(255);
        $urlListFormat->setSize(80);
        $form->addItem($urlListFormat);

        $form->addCommandButton("save", $DIC->language()->txt("save"));

        return $form;
    }


    /**
     * Save form input
     *
     */
    public function save()
    {
        global $DIC;

        $form = $this->initConfigurationForm();
        if ($form->checkInput()) {
            foreach ($form->getItems() as $item) {
                $keyword = $item->getPostVar();
                $value = $form->getInput($keyword);

                // save to db
                $DIC->database()->update(
                    "rep_robj_xmma_settings", array(
                    "value" => array(
                        "clob",
                        $value,
                    ),
                ), array("keyword" => array("text", $keyword))
                );
            }

            ilUtil::sendSuccess($DIC->language()->txt("saved_successfully"), true);
            $DIC->ctrl()->redirect($this, "configure");
        } else {
            $form->setValuesByPost();
            $DIC->ui()->mainTemplate()->setContent($form->getHtml());
        }
    }
}
