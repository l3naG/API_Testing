Feature: I want to create a new repository

  Scenario: I need a new repository
    Given I am an authenticated user
    When I create the "Java_Methods" repository
    And I request a list of my repositories
    Then The result should include a repository "Java_Methods"


