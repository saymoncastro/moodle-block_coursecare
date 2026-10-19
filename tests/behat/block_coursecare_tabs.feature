@block @block_coursecare
Feature: CourseCare page areas "Suggestions for you" and "Details"
  In order to find the suggestions and the detailed information of my course
  As a teacher with editing rights
  I need two accessible tabs that also work without JavaScript and keep every detail of the page

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
    And the following "activities" exist:
      | activity | course | name      | idnumber |
      | page     | C1     | Lab video | lab      |

  Scenario: Without JavaScript both areas and every section are shown, one after the other
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 3145728      | 1         | 600              | 1048576        |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 1         | 3145728      |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "CourseCare"
    And I should see "Small choices, a better learning space for everyone."
    And I should see "Your course in numbers"
    And I should see "An idea for your course" in the "#block_coursecare-tab-suggestions" "css_element"
    And I should see "Large materials" in the "#block_coursecare-tab-suggestions" "css_element"
    And I should see "Related activities" in the "#block_coursecare-tab-suggestions" "css_element"
    And I should see "Where the files are" in the "#block_coursecare-tab-details" "css_element"
    And I should see "Materials by activity" in the "#block_coursecare-tab-details" "css_element"
    And I should see "Technical details" in the "#block_coursecare-tab-details" "css_element"

  @javascript
  Scenario: With a suggestion the page opens on the suggestions, and the tabs switch with the mouse and the keyboard
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 3145728      | 1         | 600              | 1048576        |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 1         | 3145728      |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "See the suggestion" "link" in the "CourseCare" "block"
    Then I should see "Large materials"
    And I should not see "Where the files are"
    And the focused element is "#block_coursecare-suggestions" "css_element"
    And "[data-tab='suggestions'][aria-selected='true']" "css_element" should exist
    And I click on "Details" "button" in the "[data-region='block_coursecare-tablist']" "css_element"
    And I should see "Where the files are"
    And I should not see "Large materials" in the "#block_coursecare-suggestions" "css_element"
    And "[data-tab='details'][aria-selected='true']" "css_element" should exist
    And the focused element is "Details" "button"
    And I press the left key
    And the focused element is "Suggestions for you" "button"
    And "[data-tab='suggestions'][aria-selected='true']" "css_element" should exist
    And I should see "Large materials"
    And I press the end key
    And the focused element is "Details" "button"
    And I should see "Where the files are"
    And I press the home key
    And the focused element is "Suggestions for you" "button"
    And I press the right key
    And the focused element is "Details" "button"
    And "[data-tab='suggestions'][tabindex='-1']" "css_element" should exist
    And "[data-tab='details'][tabindex='0']" "css_element" should exist

  @javascript
  Scenario: Without a suggestion the page opens on the details
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 2048         | 1         | 600              |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 1         | 2048         |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then "[data-tab='details'][aria-selected='true']" "css_element" should exist
    And I should see "Where the files are"
    And I should not see "All good here!"
    And I click on "Suggestions for you" "button"
    And I should see "All good here!"
    And I should see "We found no suggestions for the criteria checked in this analysis."

  @javascript
  Scenario: Without a result there are no tabs
    Given the following "block_coursecare > diagnostics" exist:
      | course | requeststatus |
      | C1     | 1             |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "The file overview for this course is being prepared."
    And "[data-region='block_coursecare-tablist']" "css_element" should not exist

  @javascript @accessibility
  Scenario Outline: Both tabs meet the accessibility standards on desktop and mobile
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 3145728      | 1         | 600              | 1048576        |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 1         | 3145728      |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
    And I change viewport size to "<size>"
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "Large materials"
    And the page should meet accessibility standards
    And I click on "Details" "button"
    And I should see "Where the files are"
    And the page should meet accessibility standards

    Examples:
      | size   |
      | large  |
      | mobile |
