@mod @mod_planner @javascript
Feature: Test the plugin

    Background:
        Given the following "users" exist:
            | username | firstname | lastname | email                |
            | student1 | Vinnie    | Student1 | student1@example.com |
            | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
        And the following "courses" exist:
            | fullname | shortname | category | enablecompletion | showcompletionconditions |
            | Course 1 | C1        | 0        | 1                | 1                        |
        And the following "course enrolments" exist:
            | user | course | role           |
            | student1 | C1 | student        |
            | teacher1 | C1 | editingteacher |
        And the following "activity" exists:
            | activity                            | assign                  |
            | course                              | C1                      |
            | section                             | 1                       |
            | name                                | Test assignment name    |
            | completion                          | 1                       |
        And the following "activity" exists:
            | activity                            | quiz                    |
            | course                              | C1                      |
            | section                             | 1                       |
            | name                                | Test quiz name          |
            | completion                          | 1                       |

    Scenario: Test adding template
        Given I log in as "admin"
        And I navigate to "Plugins > Activity modules > Planner > Manage Templates" in site administration
        And I press "Add new template"
        And I set the field "Template name" to "Template 1"
        When I set the field "Step 1 time allocation" to "0"
        And I press "Submit"
        Then I should see "Total time allocated for all steps should equal 100"
        When I set the field "Step 2 time allocation" to "10"
        And I press "Submit"
        Then I should see "Required"
        When I set the field "Step 1 time allocation" to "5"
        And I press "Submit"
        Then I should see "Total time allocated for all steps should equal 100"
        When I set the field "Step 2 time allocation" to "5"
        And I press "Add 1 step to the form"
        And I set the field "Disclaimer" to "Test disclaimer"
        And I press "Submit"
        Then I should see "Template 1"
        And I should see "Enabled"
        When I click on "Edit" "link"
        Then I should not see "Step 7 name"

    Scenario: Test enabling/disabling template
        Given I log in as "admin"
        And I navigate to "Plugins > Activity modules > Planner > Manage Templates" in site administration
        And I press "Add new template"
        And I set the field "Template name" to "Template 1"
        And I press "Submit"
        Then I should see "Enabled"
        When I click on "Disable this template" "link"
        Then I should see "Disabled"
        When I click on "Enable this template" "link"
        Then I should see "Enabled"

    Scenario: Test deleting template
        Given I log in as "admin"
        And I navigate to "Plugins > Activity modules > Planner > Manage Templates" in site administration
        And I press "Add new template"
        And I set the field "Template name" to "Template 1"
        And I press "Submit"
        Then I should see "Template 1"
        When I click on "Delete" "link"
        And I press "Delete"
        Then I should not see "Template 1"

    Scenario: Test searching templates
        Given I log in as "admin"
        And I navigate to "Plugins > Activity modules > Planner > Manage Templates" in site administration
        And I press "Add new template"
        And I set the field "Template name" to "Template 1"
        And I press "Submit"
        And I press "Add new template"
        And I set the field "Template name" to "Personal"
        And I press "Submit"
        When I set the field "Search" to "per"
        And I press "Submit"
        Then I should see "Personal"

    # None of the assignments/quiz are visible in the planner
    Scenario: Test adding planner
        Given I log in as "admin"
        And I navigate to "Plugins > Activity modules > Planner > Manage Templates" in site administration
        And I press "Add new template"
        And I set the field "Template name" to "Template 1"
        And I press "Submit"
        And I am on "Course 1" course homepage with editing mode on
        And I add a "Quiz" to section "1"
        And I set the field "Name" to "Test quiz name"
        And I set the field "Description" to "Test quiz description"
        And I press "Save and return to course"
        And I wait "10" seconds
        And I add a "Planner" to section "1"
        And I set the field "Name" to "Test planner name"
        And I set the field "Description" to "Test planner description"
        And I pause
        And I select "Task number, title and due date" from the "Information on course page" singleselect
        And I select "Test assignment name" from the "select activity" singleselect
        Then I should see "Test planner name"
