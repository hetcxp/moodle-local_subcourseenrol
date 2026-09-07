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
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > Subcourse auto-enrolment" in site administration
    And I set the following fields to these values:
      | Enable | 1 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Student is auto-enrolled when accessing a subcourse activity
    Given I log in as "admin"
    And I am on "Master Course" course homepage with editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Name             | My Subcourse |
      | Referenced course| Target Course|
    And I log out
    When I log in as "student1"
    And I am on "Master Course" course homepage
    And I follow "My Subcourse"
    Then I should see "Target Course"
    And I should not see "You can not enrol yourself in this course."
    And I should not see "You cannot enrol yourself in this course."

  Scenario: Student is NOT auto-enrolled when plugin is disabled
    Given I log in as "admin"
    And I navigate to "Plugins > Local plugins > Subcourse auto-enrolment" in site administration
    And I set the following fields to these values:
      | Enable | 0 |
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    And I am on "Master Course" course homepage with editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Name              | Subcourse Disabled Test |
      | Referenced course | Target Course           |
    And I log out
    When I log in as "student1"
    And I am on "Master Course" course homepage
    And I follow "Subcourse Disabled Test"
    Then I should not see "Target Course" in the "page-content" "region"

  Scenario: Student already enrolled does not get an error on subcourse access
    Given I log in as "admin"
    And I enrol "student1" user as "Student" in "Target Course" course
    And I am on "Master Course" course homepage with editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Name              | Subcourse Already Enrolled |
      | Referenced course | Target Course              |
    And I log out
    When I log in as "student1"
    And I am on "Master Course" course homepage
    And I follow "Subcourse Already Enrolled"
    Then I should not see "You can not enrol yourself in this course."
    And I should not see "You cannot enrol yourself in this course."
