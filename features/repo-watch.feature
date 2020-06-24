Feature: This is an important repository

  Scenario: I want to know when something happens with this repository
    Given I am an authenticated user
    And I have a repository called "Java_Methods"
    When I watch the "Java_Methods" repository
    Then The "Java_Methods" repository will list me as a watcher
    And I delete the repository called "Java_Methods"