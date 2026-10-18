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
    Then I should see "There are no suggestions for this course at the moment." in the "#block_coursecare-suggestions" "css_element"
    And I should not see "Large materials" in the "#block_coursecare-suggestions" "css_element"

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
