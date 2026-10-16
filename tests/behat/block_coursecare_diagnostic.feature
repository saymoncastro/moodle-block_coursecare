@block @block_coursecare
Feature: CourseCare file overview line
  In order to know how many files Moodle records for my course and when this was calculated
  As a teacher with editing rights
  I need the block to show the recorded volume with its date, or a clear state when it is not ready

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
      | Course 2 | C2        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following "activities" exist:
      | activity | course | name    | defaultfilename                            | uploaded |
      | resource | C1     | Reading | mod/resource/tests/fixtures/samplefile.txt | 1        |

  Scenario: A course that was never analysed says that the overview is not available yet
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "The file overview for this course is not available yet." in the "CourseCare" "block"
    And I should not see "Files in this course" in the "CourseCare" "block"

  @javascript
  Scenario: Adding the block prepares the overview, which then shows the recorded volume and the date
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    When I add the "CourseCare" block
    Then I should see "The file overview for this course is being prepared." in the "CourseCare" "block"
    And I run all adhoc tasks
    And I am on the "C1" "Course" page
    # display_size() separates the number and the unit with a non-breaking space.
    And I should see "Files in this course, as recorded by Moodle: 6" in the "CourseCare" "block"
    And I should see "bytes." in the "CourseCare" "block"
    And I should see "Calculated on" in the "CourseCare" "block"

  @javascript
  Scenario: An analysed course without files says so, with the date
    Given I am on the "C2" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add the "CourseCare" block
    And I run all adhoc tasks
    When I am on the "C2" "Course" page
    Then I should see "No files were found in this course's materials and activities." in the "CourseCare" "block"
    And I should see "Calculated on" in the "CourseCare" "block"

  @javascript @accessibility
  Scenario: The block with the file overview meets the automated accessibility checks
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add the "CourseCare" block
    And I run all adhoc tasks
    And I turn editing mode off
    When I am on the "C1" "Course" page
    Then I should see "Files in this course, as recorded by Moodle: 6" in the "CourseCare" "block"
    And the "CourseCare" "block" should meet accessibility standards
