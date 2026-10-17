@block @block_coursecare
Feature: CourseCare "Your course in numbers" page
  In order to understand where the files of my course are
  As a teacher with editing rights
  I need the block to lead me to a page with the course in numbers, and back to my course

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |

  Scenario: With a result, the block links to the page of the same course and the page leads back
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 6291456      | 3         | 600              |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should see "Your course in numbers"
    # display_size() separates the number and the unit with a non-breaking space (U+00A0), written literally.
    And I should see "Files in this course, as recorded by Moodle: 6.0 MB"
    And I click on "Back to the course" "link"
    And I should see "Learn more about the files in your course" in the "CourseCare" "block"

  Scenario: Without a result, the block shows no link to the page
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "The file overview for this course is not available yet." in the "CourseCare" "block"
    And I should not see "Learn more about the files in your course" in the "CourseCare" "block"

  @javascript
  Scenario: The page shows the categories of the course and the materials of each activity, ten first
    Given the following "activities" exist:
      | activity | course | name       | idnumber |
      | page     | C1     | Reading 01 | r01     |
      | page     | C1     | Reading 02 | r02     |
      | page     | C1     | Reading 03 | r03     |
      | page     | C1     | Reading 04 | r04     |
      | page     | C1     | Reading 05 | r05     |
      | page     | C1     | Reading 06 | r06     |
      | page     | C1     | Reading 07 | r07     |
      | page     | C1     | Reading 08 | r08     |
      | page     | C1     | Reading 09 | r09     |
      | page     | C1     | Reading 10 | r10     |
      | page     | C1     | Reading 11 | r11     |
      | page     | C1     | Reading 12 | r12     |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 156000       | 24        | 600              |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category   | filecount | logicalbytes |
      | C1     | material   | 12        | 78000        |
      | C1     | submission | 12        | 78000        |
    And the following "block_coursecare > diagnostic activities" exist:
      | course | activity | materialbytes |
      | C1     | r01     | 1000          |
      | C1     | r02     | 2000          |
      | C1     | r03     | 3000          |
      | C1     | r04     | 4000          |
      | C1     | r05     | 5000          |
      | C1     | r06     | 6000          |
      | C1     | r07     | 7000          |
      | C1     | r08     | 8000          |
      | C1     | r09     | 9000          |
      | C1     | r10     | 10000         |
      | C1     | r11     | 11000         |
      | C1     | r12     | 12000         |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should see "Where the files are"
    And I should see "Course materials"
    And I should see "Files submitted in activities"
    And I should see "Materials by activity"
    And I should see "Reading 12"
    And I should see "Show all activities (12)"
    And I should not see "Reading 01"
    And I click on "Show all activities (12)" "text"
    And I should see "Reading 01"

  Scenario: An old result keeps its numbers and says it may be out of date
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 2048         | 2         | 400000           |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should see "This information may be out of date."
    And I should see "Where the files are"

  Scenario: A failed update keeps the previous result and says that the update was not completed
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | lastattemptfailed |
      | C1     | 2048         | 2         | 600              | 1                 |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should see "The latest update could not be completed."

  Scenario: A course without files shows the summary and the technical details only
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 0            | 0         | 600              |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should see "No files were found in this course's materials and activities."
    And I should see "Technical details"
    And I should not see "Where the files are"
    And I should not see "Materials by activity"

  Scenario: Without a result, the page only says that the overview is not available yet
    Given the following "block_coursecare > diagnostics" exist:
      | course | requeststatus |
      | C1     | 1             |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I am on the "C1" "block_coursecare > overview" page
    Then I should see "The file overview for this course is being prepared."
    And I should not see "Technical details"

  @javascript
  Scenario: The technical details are collapsed and open on request
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | uniquebytes | uniquecount |
      | C1     | 2048         | 2         | 600              | 1024        | 1           |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should not see "These numbers show the files Moodle records for this course."
    And I click on "Technical details" "text"
    And I should see "These numbers show the files Moodle records for this course."
    And I should see "Unique content: 1.0 KB"
