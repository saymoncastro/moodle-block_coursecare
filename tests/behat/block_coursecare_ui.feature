@block @block_coursecare
Feature: CourseCare block initial experience
  In order to understand at once what CourseCare is for
  As a teacher with editing rights
  I need a short, welcoming and accessible message that does not suggest an analysis was made

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

  Scenario: The initial message is shown without links or actions
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "CourseCare" in the "CourseCare" "block"
    And I should see "Small choices, a better learning space for everyone." in the "CourseCare" "block"
    And I should see "Soon you will find guidance here on using your course resources more mindfully." in the "CourseCare" "block"
    And "a" "css_element" should not exist in the ".block_coursecare-content" "css_element"
    And "button" "css_element" should not exist in the ".block_coursecare-content" "css_element"
    And "form" "css_element" should not exist in the ".block_coursecare-content" "css_element"

  @javascript @accessibility
  Scenario: The block meets the automated accessibility checks on desktop
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "Small choices, a better learning space for everyone." in the "CourseCare" "block"
    And the "CourseCare" "block" should meet accessibility standards

  @javascript @accessibility
  Scenario: The block is readable on a tablet
    Given I change viewport size to "tablet"
    And I am on the "C1" "Course" page logged in as "teacher1"
    When I click on "Open block drawer" "button"
    Then I should see "Small choices, a better learning space for everyone." in the "CourseCare" "block"
    And the "CourseCare" "block" should meet accessibility standards

  @javascript @accessibility
  Scenario: The block is readable on a mobile phone
    Given I change viewport size to "mobile"
    And I am on the "C1" "Course" page logged in as "teacher1"
    When I click on "Open block drawer" "button"
    Then I should see "Small choices, a better learning space for everyone." in the "CourseCare" "block"
    And the "CourseCare" "block" should meet accessibility standards

  @javascript @accessibility
  Scenario: The block works with the Classic theme
    Given the following config values are set as admin:
      | theme | classic |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "Small choices, a better learning space for everyone." in the "CourseCare" "block"
    And the "CourseCare" "block" should meet accessibility standards
