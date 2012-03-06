<?php
 
class block_phrasecomments_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) {
 
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        
        // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', get_string('title', 'block_phrasecomments'));
        $mform->setDefault('config_title', 'Inline Comments');
        $mform->setType('config_title', PARAM_MULTILANG);   
 
        // A sample string variable with a default value.
        $mform->addElement('text', 'config_qid', get_string('qid', 'block_phrasecomments'));
        $mform->setDefault('config_qid', '0');
        $mform->setType('config_qid', PARAM_MULTILANG);     

        // A sample string variable with a default value.
        $mform->addElement('text', 'config_startOffset', get_string('startOffset', 'block_phrasecomments'));
        $mform->setDefault('config_startOffset', '0');
        $mform->setType('config_startOffset', PARAM_MULTILANG);

        // A sample string variable with a default value.
        $mform->addElement('text', 'config_endOffset', get_string('endOffset', 'block_phrasecomments'));
        $mform->setDefault('config_endOffset', '0');
        $mform->setType('config_endOffset', PARAM_MULTILANG);
 
    }
}
 
?>
