@block @block_coursecare
Feature: CourseCare suggestions about the files of a course
  In order to know what I could do with the large materials of my course, if I wish
  As a teacher with editing rights
  I need a suggestion that tells what was noticed, why it may deserve attention and what I can do

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
      | page     | C1     | Slides    | slides   |

  Scenario: Large course materials make a suggestion with the three parts and the activities
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 5242880      | 2         | 600              | 1048576        |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 2         | 5242880      |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
      | C1     | slides   | 2097152  |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "Suggestions for your course"
    And I should see "Large materials" in the "#block_coursecare-suggestions" "css_element"
    And I should see "What we noticed" in the "#block_coursecare-suggestions" "css_element"
    And I should see "Why it may deserve attention" in the "#block_coursecare-suggestions" "css_element"
    And I should see "What you can do, if you wish" in the "#block_coursecare-suggestions" "css_element"
    And "Lab video" "link" should exist in the "#block_coursecare-suggestions" "css_element"
    And "Slides" "link" should exist in the "#block_coursecare-suggestions" "css_element"
    And I should see "Size alone says nothing about the quality or the importance of a material." in the "#block_coursecare-suggestions" "css_element"

  Scenario: A large submission without large course materials makes no suggestion
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold | largecount | largebytes |
      | C1     | 9437184      | 1         | 600              | 1048576        | 1          | 9437184    |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category   | filecount | logicalbytes |
      | C1     | submission | 1         | 9437184      |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "All good here!"
    And I should see "We found no suggestions for the criteria checked in this analysis."
    And I should not see "Large materials" in the "#block_coursecare-suggestions" "css_element"

  Scenario: Without a result there is no suggestions section
    Given the following "block_coursecare > diagnostics" exist:
      | course | requeststatus |
      | C1     | 1             |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "The file overview for this course is being prepared."
    And I should not see "Suggestions for your course"

  Scenario Outline: An old or failed result with large course materials keeps the suggestion, with the note
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | lastattemptfailed | largethreshold |
      | C1     | 3145728      | 1         | <ago>            | <failed>          | 1048576        |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 1         | 3145728      |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "Large materials" in the "#block_coursecare-suggestions" "css_element"
    And I should see "<note>"

    Examples:
      | ago    | failed | note                                      |
      | 400000 | 0      | This information may be out of date.      |
      | 600    | 1      | The latest update could not be completed. |

  Scenario: An old result without large course materials shows the neutral sentence
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 3145728      | 1         | 400000           | 1048576        |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 1         | 3145728      |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "There are no suggestions for this course at the moment." in the "#block_coursecare-suggestions" "css_element"
    And I should see "This information may be out of date."
    And I should not see "All good here!"

  Scenario: The block announces the suggestion and its link opens the suggestions section
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
    Then I should see "An idea for your course" in the "CourseCare" "block"
    And I should see "We found a suggestion about large materials." in the "CourseCare" "block"
    And I should not see "All good here!" in the "CourseCare" "block"
    And I click on "See the suggestion" "link" in the "CourseCare" "block"
    And I should see "Suggestions for your course"
    And I should see "Large materials" in the "#block_coursecare-suggestions" "css_element"

  Scenario: Without a large course material the block does not announce any suggestion
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 2048         | 1         | 600              | 1048576        |
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then I should see "Course files recorded in Moodle" in the "CourseCare" "block"
    And I should not see "An idea for your course" in the "CourseCare" "block"
    And I should see "All good here!" in the "CourseCare" "block"
    And I should see "We found no suggestions for the criteria checked in this analysis." in the "CourseCare" "block"

  @javascript @accessibility
  Scenario Outline: The suggestion meets the accessibility standards on desktop and mobile
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largethreshold |
      | C1     | 5242880      | 2         | 600              | 1048576        |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 2         | 5242880      |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 3145728  |
    And I change viewport size to "<size>"
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "Large materials" in the "#block_coursecare-suggestions" "css_element"
    And the page should meet accessibility standards

    Examples:
      | size   |
      | large  |
      | mobile |
