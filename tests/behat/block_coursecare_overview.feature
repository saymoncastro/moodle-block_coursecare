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
    And I should see "Course files recorded in Moodle"
    And I should see "6.0 MB"
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

  Scenario Outline: An old or failed result without files shows the page of a course without files
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | lastattemptfailed |
      | C1     | 0            | 0         | <ago>            | <failed>          |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I should see "No files were found in this course's materials and activities." in the "CourseCare" "block"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should see "No files were found in this course's materials and activities."
    And I should see "<note>"
    And I should see "Technical details"
    And I should not see "Course files recorded in Moodle"
    And I should not see "Where the files are"
    And I should not see "Materials by activity"

    Examples:
      | ago    | failed | note                                       |
      | 400000 | 0      | This information may be out of date.       |
      | 600    | 1      | The latest update could not be completed.  |

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

  Scenario: A teacher of another course cannot open the page of this course
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 2 | C2        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher2 | C2     | editingteacher |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 6291456      | 3         | 600              |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher2"
    Then I should not see "Your course in numbers"
    And I should not see "Files in this course"

  Scenario: Large files appear for the course, and the large materials by activity
    Given the following "activities" exist:
      | activity | course | name      | idnumber |
      | page     | C1     | Lab video | lab      |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago | largecount | largebytes | largethreshold |
      | C1     | 125829120    | 2         | 600              | 2          | 125829120  | 52428800       |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category   | filecount | logicalbytes |
      | C1     | material   | 1         | 62914560     |
      | C1     | submission | 1         | 62914560     |
    And the following "block_coursecare > diagnostic activities" exist:
      | course | activity | materialbytes |
      | C1     | lab      | 62914560      |
    And the following "block_coursecare > diagnostic large links" exist:
      | course | activity | filesize |
      | C1     | lab      | 62914560 |
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I click on "Learn more about the files in your course" "link" in the "CourseCare" "block"
    Then I should see "Large files"
    And I should see "Large materials by activity"
    And I should see "The threshold is a screening value set by the site, not a teaching rule."

  @javascript @accessibility
  Scenario Outline: The block and the page meet the accessibility standards on desktop and mobile
    Given the following "activities" exist:
      | activity | course | name    | idnumber |
      | page     | C1     | Reading | reading  |
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes   | filecount   | timecompletedago |
      | C1     | <logicalbytes> | <filecount> | 600              |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount   | logicalbytes   |
      | C1     | material | <filecount> | <logicalbytes> |
    And the following "block_coursecare > diagnostic activities" exist:
      | course | activity | materialbytes  |
      | C1     | reading  | <logicalbytes> |
    And I change viewport size to "<size>"
    When I am on the "C1" "Course" page logged in as "teacher1"
    Then the page should meet accessibility standards
    And I am on the "C1" "block_coursecare > overview" page
    And the page should meet accessibility standards
    And I should see "<sentence>"

    Examples:
      | size   | logicalbytes | filecount | sentence                                                         |
      | large  | 2048         | 1         | Materials by activity                                            |
      | mobile | 2048         | 1         | Materials by activity                                            |
      | large  | 0            | 0         | No files were found in this course's materials and activities. |
      | mobile | 0            | 0         | No files were found in this course's materials and activities. |

  @javascript @accessibility
  Scenario Outline: The page without a result meets the accessibility standards on desktop and mobile
    Given the following "block_coursecare > diagnostics" exist:
      | course | requeststatus |
      | C1     | 1             |
    And I change viewport size to "<size>"
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    Then the page should meet accessibility standards
    And I should see "The file overview for this course is being prepared."

    Examples:
      | size   |
      | large  |
      | mobile |

  @javascript
  Scenario: The whole journey works with the keyboard: block, page, show all, technical details and back
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
    And the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 66000        | 11        | 600              |
    And the following "block_coursecare > diagnostic categories" exist:
      | course | category | filecount | logicalbytes |
      | C1     | material | 11        | 66000        |
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
      | C1     | r11     | 17000         |
    # Each target is reached with the tab key from the element before it (focus set there without clicking), and
    # activated with the enter key; "I press tab key in" is not used because it clicks the element first. Activity
    # links are looked for inside the page, as the course index also lists the activities.
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I set the focus on the "Close block drawer" "button"
    And I press the tab key
    And I press the tab key
    And the focused element is "Learn more about the files in your course" "link"
    And I press the enter key
    Then I should see "Your course in numbers"
    And I set the focus on the "Back to the course" "link"
    And I press the tab key
    And the focused element is "//div[contains(@class, 'block_coursecare-overview')]//a[normalize-space(.)='Reading 11']" "xpath_element"
    And I set the focus on the "//div[contains(@class, 'block_coursecare-overview')]//a[normalize-space(.)='Reading 02']" "xpath_element"
    And I press the tab key
    And the focused element is ".block_coursecare-overview-more summary" "css_element"
    And I press the enter key
    And I press the tab key
    And the focused element is "//div[contains(@class, 'block_coursecare-overview')]//a[normalize-space(.)='Reading 01']" "xpath_element"
    And I press the tab key
    And the focused element is ".block_coursecare-overview-technical summary" "css_element"
    And I press the enter key
    And I should see "These numbers show the files Moodle records for this course."
    And I set the focus on the "//div[contains(@class, 'block_coursecare-overview')]//a[normalize-space(.)='Reading 11']" "xpath_element"
    And I press the shift tab key
    And the focused element is "Back to the course" "link"
    And I press the enter key
    And I should see "Learn more about the files in your course" in the "CourseCare" "block"

  @javascript
  Scenario: The technical details open with the keyboard
    Given the following "block_coursecare > diagnostics" exist:
      | course | logicalbytes | filecount | timecompletedago |
      | C1     | 2048         | 1         | 600              |
    When I am on the "C1" "block_coursecare > overview" page logged in as "teacher1"
    And I set the focus on the ".block_coursecare-overview-technical summary" "css_element"
    And I press the enter key
    Then I should see "These numbers show the files Moodle records for this course."
