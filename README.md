#Course Template Block - Catalyst IT

##Information
Name: block_course_template
Supported Version: Moodle 2.9


##Installation
Plugin path blocks/course_template
Add the plugin as given above
Update the database
Change the course settings


##Config Settings
In order to template to work, following settings should be added to config.php file

$CFG->forced_plugin_settings['backup']['backup_auto_storage']=1;
$CFG->forced_plugin_settings['backup']['backup_auto_destination']= $CFG->dataroot.'/temp/backup/';
