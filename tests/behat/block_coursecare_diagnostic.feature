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
    # display_size() separates the number and the unit with a non-breaking space (U+00A0), which the steps below
    # write literally, so that the whole sentence is checked and "6 bytes" cannot match "60 bytes".
    And I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should see "6 bytes" in the "CourseCare" "block"
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
    Then I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should see "6 bytes" in the "CourseCare" "block"
    And the "CourseCare" "block" should meet accessibility standards

  @javascript
  Scenario: Only editing teachers see the overview, always of their own course, without activity names
    Given the following "users" exist:
      | username   | firstname | lastname  | email                  |
      | student1   | Student   | One       | student1@example.com   |
      | assistant1 | Assistant | One       | assistant1@example.com |
      | teacher2   | Teacher   | Two       | teacher2@example.com   |
    And the following "course enrolments" exist:
      | user       | course | role           |
      | student1   | C1     | student        |
      | assistant1 | C1     | teacher        |
      | teacher2   | C2     | editingteacher |
    And the following "activities" exist:
      | activity | course | name           | defaultfilename                            | uploaded | visible |
      | resource | C1     | Hidden reading | mod/resource/tests/fixtures/samplefile.txt | 1        | 0       |
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add the "CourseCare" block
    And I am on the "C2" "Course" page
    And I add the "CourseCare" block
    And I run all adhoc tasks
    When I am on the "C1" "Course" page
    Then I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should see "12 bytes" in the "CourseCare" "block"
    And I should not see "Hidden reading" in the "CourseCare" "block"
    And I should not see "Reading" in the "CourseCare" "block"
    And I log out
    And I am on the "C1" "Course" page logged in as "student1"
    And "CourseCare" "block" should not exist
    And I log out
    And I am on the "C1" "Course" page logged in as "assistant1"
    And "CourseCare" "block" should not exist
    And I log out
    And I am on the "C2" "Course" page logged in as "teacher2"
    And I should see "No files were found in this course's materials and activities." in the "CourseCare" "block"
    And I should not see "Files in this course" in the "CourseCare" "block"

  Scenario: An old result keeps its value and date and says it may be out of date
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    And the following config values are set as admin:
      | stalenessthreshold | 3600 | block_coursecare |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 2048         | 2         | 7200             |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should see "2.0 KB" in the "CourseCare" "block"
    And I should see "Calculated on" in the "CourseCare" "block"
    And I should see "This information may be out of date." in the "CourseCare" "block"

  Scenario: A failed update keeps the previous result and says that the update was not completed
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | lastattemptfailed |
      | C1     | 2048         | 2         | 600              | 1                 |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should see "2.0 KB" in the "CourseCare" "block"
    And I should see "The latest update could not be completed." in the "CourseCare" "block"
    And I should not see "This information may be out of date." in the "CourseCare" "block"
