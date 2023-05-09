@mod @mod_planner @javascript
Feature: Test adding, editing, deleting, and searching for templates

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
