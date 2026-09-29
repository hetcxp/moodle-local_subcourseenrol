@local @local_subcourseenrol
Feature: Auto-enrolment in target course
  In order to participate in a subcourse
  As a student
  I need to be automatically enrolled in the target course when I access the subcourse activity

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Master Course | C1        | 0        |
      | Target Course | C2        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following config values are set as admin:
      | enabled | 1 | local_subcourseenrol |

  @javascript
  Scenario: Student is auto-enrolled when accessing a subcourse activity
    Given I log in as "admin"
    And I am on "Master Course" course homepage with editing mode on
    And I add a "subcourse" activity to course "Master Course" section "1" and I fill the form with:
      | Subcourse name                    | My Subcourse       |
      | Fetch grades from                 | Target Course (C2) |
      | Redirect to the referenced course | 1                  |
    And I log out
    When I log in as "student1"
    And I am on "Master Course" course homepage
    And I follow "My Subcourse"
    Then I should see "Target Course"
    And I should not see "You can not enrol yourself in this course."
    And I should not see "You cannot enrol yourself in this course."

  @javascript
  Scenario: Student is NOT auto-enrolled when plugin is disabled
    Given the following config values are set as admin:
      | enabled | 0 | local_subcourseenrol |
    And I log in as "admin"
    And I am on "Master Course" course homepage with editing mode on
    And I add a "subcourse" activity to course "Master Course" section "1" and I fill the form with:
      | Subcourse name                    | Subcourse Disabled Test |
      | Fetch grades from                 | Target Course (C2)      |
      | Redirect to the referenced course | 1                       |
    And I log out
    When I log in as "student1"
    And I am on "Master Course" course homepage
    And I follow "Subcourse Disabled Test"
    Then I should see "You cannot enrol yourself in this course."

  @javascript
  Scenario: Student already enrolled does not get an error on subcourse access
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C2     | student |
    And I log in as "admin"
    And I am on "Master Course" course homepage with editing mode on
    And I add a "subcourse" activity to course "Master Course" section "1" and I fill the form with:
      | Subcourse name                    | Subcourse Already Enrolled |
      | Fetch grades from                 | Target Course (C2)         |
      | Redirect to the referenced course | 1                          |
    And I log out
    When I log in as "student1"
    And I am on "Master Course" course homepage
    And I follow "Subcourse Already Enrolled"
    Then I should not see "You can not enrol yourself in this course."
    And I should not see "You cannot enrol yourself in this course."
