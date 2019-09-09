@api
Feature: Search block
  In order to be able to showcase Search
  As an anonymous user
  I want to search on ec.europa.eu by using the search block

  Scenario: I am redirected to the ec.europa.eu search results page when I use the search block
    Given I do not follow redirects
    And the following languages are available:
      | languages |
      | en        |
      | fr        |
    And I am on "the English home page"
    When I fill in "Search" with "European Commission"
    And I press "Search"
    Then I should be redirected to "https://ec.europa.eu/search/?QueryText=European%20Commission&swlang=en"

    When I am on "the French home page"
    When I fill in "Rechercher" with "European Commission"
    And I press "Rechercher"
    Then I should be redirected to "https://ec.europa.eu/search/?QueryText=European%20Commission&swlang=fr"
