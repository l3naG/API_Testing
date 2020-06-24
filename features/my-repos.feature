Feature: Get my repositories

  Scenario: I want a list of my repositories
    Given I am an authenticated user
    When I request a list of my repositories
    Then The result should include a repository "Java_Set_HashSet_incl_Challenge"
