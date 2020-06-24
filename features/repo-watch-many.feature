Feature: There a number of repositories we want to watch

  Scenario: I want to watch all the projects that my project depends on
    Given I am an authenticated user
    And I have the following repositories
      | owner        | project                         |
      | l3naG        | Java_Set_HashSet_incl_Challenge |
      | l3naG        | Java_Collections_BinarySearch   |
      | DannyDainton | All-Things-Postman              |
    When I watch each repository
    Then My watch list will include those repositories
