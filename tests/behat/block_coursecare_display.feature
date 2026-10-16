@block @block_coursecare
Feature: CourseCare block is displayed only on the course main page
  In order to find the CourseCare guidance where it belongs
  As a teacher with editing rights
  I need the block to appear on the course main page and nowhere else, even in editing mode

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | numsections |
      | Course 1 | C1        | topics | 3           |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name      | intro          | course | idnumber |
      | page     | Test page | Test page text | C1     | page1    |

  Scenario: The block is shown on the course main page with editing mode off and on
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then "CourseCare" "block" should exist
    And I turn editing mode on
    And "CourseCare" "block" should exist
    And "Configure CourseCare block" "link" should exist in the "CourseCare" "block"

  Scenario: A student does not see the block on the course main page
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    When I am on the "C1" "Course" page logged in as "student1"
    Then "CourseCare" "block" should not exist

  Scenario: The block does not appear on section pages, not even its header in editing mode
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    When I am on the "Course 1 > Section 1" "course > section" page
    Then "CourseCare" "block" should not exist
    And I should not see "CourseCare"
    And the add block selector should not contain "CourseCare" block

  Scenario: The block does not appear on activity pages when it is displayed throughout the course
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion | showinsubcontexts |
      | coursecare | Course       | C1        | *               | side-pre      | 1                 |
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    When I am on the "Test page" "page activity" page
    Then "CourseCare" "block" should not exist
    And I should not see "CourseCare"

  Scenario: Changing the course format keeps a single block on the course main page
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the field "Format" to "Weekly sections"
    And I press "Save and display"
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    Then "CourseCare" "block" should exist
    And the add block selector should not contain "CourseCare" block
