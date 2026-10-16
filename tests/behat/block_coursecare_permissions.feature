@block @block_coursecare
Feature: CourseCare block permissions
  In order to keep the CourseCare guidance for the people who manage a course
  As a teacher with editing rights
  I need the block to be added and viewed according to its own capabilities

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
      | Course 2 | C2        | topics |
    And the following "users" exist:
      | username   | firstname | lastname | email                  |
      | teacher1   | Teacher   | One      | teacher1@example.com   |
      | assistant1 | Assistant | One      | assistant1@example.com |
      | student1   | Student   | One      | student1@example.com   |
      | manager1   | Manager   | One      | manager1@example.com   |
    And the following "course enrolments" exist:
      | user       | course | role           |
      | teacher1   | C1     | editingteacher |
      | teacher1   | C2     | editingteacher |
      | assistant1 | C1     | teacher        |
      | student1   | C1     | student        |
      | manager1   | C1     | manager        |

  Scenario: An editing teacher adds the block and it is not offered a second time
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And the add block selector should contain "CourseCare" block
    When I add the "CourseCare" block
    Then I should see "Small choices, a better learning space for everyone." in the "CourseCare" "block"
    And the add block selector should not contain "CourseCare" block

  Scenario: A manager sees the block
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    When I am on the "C1" "Course" page logged in as "manager1"
    Then "CourseCare" "block" should exist

  Scenario Outline: Users without the view capability do not see the block
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    When I am on the "C1" "Course" page logged in as "<user>"
    Then "CourseCare" "block" should not exist
    And I should not see "Small choices, a better learning space for everyone."

    Examples:
      | user       |
      | assistant1 |
      | student1   |

  Scenario: The block is not offered when the add capability is prohibited
    Given the following "permission overrides" exist:
      | capability                   | permission | role           | contextlevel | reference |
      | block/coursecare:addinstance | Prohibit   | editingteacher | Course       | C2        |
    When I log in as "teacher1"
    And I am on "Course 2" course homepage with editing mode on
    Then the add block selector should not contain "CourseCare" block

  Scenario: Granting the view capability to non-editing teachers shows the block without editing controls
    Given the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |
    And the following "permission overrides" exist:
      | capability            | permission | role    | contextlevel | reference |
      | block/coursecare:view | Allow      | teacher | Course       | C1        |
    When I am on the "C1" "Course" page logged in as "assistant1"
    Then I should see "Small choices, a better learning space for everyone." in the "CourseCare" "block"
    And "Edit mode" "field" should not exist
