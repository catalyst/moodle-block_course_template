<?php

class block_course_template extends block_base {

    //
    // Don't allow multiple instances
    //
    public function instance_allow_multiple() {
        return false;
    }

    //
    // Define courseformats the block may be displayed on
    //
    public function applicable_formats() {
        // only allow to be created on site index (global)
        $formatopts = array(
            'site-index' => true,
            'mod' => false,
            'course' => false
        );

        return $formatopts;
    }

    //
    // Initialise and insert custom items into navigation tree
    //
    public function init() {
        global $CFG, $PAGE;

        // set block title
        $this->title = get_string('pluginname', 'block_course_template');
    }

    public function get_content() {
        global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;

        // make sure that the block only displays if the current view is of an allowed course format
        $isallowedformat = $this->is_allowed_format();

        if($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $course = $this->page->course;

        //
        // Generate content
        //
        $tempurl = new moodle_url("{$CFG->wwwroot}/blocks/course_template/edit.php", array('course' => $COURSE->id));
        $courseurl = new moodle_url("{$CFG->wwwroot}/blocks/course_template/newcourse.php");
        $viewurl = new moodle_url("{$CFG->wwwroot}/blocks/course_template/view.php");

        $this->content->text .= html_writer::start_tag('ul');

        $cxt = get_context_instance(CONTEXT_SYSTEM);
        $canedit = has_capability('block/course_template:edit', $cxt);

        // new template
        if ($canedit) {
            $this->content->text .= html_writer::start_tag('li');
            $this->content->text .= html_writer::link($tempurl, get_string('newtemplate', 'block_course_template'));
            $this->content->text .= html_writer::end_tag('li');
        }

        // new course
        $this->content->text .= html_writer::start_tag('li');
        $this->content->text .= html_writer::link($courseurl, get_string('newcourse', 'block_course_template'));
        $this->content->text .= html_writer::end_tag('li');

        // view all
        $this->content->text .= html_writer::start_tag('li');
        $this->content->text .= html_writer::link($viewurl, get_string('alltemplates', 'block_course_template'));
        $this->content->text .= html_writer::end_tag('li');

        $this->content->text .= html_writer::end_tag('ul');

        //
        // Format-orientated display
        //
        // if view is not an allowed courseformat prevent display
        if (!$isallowedformat) {
            $this->content = null;
        }

        return $this->content;
    }

    /**
     * Check whether the course view in which the block is displayed has
     * been allowed in the global settings.
     *
     * @return boolean success
     */
    private function is_allowed_format() {
        global $COURSE;

        $settings = get_config('block_course_template', 'allowcourseformats');
        $allowed = false;

        if (!empty($settings)) {
            $settings = explode(',', $settings);
            foreach ($settings as $setting) {
                if ($COURSE->format === $setting) {
                    $allowed = true;
                }
            }
        }

        return $allowed;
    }
}


