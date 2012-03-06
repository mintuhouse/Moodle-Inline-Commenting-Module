<?php
$settings->add(new admin_setting_configcheckbox(
            'phrasecomments/Allow_HTML',
            get_string('labelallowhtml', 'block_phrasecomments'),
            get_string('descallowhtml', 'block_phrasecomments'),
            '0'
));

?>
