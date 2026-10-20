@block @block_coursecare
Feature: CourseCare guidance about the submission settings of assignments
  In order to understand and, if I wish, review the submission settings of my assignments
  As a teacher with editing rights
  I need a guidance for each assignment and a suggestion only when an approved criterion applies

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    And the following "activities" exist:
      | activity | course | name       | idnumber | maxattempts | attemptreopenmethod | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes | assignsubmission_onlinetext_enabled |
      | assign   | C1     | Lab report | lab      | -1          | manual              | 1                             | 3                              | 1048576                            | 0                                   |
      | assign   | C1     | Essay      | essay    | 1           | manual              | 1                             | 1                              | 1048576                            | 0                                   |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 2048         | 1         | 600              |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 1         | 2048         |

  Scenario: The block and the page show the stored suggestion about an assignment and its actions
    Given the following "block_coursecare > diagnostic suggestions" exist:
      | course | activity |
      | C1     | lab      |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "An idea for your course" in the "CourseCare" "block"
    And I should see "We found suggestions about the attempts of assignments." in the "CourseCare" "block"
    And I click on "See the suggestion" "link" in the "CourseCare" "block"
    And I should see "Get to know the submission possibilities of this assignment" in the "#block_coursecare-suggestions" "css_element"
    And I should see "Lab report" in the "#block_coursecare-suggestions" "css_element"
    And I should see "This assignment allows new attempts without a maximum defined." in the "#block_coursecare-suggestions" "css_element"
    And I should see "Allowed attempts" in the "#block_coursecare-suggestions" "css_element"
    And I should see "Open the settings of this assignment" in the "#block_coursecare-suggestions" "css_element"
    And I should not see "Essay" in the "#block_coursecare-suggestions" "css_element"

  Scenario: Every assignment that accepts files has a guidance, without any suggested value
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "How to read and review these settings" in the "#block_coursecare-potential" "css_element"
    And I should not see "Get to know the submission possibilities of this assignment"
    And I should see "All good here!"

  Scenario: A hidden assignment has no suggestion
    Given the following "block_coursecare > diagnostic suggestions" exist:
      | course | activity |
      | C1     | lab      |
    And I am on the "Lab report" "assign activity editing" page logged in as "admin"
    And I set the field "Availability" to "Hide on course page"
    And I press "Save and return to course"
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should not see "An idea for your course" in the "CourseCare" "block"

  @javascript
  Scenario: The link of the card opens the guidance of the assignment in the details
    Given the following "block_coursecare > diagnostic suggestions" exist:
      | course | activity |
      | C1     | lab      |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    And I click on "See the guidance for this assignment" "link"
    Then "[data-tab='details'][aria-selected='true']" "css_element" should exist
    And I should see "How to review in Moodle"
    And I should see "Questions that may help"

  @javascript @accessibility
  Scenario Outline: The card and the guidance meet the accessibility standards on desktop and mobile
    Given the following "block_coursecare > diagnostic suggestions" exist:
      | course | activity |
      | C1     | lab      |
    And I change viewport size to "<size>"
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "Get to know the submission possibilities of this assignment"
    And the page should meet accessibility standards
    And I click on "See the guidance for this assignment" "link"
    And I should see "How to review in Moodle"
    And the page should meet accessibility standards

    Examples:
      | size   |
      | large  |
      | mobile |
