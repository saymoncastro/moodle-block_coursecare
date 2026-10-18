@block @block_coursecare
Feature: CourseCare history of the files of a course
  In order to understand how the files of my course have changed
  As a teacher with editing rights
  I need to see the latest measurement, the change since the previous one and every measurement as text

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
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 146800640    | 3         | 600              |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 3         | 146800640    |

  Scenario: Three measurements show the change and, as text, every measurement
    Given the following "block_coursecare > diagnostic history points" exist:
      | course | daysago | materialbytes |
      | C1     | 2       | 125829120     |
      | C1     | 1       | 157286400     |
      | C1     | 0       | 146800640     |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "How the files of your course have changed"
    And I should see "decreased by" in the "#block_coursecare-history" "css_element"
    And I should see "does not mean a problem or an improvement" in the "#block_coursecare-history" "css_element"
    And I should see "Measurements and categories" in the "#block_coursecare-history" "css_element"

  Scenario Outline: A short or changed history says so
    Given the following "block_coursecare > diagnostic history points" exist:
      | course | daysago | materialbytes | methodversion |
      | C1     | 1       | 125829120     | <before>      |
      | C1     | 0       | 146800640     | 1             |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "<sentence>" in the "#block_coursecare-history" "css_element"

    Examples:
      | before | sentence                         |
      | 1      | increased by                     |
      | 0      | The way of measuring changed on  |

  Scenario: One measurement only
    Given the following "block_coursecare > diagnostic history points" exist:
      | course | daysago | materialbytes |
      | C1     | 0       | 146800640     |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "This is the first measurement kept for this course" in the "#block_coursecare-history" "css_element"

  Scenario: Without measurements there is no history section
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should not see "How the files of your course have changed"

  @javascript
  Scenario: The table of the measurements is collapsed until the teacher opens it
    Given the following "block_coursecare > diagnostic history points" exist:
      | course | daysago | materialbytes |
      | C1     | 1       | 157286400     |
      | C1     | 0       | 146800640     |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should not see "Days without an analysis have no value"
    And I click on "Measurements and categories" "text" in the "#block_coursecare-history" "css_element"
    And I should see "Days without an analysis have no value"
    And I should see "Course materials" in the "#block_coursecare-history" "css_element"

  @javascript @accessibility
  Scenario Outline: The history section meets the accessibility standards on desktop and mobile
    Given the following "block_coursecare > diagnostic history points" exist:
      | course | daysago | materialbytes | partial |
      | C1     | 9       | 104857600     | 1       |
      | C1     | 1       | 157286400     | 0       |
      | C1     | 0       | 146800640     | 0       |
    And I change viewport size to "<size>"
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    And I click on "Measurements and categories" "text" in the "#block_coursecare-history" "css_element"
    Then I should see "Partial coverage" in the "#block_coursecare-history" "css_element"
    And the page should meet accessibility standards

    Examples:
      | size   |
      | large  |
      | mobile |
