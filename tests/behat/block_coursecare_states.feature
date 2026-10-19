@block @block_coursecare
Feature: CourseCare block educational states
  In order to understand the situation of my course at a glance
  As a teacher with editing rights
  I need the block to show an educational state that never hides a failure, an old result or a partial coverage

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

  Scenario: A complete, current and fully classified result without suggestion is all good
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | partial |
      | C1     | 2048         | 2         | 600              | 0       |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "All good here!" in the "CourseCare" "block"
    And I should see "We found no suggestions for the criteria checked in this analysis." in the "CourseCare" "block"
    And I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should see "Learn more about the files in your course" in the "CourseCare" "block"

  Scenario Outline: An old, failed or partial result without suggestion is neutral and keeps its note
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | lastattemptfailed | partial   |
      | C1     | 2048         | 2         | <ago>            | <failed>          | <partial> |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "<note>" in the "CourseCare" "block"
    And I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should see "Learn more about the files in your course" in the "CourseCare" "block"
    And I should not see "All good here!" in the "CourseCare" "block"
    And I should not see "An idea for your course" in the "CourseCare" "block"

    Examples:
      | ago    | failed | partial | note                                                                        |
      | 400000 | 0      | 0       | This information may be out of date.                                        |
      | 600    | 1      | 0       | The latest update could not be completed.                                   |
      | 600    | 0      | 1       | Part of the files could not be classified; they are included in the total. |

  Scenario: An old result with a suggestion keeps the idea and says it may be out of date
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 3145728      | 1         | 400000           | 1048576        |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "An idea for your course" in the "CourseCare" "block"
    And I should see "This information may be out of date." in the "CourseCare" "block"
    And I should see "See the suggestion" in the "CourseCare" "block"
    And I should not see "All good here!" in the "CourseCare" "block"

  Scenario: Without files the block is neutral
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 0            | 0         | 600              |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "No files were found in this course's materials and activities." in the "CourseCare" "block"
    And I should not see "All good here!" in the "CourseCare" "block"
    And I should not see "An idea for your course" in the "CourseCare" "block"

  Scenario: Being prepared, the block has no educational title and no action
    Given the following "block_coursecare > diagnostics" exist:
      | course | requeststatus |
      | C1     | 1             |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "The file overview for this course is being prepared." in the "CourseCare" "block"
    And I should not see "All good here!" in the "CourseCare" "block"
    And I should not see "Learn more about the files in your course" in the "CourseCare" "block"

  @javascript @accessibility
  Scenario Outline: The educational states of the block meet the accessibility standards on desktop
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 3145728      | 1         | 600              | 1048576        |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize   |
      | C1     | lab      | <filesize> |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "<title>" in the "CourseCare" "block"
    And the page should meet accessibility standards

    Examples:
      | filesize | title                   |
      | 3145728  | An idea for your course |

  @javascript @accessibility
  Scenario: The all good state of the block meets the accessibility standards on desktop
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 2048         | 2         | 600              |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "All good here!" in the "CourseCare" "block"
    And the page should meet accessibility standards

  @javascript @accessibility
  Scenario: The idea state of the block meets the accessibility standards on mobile, in the block drawer
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 3145728      | 1         | 600              | 1048576        |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
    And I change viewport size to "mobile"
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Open block drawer" "button"
    Then I should see "An idea for your course" in the "CourseCare" "block"
    And the page should meet accessibility standards

  @javascript @accessibility
  Scenario: The all good state of the block meets the accessibility standards on mobile, in the block drawer
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 2048         | 2         | 600              |
    And I change viewport size to "mobile"
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Open block drawer" "button"
    Then I should see "All good here!" in the "CourseCare" "block"
    And the page should meet accessibility standards
