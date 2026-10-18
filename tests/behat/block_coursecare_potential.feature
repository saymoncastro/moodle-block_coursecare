@block @block_coursecare
Feature: CourseCare potential of the assignments of a course
  In order to understand what the settings of my assignments allow
  As a teacher with editing rights
  I need to see, for each assignment that accepts files, its settings and the theoretical potential they allow

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
    And the following "blocks" exist:
      | blockname  | contextlevel | reference | pagetypepattern | defaultregion |
      | coursecare | Course       | C1        | course-view-*   | side-pre      |

  Scenario: An assignment with file submissions shows its settings and its potential; one without files is not listed
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name        | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes | assignsubmission_onlinetext_enabled | maxattempts |
      | assign   | C1     | Lab report  | 1                             | 3                              | 1048576                            | 0                                   | 1           |
      | assign   | C1     | Online text | 0                             | 0                              | 0                                  | 1                                   | 1           |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "Potential of assignments"
    And "Lab report" "link" should exist in the "#block_coursecare-potential" "css_element"
    And I should see "Up to 3 files per participant" in the "#block_coursecare-potential" "css_element"
    And I should see "1 participant can submit." in the "#block_coursecare-potential" "css_element"
    And I should see "Theoretical potential:" in the "#block_coursecare-potential" "css_element"
    And I should not see "Online text" in the "#block_coursecare-potential" "css_element"

  @javascript
  Scenario: What the potential means is visible at once; the calculation and its limits are collapsed
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name       | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes | assignsubmission_onlinetext_enabled | maxattempts |
      | assign   | C1     | Lab report | 1                             | 3                              | 1048576                            | 0                                   | 1           |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 5242880      | 2         | 600              |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category   | filecount | logicalbytes |
      | C1     | submission | 2         | 5242880      |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "The theoretical potential is a ceiling set by the settings" in the "#block_coursecare-potential" "css_element"
    And I should see "In this course, files already submitted in activities add up to" in the "#block_coursecare-potential" "css_element"
    And I should not see "Due dates and access restrictions are not considered."
    And I click on "How the potential is calculated, and its limits" "text" in the "#block_coursecare-potential" "css_element"
    And I should see "Due dates and access restrictions are not considered."

  Scenario Outline: Without a number, the page says why
    Given the following "course enrolments" exist:
      | user     | course | role       |
      | student1 | C1     | <enrolled> |
    And the following "activities" exist:
      | activity | course | name       | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes | assignsubmission_onlinetext_enabled | maxattempts | visible   |
      | assign   | C1     | Assignment | <files>                       | <maxfiles>                     | 1048576                            | 1                                   | 1           | <visible> |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then I should see "<sentence>" in the "#block_coursecare-potential" "css_element"

    Examples:
      | enrolled       | files | maxfiles | visible | sentence                                                   |
      | editingteacher | 1     | 3        | 1       | Nobody can submit in this assignment at the moment.        |
      | student        | 1     | 0        | 1       | No file can be submitted with the current settings.        |
      | student        | 0     | 0        | 1       | No assignment in this course accepts files at the moment.  |
      | student        | 1     | 3        | 0       | Hidden from students                                       |

  @javascript @accessibility
  Scenario Outline: The potential section meets the accessibility standards on desktop and mobile
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name       | assignsubmission_file_enabled | assignsubmission_file_maxfiles | assignsubmission_file_maxsizebytes | assignsubmission_onlinetext_enabled | maxattempts |
      | assign   | C1     | Lab report | 1                             | 3                              | 1048576                            | 0                                   | -1          |
    And I change viewport size to "<size>"
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    And I click on "How the potential is calculated, and its limits" "text" in the "#block_coursecare-potential" "css_element"
    Then I should see "No defined ceiling" in the "#block_coursecare-potential" "css_element"
    And the page should meet accessibility standards

    Examples:
      | size   |
      | large  |
      | mobile |
