@block @block_course_template
Feature: Duplicate a course
    In order do duplicate a course
    As a user
    I need to use this block to select copy options

    Background:
        Given the following "categories" exist:
           | name       | category | idnumber |
           | Category One | 0 | CAT1 |
        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1 | CAT1 |
        And the following "users" exist:
            | username | firstname | lastname | email |
            | teacher1 | Teacher | First | teacher1@example.com |
            | student1 | Student | First | student1@example.com |
            | student2 | Student | Second | student2@example.com |
        And the following "course enrolments" exist:
            | user | course | role |
            | teacher1 | C1 | editingteacher |
            | student1 | C1 | student |
            | student2 | C1 | student |
        And the following "role assigns" exist:
            | user  | role           | contextlevel | reference |
            | teacher1 | coursecreator | Category | CAT1 |
        And the following "permission overrides" exist:
            | capability | permission | role | contextlevel | reference |
            | block/course_template:duplicatecourse | Allow | editingteacher | Course | C1 |
        And I log in as "teacher1"
        And I follow "Course 1"
        And I turn editing mode on
        And I add the "Course Templates" block
        And I follow "Duplicate course"
        Then I should see "Duplicate course 'Course 1'"
        And the "Course category" select box should contain "Category One"
        And I set the following fields to these values:
            | Course full name | Course 2 |
            | Course short name | C2 |
            | Course category | Category One |

    Scenario: Copy a course without enrolment data
        When I set the following fields to these values:
            | Visibility | Yes |
            | Enrolment | No |
        And I press "Duplicate course"
        Then I should see "Course has been duplicated"
        And I should see "Please, edit the course below"
        And I expand "Course administration > Users" node
        And I follow "Enrolled users"
        Then I should see "Teacher First"
        And I should not see "Student First"
        And I should not see "Student Second"
        Then I log out
        And I log in as "student1"
        And I follow "Course 2"
        And I should see "This course is currently unavailable to students"

    Scenario: Copy a course with enrolment data
        When I set the following fields to these values:
            | Visibility | Yes |
            | Enrolment | Yes |
        And I press "Duplicate course"
        Then I should see "Course has been duplicated"
        And I should see "Please, edit the course below"
        And I expand "Course administration > Users" node
        And I follow "Enrolled users"
        Then I should see "Teacher First"
        And I should see "Student First"
        And I should see "Student Second"
        Then I log out
        And I log in as "student1"
        And I follow "Course 2"
        And I should see "Topic 1"
        And I should not see "This course is currently unavailable to students"

    Scenario: Copy a course and set it to hidden
        When I set the following fields to these values:
            | Visibility | No |
            | Enrolment | No |
        And I press "Duplicate course"
        Then I should see "Course has been duplicated"
        And I should see "Please, edit the course below"
        And I expand "Course administration > Users" node
        And I follow "Enrolled users"
        Then I should see "Teacher First"
        And I should not see "Student First"
        And I should not see "Student Second"
        Then I log out
        And I log in as "student1"
        And I should not see "Course 2"
        And I should see "Course 1"
